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
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->libdir . '/evalmath/evalmath.class.php');


/**
 * Unit tests for the EvalMath expression evaluator, specific to this question type.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      qtype_varnumericset
 */
class qtype_varnumericset_evalmath_test extends basic_testcase {
    public function test_basic_expressions() {
        $ev = new EvalMath(true, true);

        $this->assertEquals($ev->evaluate('a=2'), 2);

        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->assertFalse($ev->evaluate('b=2+'));
        $this->assertEquals($ev->last_error, get_string('operatorlacksoperand', 'mathslib', '+'));

        $this->assertEquals($ev->evaluate('a'), 2);

    }
    public function test_random_expressions() {
        $ev = new EvalMath(true, true);
        $results = array();
        for ($i=0; $i < 500; $i++) {
            $ev->evaluate("a$i=rand_float()");
            $results[] = $ev->evaluate("a$i");
        }
        $this->assertTrue(min($results) >= 0 && max($results) <= 1);

        $ev = new EvalMath(true, true);
        $results = array();
        for ($i=0; $i < 500; $i++) {
            $ev->evaluate("a$i=rand_int(500,1000)");
            $results[] = $ev->evaluate("a$i");
        }
        $this->assertTrue(min($results) >= 500 && max($results) <= 1000);
    }

}
