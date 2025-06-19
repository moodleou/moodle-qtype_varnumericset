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
     * @var bool
     */
    public $usesupeditor;

    /**
     * Whether to require scientific notation.
     *
     * @var bool
     */
    public $requirescinotation;

    /** @var array of question_answer. */
    public $answers = [];

    /**
     * @var string not really used here, the value used is stored in the calculator,
     * but this gets set because of extra_question_fields() so we need to declare it.
     */
    public $randomseed;

    #[\Override]
    public function get_expected_data() {
        return ['answer' => PARAM_RAW_TRIMMED];
    }

    #[\Override]
    public function get_question_summary() {
        return trim($this->html_to_text($this->calculator->evaluate_variables_in_text(
                $this->questiontext), $this->questiontextformat),
                "\n\r \t");
    }

    #[\Override]
    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    /**
     * Check if the response is empty.
     *
     * @param array $response the response to check.
     * @return bool true if the response is empty, false otherwise.
     */
    public function is_no_response(array $response) {
        return (!array_key_exists('answer', $response)) || ($response['answer'] === '');
    }

    #[\Override]
    public function is_complete_response(array $response) {
        return ('' == $this->get_validation_error($response));
    }

    #[\Override]
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

    /**
     * Get the error message for pre- and post-validation.
     *
     * @param string $prefix The prefix part of the response.
     * @param string $postfix The postfix part of the response.
     * @return string Error message if there are any prefixes or postfixes, otherwise empty.
     */
    protected function get_pre_post_validation_error($prefix, $postfix) {
        if (!empty($prefix) || !empty($postfix)) {
            return get_string('notvalidnumberprepostfound', 'qtype_varnumericset');
        } else {
            return '';
        }
    }

    #[\Override]
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

    #[\Override]
    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    /**
     * Get the answers for this question.
     *
     * @return array of qtype_varnumericset_answer objects.
     */
    public function get_answers() {
        return $this->answers;
    }

    /**
     * Get the answer with the given id.
     * @param array $response a response.
     */
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

    #[\Override]
    public function grade_response(array $response) {
        $answer = $this->get_matching_answer($response);
        if (!is_null($answer)) {
            return [$answer->fraction,
                    question_state::graded_state_for_fraction($answer->fraction)];
        } else {
            return [0, question_state::$gradedwrong];
        }
    }

    #[\Override]
    public function get_correct_response() {
        $answer = clone($this->get_first_answer_graded_correct());
        if (!$answer) {
            return [];
        }
        $evaluated = $this->calculator->evaluate($answer->answer);
        $answer->answer =
                    $this->round_to($evaluated, $answer->sigfigs, $this->requirescinotation);
        return ['answer' => $answer->answer];
    }

    /**
     * Get the correct answer for this question.
     *
     * @return qtype_varnumericset_answer|null the first answer that is graded correct, or null if none.
     */
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

    /**
     * Get the first answer that is graded correct.
     *
     * @return qtype_varnumericset_answer|null the first answer that is graded correct, or null if none.
     */
    public function get_first_answer_graded_correct() {
        foreach ($this->get_answers() as $answer) {
            $state = question_state::graded_state_for_fraction($answer->fraction);
            if ($state == question_state::$gradedright) {
                return $answer;
            }
        }
    }

    /**
     * Compare a student's response with one of the answers.
     * @param array $response a response.
     * @param question_answer $answer an answer.
     * @return question_answer|null the answer if it matches, null otherwise.
     */
    public function compare_response_with_answer(array $response, question_answer $answer) {
        if (!isset($response['answer'])) {
            return null;
        }
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
        $htmlresponse = $string;
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

        // Get the correct answer.
        $correcthtmlanswer = $this->round_to($evaluated, $answer->sigfigs, $this->requirescinotation);
        $correcthtmlanswer = $this->qtype->calculator_name()::htmlize_exponent($correcthtmlanswer);

        // Get the float value of the response and answer.
        $floatvalresponse = floatval($string);
        $num->match($correcthtmlanswer);
        $floatvalanswer = floatval($num->get_normalised());

        // Evaluate.
        if (self::num_within_allowed_error($string, $rounded, $allowederror) &&
                (($answer->sigfigs == 0) || self::has_number_of_sig_figs($string, $answer->sigfigs)) &&
                (!$this->requirescinotation || self::is_sci_notation($string)) &&
                ($answer->checkscinotationformat == 0 || !$this->wrong_sci_notation_format($htmlresponse,
                    $correcthtmlanswer, $floatvalresponse, $floatvalanswer))) {
            return [0, '', $warning]; // This answer is a perfect match 0% penalty.

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

        } else if ($answer->checkscinotationformat != 0 &&
                $this->wrong_sci_notation_format($htmlresponse, $correcthtmlanswer,
                    $floatvalresponse, $floatvalanswer)) {
            if ($answer->checkscinotation == 0) {
                return [0, '', $warning];
            }
            $autofireerrorfeedback = 'scinotationformatted';
            $autofireerrors = 1;
        } else {
            return [1, '', '']; // This answer is not a match 100% penalty.
        }

        if (!empty($autofireerrorfeedback)
                            && $this->requirescinotation && !self::is_sci_notation($string)) {
            $autofireerrorfeedback = $autofireerrorfeedback . 'andwrongformat';
            $autofireerrors ++;
        }

        $penalty = $answer->syserrorpenalty * $autofireerrors;

        return [
            $penalty,
            get_string('ae_' . $autofireerrorfeedback, 'qtype_varnumericset'),
            $warning,
        ];
    }

    /**
     * Get the feedback for the prefix and postfix parts of the response.
     *
     * @param string $prefix The prefix part of the response.
     * @param string $postfix The postfix part of the response.
     * @return string Feedback message if there are any prefixes or postfixes, otherwise empty.
     */
    protected function feedback_for_post_prefix_parts($prefix, $postfix) {
        if ($prefix . $postfix !== '') {
            return get_string('preandpostfixesignored', 'qtype_varnumericset');
        } else {
            return '';
        }
    }

    /**
     * Check to see if $string is within the allowed error of $answer.
     * @param string $string number as a normalized string
     * @param float $answer the answer to compare against
     * @param float $allowederror the allowed error
     * @return boolean Whether the number is within the allowed error.
     */
    public static function num_within_allowed_error($string, $answer, $allowederror) {
        $errorduetofloatprecision = abs($answer * 1e-14);
        return abs($answer - (float) $string) <= abs((float) $allowederror) + $errorduetofloatprecision;
    }

    /**
     * Check to see if $normalizedstring is out by a (positive or negative) factor of ten
     * @param string $normalizedstring number as a normalized string
     * @param float $roundedanswer
     * @param float $error accepted error
     * @param int $maxfactor maximum factor of ten
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

    /**
     * Round a number to a given number of significant figures.
     *
     * @param float $number the number to round.
     * @param int $sigfigs the number of significant figures to round to.
     * @param bool $scinotation whether to use scientific notation.
     * @param bool $floor whether to round down (default false).
     * @return string the rounded number as a string.
     */
    public static function round_to($number, $sigfigs, $scinotation, $floor = false) {
        // Do the rounding ourselves so we can get it wrong (round down) if requested.
        if ((int) $sigfigs != 0) {
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
        if (strlen($roundednormalizedstring) > strlen($normalizedstring ?? '')) {
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

    /**
     * Check to see if $normalizedstring is rounded incorrectly.
     *
     * @param string $normalizedstring student response as a normalized string
     * @param float $answerunrounded
     * @param integer $sigfigs correct ammount of sigfigs
     * @return boolean Whether the response is rounded incorrectly.
     */
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
        if (strpos($normalizedstring ?? '', 'e') !== false) {
            return true;
        } else {
            return false;
        }
    }

    #[\Override]
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $qa->get_question()->get_matching_answer(['answer' => $currentanswer]);
            $answerid = reset($args); // The itemid is answer id.
            return $options->feedback && $answerid == $answer->id;

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    #[\Override]
    public function get_variants_selection_seed() {
        return $this->calculator->get_random_seed();
    }

    #[\Override]
    public function get_num_variants() {
        return $this->calculator->get_num_variants_in_form();
    }

    #[\Override]
    public function start_attempt(question_attempt_step $step, $variantno) {
        $this->calculator->evaluate_variant($variantno - 1);
        $this->calculator->save_state_as_qt_data($step);
    }

    #[\Override]
    public function apply_attempt_state(question_attempt_step $step) {
        $this->calculator->load_state_from_qt_data($step);
    }

    /**
     * Get the seed used for random number generation.
     *
     * @return string seed used for random number generation. Used to randomise variant order.
     */
    public function get_random_seed() {
        return $this->calculator->get_random_seed();
    }

    #[\Override]
    public function format_text($text, $format, $qa, $component, $filearea, $itemid,
            $clean = false) {
        $processedtext = $this->calculator->evaluate_variables_in_text($text);
        return parent::format_text($processedtext, $format, $qa, $component,
                                     $filearea, $itemid, $clean);
    }

    #[\Override]
    public function get_hint($hintnumber, question_attempt $qa) {
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');
        $answer = $question->get_matching_answer(['answer' => $currentanswer]);
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

    #[\Override]
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
                        if (isset($this->hints[$i]) && $this->hints[$i]->clearwrong) {
                            $totalpenalty += $syserrorpenalty;
                        } else {
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


    /**
     * Wrong scientific notation format.
     *
     * @param string $responses A responses of the answer.
     * @param string $answer The answer.
     * @param float $floatvalresponse The float value of the response.
     * @param float $floatvalanswer The float value of the answer.
     * @return bool
     */
    public function wrong_sci_notation_format(string $responses, string $answer, float $floatvalresponse,
            float $floatvalanswer): bool {
        // Find the spacing in the response and answer.
        // Group the spacing in group 1, 5, 7, 8, 9 and 10.
        $pattern = "/\S?(\s*)?(\d+)(\.\d+)?((\s*)(\S)*?(\s*)?10(\s*)?<sup>(\s*)?\S?(\s*)?\d+(\.\d+)?\s*?<\/sup>)?/";
        // Match pattern against $responses.
        preg_match($pattern, $responses, $matchresponse);

        // Match pattern against $answer.
        preg_match($pattern, $answer, $matchanswer);

        // The correct format to check for in the array of responses is equal to or greater than 8 elements.
        if (count($matchresponse) >= 8) {
            // Check if the response and answer is the same, and the spacing is correct.
            if (count($matchresponse) === count($matchanswer) && $floatvalresponse === $floatvalanswer) {
                // Check spacing format using the length of the group.
                // If the length of the group is not the same, then the spacing format is wrong.
                $checkspacingformat = (strlen($matchresponse[1]) !== strlen($matchanswer[1]) ||
                    strlen($matchresponse[5]) !== strlen($matchanswer[5]) ||
                    strlen($matchresponse[7]) !== strlen($matchanswer[7]) ||
                    strlen($matchresponse[8]) !== strlen($matchanswer[8]) ||
                    strlen($matchresponse[9]) !== strlen($matchanswer[9]) ||
                    strlen($matchresponse[10]) !== strlen($matchanswer[10]));
                // The $checkspacingformat is true if the length of the group is not the same.
                // Meaning the spacing format is wrong.
                if ($checkspacingformat) {
                    return true;
                }
            }
        }

        return false;
    }

    #[\Override]
    public function classify_response(array $response) {
        if (!$this->is_gradable_response($response)) {
            return [$this->id => question_classified_response::no_response()];
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
        return [$this->id => new question_classified_response($ansid, $responsetodisplay, $fraction)];
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

    /**
     * The number of significant figures for this answer.
     *
     * @var int
     */
    public $sigfigs;

    /**
     * The error allowed for this answer.
     *
     * @var string
     */
    public $error;

    /**
     * The penalty for a system error.
     *
     * @var float
     */
    public $syserrorpenalty;

    /**
     * Whether to check the response for numerical correctness.
     *
     * @var int
     */
    public $checknumerical;

    /**
     * Whether to check the response for scientific notation.
     *
     * @var int
     */
    public $checkscinotation;

    /**
     * Whether to check the response for a factor of 10.
     *
     * @var int
     */
    public $checkpowerof10;

    /**
     * Whether to check the rounding.
     *
     * @var int
     */
    public $checkrounding;

    /**
     * Whether to check the scientific notation format.
     *
     * @var int
     */
    public $checkscinotationformat;

    /**
     * Constructor.
     * @param int $id the answer.
     * @param string $answer the answer.
     * @param float $fraction the fraction for this answer.
     * @param string $feedback the feedback for this answer.
     * @param int $feedbackformat the format of the feedback.
     * @param int $sigfigs the number of significant figures for this answer.
     * @param string $error the error allowed for this answer.
     * @param float $syserrorpenalty the penalty for a system error.
     * @param int $checknumerical whether to check the response for numerical correctness.
     * @param int $checkscinotation whether to check the response for scientific notation.
     * @param int $checkpowerof10 whether to check the response for a factor of 10.
     * @param int $checkrounding whether to check the rounding.
     * @param int $checkscinotationformat whether to check the scientific notation format.
     */
    public function __construct($id, $answer, $fraction, $feedback, $feedbackformat,
                            $sigfigs, $error, $syserrorpenalty, $checknumerical, $checkscinotation,
                            $checkpowerof10, $checkrounding, $checkscinotationformat) {
        parent::__construct($id, $answer, $fraction, $feedback, $feedbackformat);
        $this->sigfigs = $sigfigs;
        $this->error = $error;
        $this->syserrorpenalty = $syserrorpenalty;
        $this->checknumerical = $checknumerical;
        $this->checkscinotation = $checkscinotation;
        $this->checkpowerof10 = $checkpowerof10;
        $this->checkrounding = $checkrounding;
        $this->checkscinotationformat = $checkscinotationformat;
    }
}
