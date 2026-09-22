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
 * Presentation helpers for the rubric importer.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * Returns eligible activity labels for the course selector.
 *
 * @param stdClass $course Course record.
 * @return array
 */
function local_evalimport_get_importable_activities(stdClass $course): array {
    return \local_evalimport\local\activity_service::options($course);
}

/**
 * Render an escaped, accessible preview of the normalised rubric.
 *
 * @param array $criteria Validated criteria.
 * @return string
 */
function local_evalimport_preview_table(array $criteria): string {
    $table = new html_table();
    $table->head = array_map(static function($column) {
        return get_string('column_' . $column, 'local_evalimport');
    }, \local_evalimport\local\rubric_validator::HEADERS);
    foreach ($criteria as $criterion) {
        foreach ($criterion['levels'] as $level) {
            $table->data[] = [s($criterion['description']), s($level['label']),
                nl2br(s($level['definition'])), s((string)$level['score'])];
        }
    }
    return html_writer::table($table);
}
