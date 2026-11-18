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
 * Rubric file upload form.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\form;

use local_evalimport\local\rubric_validator;
use local_evalimport\local\score_policy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Upload and target selection form.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /**
     * Define form controls.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('select', 'cmid', get_string('selectactivity', 'local_evalimport'), $this->_customdata['activities']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addRule('cmid', null, 'required', null, 'server');
        $names = $this->_customdata['names'];
        $mform->addElement('text', 'rubricname', get_string('rubricname', 'local_evalimport'), ['size' => 40]);
        $mform->setType('rubricname', PARAM_TEXT);
        $mform->addRule('rubricname', null, 'required', null, 'server');
        $mform->addHelpButton('rubricname', 'rubricname', 'local_evalimport');
        if ($names) {
            $mform->setDefault('rubricname', reset($names));
            if (count($names) > 1) {
                global $PAGE;
                $PAGE->requires->js_call_amd('local_evalimport/import_form', 'init', [(object)$names]);
            }
        }
        $mform->addElement('filepicker', 'rubricfile', get_string('choosefile', 'local_evalimport'), null, [
            'accepted_types' => ['.csv', '.xls', '.xlsx'],
            'maxbytes' => \local_evalimport\local\file_reader::MAX_BYTES,
            'subdirs' => 0,
        ]);
        $mform->addRule('rubricfile', null, 'required', null, 'server');
        if ($this->_customdata['canoverride']) {
            foreach (score_policy::defaults() as $key => $default) {
                $description = $key === score_policy::MASTER ? get_string('validatescores_help', 'local_evalimport') : null;
                $mform->addElement('advcheckbox', $key, get_string($key, 'local_evalimport'), $description);
                $mform->setType($key, PARAM_BOOL);
                $mform->setDefault($key, $default);
                if ($key !== score_policy::MASTER) {
                    $mform->disabledIf($key, score_policy::MASTER, 'notchecked');
                }
            }
        }
        $mform->addElement('hidden', 'id', $this->_customdata['courseid']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(true, get_string('previewbutton', 'local_evalimport'));
    }

    /**
     * Validate the rubric name on the upload form.
     *
     * @param array $data Submitted form fields.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            rubric_validator::validate_name($data['rubricname'] ?? '');
        } catch (\moodle_exception $exception) {
            $errors['rubricname'] = get_string($exception->errorcode, 'local_evalimport', $exception->a);
        }
        return $errors;
    }
}
