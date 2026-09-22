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
 * Two-step rubric import page.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_evalimport\local\file_reader;
use local_evalimport\local\importer;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$course = get_course(required_param('id', PARAM_INT));
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/evalimport:view', $context);
$url = new moodle_url('/local/evalimport/import.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pageheading', 'local_evalimport'));
$PAGE->set_heading(format_string($course->fullname));

$activities = local_evalimport_get_importable_activities($course);
$form = new \local_evalimport\form\import_form($url, ['activities' => $activities, 'courseid' => $course->id]);
$token = optional_param('token', '', PARAM_ALPHANUM);
$preview = null;
$confirmform = null;
$error = null;

// Session previews contain only draft IDs and hashes, and expire after 30 minutes.
if (!isset($SESSION->local_evalimport)) {
    $SESSION->local_evalimport = [];
}
foreach ($SESSION->local_evalimport as $key => $pending) {
    if ($pending['expires'] < time()) {
        unset($SESSION->local_evalimport[$key]);
    }
}
try {
    if ($token !== '') {
        $confirmform = new \local_evalimport\form\confirm_form($url, ['courseid' => $course->id, 'token' => $token]);
        if ($confirmform->is_cancelled()) {
            unset($SESSION->local_evalimport[$token]);
            redirect($url);
        }
        if ($confirmform->get_data()) {
            require_sesskey();
            $pending = $SESSION->local_evalimport[$token] ?? null;
            if (!$pending || $pending['courseid'] !== (int)$course->id) {
                throw new moodle_exception('previewexpired', 'local_evalimport');
            }
            $file = file_reader::draft_file($pending['draftid']);
            if (!hash_equals($pending['hash'], $file->get_contenthash())) {
                throw new moodle_exception('previewexpired', 'local_evalimport');
            }
            $editorurl = importer::save($course, $pending['cmid'], $file);
            unset($SESSION->local_evalimport[$token]);
            redirect($editorurl, get_string('importsuccess', 'local_evalimport'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        throw new moodle_exception('previewexpired', 'local_evalimport');
    }
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
    }
    if ($data = $form->get_data()) {
        require_sesskey();
        $file = file_reader::draft_file((int)$data->rubricfile);
        $preview = importer::preview($course, (int)$data->cmid, $file);
        $token = bin2hex(random_bytes(16));
        // Bound session growth when the user opens many previews.
        while (count($SESSION->local_evalimport) >= 5) {
            array_shift($SESSION->local_evalimport);
        }
        $SESSION->local_evalimport[$token] = [
            'courseid' => (int)$course->id, 'cmid' => (int)$data->cmid,
            'draftid' => (int)$data->rubricfile, 'hash' => $file->get_contenthash(), 'expires' => time() + 1800,
        ];
        $confirmform = new \local_evalimport\form\confirm_form($url, ['courseid' => $course->id, 'token' => $token]);
    }
} catch (moodle_exception $exception) {
    $error = $exception->module === 'local_evalimport' ? $exception->getMessage() : get_string('importfailed', 'local_evalimport');
    $preview = null;
} catch (Throwable $exception) {
    $error = get_string('importfailed', 'local_evalimport');
    $preview = null;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pageheading', 'local_evalimport'));
if ($error !== null) {
    echo $OUTPUT->notification(s($error), \core\output\notification::NOTIFY_ERROR);
}
echo html_writer::tag('p', get_string('filehelp', 'local_evalimport'));
foreach (['xlsx', 'xls', 'csv'] as $format) {
    echo html_writer::link(new moodle_url('/local/evalimport/template.php', ['id' => $course->id, 'format' => $format]),
        get_string('downloadtemplate', 'local_evalimport', strtoupper($format)), ['class' => 'btn btn-secondary mr-2 mb-2']);
}
if ($preview !== null) {
    echo $OUTPUT->heading(get_string('previewimport', 'local_evalimport'), 3);
    echo html_writer::tag('p', s($preview['target']['cm']->name));
    echo $OUTPUT->notification(get_string('previewnotice', 'local_evalimport'), \core\output\notification::NOTIFY_INFO);
    echo local_evalimport_preview_table($preview['criteria']);
    $confirmform->display();
} else if (!$activities) {
    echo $OUTPUT->notification(get_string('noactivitiesavailable', 'local_evalimport'));
} else {
    $form->display();
}
echo $OUTPUT->footer();
