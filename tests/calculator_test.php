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

use basic_testcase;

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
class calculator_test extends basic_testcase {
    /**
     * Test cases for {@see test_format_number}.
     *
     * @return array[]
     */
    public function format_number_cases(): array {
        return [
            ['3.14', 3.14159, '.2f'],
            ['3.0 × 10<sup>+8</sup>', 299792458, '.1e'],
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

        $this->assertEquals($expected, \qtype_varnumeric_calculator_base::htmlize_exponent(
                \qtype_varnumeric_calculator_base::format_number($number, $format)));
    }
}
