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
     * @param string|null $name Optional rubric name.
     * @param array|null $overrides Requested per-import scoring rules.
     * @return array
     */
    public static function preview(
        \stdClass $course,
        int $cmid,
        \stored_file $file,
        ?string $name = null,
        ?array $overrides = null
    ): array {
        $target = activity_service::target($course, $cmid);
        $name = self::resolve_name($target['cm'], $name);
        $policy = score_policy::resolve(\context_course::instance($course->id), $overrides);
        $rubric = rubric_validator::validate_with_total(file_reader::read($file), $target['grademax'], $policy['rules']);
        return $rubric + ['target' => $target, 'name' => $name, 'policy' => $policy];
    }

    /**
     * Revalidate and atomically create a draft rubric, serialising plugin submissions.
     *
     * @param \stdClass $course Course record.
     * @param int $cmid Module ID.
     * @param \stored_file $file Uploaded rubric.
     * @param string|null $name Optional rubric name.
     * @param array|null $overrides Requested per-import scoring rules.
     * @param array|null $reviewed Policy and activity maximum kept in the server-side preview, when applicable.
     * @return \moodle_url Native rubric editor URL.
     */
    public static function save(
        \stdClass $course,
        int $cmid,
        \stored_file $file,
        ?string $name = null,
        ?array $overrides = null,
        ?array $reviewed = null
    ): \moodle_url {
        global $DB;
        $lock = \core\lock\lock_config::get_lock_factory('local_evalimport')->get_lock('cm:' . $cmid, 10);
        if (!$lock) {
            throw new \moodle_exception('importbusy', 'local_evalimport');
        }
        try {
            activity_service::target($course, $cmid);
            $rows = file_reader::read($file);
            $transaction = $DB->start_delegated_transaction();
            try {
                // Recheck permissions, current scoring rules and activity state immediately before writing.
                $target = activity_service::target($course, $cmid);
                $name = self::resolve_name($target['cm'], $name);
                $policy = score_policy::resolve(\context_course::instance($course->id), $overrides);
                if ($reviewed !== null && ($reviewed['policy'] !== $policy || $reviewed['grademax'] !== $target['grademax'])) {
                    throw new \moodle_exception('previewsettingschanged', 'local_evalimport');
                }
                $criteria = rubric_validator::validate($rows, $target['grademax'], $policy['rules']);
                $manager = get_grading_manager($target['context'], 'mod_' . $target['cm']->modname, $target['area']);
                $manager->set_active_method('rubric');
                $controller = $manager->get_controller('rubric');
                $controller->update_definition(self::definition($criteria, $name));
                $DB->insert_record('local_evalimport_rules', (object)[
                    'definitionid' => $controller->get_definition()->id,
                    'rules' => json_encode($policy['rules'], JSON_THROW_ON_ERROR),
                ]);
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
     * Suggest a name using the same text for the form and direct imports.
     *
     * @param \cm_info $cm Import target.
     * @return string
     */
    public static function suggested_name(\cm_info $cm): string {
        $activity = format_string($cm->name, true, ['context' => $cm->context]);
        $activity = html_entity_decode(strip_tags($activity), ENT_QUOTES, 'UTF-8');
        $suggestion = get_string('rubricforactivity', 'local_evalimport', $activity);
        return \core_text::substr($suggestion, 0, rubric_validator::MAX_NAME_LENGTH);
    }

    /**
     * Validate the supplied name, or use the target's suggested name.
     *
     * @param \cm_info $cm Import target.
     * @param string|null $name User-supplied name.
     * @return string
     */
    private static function resolve_name(\cm_info $cm, ?string $name): string {
        $name = $name === null ? self::suggested_name($cm) : trim(clean_param($name, PARAM_TEXT));
        rubric_validator::validate_name($name);
        return $name;
    }

    /**
     * Build the same payload as the native rubric editor.
     *
     * @param array $criteria Validated criteria.
     * @param string $name Rubric name.
     * @return \stdClass
     */
    private static function definition(array $criteria, string $name): \stdClass {
        $definition = (object)[
            'id' => 0,
            'name' => $name,
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
