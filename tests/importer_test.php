<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Import and authorisation integration tests.
 *
 * @package    local_evalimport
 * @category   test
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport;

use local_evalimport\local\activity_service;
use local_evalimport\local\importer;
use local_evalimport\local\score_policy;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\activity_service::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\importer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\score_policy::class)]

/**
 * Integration tests against real Moodle grading tables and controllers.
 *
 * @covers \local_evalimport\local\activity_service
 * @covers \local_evalimport\local\importer
 * @covers \local_evalimport\local\score_policy
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class importer_test extends \advanced_testcase {
    /**
     * Preview never creates a grading area or definition.
     *
     * @return void
     */
    public function test_preview_is_read_only(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $areas = $DB->count_records('grading_areas');
        $definitions = $DB->count_records('grading_definitions');
        $preview = importer::preview($course, $activity->cmid, $file);
        $this->assertCount(2, $preview['criteria']);
        $this->assertSame(20.0, $preview['totalpoints']);
        $this->assertSame($areas, $DB->count_records('grading_areas'));
        $this->assertSame($definitions, $DB->count_records('grading_definitions'));
    }

    /**
     * Successful imports are native, editable drafts with complete criteria and levels.
     *
     * @return void
     */
    public function test_assignment_import_creates_native_draft(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $url = importer::save($course, $activity->cmid, $file);
        $this->assertStringContainsString('/grade/grading/form/rubric/edit.php', $url->out(false));
        $context = \context_module::instance($activity->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('rubric');
        $definition = $controller->get_definition();
        $this->assertEquals(\gradingform_controller::DEFINITION_STATUS_DRAFT, $definition->status);
        $this->assertCount(2, $definition->rubric_criteria);
        foreach ($definition->rubric_criteria as $criterion) {
            $this->assertCount(3, $criterion['levels']);
        }
        $this->assertSame('rubric', $manager->get_active_method());
        $this->assertEquals(1, $DB->count_records('grading_definitions', ['areaid' => $controller->get_areaid()]));
        // The imported definition can be opened and made ready through the native editor API.
        $editable = $controller->get_definition_for_editing();
        $editable->status = \gradingform_controller::DEFINITION_STATUS_READY;
        $controller->update_definition($editable);
        $this->assertEquals(\gradingform_controller::DEFINITION_STATUS_READY, $controller->get_definition(true)->status);
    }

    /**
     * A second submission must not append criteria or overwrite the existing definition.
     *
     * @return void
     */
    public function test_duplicate_import_leaves_existing_definition_unchanged(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        importer::save($course, $activity->cmid, $file);
        $before = $DB->get_records('grading_definitions');
        try {
            importer::save($course, $activity->cmid, $file);
            $this->fail('Duplicate import was accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('errorrubricexists', $exception->errorcode);
        }
        $this->assertEquals($before, $DB->get_records('grading_definitions'));
        $this->assertEquals(2, $DB->count_records('gradingform_rubric_criteria'));
    }

    /**
     * Invalid input leaves both grading area configuration and definitions unchanged.
     *
     * @return void
     */
    public function test_invalid_import_has_no_side_effects(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity('assign', 30);
        $before = $DB->get_records('grading_areas');
        try {
            importer::save($course, $activity->cmid, $file);
            $this->fail('Mismatched total was accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('errormismatchtotal', $exception->errorcode);
        }
        $this->assertEquals($before, $DB->get_records('grading_areas'));
        $this->assertEquals(0, $DB->count_records('grading_definitions'));
    }

    /**
     * Forum imports use whole-forum grading, not post ratings.
     *
     * @return void
     */
    public function test_forum_uses_whole_forum_grade(): void {
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity('forum');
        $target = activity_service::target($course, $activity->cmid);
        $this->assertSame('forum', $target['area']);
        $this->assertSame(20.0, $target['grademax']);
        importer::save($course, $activity->cmid, $file);
        $manager = get_grading_manager($target['context'], 'mod_forum', 'forum');
        $this->assertCount(2, $manager->get_controller('rubric')->get_definition()->rubric_criteria);
    }

    /**
     * Students cannot import even with a forged target.
     *
     * @return void
     */
    public function test_student_is_rejected(): void {
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        importer::preview($course, $activity->cmid, $file);
    }

    /**
     * A module outside the submitted course is not a valid target.
     *
     * @return void
     */
    public function test_cross_course_target_is_rejected(): void {
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $other = $this->getDataGenerator()->create_course();
        $this->expectException(\moodle_exception::class);
        importer::preview($other, $activity->cmid, $file);
    }

    /**
     * Existing grading guides are never silently replaced.
     *
     * @return void
     */
    public function test_other_method_is_preserved(): void {
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $context = \context_module::instance($activity->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $manager->set_active_method('guide');
        try {
            importer::save($course, $activity->cmid, $file);
            $this->fail('Grading guide was replaced');
        } catch (\moodle_exception $exception) {
            $this->assertSame('othergradingmethod', $exception->errorcode);
        }
        $this->assertSame('guide', get_grading_manager($context, 'mod_assign', 'submissions')->get_active_method());
    }

    /**
     * Users with accessallgroups do not need group membership.
     *
     * @return void
     */
    public function test_group_access_with_and_without_override(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity] = $this->setup_activity('assign', 20, SEPARATEGROUPS);
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_course::instance($course->id);
        assign_capability('moodle/course:view', CAP_ALLOW, $roleid, $context->id);
        assign_capability('mod/assign:view', CAP_ALLOW, $roleid, $context->id);
        assign_capability('local/evalimport:view', CAP_ALLOW, $roleid, $context->id);
        assign_capability('moodle/grade:managegradingforms', CAP_ALLOW, $roleid, $context->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $roleid);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($teacher);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $this->assertSame(SEPARATEGROUPS, (int) groups_get_activity_groupmode($cm));
        $this->assertTrue($cm->uservisible);
        $this->assertTrue(has_capability('moodle/grade:managegradingforms', $cm->context));
        $this->assertFalse(has_capability('moodle/site:accessallgroups', $cm->context));
        $this->assertArrayNotHasKey($activity->cmid, activity_service::options($course));
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $teacher);
        get_fast_modinfo($course, 0, true);
        $this->assertArrayHasKey($activity->cmid, activity_service::options($course));

        $grouping = $this->getDataGenerator()->create_grouping(['courseid' => $course->id]);
        $DB->set_field('course_modules', 'groupingid', $grouping->id, ['id' => $activity->cmid]);
        rebuild_course_cache($course->id, true);
        $this->assertArrayNotHasKey($activity->cmid, activity_service::options($course));
        groups_assign_grouping($grouping->id, $group->id);
        get_fast_modinfo($course, 0, true);
        $this->assertArrayHasKey($activity->cmid, activity_service::options($course));
        groups_remove_member($group, $teacher);
        get_fast_modinfo($course, 0, true);
        $this->assertArrayNotHasKey($activity->cmid, activity_service::options($course));
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->assertArrayHasKey($activity->cmid, activity_service::options($course));
    }

    /**
     * A 14-criterion rubric retains its original scores in a five-point assignment.
     */
    public function test_overridden_total_uses_native_grade_and_stores_rules(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity('assign', 5);
        $csv = "criterion,level,level_description,score\n";
        for ($criterion = 1; $criterion <= 14; $criterion++) {
            $csv .= "Criterion $criterion,Excellent,Excellent work,10\n";
            $csv .= "Criterion $criterion,Good,Good work,8.5\n";
            $csv .= "Criterion $criterion,Satisfactory,Satisfactory work,7\n";
            $csv .= "Criterion $criterion,Developing,Developing work,5\n";
            $csv .= "Criterion $criterion,Missing,Missing work,0\n";
        }
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $file->get_contextid(), 'component' => 'user', 'filearea' => 'draft',
            'itemid' => file_get_unused_draft_itemid(), 'filepath' => '/', 'filename' => 'rubric-140.csv',
        ], $csv);

        $name = 'Assessment rubric';
        try {
            importer::preview($course, $activity->cmid, $file, $name);
            $this->fail('The default maximum-score matching rule accepted a 140-point rubric for a five-point assignment.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('errormismatchtotal', $exception->errorcode);
        }

        $overrides = ['matchactivitymaximum' => false];
        $preview = importer::preview($course, $activity->cmid, $file, $name, $overrides);
        $this->assertTrue($preview['policy']['canoverride']);
        $this->assertFalse($preview['policy']['rules']['matchactivitymaximum']);
        $this->assertTrue($preview['policy']['rules']['requirezerolevel']);
        $this->assertSame(140.0, $preview['totalpoints']);
        $reviewed = ['policy' => $preview['policy'], 'grademax' => $preview['target']['grademax']];
        importer::save($course, $activity->cmid, $file, $name, $overrides, $reviewed);
        $manager = get_grading_manager($preview['target']['context'], 'mod_assign', 'submissions');
        $controller = $manager->get_controller('rubric');
        $editable = $controller->get_definition_for_editing();
        $this->assertSame($name, $editable->name);
        $editable->status = \gradingform_controller::DEFINITION_STATUS_READY;
        $controller->update_definition($editable);
        $definition = $controller->get_definition(true);
        $rules = $DB->get_field('local_evalimport_rules', 'rules', ['definitionid' => $definition->id], MUST_EXIST);
        $this->assertSame($preview['policy']['rules'], json_decode($rules, true, 512, JSON_THROW_ON_ERROR));

        $this->assertCount(14, $definition->rubric_criteria);
        $this->assertSame(5.0, $preview['target']['grademax']);
        $controller->set_grade_range(make_grades_menu((int)$preview['target']['grademax']), true);
        $instance = $controller->create_instance($USER->id, 1);
        $levelsbycriterion = [];
        foreach ($definition->rubric_criteria as $criterionid => $criterion) {
            $storedscores = array_map('floatval', array_column($criterion['levels'], 'score'));
            sort($storedscores, SORT_NUMERIC);
            $this->assertSame([0.0, 5.0, 7.0, 8.5, 10.0], $storedscores);
            foreach ($criterion['levels'] as $levelid => $level) {
                $levelsbycriterion[$criterionid][(string)(float)$level['score']] = $levelid;
            }
        }

        // Select the same level in all 14 criteria and let Moodle calculate the activity grade.
        foreach (['10' => 5.0, '8.5' => 4.25, '7' => 3.5, '5' => 2.5, '0' => 0.0] as $score => $expectedgrade) {
            $selection = [];
            foreach ($levelsbycriterion as $criterionid => $levels) {
                $selection[$criterionid] = ['levelid' => $levels[$score], 'remark' => ''];
            }
            $instance->update(['criteria' => $selection]);
            $this->assertEquals($expectedgrade, $instance->get_grade());
        }
        $controller->delete_definition();
        (new \local_evalimport\task\cleanup_rules())->execute();
        $this->assertFalse($DB->record_exists('local_evalimport_rules', ['definitionid' => $definition->id]));
    }

    /**
     * Global rules apply without delegation and changes invalidate the review.
     */
    public function test_stale_review_is_rejected(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file] = $this->setup_activity();
        $preview = importer::preview($course, $activity->cmid, $file);
        $reviewed = ['policy' => $preview['policy'], 'grademax' => $preview['target']['grademax']];
        set_config('matchactivitymaximum', 0, 'local_evalimport');
        try {
            importer::save($course, $activity->cmid, $file, $preview['name'], null, $reviewed);
            $this->fail('A stale review was saved');
        } catch (\moodle_exception $exception) {
            $this->assertSame('previewsettingschanged', $exception->errorcode);
        }
        $this->assertFalse($DB->record_exists('grading_definitions', []));
        $this->assertFalse($DB->record_exists('local_evalimport_rules', []));
    }

    /**
     * A delegated teacher needs the site switch and the Moodle capability.
     */
    public function test_delegated_rules_require_both_permissions(): void {
        $this->resetAfterTest();
        [$course] = $this->setup_activity();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $context = \context_course::instance($course->id);
        $this->assertFalse(score_policy::can_override($context));

        set_config('allowvalidationoverride', 1, 'local_evalimport');
        $this->assertFalse(score_policy::can_override($context));
        $roleid = $this->getDataGenerator()->create_role();
        role_assign($roleid, $teacher->id, $context->id);
        assign_capability('local/evalimport:overridevalidation', CAP_ALLOW, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->assertTrue(score_policy::can_override($context));

        set_config('allowvalidationoverride', 0, 'local_evalimport');
        $this->assertFalse(score_policy::can_override($context));
        $this->expectExceptionMessage(get_string('previewsettingschanged', 'local_evalimport'));
        score_policy::resolve($context, ['matchactivitymaximum' => false]);
    }

    /**
     * Create an activity and a real fixture upload.
     *
     * @param string $module Module name.
     * @param int $maximum Maximum grade.
     * @param int $groupmode Group mode.
     * @return array
     */
    private function setup_activity(string $module = 'assign', int $maximum = 20, int $groupmode = 0): array {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->libdir . '/gradelib.php');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $settings = ['course' => $course->id, 'groupmode' => $groupmode];
        if ($module === 'forum') {
            $settings['grade_forum'] = $maximum;
            $settings['assessed'] = 0;
        } else {
            $settings['grade'] = $maximum;
        }
        $activity = $this->getDataGenerator()->create_module($module, $settings);
        if ($groupmode !== NOGROUPS) {
            $DB->set_field('course_modules', 'groupmode', $groupmode, ['id' => $activity->cmid]);
            rebuild_course_cache($course->id, true);
        }
        $file = get_file_storage()->create_file_from_pathname([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/', 'filename' => 'rubric.csv',
        ], __DIR__ . '/fixtures/rubric.csv');
        return [$course, $activity, $file];
    }
}
