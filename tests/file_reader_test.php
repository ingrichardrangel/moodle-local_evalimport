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
 * File reader regression tests.
 *
 * @package    local_evalimport
 * @category   test
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalimport;

use local_evalimport\local\file_reader;
use local_evalimport\local\rubric_validator;

/**
 * File format regression tests.
 *
 * @covers \local_evalimport\local\file_reader
 * @covers \local_evalimport\local\rubric_validator
 *
 * @package    local_evalimport
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\file_reader::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_evalimport\local\rubric_validator::class)]
final class file_reader_test extends \advanced_testcase {
    /**
     * The original CSV, XLS and XLSX fixtures must normalise identically.
     *
     * @return void
     */
    public function test_all_formats_produce_identical_rubrics(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $expected = null;
        foreach (['csv', 'xls', 'xlsx'] as $extension) {
            $actual = rubric_validator::validate(file_reader::read($this->fixture('rubric.' . $extension)), 20.0);
            if ($expected === null) {
                $expected = $actual;
            }
            $this->assertSame($expected, $actual, $extension);
        }
        $this->assertCount(2, $expected);
        $this->assertCount(3, $expected[0]['levels']);
    }

    /**
     * Quoted delimiters, quotes, BOM, CRLF and multiline descriptions survive.
     *
     * @return void
     */
    public function test_csv_quoted_records(): void {
        $this->resetAfterTest();
        $rows = file_reader::csv("\xEF\xBB\xBFcriterion,level,level_description,score\r\n" .
            "Argument,Excellent,\"First line, with a comma\r\nSecond \"\"quoted\"\" line\",10\r\n" .
            "Argument,Developing,Missing,0\r\n");
        $rubric = rubric_validator::validate($rows, 10.0);
        $this->assertSame("First line, with a comma\nSecond \"quoted\" line", $rubric[0]['levels'][0]['definition']);
    }

    /**
     * Semicolon CSV accepts unambiguous decimal commas.
     *
     * @return void
     */
    public function test_semicolon_and_decimal_comma(): void {
        $this->resetAfterTest();
        $rows = file_reader::csv("criterion;level;level_description;score\nA;High;Good;2,5\nA;Low;Missing;0\n");
        $result = rubric_validator::validate($rows, 2.5);
        $this->assertSame(2.5, $result[0]['levels'][0]['score']);
    }

    /**
     * An explicit Rubrica sheet takes precedence over an instruction sheet.
     *
     * @return void
     */
    public function test_named_sheet_is_selected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $rows = file_reader::read($this->fixture('multiple-sheets.xlsx'));
        $this->assertCount(2, rubric_validator::validate($rows, 20.0));
    }

    /**
     * Formula cells must be rejected without calculation.
     *
     * @return void
     */
    public function test_formula_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('formulanotallowed', 'local_evalimport', 'D2'));
        file_reader::read($this->fixture('formula.xlsx'));
    }

    /**
     * A renamed text file is not an Excel workbook.
     *
     * @return void
     */
    public function test_fake_workbook_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $file = $this->fixture('rubric.csv', 'fake.xlsx');
        $this->expectException(\moodle_exception::class);
        file_reader::read($file);
    }

    /**
     * Merged cells are not silently expanded or guessed.
     *
     * @return void
     */
    public function test_merged_cells_are_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        file_reader::read($this->fixture('merged.xlsx'));
    }

    /**
     * The CSV row limit bounds parsing work.
     *
     * @return void
     */
    public function test_row_limit(): void {
        $this->expectException(\moodle_exception::class);
        file_reader::csv("criterion,level,level_description,score\n" . str_repeat("A,B,C,0\n", 2001));
    }

    /**
     * Draft lookups never read another user's file.
     *
     * @return void
     */
    public function test_draft_is_user_scoped(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $file = $this->fixture('rubric.csv');
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        file_reader::draft_file($file->get_itemid());
    }

    /**
     * Reject formula XLS input with a controlled validation error.
     *
     * @return void
     */
    public function test_xls_formula_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        try {
            file_reader::read($this->fixture('formula.xls'));
            $this->fail('Invalid XLS accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('formulanotallowed', $exception->errorcode);
        }
    }

    /**
     * Reject merged XLS input with a controlled validation error.
     *
     * @return void
     */
    public function test_xls_merged_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        try {
            file_reader::read($this->fixture('merged.xls'));
            $this->fail('Invalid XLS accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('mergedcells', $exception->errorcode);
        }
    }

    /**
     * Reject boolean XLS input with a controlled validation error.
     *
     * @return void
     */
    public function test_xls_boolean_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        try {
            file_reader::read($this->fixture('boolean.xls'));
            $this->fail('Invalid XLS accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('invalidcell', $exception->errorcode);
        }
    }

    /**
     * Reject cyclic XLS input with a controlled validation error.
     *
     * @return void
     */
    public function test_xls_cyclic_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        try {
            file_reader::read($this->fixture('cyclic.xls'));
            $this->fail('Invalid XLS accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('invalidworkbook', $exception->errorcode);
        }
    }

    /**
     * XLS selects Rubrica and reads numeric values independently of formatting.
     *
     * @return void
     */
    public function test_xls_sheet_selection_and_numeric_values(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $expected = rubric_validator::validate(file_reader::read($this->fixture('rubric.csv')), 20.0);
        foreach (['multiple-sheets.xls', 'numeric-format.xls'] as $name) {
            $actual = rubric_validator::validate(file_reader::read($this->fixture($name)), 20.0);
            $this->assertSame($expected, $actual, $name);
        }
    }

    /**
     * Both downloadable XLS templates can be imported as twenty-point rubrics.
     *
     * @return void
     */
    public function test_xls_templates_are_importable(): void {
        $this->resetAfterTest();
        foreach (['en', 'es'] as $language) {
            $rows = \local_evalimport\local\xls_reader::read(__DIR__ . '/../templates/rubric-' . $language . '.xls');
            $this->assertCount(2, rubric_validator::validate($rows, 20.0));
        }
    }

    /**
     * Put a fixture in the current user's draft area.
     *
     * @param string $name Source filename.
     * @param string|null $filename Uploaded name override.
     * @return \stored_file
     */
    private function fixture(string $name, ?string $filename = null): \stored_file {
        global $USER;
        return get_file_storage()->create_file_from_pathname([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/', 'filename' => $filename ?? $name,
        ], __DIR__ . '/fixtures/' . $name);
    }
}
