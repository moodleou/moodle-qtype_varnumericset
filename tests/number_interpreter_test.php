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
 * Unit tests for the number interpreter classes .
 *
 * @package   qtype_varnumericset
 * @copyright 2012 The Open University
 * @author    Jamie Pratt me@jamiep.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/varnumericset/number_interpreter.php');


/**
 * Unit tests for the number interpreter classes .
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group qtype_varnumericset
 */
class qtype_varnumericset_number_interpreter_test extends basic_testcase {
    public function test_interpret_number_with_optional_decimal_place() {
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_decimal_place();
        $this->assertTrue($num->match('1.23'));
        $this->assertSame('1.23', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('', $num->get_postfix());

        $this->assertTrue($num->match('1000m'));
        $this->assertSame('1000', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('010.000 m'));
        $this->assertSame('10.000', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame(' m', $num->get_postfix());

        $this->assertTrue($num->match('12.0'));
        $this->assertSame('12.0', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('', $num->get_postfix());

        $this->assertTrue($num->match('12.'));
        $this->assertSame('12', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('', $num->get_postfix());
    }

    public function test_interpret_number_with_optional_sci_notation_not_accepting_html_exponent() {
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation(false);
        $this->assertTrue($num->match('1.23e4m'));
        $this->assertSame('1.23e4', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('0.00023e67m'));
        $this->assertSame('2.3e63', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('010.000e45 m'));
        $this->assertSame('1.0000e46', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame(' m', $num->get_postfix());

        $this->assertTrue($num->match('0'));
        $this->assertSame('0', $num->get_normalised());

        $this->assertTrue($num->match('-0'));
        $this->assertSame('0', $num->get_normalised());

        $this->assertTrue($num->match('12.0'));
        $this->assertSame('12.0', $num->get_normalised());

        $this->assertTrue($num->match('12.'));
        $this->assertSame('12', $num->get_normalised());

        $this->assertTrue($num->match('12.m'));
        $this->assertSame('12', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());
    }

    public function test_interpret_number_with_optional_sci_notation_accepting_html_exponent() {
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation(true);

        $this->assertTrue($num->match('1.23e4m'));
        $this->assertSame('1.23e4', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('1.23x10<sup>4</sup>m'));
        $this->assertSame('1.23e4', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('1.23*10<sup>4</sup>m'));
        $this->assertSame('1.23e4', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('1.23×10<sup>4</sup>m'));// Using Unicode multiplication symbol.
        $this->assertSame('1.23e4', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());

        $this->assertTrue($num->match('12.0'));
        $this->assertSame('12.0', $num->get_normalised());

        $this->assertTrue($num->match('12.'));
        $this->assertSame('12', $num->get_normalised());

        $this->assertTrue($num->match('12.m'));
        $this->assertSame('12', $num->get_normalised());
        $this->assertSame('', $num->get_prefix());
        $this->assertSame('m', $num->get_postfix());
    }
}
