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

namespace qtype_varnumericset;

use advanced_testcase;
use qtype_varnumeric_calculator_base;
use qtype_varnumericset_calculator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/varnumericset/calculator.php');

/**
 * Unit tests for the calculator class.
 *
 * @package   qtype_varnumericset
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_varnumericset_calculator
 * @covers    \qtype_varnumeric_calculator_base
 */
class calculator_test extends advanced_testcase {
    /**
     * Test cases for {@see test_format_number}.
     *
     * @return array[]
     */
    public function format_number_cases(): array {
        return [
            ['3.14', 3.14159, '.02f'],
            ['3.1400', 3.14, '.04f'],
            ['3.0 × 10<sup>+8</sup>', 299792458, '.01e'],
        ];
    }

    /**
     * Test for {@see \qtype_varnumeric_calculator_base::format_number}.
     *
     * @dataProvider format_number_cases()
     * @param string $expected the expected output.
     * @param float $number the number to format.
     * @param string $format the format to apply.
     */
    public function test_format_number(string $expected, float $number, string $format): void {

        $this->assertEquals($expected, qtype_varnumeric_calculator_base::htmlize_exponent(
                qtype_varnumeric_calculator_base::format_number($number, $format)));
    }

    /**
     * Test replace variable in text.
     *
     * @dataProvider evaluate_variables_in_text_provider
     * @param string $variable Variable in text.
     * @param string $expected Expected result.
     */
    public function test_evaluate_variables_in_text(string $variable, string $expected): void {
        $this->resetAfterTest();
        $calculator = new qtype_varnumericset_calculator();
        $calculator->evaluate_variant(0);
        $result = $calculator->evaluate_variables_in_text($variable);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for the test_replace_variables_to_text() test cases.
     *
     * @return array List of data sets (test cases).
     */
    public function evaluate_variables_in_text_provider(): array {

        return [
            'The argument is treated as an integer and presented as a binary number' => [
                '[[3,b]]',
                '11',
            ],
            'The argument is treated as an integer and presented as a (signed) decimal number' => [
                '[[3,d]]',
                '3',
            ],
            'The argument is treated as scientific notation (e.g. 1.2e+2)' => [
                '[[3,e]]',
                '3.000000 × 10<sup>+0</sup>',
            ],
            'General format.' => [
                '[[3,g]]',
                '3',
            ],
            'The argument is treated as an integer and presented as an octal number' => [
                '[[20,o]]',
                '24',
            ],
            'The argument is treated and presented as a string' => [
                '[[20,s]]',
                '20',
            ],
            'The argument is treated as an integer and presented as an unsigned decimal number' => [
                '[[20,u]]',
                '20',
            ],
            'The argument is treated as an integer and presented as a hexadecimal number (with lowercase letters)' => [
                '[[200,x]]',
                'c8',
            ],
            'The argument is treated as an integer and presented as a hexadecimal number (with uppercase letters)' => [
                '[[200,X]]',
                'C8',
            ],
        ];
    }
}
