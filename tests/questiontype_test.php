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
use qtype_varnumericset;
use question_possible_response;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/varnumericset/questiontype.php');


/**
 * Unit tests for the varnumericset question type class.
 *
 * @package    qtype_varnumericset
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_varnumericset
 */
class questiontype_test extends basic_testcase {

    protected $qtype;

    protected function setUp(): void {
        $this->qtype = new qtype_varnumericset();
    }

    protected function get_test_question_data(): stdClass {
        $q = new stdClass();
        $q->id = 1;
        $q->options = new stdClass();
        $q->options->answers[1] = (object) ['answer' => 'frog', 'fraction' => 1];
        $q->options->answers[2] = (object) ['answer' => '*', 'fraction' => 0.1];

        return $q;
    }

    public function test_name(): void {
        $this->assertEquals('varnumericset', $this->qtype->name());
    }

    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    public function test_get_random_guess_score(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals(0.1, $this->qtype->get_random_guess_score($q));
    }

    public function test_get_possible_responses(): void {
        $q = $this->get_test_question_data();

        $this->assertEquals([
            $q->id => [
                1 => new question_possible_response('frog', 1),
                2 => new question_possible_response('*', 0.1),
                null => question_possible_response::no_response()],
        ], $this->qtype->get_possible_responses($q));
    }
}
