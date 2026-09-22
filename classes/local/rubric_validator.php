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
 * Shared rubric validation.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalises rows and applies the same rubric rules to every file format.
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rubric_validator {
    /** @var array Canonical, Chrome-compatible headers. */
    public const HEADERS = ['criterion', 'level', 'level_description', 'score'];

    /**
     * Build validated criteria. Names and level labels remain plain text.
     *
     * @param array $rows Rows including header.
     * @param float $grademax Activity maximum.
     * @return array Criteria in their first-appearance order.
     */
    public static function validate(array $rows, float $grademax): array {
        if (!$rows || count($rows) > file_reader::MAX_ROWS + 1) {
            throw new \moodle_exception('emptyrubric', 'local_evalimport');
        }
        $headers = array_map(static function($value) {
            return strtolower(trim((string)$value));
        }, array_shift($rows));
        if (count($headers) !== 4 || count(array_unique($headers)) !== 4 ||
                array_diff(self::HEADERS, $headers)) {
            throw new \moodle_exception('csvmissingcolumns', 'local_evalimport');
        }
        $criteria = [];
        $indices = [];
        foreach ($rows as $index => $values) {
            $rownum = $index + 2;
            $values = array_map(static function($value) {
                return trim((string)$value);
            }, $values);
            if (!array_filter($values, static function($value) {
                return $value !== '';
            })) {
                continue;
            }
            if (count($values) !== 4) {
                self::row_error($rownum, 'fourcolumns');
            }
            $row = array_combine($headers, $values);
            foreach (self::HEADERS as $column) {
                if ($row[$column] === '') {
                    self::row_error($rownum, 'emptycell', $column);
                }
                if (\core_text::strlen($row[$column]) > 10000 || strpos($row[$column], "\0") !== false) {
                    self::row_error($rownum, 'invalidtext', $column);
                }
            }
            // Decimal comma is accepted, but thousands separators and scientific notation are not.
            if (!preg_match('/^\d+(?:[.,]\d{1,5})?$/D', $row['score'])) {
                self::row_error($rownum, 'invalidscore', 'score');
            }
            $score = (float)str_replace(',', '.', $row['score']);
            if (!is_finite($score) || $score > 99999999) {
                self::row_error($rownum, 'invalidscore', 'score');
            }
            $key = 'criterion:' . $row['criterion'];
            if (!array_key_exists($key, $indices)) {
                $indices[$key] = count($criteria);
                $criteria[] = ['description' => $row['criterion'], 'levels' => []];
            }
            $criteria[$indices[$key]]['levels'][] = [
                'label' => $row['level'],
                'definition' => $row['level_description'],
                'score' => $score,
            ];
        }
        if (!$criteria) {
            throw new \moodle_exception('emptyrubric', 'local_evalimport');
        }
        $config = get_config('local_evalimport');
        $total = 0.0;
        foreach ($criteria as &$criterion) {
            $scores = array_column($criterion['levels'], 'score');
            if (count($scores) < 2) {
                throw new \moodle_exception('notenoughlevels', 'local_evalimport', '', $criterion['description']);
            }
            if (count(array_unique($scores, SORT_REGULAR)) !== count($scores)) {
                throw new \moodle_exception('errorrepeatedscores', 'local_evalimport', '', $criterion['description']);
            }
            $minimum = (float)($config->minlevelscore ?? 0);
            if (!empty($config->enableminlevelscore) && !in_array($minimum, $scores, true)) {
                throw new \moodle_exception('errorminmissing', 'local_evalimport', '', (object)[
                    'criterion' => $criterion['description'], 'min' => $minimum,
                ]);
            }
            $maximum = (float)($config->maxlevelscore ?? 10);
            if (!empty($config->enablemaxlevelscore) && max($scores) > $maximum) {
                throw new \moodle_exception('errormaxexceeded', 'local_evalimport', '', (object)[
                    'criterion' => $criterion['description'], 'score' => max($scores), 'max' => $maximum,
                ]);
            }
            usort($criterion['levels'], static function($left, $right) {
                return $right['score'] <=> $left['score'];
            });
            $total += max($scores);
        }
        unset($criterion);
        // Preserve the legacy comparison at two decimal places.
        if ($grademax <= 0 || round($total, 2) !== round($grademax, 2)) {
            throw new \moodle_exception('errormismatchtotal', 'local_evalimport', '', (object)[
                'sum' => $total, 'grademax' => $grademax,
            ]);
        }
        return $criteria;
    }

    /**
     * Report a row-specific validation error.
     *
     * @param int $row Row number (CSV record number).
     * @param string $reason Language string identifier.
     * @param string $column Optional column name.
     */
    private static function row_error(int $row, string $reason, string $column = ''): void {
        throw new \moodle_exception('rowerror', 'local_evalimport', '', (object)[
            'row' => $row, 'column' => $column, 'reason' => get_string($reason, 'local_evalimport'),
        ]);
    }
}
