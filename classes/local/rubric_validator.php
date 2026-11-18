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

    /** @var int Maximum number of characters in a rubric name. */
    public const MAX_NAME_LENGTH = 255;

    /** @var int Maximum UTF-8 byte length of an imported text field. */
    public const MAX_TEXT_BYTES = 10000;

    /** @var float Largest score supported by Moodle's DECIMAL(10,5) level field. */
    private const MAX_SCORE = 99999.99999;

    /**
     * Check the length of the rubric name before preview and save.
     *
     * @param string $name Rubric name.
     * @return void
     */
    public static function validate_name(string $name): void {
        if (trim($name) === '') {
            throw new \moodle_exception('rubricnamerequired', 'local_evalimport');
        }
        if (\core_text::strlen($name) > self::MAX_NAME_LENGTH) {
            throw new \moodle_exception('rubricnametoolong', 'local_evalimport', '', self::MAX_NAME_LENGTH);
        }
    }

    /**
     * Build validated criteria. Names and level labels remain plain text.
     *
     * @param array $rows Rows including header.
     * @param float $grademax Activity maximum.
     * @param array|null $rules Effective scoring rules, or null to use the site defaults.
     * @return array Criteria in their first-appearance order.
     */
    public static function validate(array $rows, float $grademax, ?array $rules = null): array {
        return self::validate_with_total($rows, $grademax, $rules)['criteria'];
    }

    /**
     * Build validated criteria and return the sum of their highest scores.
     * @param array $rows Rows including header.
     * @param float $grademax Activity maximum.
     * @param array|null $rules Effective scoring rules, or null to use the site defaults.
     * @return array Validated criteria and their total maximum score.
     */
    public static function validate_with_total(array $rows, float $grademax, ?array $rules = null): array {
        $rules = $rules ?? score_policy::effective(get_config('local_evalimport'));
        if (!$rows || count($rows) > file_reader::MAX_ROWS + 1) {
            throw new \moodle_exception('emptyrubric', 'local_evalimport');
        }
        $headers = array_map(static function ($value) {
            return strtolower(trim((string)$value));
        }, array_shift($rows));
        if (
            count($headers) !== 4 || count(array_unique($headers)) !== 4 ||
            array_diff(self::HEADERS, $headers)
        ) {
            throw new \moodle_exception('csvmissingcolumns', 'local_evalimport');
        }
        $criteria = [];
        $indices = [];
        foreach ($rows as $index => $values) {
            $rownum = $index + 2;
            $values = array_map(static function ($value) {
                return trim((string)$value, " \t\n\r\v");
            }, $values);
            $nonempty = array_filter($values, static function ($value) {
                return $value !== '';
            });
            if (!$nonempty) {
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
                if (strpos($row[$column], "\0") !== false || !preg_match('//u', $row[$column])) {
                    self::row_error($rownum, 'invalidtext', $column);
                }
                if ($column !== 'score' && strlen($row[$column]) > self::MAX_TEXT_BYTES) {
                    self::row_error($rownum, 'texttoolong', $column, self::MAX_TEXT_BYTES);
                }
            }
            // Decimal comma is accepted, but thousands separators and scientific notation are not.
            if (!preg_match('/^\d+(?:[.,]\d{1,5})?$/D', $row['score'])) {
                self::row_error($rownum, 'invalidscore', 'score');
            }
            $score = (float)str_replace(',', '.', $row['score']);
            if (!is_finite($score) || $score > self::MAX_SCORE) {
                self::row_error($rownum, 'scoretoolarge', 'score', self::MAX_SCORE);
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
        $total = 0.0;
        $minimumtotal = 0.0;
        foreach ($criteria as &$criterion) {
            $scores = array_column($criterion['levels'], 'score');
            if (count($scores) < 2) {
                throw new \moodle_exception('notenoughlevels', 'local_evalimport', '', $criterion['description']);
            }
            if ($rules['requireuniquescores'] && count(array_unique($scores, SORT_REGULAR)) !== count($scores)) {
                throw new \moodle_exception('errorrepeatedscores', 'local_evalimport', '', $criterion['description']);
            }
            if ($rules['requirezerolevel'] && !in_array(0.0, $scores, true)) {
                throw new \moodle_exception('errorzerolevelmissing', 'local_evalimport', '', $criterion['description']);
            }
            $minimum = $rules['minimumlevelscore'];
            if ($minimum !== null && !in_array($minimum, $scores, true)) {
                throw new \moodle_exception('errorminmissing', 'local_evalimport', '', (object)[
                    'criterion' => $criterion['description'], 'min' => $minimum,
                ]);
            }
            $maximum = $rules['maximumlevelscore'];
            if ($maximum !== null && max($scores) > $maximum) {
                throw new \moodle_exception('errormaxexceeded', 'local_evalimport', '', (object)[
                    'criterion' => $criterion['description'], 'score' => max($scores), 'max' => $maximum,
                ]);
            }
            usort($criterion['levels'], static function ($left, $right) {
                return $right['score'] <=> $left['score'];
            });
            $total += max($scores);
            $minimumtotal += min($scores);
        }
        unset($criterion);
        if ($total <= 0) {
            throw new \moodle_exception('nonpositiverubrictotal', 'local_evalimport');
        }
        if ($total <= $minimumtotal) {
            throw new \moodle_exception('norubricscorerange', 'local_evalimport');
        }
        // Preserve the legacy comparison at two decimal places when the rule is enabled.
        if ($rules['matchactivitymaximum'] && ($grademax <= 0 || round($total, 2) !== round($grademax, 2))) {
            throw new \moodle_exception('errormismatchtotal', 'local_evalimport', '', (object)[
                'sum' => $total, 'grademax' => $grademax,
            ]);
        }
        return ['criteria' => $criteria, 'totalpoints' => $total];
    }

    /**
     * Report a row-specific validation error.
     *
     * @param int $row Row number (CSV record number).
     * @param string $reason Language string identifier.
     * @param string $column Optional column name.
     * @param mixed $argument Optional reason-string argument.
     */
    private static function row_error(int $row, string $reason, string $column = '', $argument = null): void {
        $message = get_string($reason, 'local_evalimport', $argument);
        if ($column === '') {
            throw new \moodle_exception('rowerrorwithoutcolumn', 'local_evalimport', '', (object)[
                'row' => $row, 'reason' => $message,
            ]);
        }
        throw new \moodle_exception('rowerror', 'local_evalimport', '', (object)[
            'row' => $row, 'column' => $column, 'reason' => $message,
        ]);
    }
}
