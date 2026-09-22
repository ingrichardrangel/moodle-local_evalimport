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
 * Bounded adapter for the bundled legacy Excel reader.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../vendor/simplexls/SimpleXLS.php');

/**
 * Read BIFF workbooks without Moodle's removed XLS/OLE classes.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xls_reader extends \local_evalimport_vendor\SimpleXLS {
    // The method name is fixed by the inherited SimpleXLS API.
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    /**
     * Convert the selected worksheet to unformatted values.
     *
     * @param string $path Local file path.
     * @return array
     */
    public static function read(string $path): array {
        // Malformed BIFF must become a controlled error, including PHP 7 warnings.
        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $book = new self($path);
            $index = array_search('Rubrica', $book->sheetNames(), true);
            $index = $index === false ? 0 : $index;
            if (!isset($book->sheets[$index])) {
                throw new \moodle_exception('invalidworkbook', 'local_evalimport');
            }
            $sheet = $book->sheets[$index];
            $rowcount = max($sheet['numRows'], $sheet['maxrow'] + 1);
            $colcount = max($sheet['numCols'], $sheet['maxcol'] + 1);
            if ($rowcount > file_reader::MAX_ROWS + 1 || $colcount > 4) {
                throw new \moodle_exception('workbookdimensions', 'local_evalimport');
            }
            $rows = [];
            for ($row = 0; $row < $rowcount; $row++) {
                $values = [];
                for ($column = 0; $column < 4; $column++) {
                    $info = $sheet['cellsInfo'][$row][$column] ?? [];
                    $type = $info['t'] ?? '';
                    if ($column === 3 && $type === 'd') {
                        throw new \moodle_exception('invalidcell', 'local_evalimport', '', 'D' . ($row + 1));
                    }
                    $value = $sheet['cells'][$row][$column] ?? '';
                    if ($type === 'n') {
                        $value = $info['raw'] ?? $value;
                    }
                    $values[] = (string)$value;
                }
                $rows[] = $values;
            }
            return $rows;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Select one sheet and check BIFF records before decoding cells.
     *
     * @param int $spos Worksheet offset in the workbook stream.
     * @return bool
     */
    protected function parseSheet($spos) {
        // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        $selected = array_search('Rubrica', array_column($this->boundsheets, 'name'), true);
        $selected = $selected === false ? 0 : $selected;
        if ($this->sn !== $selected) {
            return true;
        }
        $length = strlen($this->data);
        $offset = $spos;
        $ended = false;
        while ($offset + 4 <= $length) {
            $record = unpack('vtype/vsize', substr($this->data, $offset, 4));
            $offset += 4;
            if ($offset + $record['size'] > $length) {
                throw new \moodle_exception('invalidworkbook', 'local_evalimport');
            }
            $type = $record['type'];
            if ($type === self::TYPE_EOF) {
                $ended = true;
                break;
            }
            if ($type === self::TYPE_MERGEDCELLS) {
                throw new \moodle_exception('mergedcells', 'local_evalimport');
            }
            if (in_array($type, [self::TYPE_FORMULA, self::TYPE_FORMULA2, self::TYPE_BOOLERR], true)) {
                if ($record['size'] < 8) {
                    throw new \moodle_exception('invalidworkbook', 'local_evalimport');
                }
                $cell = unpack('vrow/vcolumn', substr($this->data, $offset, 4));
                $address = chr(65 + min($cell['column'], 25)) . ($cell['row'] + 1);
                $error = $type === self::TYPE_BOOLERR && ord($this->data[$offset + 7]) === 0
                    ? 'invalidcell' : 'formulanotallowed';
                throw new \moodle_exception($error, 'local_evalimport', '', $address);
            }
            $offset += $record['size'];
        }
        if (!$ended) {
            throw new \moodle_exception('invalidworkbook', 'local_evalimport');
        }
        return parent::parseSheet($spos);
    }

    // The method name is fixed by the inherited SimpleXLS API.
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    /**
     * Bound sparse cell allocations, regardless of the DIMENSION record.
     *
     * @param int $row Zero-based row.
     * @param int $col Zero-based column.
     * @param mixed $string Display value.
     * @param mixed $raw Raw value.
     * @param int $typecode BIFF record type.
     * @param string $typealias Cell type.
     */
    protected function addCell($row, $col, $string, $raw = '', $typecode = 0, $typealias = '') {
        // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        if ($row > file_reader::MAX_ROWS || $col > 3) {
            throw new \moodle_exception('workbookdimensions', 'local_evalimport');
        }
        parent::addCell($row, $col, $string, $raw, $typecode, $typealias);
    }
}
