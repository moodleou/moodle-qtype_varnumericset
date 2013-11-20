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
 * This file contains overall tests of varnumericset questions.
 *
 * @package   qtype_varnumericset
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/varnumericset/tests/helper.php');


/**
 * Walk through Unit tests for varnumericset questions.
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group qtype_varnumericset
 */
class qtype_varnumericset_walkthrough_testcase extends qbehaviour_walkthrough_test_base {
    public function test_validation_and_interactive_with_one_try_for_3_sig_figs() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $q->hints = array(
            new question_hint(1, 'This is the first hint.', FORMAT_HTML),
            new question_hint(2, 'This is the second hint.', FORMAT_HTML),
        );
        $this->start_attempt_at_question($q, 'interactive', 100);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_marked_out_of_summary(),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_does_not_contain_validation_error_expectation(),
                $this->get_does_not_contain_try_again_button_expectation(),
                $this->get_no_hint_visible_expectation());

        // Submit blank.
        $this->process_submission(array('-submit' => 1, 'answer' => ''));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_validation_error_expectation(),
            new question_pattern_expectation('/' .
                preg_quote(get_string('pleaseenterananswer', 'qtype_varnumericset') . '/')),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Submit something that does not look like a number.
        $this->process_submission(array('-submit' => 1, 'answer' => 'newt'));
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_validation_error_expectation(),
            new question_pattern_expectation('/' .
                preg_quote(get_string('notvalidnumber', 'qtype_varnumericset') . '/')),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        $this->process_submission(array('-submit' => 1, 'answer' => '12,300'));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_validation_error_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Now get it right.
        $this->process_submission(array('-submit' => 1, 'answer' => '12300'));

        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(100);
        $this->check_current_output(
                $this->get_contains_mark_summary(100),
                $this->get_contains_submit_button_expectation(false),
                $this->get_contains_correct_expectation(),
                $this->get_does_not_contain_validation_error_expectation(),
                $this->get_no_hint_visible_expectation());
        $this->assertEquals('12300', $this->quba->get_response_summary($this->slot));
    }

    public function test_validation_and_interactive_with_several_tries_for_3_sig_figs() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $q->hints = array(
            // Fourth param for hint constructor is clearwrong.
            // In this case controls if the suppression of the hint and the penalty when numerical error 'auto fires'.
            new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, null, true),
            new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, null, true),
        );
        $this->start_attempt_at_question($q, 'interactive', 100);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Submit blank.
        $this->process_submission(array('-submit' => 1, 'answer' => ''));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_validation_error_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Submit something that does not look like a number.
        $this->process_submission(array('-submit' => 1, 'answer' => 'newt'));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_validation_error_expectation(),
            new question_pattern_expectation('/' .
                preg_quote(get_string('notvalidnumber', 'qtype_varnumericset') . '/')),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Submit something partially correct (too many sig figs 10% penalty).
        $this->process_submission(array('-submit' => 1, 'answer' => '12350'));

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/' .
                preg_quote(get_string('notcomplete', 'qbehaviour_interactive')) . '/'),
            new question_pattern_expectation('/' .
                preg_quote(get_string('ae_toomanysigfigs', 'qtype_varnumericset')) . '/'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_try_again_button_expectation());

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        // Now get it right.
        $this->process_submission(array('-submit' => 1, 'answer' => '12300'));

        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(90);
        $this->check_current_output(
            $this->get_contains_mark_summary(90),
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_correct_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('12300', $this->quba->get_response_summary($this->slot));
    }

    public function test_deferred_feedback_for_3_sig_figs_blank_answer() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $q->hints = array(
            new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, null, true),
            new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, null, true),
        );
        $this->start_attempt_at_question($q, 'deferredfeedback', 100);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Submit blank.
        $this->process_submission(array('answer' => ''));

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('', $this->quba->get_response_summary($this->slot));

        $this->process_submission(array('-finish' => 1));

        $this->check_current_state(question_state::$gaveup);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_incorrect_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('', $this->quba->get_response_summary($this->slot));
    }

    public function test_deferred_feedback_for_3_sig_figs_answer_with_thousand_separator() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $q->hints = array(
            new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, null, true),
            new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, null, true),
        );
        $this->start_attempt_at_question($q, 'deferredfeedback', 100);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        $this->process_submission(array('answer' => '12,300'));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals(null, $this->quba->get_response_summary($this->slot));

        $this->process_submission(array('-finish' => 1));

        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(100);
        $this->check_current_output(
            $this->get_contains_mark_summary(100),
            $this->get_contains_correct_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('12,300', $this->quba->get_response_summary($this->slot));
    }

    public function test_deferred_feedback_for_3_sig_figs_answer_point_0() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs_point_0');
        $this->start_attempt_at_question($q, 'deferredfeedback', 100);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Save the correct answer that will be accepted.
        $this->process_submission(array('answer' => '12.0'));

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals(null, $this->quba->get_response_summary($this->slot));

        $this->process_submission(array('-finish' => 1));

        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(100);
        $this->check_current_output(
            $this->get_contains_mark_summary(100),
            $this->get_contains_correct_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('12.0', $this->quba->get_response_summary($this->slot));
    }

    public function test_deferred_feedback_for_3_sig_figs_answer_with_correct_answer() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $q->hints = array(
            new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, null, true),
            new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, null, true),
        );
        $this->start_attempt_at_question($q, 'deferredfeedback', 100);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Now give an answer that will be accepted.
        $this->process_submission(array('answer' => '12300'));

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals(null, $this->quba->get_response_summary($this->slot));

        $this->process_submission(array('-finish' => 1));

        $this->check_current_mark(100);
        $this->check_current_output(
            $this->get_contains_mark_summary(100),
            $this->get_contains_correct_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals('12300', $this->quba->get_response_summary($this->slot));
    }

    public function test_deferred_feedback_custom_rounding_feebdack_should_still_show_with_unit() {

        // Create a varnumericset question.
        $q = test_question_maker::make_question('varnumericset', 'custom_rounding_feebdack');
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_no_hint_visible_expectation());

        // Now give a worongly-rounded answer with unit.
        $this->process_submission(array('answer' => '2.2%'));

        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_validation_error_expectation(),
            $this->get_no_hint_visible_expectation());
        $this->assertEquals(null, $this->quba->get_response_summary($this->slot));

        // Finsh the question, the numerical part should be graded, ingoring the %.
        $this->process_submission(array('-finish' => 1));

        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(0.9);
        $this->check_current_output(
            $this->get_contains_mark_summary(0.9),
            $this->get_contains_partcorrect_expectation(),
            $this->get_does_not_contain_validation_error_expectation(),
            $this->get_no_hint_visible_expectation(),
            new question_pattern_expectation('~' . preg_quote($q->answers[2]->feedback, '~') . '~'));
        $this->assertEquals('2.2%', $this->quba->get_response_summary($this->slot));
    }
}
