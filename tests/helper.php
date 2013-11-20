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
        return array('no_accepted_error', 'numeric_accepted_error', '3_sig_figs', '3_sig_figs_2',
                        '3_sig_figs_trailing_zero', '3_sig_figs_trailing_zero_negative_answer',
                        '1_sig_fig', '3_sig_figs_point_0', 'with_variables', 'custom_rounding_feebdack');
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_no_accepted_error() {
        question_bank::load_question_definition_classes('varnumericset');
        $vs = new qtype_varnumericset_question();
        test_question_maker::initialise_a_question($vs);
        $vs->name = 'test question 1';
        $vs->questiontext = '<p>The correct answer is -4.2.</p>';
        $vs->generalfeedback = '<p>General feedback -4.2.</p>';
        $vs->penalty = 0.3333333;
        $vs->randomseed = '';
        $vs->requirescinotation = false;
        $vs->usesupeditor = false;
        $vs->qtype = question_bank::get_qtype('varnumericset');

        $vs->answers = array(1 => new qtype_varnumericset_answer('1', // Id.
                                                 '-4.2',  // Answer.
                                                 '1',     // Fraction.
                                                 '<p>Your answer is correct.</p>', // Feedback.
                                                 FORMAT_HTML,  // Feedbackformat.
                                                 '0',     // Sigfigs.
                                                 '',      // Error.
                                                 '0.1',   // Syserrorpenalty.
                                                 '0',     // Checknumerical.
                                                 '0',     // Checkscinotation.
                                                 '0',     // Checkpowerof10.
                                                 '0'),    // Checkrounding.
                            2 => new qtype_varnumericset_answer('2', // Id.
                                                 '*',     // Answer.
                                                 '0',     // Fraction.
                                                 '<p>Your answer is incorrect.</p>', // Feedback.
                                                 FORMAT_HTML,  // Feedbackformat.
                                                 '0',     // Sigfigs.
                                                 '',      // Error.
                                                 '0.1000000', // Syserrorpenalty.
                                                 '0',     // Checknumerical.
                                                 '0',     // Checkscinotation.
                                                 '0',     // Checkpowerof10.
                                                 '0'));   // Checkrounding.
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
        // Add acceptable error for correct answer.
        $vs->name = 'test question 2';
        $vs->answers[1]->error = '0.000001';
        return $vs;
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_3_sig_figs() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The correct answer is 12300.</p>';
        $vs->generalfeedback = '<p>General feedback 12300.</p>';
        $vs->answers[1]->answer = '12345';
        $vs->answers[1]->sigfigs = 3;
        return $vs;
    }

    public function make_varnumericset_question_3_sig_figs_2() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The answer is 1.23456 to 3 sig figs = 1.23.</p>';
        $vs->generalfeedback = '<p>General feedback 1.23456.</p>';
        $vs->answers[1]->answer = '1.23456';
        $vs->answers[1]->sigfigs = 3;
        return $vs;
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_3_sig_figs_trailing_zero() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The correct answer is 0.0720.</p>';
        $vs->generalfeedback = '<p>General feedback 0.0720.</p>';
        $vs->answers[1]->answer = '0.0720';
        $vs->answers[1]->sigfigs = 3;
        return $vs;
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_3_sig_figs_trailing_zero_negative_answer() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The correct answer is -0.0720.</p>';
        $vs->generalfeedback = '<p>General feedback -0.0720.</p>';
        $vs->answers[1]->answer = '-0.0720';
        $vs->answers[1]->sigfigs = 3;
        return $vs;
    }

    /**
     * @return qtype_varnumericset_question
     */
    public function make_varnumericset_question_1_sig_fig() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The correct answer is 1e9.</p>';
        $vs->generalfeedback = '<p>General feedback 1e9.</p>';
        $vs->requirescinotation = true;
        $vs->usesupeditor = true;
        $vs->answers[1]->answer = '1.0e9';
        $vs->answers[1]->sigfigs = 1;
        $vs->answers[1]->checknumerical = 1;
        $vs->answers[1]->checkscinotation = 1;
        return $vs;
    }

    public function make_varnumericset_question_3_sig_figs_point_0() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The answer is 12.0 to 3 sig figs.</p>';
        $vs->generalfeedback = '<p>General feedback 12.0.</p>';
        $vs->answers[1]->answer = '12.0';
        $vs->answers[1]->sigfigs = 3;
        return $vs;
    }

    public function make_varnumericset_question_with_variables() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>What is [[a]] + [[b]]?</p>';
        $vs->generalfeedback = '<p>General feedback 1e9.</p>';
        $vs->requirescinotation = 1;
        $vs->answers[1]->answer = 'a + b';
        $vs->answers[1]->sigfigs = 1;
        $vs->answers[1]->checknumerical = 1;
        $vs->answers[1]->checkscinotation = 1;

        $vs->calculator->add_variable(0, 'a');
        $vs->calculator->add_variable(1, 'b');
        $vs->calculator->add_defined_variant(0, 0, 2);
        $vs->calculator->add_defined_variant(1, 0, 3);
        $vs->calculator->evaluate_variant(0);

        return $vs;
    }

    public function make_varnumericset_question_custom_rounding_feebdack() {
        $vs = $this->make_varnumericset_question_no_accepted_error();

        $vs->questiontext = '<p>The figure shows selected data ... What is ...? ' .
                '(Give your answer to one decimal place and do not include the % symbol.) ________ %?</p>';
        $vs->generalfeedback = '<p>The answer is 2.3.</p>';
        $vs->requirescinotation = 0;

        $vs->answers[1]->answer = '2.3';
        $vs->answers[1]->error = 0.000001;
        $vs->answers[1]->sigfigs = 2;
        $vs->answers[1]->fraction = 1;
        $vs->answers[1]->feedback = 'Your answer is correct.';
        $vs->answers[1]->checkrounding = 1;

        $vs->answers[2]->answer = '2.2';
        $vs->answers[2]->error = 0.000001;
        $vs->answers[2]->sigfigs = 2;
        $vs->answers[2]->fraction = 0.9;
        $vs->answers[2]->feedback = 'Your answer is acceptable but you should have rounded your answer to 2.3.';

        $vs->answers[3] = new qtype_varnumericset_answer(
                '3',        // Id.
                '2.285714', // Answer.
                '0',        // Fraction.
                '<p>You have not given your answer to just one decimal place as requested.</p>', // Feedback.
                FORMAT_HTML,     // Feedbackformat.
                '0',        // Sigfigs.
                '0.01',     // Error.
                '0.1',      // Syserrorpenalty.
                '0',        // Checknumerical.
                '0',        // Checkscinotation.
                '0',        // Checkpowerof10.
                '0');       // Checkrounding.

        $vs->answers[4] = new qtype_varnumericset_answer(
                '4',        // Id.
                '*',        // Answer.
                '0',        // Fraction.
                '<p>Your answer is incorrect.</p>', // Feedback.
                FORMAT_HTML,     // Feedbackformat.
                '0',        // Sigfigs.
                '',         // Error.
                '0.1',      // Syserrorpenalty.
                '0',        // Checknumerical.
                '0',        // Checkscinotation.
                '0',        // Checkpowerof10.
                '0');       // Checkrounding.

        return $vs;
    }
}
