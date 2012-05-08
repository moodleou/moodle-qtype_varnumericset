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
 * Test helpers for the varnumericset question type.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the varnumericset question type.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumericset_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('no_accepted_error', 'numeric_accepted_error');
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_no_accepted_error() {
        question_bank::load_question_definition_classes('varnumericset');
        $vs = new qtype_varnumericset_question();
        test_question_maker::initialise_a_question($vs);
        $vs->name = 'Q1a1 FAULTY No accepted error VN Response match directly specified negative value';
        $vs->questiontext = '<p>The correct answer is -4.2.</p>';
        $vs->generalfeedback = '<p>General feedback -4.2.</p>';
        $vs->penalty = 0.3333333;
        $vs->randomseed = '';
        $vs->requirescinotation = 0;
        $vs->qtype = question_bank::get_qtype('varnumericset');

        $vs->answers = array(1 => new qtype_varnumericset_answer('1', //id
                                                 '-4.2',  //answer
                                                 '100',  //fraction
                                                 '<p>Your answer is correct.</p>', //feedback
                                                 'html', //feedbackformat
                                                 '0', //sigfigs
                                                 '', //error
                                                 '0.1000000', //syserrorpenalty
                                                 '0', //checknumerical
                                                 '0', //checkscinotation
                                                 '0', //checkpowerof10
                                                 '0'), //checkrounding
                            2 => new qtype_varnumericset_answer('2', //id
                                                 '*',  //answer
                                                 '0',  //fraction
                                                 '<p>Your answer is incorrect.</p>', //feedback
                                                 'html', //feedbackformat
                                                 '0', //sigfigs
                                                 '', //error
                                                 '0.1000000', //syserrorpenalty
                                                 '0', //checknumerical
                                                 '0', //checkscinotation
                                                 '0', //checkpowerof10
                                                 '0')); //checkrounding);
        $calculatorname = $vs->qtype->calculator_name();
        $vs->calculator = new $calculatorname();
        $vs->calculator->evaluate_variant(0);
        return $vs;
    }
    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_numeric_accepted_error() {
        $vs = $this->make_varnumericset_question_no_accepted_error();
        //add acceptable error for correct answer.
        $vs->answers[1]->error = '0.000001';
        return $vs;
    }

}
