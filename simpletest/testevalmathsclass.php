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
 * Unit tests for the short answer question definition class.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/evalmath/evalmath.class.php');


/**
 * Unit tests for the EvalMath expression evaluator, specific to this question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_evalmath_test extends UnitTestCase {
    public function test_basic_expressions() {
        $ev = new EvalMath(true, true);

        $this->assertTrue($ev->evaluate('a=2'));

        $this->expectError();
        $this->assertFalse($ev->evaluate('b=2+'));
        $this->assertEqual($ev->last_error, get_string('operatorlacksoperand', 'mathslib', '+'));

        $this->assertEqual($ev->evaluate('a'), 2);

    }


}
