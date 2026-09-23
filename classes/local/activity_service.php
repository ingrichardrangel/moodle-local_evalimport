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
 * Supported activity discovery and authorisation.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

/**
 * Resolves supported activities without changing grading configuration.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_service {
    /**
     * List eligible activities using one grade-items query per course.
     *
     * @param \stdClass $course Course record.
     * @return array cmid => label.
     */
    public static function options(\stdClass $course): array {
        global $DB;
        $items = $DB->get_records('grade_items', ['courseid' => $course->id, 'itemtype' => 'mod']);
        $grades = [];
        foreach ($items as $item) {
            $grades[$item->itemmodule . ':' . $item->iteminstance . ':' . $item->itemnumber] = $item;
        }
        $options = [];
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_cms() as $cm) {
            if (!in_array($cm->modname, ['assign', 'forum'], true) || !self::accessible($cm)) {
                continue;
            }
            $number = $cm->modname === 'forum' ? 1 : 0;
            $item = $grades[$cm->modname . ':' . $cm->instance . ':' . $number] ?? null;
            if (!$item || (int)$item->gradetype !== GRADE_TYPE_VALUE || (float)$item->grademax <= 0) {
                continue;
            }
            $section = get_section_name($course, $modinfo->get_section_info($cm->sectionnum));
            $options[$cm->id] = format_string($cm->name) . ' (' . $section . ')';
        }
        return $options;
    }

    /**
     * Check access consistently in the selector and submitted requests.
     *
     * @param \cm_info $cm Course module.
     * @return bool
     */
    private static function accessible(\cm_info $cm): bool {
        global $USER;
        if (!$cm->uservisible || !has_capability('moodle/grade:managegradingforms', $cm->context)) {
            return false;
        }
        if (
            (int) groups_get_activity_groupmode($cm) === SEPARATEGROUPS &&
            !has_capability('moodle/site:accessallgroups', $cm->context)
        ) {
            $groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
            if (!$groups) {
                return false;
            }
        }
        return true;
    }

    /**
     * Recheck the submitted target and resolve its exact grading area/item.
     *
     * @param \stdClass $course Course record.
     * @param int $cmid Module ID.
     * @return array Target metadata, without database mutations.
     */
    public static function target(\stdClass $course, int $cmid): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_capability('local/evalimport:view', \context_course::instance($course->id));
        $cm = get_fast_modinfo($course)->get_cm($cmid);
        if (!in_array($cm->modname, ['assign', 'forum'], true) || !self::accessible($cm)) {
            throw new \moodle_exception('activitynotvisible', 'local_evalimport');
        }
        $area = $cm->modname === 'forum' ? 'forum' : 'submissions';
        $areas = \grading_manager::available_areas('mod_' . $cm->modname);
        if (!array_key_exists($area, $areas)) {
            throw new \moodle_exception('nogradingarea', 'local_evalimport');
        }
        $grade = $DB->get_record('grade_items', [
            'courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => $cm->modname,
            'iteminstance' => $cm->instance, 'itemnumber' => $cm->modname === 'forum' ? 1 : 0,
        ]);
        if (!$grade || (int)$grade->gradetype !== GRADE_TYPE_VALUE || (float)$grade->grademax <= 0) {
            throw new \moodle_exception('invalidgrading', 'local_evalimport');
        }
        $gradingarea = $DB->get_record('grading_areas', [
            'contextid' => $cm->context->id, 'component' => 'mod_' . $cm->modname, 'areaname' => $area,
        ]);
        if ($gradingarea && $DB->record_exists('grading_definitions', ['areaid' => $gradingarea->id, 'method' => 'rubric'])) {
            throw new \moodle_exception('errorrubricexists', 'local_evalimport');
        }
        if ($gradingarea && !empty($gradingarea->activemethod) && $gradingarea->activemethod !== 'rubric') {
            throw new \moodle_exception('othergradingmethod', 'local_evalimport');
        }
        return ['cm' => $cm, 'context' => $cm->context, 'area' => $area, 'grademax' => (float)$grade->grademax];
    }
}
