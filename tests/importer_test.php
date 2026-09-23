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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\activity_service::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\importer::class)]

/**
 * Integration tests against real Moodle grading tables and controllers.
 *
 * @covers \local_evalimport\local\activity_service
 * @covers \local_evalimport\local\importer
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
        groups_remove_member($group, $teacher);
        get_fast_modinfo($course, 0, true);
        $this->assertArrayNotHasKey($activity->cmid, activity_service::options($course));
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->assertArrayHasKey($activity->cmid, activity_service::options($course));
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
        global $USER, $CFG;
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
            set_coursemodule_groupmode($activity->cmid, $groupmode);
            get_fast_modinfo($course, 0, true);
        }
        $file = get_file_storage()->create_file_from_pathname([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/', 'filename' => 'rubric.csv',
        ], __DIR__ . '/fixtures/rubric.csv');
        return [$course, $activity, $file];
    }
}
