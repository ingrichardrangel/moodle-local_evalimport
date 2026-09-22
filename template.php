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
 * Download a four-column example in any supported format.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$course = get_course(required_param('id', PARAM_INT));
require_login($course);
require_capability('local/evalimport:view', context_course::instance($course->id));
$format = required_param('format', PARAM_ALPHA);
if (!in_array($format, ['csv', 'xls', 'xlsx'], true)) {
    throw new moodle_exception('invalidfiletype', 'local_evalimport');
}
$rows = [\local_evalimport\local\rubric_validator::HEADERS];
foreach (['argument', 'structure'] as $criterion) {
    foreach (['excellent' => 10, 'competent' => 7, 'developing' => 0] as $level => $score) {
        $rows[] = [get_string('sample_' . $criterion, 'local_evalimport'),
            get_string('sample_' . $level, 'local_evalimport'),
            get_string('sample_' . $criterion . '_' . $level, 'local_evalimport'), $score];
    }
}
$directory = make_request_directory();
$path = $directory . '/rubric-template.' . $format;
if ($format === 'csv') {
    $stream = fopen($path, 'w');
    fwrite($stream, "\xEF\xBB\xBF");
    foreach ($rows as $row) {
        fputcsv($stream, $row, ',', '"', '');
    }
    fclose($stream);
} else {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Rubrica');
    $sheet->fromArray($rows, null, 'A1');
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);
    foreach (['A' => 25, 'B' => 20, 'C' => 70, 'D' => 12] as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }
    $sheet->getStyle('A1:D7')->getAlignment()->setWrapText(true);
    $sheet->freezePane('A2');
    \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, $format === 'xls' ? 'Xls' : 'Xlsx')->save($path);
    $book->disconnectWorksheets();
}
send_temp_file($path, 'rubric-template.' . $format);
