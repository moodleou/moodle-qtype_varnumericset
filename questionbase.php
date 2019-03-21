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
 * Base question definition class for varnumeric questions.
 *
 * @package    qtype_varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/type/varnumericset/number_interpreter.php');


/**
 * Represents a varnumeric question.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_question_base extends question_graded_automatically_with_countback {

    /**
     * @var qtype_varnumeric_calculator_base calculator to deal with expressions,
     *      variable and variants.
     */
    public $calculator;

    /**
     * Whether to allow use of superscript and expect html input instead of plain text in response.
     *
     * @var boolean
     */
    public $usesupeditor;

    /**
     * Whether to require scientific notation.
     *
     * @var boolean
     */
    public $requirescinotation;

    /** @var array of question_answer. */
    public $answers = array();

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function get_question_summary() {
        return trim($this->html_to_text($this->calculator->evaluate_variables_in_text(
                $this->questiontext), $this->questiontextformat),
                "\n\r \t");
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function is_no_response(array $response) {
        return (!array_key_exists('answer', $response)) || ($response['answer'] === '');
    }

    public function is_complete_response(array $response) {
        return ('' == $this->get_validation_error($response));
    }

    public function get_validation_error(array $response) {
        if ($this->is_no_response($response)) {
            return get_string('pleaseenterananswer', 'qtype_varnumericset');
        }
        if (false !== strpos($response['answer'], QTYPE_VARNUMERICSET_THOUSAND_SEP)) {
            $a = new stdClass();
            $a->thousandssep = QTYPE_VARNUMERICSET_THOUSAND_SEP;
            $a->decimalsep = QTYPE_VARNUMERICSET_DECIMAL_SEP;
            return get_string('illegalthousandseparator', 'qtype_varnumericset', $a);
        }

        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation($this->usesupeditor);

        if (!$num->match($response['answer'])) {
            return get_string('notvalidnumber', 'qtype_varnumericset');
        }

        $preposterror = $this->get_pre_post_validation_error($num->get_prefix(), $num->get_postfix());
        if ($preposterror !== '') {
            return $preposterror;
        }

        return '';
    }

    protected function get_pre_post_validation_error($prefix, $postfix) {
        if (!empty($prefix) || !empty($postfix)) {
            return get_string('notvalidnumberprepostfound', 'qtype_varnumericset');
        } else {
            return '';
        }
    }

    public function is_gradable_response(array $response) {
        if ($this->is_no_response($response)) {
            return false;
        }
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation($this->usesupeditor);

        if (!$num->match($response['answer'])) {
            return false;
        }
        return true;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        return $this->answers;
    }


    public function get_matching_answer($response) {
        foreach ($this->get_answers() as $aid => $answer) {
            $thisanswer = $this->compare_response_with_answer($response, $answer);
            if (!is_null($thisanswer)) {
                $thisanswer->id = $aid;
                return $thisanswer;
            }
        }
        return null;
    }

    public function grade_response(array $response) {
        $answer = $this->get_matching_answer($response);
        if (!is_null($answer)) {
            return array($answer->fraction,
                    question_state::graded_state_for_fraction($answer->fraction));
        } else {
            return array(0, question_state::$gradedwrong);
        }
    }

    public function get_correct_response() {
        $answer = clone($this->get_first_answer_graded_correct());
        if (!$answer) {
            return array();
        }
        $evaluated = $this->calculator->evaluate($answer->answer);
        $answer->answer =
                    $this->round_to($evaluated, $answer->sigfigs, $this->requirescinotation);
        return array('answer' => $answer->answer);
    }

    public function get_correct_answer() {
        $answer = clone($this->get_first_answer_graded_correct());
        if (!is_null($answer)) {
            $evaluated = $this->calculator->evaluate($answer->answer);
            $answer->answer =
                    $this->round_to($evaluated, $answer->sigfigs, $this->requirescinotation);
            $calculatorname = $this->qtype->calculator_name();
            $answer->answer = $calculatorname::htmlize_exponent($answer->answer);
            if ($answer->error != '') {
                $answer->error = $this->calculator->evaluate($answer->error);
                $answer->answer =
                            get_string('correctansweriserror', 'qtype_varnumericset', $answer);
            }
            if ($answer->sigfigs != 0) {
                $answer->answer =
                            get_string('correctanswerissigfigs', 'qtype_varnumericset', $answer);
            }
            return $answer;
        } else {
            return null;
        }
    }

    public function get_first_answer_graded_correct() {
        foreach ($this->get_answers() as $answer) {
            $state = question_state::graded_state_for_fraction($answer->fraction);
            if ($state == question_state::$gradedright) {
                return $answer;
            }
        }
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        if ($answer->answer == '*') {
            return $answer;
        }
        list($penalty, $feedback, $warning) = self::compare_num_as_string_with_answer(
                $response['answer'], $answer);
        $answertoreturn = clone($answer);
        $answertoreturn->fraction = $answer->fraction - $penalty;
        if (!empty($feedback)) {
            $answertoreturn->feedback = $feedback;
        }
        $answertoreturn->feedback .= $warning;
        $state = question_state::graded_state_for_fraction($answertoreturn->fraction);
        if ($penalty == 1 && $feedback == '') {
            return null;
        } else {
            return $answertoreturn;
        }
    }

    /**
     * Compare a student's response with one of the answers.
     * @param string $string a response.
     * @param qtype_varnumericset_answer $answer an answer.
     * @return array with three elements: penalty, automatic feedback and warning.
     * The automatic feedback is something like "you have the wrong number of significant figures."
     * The warning is something like "Only the numerical part of your response was graded."
     */
    public function compare_num_as_string_with_answer($string,
            qtype_varnumericset_answer $answer) {
        $autofireerrorfeedback = '';

        // Evaluate the answer.
        $evaluated = $this->calculator->evaluate($answer->answer);
        $rounded = (float)self::round_to($evaluated, $answer->sigfigs, false);
        if ($answer->error == '') {
            $allowederror = 0;
        } else {
            $allowederror = $this->calculator->evaluate($answer->error);
        }

        // Parse the response.
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation($this->usesupeditor);
        $num->match($string);
        $string = $num->get_normalised();
        $warning = $this->feedback_for_post_prefix_parts($num->get_prefix(), $num->get_postfix());

        // Evaluate.
        if (self::num_within_allowed_error($string, $rounded, $allowederror) &&
                (($answer->sigfigs == 0)
                        || self::has_number_of_sig_figs($string, $answer->sigfigs)) &&
                (!$this->requirescinotation || self::is_sci_notation($string))) {
            return array(0, '', $warning); // This answer is a perfect match 0% penalty.

        } else if ($answer->checknumerical &&
                        self::num_within_allowed_error($string, $rounded, $allowederror)) {
            // Numerically correct.
            $autofireerrorfeedback = 'numericallycorrect';
            if (!self::has_number_of_sig_figs($string, $answer->sigfigs)) {
                $autofireerrors = 1;
            } else {
                $autofireerrors = 0;
            }

        } else if (($answer->sigfigs != 0) &&
                        self::has_too_many_sig_figs($string, $evaluated, $answer->sigfigs)) {
            $autofireerrorfeedback = 'toomanysigfigs';
            $autofireerrors = 1;

        } else if (($answer->checkpowerof10 != 0) && self::wrong_by_a_factor_of_ten($string,
                                        $rounded, $allowederror, $answer->checkpowerof10)) {
            $autofireerrorfeedback = 'wrongbyfactorof10';
            $autofireerrors = 1;

        } else if (($answer->checkrounding != 0) &&
                            self::rounding_incorrect($string, $evaluated, $answer->sigfigs)) {
            $autofireerrorfeedback = 'roundingincorrect';
            $autofireerrors = 1;

        } else {
            return array(1, '', ''); // This answer is not a match 100% penalty.
        }

        if (!empty($autofireerrorfeedback)
                            && $this->requirescinotation && !self::is_sci_notation($string)) {
            $autofireerrorfeedback = $autofireerrorfeedback . 'andwrongformat';
            $autofireerrors ++;
        }

        $penalty = $answer->syserrorpenalty * $autofireerrors;
        return array($penalty,
                get_string('ae_' . $autofireerrorfeedback, 'qtype_varnumericset'),
                $warning);
    }

    protected function feedback_for_post_prefix_parts($prefix, $postfix) {
        if ($prefix . $postfix !== '') {
            return get_string('preandpostfixesignored', 'qtype_varnumericset');
        } else {
            return '';
        }
    }

    public static function num_within_allowed_error($string, $answer, $allowederror) {
        $cast = (float)$string;
        $errorduetofloatprecision = abs($answer * 1e-14);
        return abs($answer - $cast) <= abs($allowederror) + $errorduetofloatprecision;
    }

    /**
     * Check to see if $normalizedstring is out by a (positive or negative) factor of ten
     * @param string $normalizedstring number as a normalized string
     * @param $roundedanswer
     * @param $error accepted error
     * @param $maxfactor maximum factor of ten
     * @return boolean
     */
    public static function wrong_by_a_factor_of_ten($normalizedstring, $roundedanswer,
                                                        $error, $maxfactor) {
        for ($wrongby = 1; $wrongby <= $maxfactor; $wrongby++) {
            $multiplier = pow(10, $wrongby);
            if (self::num_within_allowed_error($normalizedstring, $roundedanswer * $multiplier,
                    (float) $error * $multiplier)) {
                return true;
            }
            if (self::num_within_allowed_error($normalizedstring, $roundedanswer / $multiplier,
                    (float) $error / $multiplier)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check to see if $normalizedstring has $sigfigs significant figures.
     * @param string $normalizedstring number as a normalized string
     * @param integer $sigfigs
     * @return boolean
     */
    public static function has_number_of_sig_figs($normalizedstring, $sigfigs) {
        $scinotation = self::is_sci_notation($normalizedstring);
        if (self::round_to($normalizedstring, $sigfigs, $scinotation) === $normalizedstring) {
            return true;
        } else {
            return false;
        }
    }

    public static function round_to($number, $sigfigs, $scinotation, $floor = false) {
        // Do the rounding ourselves so we can get it wrong (round down) if requested.
        if ($sigfigs != 0) {
            if ($number == 0.0) {
                $poweroften = 0; // Avoid NaN result for log10.
            } else {
                $poweroften = floor(log10(abs($number)));
            }
            // What power of ten do we multiply by before chopping off bit behind
            // decimal point?
            $digitsafterdecimalpoint = $sigfigs - $poweroften - 1;
            $number = $number * pow(10, $digitsafterdecimalpoint);
            if (!$floor) {
                $rounded = round($number);
            } else {
                $rounded = floor($number);
            }
            $number = $rounded / pow(10, $digitsafterdecimalpoint);
            // Change to a string so we can do a string compare and check we
            // have the right no of 0s on the end if necessary.
            if ($scinotation) {
                $f = '%.'.($sigfigs - 1).'e';
            } else if ($digitsafterdecimalpoint >= 0) {
                $f = '%.'.($digitsafterdecimalpoint).'F';
            } else {
                $f = '%.0F'; // No digits after decimal point.
            }
            $number = sprintf($f, $number);
        } else {
            if ($scinotation) {
                // Warning, this rounds to 7 sig figs, whether we want it to or not.
                $f = '%e';
                $number = sprintf($f, $number);
            }
        }
        return (str_replace('+', '', $number)); // Remove extra '+' in sci notation.
    }

    /**
     * Check to see if $normalizedstring has the correct answer to too many
     * $sigfigs significant figures.
     * @param string $normalizedstring student response as a normalized string
     * @param float $answerunrounded
     * @param integer $sigfigs correct ammount of sigfigs
     * @return boolean
     */
    public static function has_too_many_sig_figs($normalizedstring, $answerunrounded, $sigfigs) {
        $scinotation = self::is_sci_notation($normalizedstring);
        for ($roundto = ($sigfigs + 1); $roundto <= 6; $roundto++) {
            $rounded = self::round_to($answerunrounded, $roundto, $scinotation);
            if ($rounded === $normalizedstring) {
                return true;
            }
        }
        // Anything in the student response more than 7 figs is ignored.
        $rounded = self::round_to($answerunrounded, 7, $scinotation);
        $roundedfloored = self::round_to($answerunrounded, 7, $scinotation, true);
        $roundednormalizedstring = self::round_to($normalizedstring, 7, $scinotation);

        // We need this test to stop Moodle adding zeroes onto the end of the
        // normalized string and giving a false positive.
        if (strlen($roundednormalizedstring) > strlen($normalizedstring)) {
            return false;
        }
        if ($roundednormalizedstring === $rounded || $roundednormalizedstring === $roundedfloored) {
            return true;
        }
        $roundednormalizedstringfloored = self::round_to($normalizedstring, 7, $scinotation, true);
        if ($roundednormalizedstringfloored === $rounded ||
                $roundednormalizedstringfloored === $roundedfloored) {
            return true;
        }
        return false;
    }

    public static function rounding_incorrect($normalizedstring, $answerunrounded, $sigfigs) {
        $scinotation = self::is_sci_notation($normalizedstring);
        $incorrectlyrounded = self::round_to($answerunrounded, $sigfigs, $scinotation, true);
        $correctlyrounded = self::round_to($answerunrounded, $sigfigs, $scinotation, false);
        return (($correctlyrounded !== $incorrectlyrounded)
                                                && $normalizedstring === $incorrectlyrounded);
    }

    /**
     * Check to see if $normalizedstring uses scientific notation.
     * @param string $normalizedstring number as a normalized string
     * @return boolean
     */
    public static function is_sci_notation($normalizedstring) {
        if (strpos($normalizedstring, 'e') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $qa->get_question()->get_matching_answer(array('answer' => $currentanswer));
            $answerid = reset($args); // The itemid is answer id.
            return $options->feedback && $answerid == $answer->id;

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    public function get_variants_selection_seed() {
        return $this->calculator->get_random_seed();
    }

    public function get_num_variants() {
        return $this->calculator->get_num_variants_in_form();
    }

    public function start_attempt(question_attempt_step $step, $variantno) {
        $this->calculator->evaluate_variant($variantno - 1);
        $this->calculator->save_state_as_qt_data($step);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        $this->calculator->load_state_from_qt_data($step);
    }

    /**
     * @return string seed used for random number generation. Used to randomise variant order.
     */
    public function get_random_seed() {
        return $this->calculator->get_random_seed();
    }

    public function format_text($text, $format, $qa, $component, $filearea, $itemid,
            $clean = false) {
        $processedtext = $this->calculator->evaluate_variables_in_text($text);
        return parent::format_text($processedtext, $format, $qa, $component,
                                     $filearea, $itemid, $clean);
    }

    public function get_hint($hintnumber, question_attempt $qa) {
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');
        $answer = $question->get_matching_answer(array('answer' => $currentanswer));
        if ($answer) {
            $fraction = $answer->fraction;
        } else {
            $fraction = 0;
        }
        $state = question_state::graded_state_for_fraction($fraction);
        $hint = parent::get_hint($hintnumber, $qa);
        if ($state != question_state::$gradedpartial || !$hint->clearwrong) {
            return $hint;
        } else {
            return null;
        }
    }

    public function compute_final_grade($responses, $totaltries) {
        $answers = $this->get_answers();

        $totalpenalty = 0;

        $finalresponse = array_pop($responses);

        // Calculate how previous attempts affect final grade.
        if (count($responses)) {
            foreach ($responses as $i => $response) {
                $answerwithsyserrorpenalty = $this->get_matching_answer($response);
                if ($answerwithsyserrorpenalty !== null) {
                    $answerbeforesyserrorpenalty = $answers[$answerwithsyserrorpenalty->id];
                    // Auto fire error penalty applied to answer before it is returned from get_matching_answer() method.
                    $syserrorpenalty = $answerbeforesyserrorpenalty->fraction - $answerwithsyserrorpenalty->fraction;
                    if ($syserrorpenalty !== 0) {
                        $totalpenalty += $syserrorpenalty;
                        if (!(isset($this->hints[$i]) && $this->hints[$i]->clearwrong)) {
                            $totalpenalty += $this->penalty;
                        }
                    } else if ($answerwithsyserrorpenalty->fraction != '1') {
                        $totalpenalty += $this->penalty;
                    }
                } else {
                    $totalpenalty += $this->penalty;
                }
            }
        }
        $finalanswer = $this->get_matching_answer($finalresponse);
        if ($finalanswer === null) {
            return 0;
        }
        $finalanswer->fraction -= $totalpenalty;
        return max(0, $finalanswer->fraction);
    }

    public function classify_response(array $response) {
        if (!$this->is_gradable_response($response)) {
            return array($this->id => question_classified_response::no_response());
        }

        $ans = $this->get_matching_answer($response);
        if (!$ans) {
            $fraction = $ansid = 0;
        } else {
            $ansid = $ans->id;
            $fraction = $ans->fraction;
        }

        $num = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation(true);
        $num->match($response['answer']);

        $calculatorname = $this->qtype->calculator_name();
        $responsehtmlized = $calculatorname::htmlize_exponent($num->get_normalised());

        $responsetodisplay = $num->get_prefix().$responsehtmlized.$num->get_postfix();
        return array($this->id => new question_classified_response($ansid, $responsetodisplay, $fraction));
    }
}


/**
 * Class to represent a question answer, loaded from the question_answers table
 * in the database.
 *
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumericset_answer extends question_answer {
    public $sigfigs;
    public $error;
    public $syserrorpenalty;
    public $checknumerical;
    public $checkscinotation;
    public $checkpowerof10;
    public $checkrounding;

    /**
     * Constructor.
     * @param int $id the answer.
     * @param string $answer the answer.
     * @param int $answerformat the format of the answer.
     * @param number $fraction the fraction this answer is worth.
     * @param string $feedback the feedback for this answer.
     * @param int $feedbackformat the format of the feedback.
     */
    public function __construct($id, $answer, $fraction, $feedback, $feedbackformat,
                            $sigfigs, $error, $syserrorpenalty, $checknumerical, $checkscinotation,
                            $checkpowerof10, $checkrounding) {
        parent::__construct($id, $answer, $fraction, $feedback, $feedbackformat);
        $this->sigfigs = $sigfigs;
        $this->error = $error;
        $this->syserrorpenalty = $syserrorpenalty;
        $this->checknumerical = $checknumerical;
        $this->checkscinotation = $checkscinotation;
        $this->checkpowerof10 = $checkpowerof10;
        $this->checkrounding = $checkrounding;
    }
}
