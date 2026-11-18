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
 * Clean up rules for rubrics removed through Moodle.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\task;

/**
 * Remove applied settings when a core rubric definition no longer exists.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup_rules extends \core\task\scheduled_task {
    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('cleanuprules', 'local_evalimport');
    }

    /**
     * Remove orphaned import settings.
     */
    public function execute(): void {
        global $DB;
        $DB->delete_records_select(
            'local_evalimport_rules',
            'NOT EXISTS (SELECT 1 FROM {grading_definitions} gd WHERE gd.id = {local_evalimport_rules}.definitionid)'
        );
    }
}
