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
 * Atomic rubric import service.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

/**
 * Saves validated imports through Moodle's rubric controller.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * Validate without altering the target activity.
     *
     * @param \stdClass $course Course record.
     * @param int $cmid Module ID.
     * @param \stored_file $file File in the current user's draft area.
     * @return array
     */
    public static function preview(\stdClass $course, int $cmid, \stored_file $file): array {
        $target = activity_service::target($course, $cmid);
        $criteria = rubric_validator::validate(file_reader::read($file), $target['grademax']);
        return ['target' => $target, 'criteria' => $criteria];
    }

    /**
     * Revalidate and atomically create a draft rubric, serialising plugin submissions.
     *
     * @param \stdClass $course Course record.
     * @param int $cmid Module ID.
     * @param \stored_file $file Uploaded rubric.
     * @return \moodle_url Native rubric editor URL.
     */
    public static function save(\stdClass $course, int $cmid, \stored_file $file): \moodle_url {
        global $DB;
        $lock = \core\lock\lock_config::get_lock_factory('local_evalimport')->get_lock('cm:' . $cmid, 10);
        if (!$lock) {
            throw new \moodle_exception('importbusy', 'local_evalimport');
        }
        try {
            $target = activity_service::target($course, $cmid);
            $rows = file_reader::read($file);
            rubric_validator::validate($rows, $target['grademax']);
            $transaction = $DB->start_delegated_transaction();
            try {
                // Recheck permissions, grading configuration and existing definitions immediately before writing.
                $target = activity_service::target($course, $cmid);
                $criteria = rubric_validator::validate($rows, $target['grademax']);
                $manager = get_grading_manager($target['context'], 'mod_' . $target['cm']->modname, $target['area']);
                $manager->set_active_method('rubric');
                $controller = $manager->get_controller('rubric');
                $controller->update_definition(self::definition($criteria));
                $url = $controller->get_editor_url();
                $transaction->allow_commit();
                return $url;
            } catch (\Throwable $exception) {
                $transaction->rollback($exception instanceof \Exception ? $exception :
                    new \moodle_exception('importfailed', 'local_evalimport'));
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Build the same payload as the native rubric editor.
     *
     * @param array $criteria Validated criteria.
     * @return \stdClass
     */
    private static function definition(array $criteria): \stdClass {
        $definition = (object)[
            'id' => 0,
            'name' => get_string('importedrubricname', 'local_evalimport', userdate(time())),
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'description_editor' => ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0],
            'status' => \gradingform_controller::DEFINITION_STATUS_DRAFT,
            'rubric' => ['criteria' => [], 'options' => \gradingform_rubric_controller::get_default_options()],
        ];
        $definition->rubric['options']['sortlevelsasc'] = 0;
        foreach ($criteria as $index => $criterion) {
            $item = [
                'description' => nl2br(s($criterion['description'])),
                'descriptionformat' => FORMAT_HTML,
                'sortorder' => $index,
                'levels' => [],
            ];
            foreach ($criterion['levels'] as $levelindex => $level) {
                $item['levels']['NEWID' . ($levelindex + 1)] = [
                    'definition' => nl2br(s($level['definition'])),
                    'definitionformat' => FORMAT_HTML,
                    'score' => $level['score'],
                ];
            }
            $definition->rubric['criteria']['NEWID' . ($index + 1)] = $item;
        }
        return $definition;
    }
}
