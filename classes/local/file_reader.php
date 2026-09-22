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
 * File readers for rubric imports.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

/**
 * Reads supported files into the same four-column row structure.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_reader {
    /** @var int Maximum compressed/uploaded size. */
    public const MAX_BYTES = 2097152;
    /** @var int Maximum number of data rows. */
    public const MAX_ROWS = 2000;
    /** @var int Maximum uncompressed XLSX size. */
    private const MAX_EXPANDED_BYTES = 20971520;

    /**
     * Obtain exactly one file from the current user's draft area.
     *
     * @param int $draftid Draft item ID.
     * @return \stored_file
     */
    public static function draft_file(int $draftid): \stored_file {
        global $USER;
        $context = \context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files($context->id, 'user', 'draft', $draftid, 'id', false);
        if (count($files) !== 1) {
            throw new \moodle_exception('filerequired', 'local_evalimport');
        }
        return reset($files);
    }

    /**
     * Read an uploaded file; temporary copies are always removed.
     *
     * @param \stored_file $file Input file.
     * @return array Rows including the header.
     */
    public static function read(\stored_file $file): array {
        if ($file->get_filesize() > self::MAX_BYTES || $file->get_filesize() === 0) {
            throw new \moodle_exception('invalidfilesize', 'local_evalimport');
        }
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            return self::csv($file->get_content());
        }
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            throw new \moodle_exception('invalidfiletype', 'local_evalimport');
        }
        $directory = make_request_directory();
        $path = $directory . '/rubric.' . $extension;
        try {
            $file->copy_content_to($path);
            return self::spreadsheet($path, $extension);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Parse CSV records, including quoted newlines and UTF-8 BOM.
     *
     * @param string $content CSV text.
     * @return array
     */
    public static function csv(string $content): array {
        if (strlen($content) > self::MAX_BYTES || !preg_match('//u', $content)) {
            throw new \moodle_exception('invalidcsvencoding', 'local_evalimport');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $stream = fopen('php://temp', 'w+');
        try {
            fwrite($stream, str_replace(["\r\n", "\r"], "\n", $content));
            rewind($stream);
            $firstline = fgets($stream);
            $delimiter = count(str_getcsv((string)$firstline, ';', '"', '')) === 4 ? ';' : ',';
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                $rows[] = $row;
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw new \moodle_exception('toomanyrows', 'local_evalimport');
                }
            }
            return $rows;
        } finally {
            fclose($stream);
        }
    }

    /**
     * Read a real XLS/XLSX workbook using Moodle's bundled PhpSpreadsheet.
     *
     * @param string $path Local temporary path.
     * @param string $extension Expected file type.
     * @return array
     */
    private static function spreadsheet(string $path, string $extension): array {
        $book = null;
        try {
            if ($extension === 'xlsx') {
                self::check_archive($path);
            }
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($extension === 'xls' ? 'Xls' : 'Xlsx');
            if (!$reader->canRead($path)) {
                throw new \moodle_exception('invalidworkbook', 'local_evalimport');
            }
            $sheets = $reader->listWorksheetInfo($path);
            if (!$sheets) {
                throw new \moodle_exception('invalidworkbook', 'local_evalimport');
            }
            $selected = reset($sheets);
            foreach ($sheets as $sheet) {
                if ($sheet['worksheetName'] === 'Rubrica') {
                    $selected = $sheet;
                    break;
                }
            }
            if ($selected['totalRows'] > self::MAX_ROWS + 1 || $selected['totalColumns'] > 4) {
                throw new \moodle_exception('workbookdimensions', 'local_evalimport');
            }
            $reader->setLoadSheetsOnly([$selected['worksheetName']]);
            // Retain cell types and merged-cell information; never calculate formulas.
            $reader->setReadDataOnly(false);
            $book = $reader->load($path);
            $sheet = $book->getSheetByName($selected['worksheetName']);
            if (!$sheet || $sheet->getMergeCells()) {
                throw new \moodle_exception('mergedcells', 'local_evalimport');
            }
            if (
                $sheet->getHighestRow() > self::MAX_ROWS + 1 ||
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()) > 4
            ) {
                throw new \moodle_exception('workbookdimensions', 'local_evalimport');
            }
            $rows = [];
            for ($number = 1; $number <= $sheet->getHighestDataRow(); $number++) {
                $row = [];
                foreach (['A', 'B', 'C', 'D'] as $column) {
                    $cell = $sheet->getCell($column . $number);
                    if (in_array($cell->getDataType(), ['f', 'e'], true)) {
                        throw new \moodle_exception('formulanotallowed', 'local_evalimport', '', $column . $number);
                    }
                    $value = $cell->getValue();
                    if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                        $value = $value->getPlainText();
                    }
                    if (
                        is_bool($value) || ($column === 'D' &&
                        \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell))
                    ) {
                        throw new \moodle_exception('invalidcell', 'local_evalimport', '', $column . $number);
                    }
                    $row[] = $value === null ? '' : (string)$value;
                }
                $rows[] = $row;
            }
            return $rows;
        } catch (\moodle_exception $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new \moodle_exception('invalidworkbook', 'local_evalimport');
        } finally {
            if ($book !== null) {
                $book->disconnectWorksheets();
            }
        }
    }

    /**
     * Bound ZIP expansion before loading XLSX contents.
     *
     * @param string $path Archive path.
     */
    private static function check_archive(string $path): void {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \moodle_exception('invalidworkbook', 'local_evalimport');
        }
        try {
            $size = 0;
            if ($zip->numFiles > 1000) {
                throw new \moodle_exception('invalidfilesize', 'local_evalimport');
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $size += $entry['size'];
                if ($size > self::MAX_EXPANDED_BYTES) {
                    throw new \moodle_exception('invalidfilesize', 'local_evalimport');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
