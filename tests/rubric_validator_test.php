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
 * Rubric validation tests.
 *
 * @package    local_evalimport
 * @category   test
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport;

use local_evalimport\local\rubric_validator;

/**
 * Validation regression tests.
 *
 * @covers \local_evalimport\local\rubric_validator
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\rubric_validator::class)]
final class rubric_validator_test extends \advanced_testcase {
    /**
     * Numeric-looking criterion names are preserved and grouped without key conversion.
     *
     * @return void
     */
    public function test_criterion_order_and_labels(): void {
        $this->resetAfterTest();
        $rows = [rubric_validator::HEADERS, ['01', 'Excellent', '<b>Literal text</b>', '10'],
            ['1', 'Excellent', 'Good', '10'], ['01', 'Developing', 'Missing', '0'], ['1', 'Developing', 'Missing', '0']];
        $result = rubric_validator::validate($rows, 20.0);
        $this->assertSame(['01', '1'], array_column($result, 'description'));
        $this->assertSame('<b>Literal text</b>', $result[0]['levels'][0]['definition']);
    }

    /**
     * Broken rows, duplicate headers and invalid numeric inputs must never be skipped/coerced.
     *
     * @return void
     */
    public function test_invalid_inputs_are_rejected(): void {
        $this->resetAfterTest();
        $valid = [rubric_validator::HEADERS, ['A', 'High', 'Good', '10'], ['A', 'Low', 'Missing', '0']];
        $cases = [];
        foreach (['abc', '', '-1', 'NaN', '1e1', '1.000000', '1,000.00'] as $score) {
            $rows = $valid;
            $rows[1][3] = $score;
            $cases[] = $rows;
        }
        $rows = $valid;
        $rows[1] = ['A', 'High', 'Good'];
        $cases[] = $rows;
        $rows = $valid;
        $rows[1][] = 'extra';
        $cases[] = $rows;
        $rows = $valid;
        $rows[0][1] = 'criterion';
        $cases[] = $rows;
        $rows = $valid;
        $rows[2][3] = '10';
        $cases[] = $rows;
        $rows = $valid;
        $rows[1][0] = '';
        $cases[] = $rows;
        $cases[] = [rubric_validator::HEADERS];
        foreach ($cases as $index => $rows) {
            try {
                rubric_validator::validate($rows, 10.0);
                $this->fail('Invalid input accepted: ' . $index);
            } catch (\moodle_exception $exception) {
                $this->assertSame('local_evalimport', $exception->module);
            }
        }
    }

    /**
     * Legacy total-matching policy is retained.
     *
     * @return void
     */
    public function test_total_mismatch(): void {
        $this->resetAfterTest();
        $this->expectException(\moodle_exception::class);
        rubric_validator::validate($this->rows(), 20.0);
    }

    /**
     * The configured minimum must exist in every criterion.
     *
     * @return void
     */
    public function test_configured_minimum(): void {
        $this->resetAfterTest();
        set_config('enableminlevelscore', 1, 'local_evalimport');
        set_config('minlevelscore', 1, 'local_evalimport');
        $this->expectException(\moodle_exception::class);
        rubric_validator::validate($this->rows(), 10.0);
    }

    /**
     * The optional cap rejects excessive individual scores.
     *
     * @return void
     */
    public function test_configured_maximum(): void {
        $this->resetAfterTest();
        set_config('enablemaxlevelscore', 1, 'local_evalimport');
        set_config('maxlevelscore', 5, 'local_evalimport');
        $this->expectException(\moodle_exception::class);
        rubric_validator::validate($this->rows(), 10.0);
    }

    /**
     * Empty records are ignored, while zero scores are kept.
     *
     * @return void
     */
    public function test_blank_rows_and_zero_scores(): void {
        $this->resetAfterTest();
        $rows = $this->rows();
        $rows[] = ['', '', '', ''];
        $this->assertSame(0.0, rubric_validator::validate($rows, 10.0)[0]['levels'][1]['score']);
    }

    /**
     * Valid small rubric.
     *
     * @return array
     */
    private function rows(): array {
        return [rubric_validator::HEADERS, ['A', 'High', 'Good', '10'], ['A', 'Low', 'Missing', '0']];
    }
}
