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
use EvalMath;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->libdir . '/evalmath/evalmath.class.php');


/**
 * Unit tests for the EvalMath expression evaluator, specific to this question type.
 *
 * @package   qtype_varnumericset
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \EvalMath
 */
class evalmath_test extends basic_testcase {
    public function test_basic_expressions() {
        $ev = new EvalMath(true, true);

        $this->assertEquals(2, $ev->evaluate('a=2'));

        $this->expectWarning();
        $this->assertFalse($ev->evaluate('b=2+'));
        $this->assertEquals(get_string('operatorlacksoperand', 'mathslib', '+'), $ev->last_error);

        $this->assertEquals(2, $ev->evaluate('a'));

    }
    public function test_random_expressions() {
        $ev = new EvalMath(true, true);
        $results = [];
        for ($i = 0; $i < 500; $i++) {
            $ev->evaluate("a$i=rand_float()");
            $results[] = $ev->evaluate("a$i");
        }
        $this->assertTrue(min($results) >= 0 && max($results) <= 1);

        $ev = new EvalMath(true, true);
        $results = [];
        for ($i = 0; $i < 500; $i++) {
            $ev->evaluate("a$i=rand_int(500,1000)");
            $results[] = $ev->evaluate("a$i");
        }
        $this->assertTrue(min($results) >= 500 && max($results) <= 1000);
    }

}
