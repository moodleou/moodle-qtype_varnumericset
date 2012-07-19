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
 * varnumeric base question definition class.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/type/questionbase.php');

defined('MOODLE_INTERNAL') || die();


//these only affect how student input is processed, not how values are displayed.
define('QTYPE_VARNUMERICSET_THOUSAND_SEP', ',');
define('QTYPE_VARNUMERICSET_DECIMAL_SEP', '.');

define('QTYPE_VARNUMERICSET_VALID_NORMALISED_STRING',
                        "(?<sign>-?)(?<coeff1>[0-9]+)(\.(?<coeff2>[0-9]*))?".
                        "(e(?<exp>-?[0-9]*))?");

/**
 * Represents a varnumeric question.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_question_base extends question_graded_automatically_with_countback {


    /** @var qtype_varnumeric_calculator_base calculator to deal with expressions,
     *                                    variable and variants.
     */
    public $calculator;


    /**
     *
     * Whether to require scientific notation and whether to allow use of superscript.
     *
     * @var boolean
     */
    public $requirescinotation;

    /** @var array of question_answer. */
    public $answers = array();


    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
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

    public static function is_valid_normalized_number_string($number) {
        return (1 === preg_match('!'.QTYPE_VARNUMERICSET_VALID_NORMALISED_STRING.'$!A', $number));
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
        list($string, $postorprefix) = self::normalize_number_format($response['answer'], $this->requirescinotation);

        if (!empty($string) && (!empty($postorprefix[0]) || !empty($postorprefix[1]))) {
            return get_string('notvalidnumberprepostfound', 'qtype_varnumericset');
        }

        if (!self::is_valid_normalized_number_string($string)) {
            return get_string('notvalidnumber', 'qtype_varnumericset');
        }
        return '';
    }

    public function is_gradable_response(array $response) {
        if ($this->is_no_response($response)) {
            return false;
        }
        list($string, ) = self::normalize_number_format($response['answer'], $this->requirescinotation);

        if (!self::is_valid_normalized_number_string($string)) {
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
        list($penalty, $feedback) =
                        self::compare_num_as_string_with_answer($response['answer'], $answer);
        $answertoreturn = clone($answer);
        $answertoreturn->fraction = $answer->fraction - $penalty;
        if (!empty($feedback)) {
            $answertoreturn->feedback = $feedback;
        }
        $state = question_state::graded_state_for_fraction($answertoreturn->fraction);
        if ($penalty == 1 && $feedback == '') {
            return null;
        } else {
            return $answertoreturn;
        }
    }

    protected function compare_num_as_string_with_answer($string,
                                                            qtype_varnumericset_answer $answer) {
        $autofireerrorfeedback = '';
        $evaluated = $this->calculator->evaluate($answer->answer);
        $rounded = (float)self::round_to($evaluated, $answer->sigfigs, true);
        list($string, $postorprefix) = self::normalize_number_format($string, $this->requirescinotation);
        if ($postorprefix[0].$postorprefix[1] != '') {
            $feedback = get_string('preandpostfixesignored', 'qtype_varnumericset');
        } else {
            $feedback = '';
        }
        if ($answer->error == '') {
            $allowederror = 0;
        } else {
            $allowederror = $this->calculator->evaluate($answer->error);
        }
        if (self::num_within_allowed_error($string, $rounded, $allowederror) &&
                (($answer->sigfigs == 0)
                        || self::has_number_of_sig_figs($string, $answer->sigfigs)) &&
                (!$this->requirescinotation || self::is_sci_notation($string))) {
            return array(0, $feedback); //this answer is a perfect match 0% penalty
        } else if ($answer->checknumerical &&
                        self::num_within_allowed_error($string, $rounded, $allowederror)) {
            //numerically correct
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
            return array(1, '');//this answer is not a match 100% penalty
        }
        if (!empty($autofireerrorfeedback)
                            && $this->requirescinotation && !self::is_sci_notation($string)) {
            $autofireerrorfeedback = $autofireerrorfeedback.'andwrongformat';
            $autofireerrors ++;
        }
        $penalty = ($answer->syserrorpenalty * $autofireerrors);
        return array($penalty, get_string('ae_'.$autofireerrorfeedback, 'qtype_varnumericset').$feedback);
    }

    public static function num_within_allowed_error($string, $answer, $allowederror) {
        $cast = (float)$string;
        if ($allowederror == 0) {
            $allowederror = $answer * 1e-6;
        }
        $errorduetofloatprecision = abs($answer * 1e-15);
        if (abs($answer - $cast) <= abs($allowederror) + $errorduetofloatprecision) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     * Convert html used to write exponential to standard php way. Used to process numbers entered
     * as strings by students.
     * @param string $string number as string
     * @return array with contents string number in standardised formt with / without standard php scientific notation
     *               and boolean whether a non-numeric post or prefix was stripped from the string
     */
    public static function normalize_number_format($string, $normalizescinotation) {
        if ($normalizescinotation) {
            //strip any extra tags added by html editor that are not sup
            $string = strip_tags($string, '<sup>');
            //Convert html used to write exponential to standard php way.
            $string = preg_replace('!\s*[x*]\s*10\s*<sup>\s*([+-]?[0-9]+)\s*</sup>\s*!i', 'e$1',
                                    $string, 1);
        }
        if (QTYPE_VARNUMERICSET_DECIMAL_SEP != '.') {
            $string = str_replace(QTYPE_VARNUMERICSET_DECIMAL_SEP, '.', $string);
        }
        //remove any redundant characters
        $string = str_replace(array(' ', '+', QTYPE_VARNUMERICSET_THOUSAND_SEP), '', $string);
        $string = str_replace('E', 'e', $string); // use lower case e
        $matches = array();
        $postorprefix = array('', '');
        $pattern = '!'.QTYPE_VARNUMERICSET_VALID_NORMALISED_STRING.'!';
        if (1 === preg_match($pattern, $string, $matches, PREG_OFFSET_CAPTURE)) {
            if (strlen($matches[0][0]) != strlen($string)) {
                $prefix = substr($string, 0, $matches[0][1]);
                $postfix = substr($string, $matches[0][1] + strlen($matches[0][0]),
                                    strlen($string)- $matches[0][1] - strlen($matches[0][0]));
                $postorprefix = array($prefix, $postfix);
                $string = $matches[0][0];
            }
        }
        if (self::is_sci_notation($string)) {
            //make sure that coefficient is between 1 and 10.
            preg_match($pattern, $string, $no);
            $no['coeff1'] =  ltrim($no['coeff1'], '0');
            if (strlen($no['coeff1'])>1) {
                $no['exp'] += strlen($no['coeff1'])-1;
                $no['coeff2'] = substr($no['coeff1'], 1).$no['coeff2'];
                $no['coeff1'] = substr($no['coeff1'], 0, 1);
            }
            while ($no['coeff1'] === '' || $no['coeff1'] === "0") {
                $no['exp']--;
                $no['coeff1'] =  substr($no['coeff2'], 0, 1);
                $no['coeff2'] =  substr($no['coeff2'], 1);
            }
            if ($no['coeff2'] !== '') {
                $no['coeff2'] = '.'.$no['coeff2'];
            }
            $string = $no['sign'].$no['coeff1'].$no['coeff2'].'e'.$no['exp'];
        } else {
            if ($string === '-0') {//unlikely but possible
                $string = '0';
            }
            if ($string !== '0') {
                // put zero back on string to lead before any decimal point
                $string = preg_replace('!^(\-)?(0)+!', '$1', $string);
                // put zero back on string to lead before any decimal point
                $string = preg_replace('!^(\-)?\.!', '${1}0.', $string);
            }
        }

        return array($string, $postorprefix);
    }

    /**
     *
     * Check to see if $normalizedstring is out by a (positive or negative) factor of ten
     * @param string $normalizedstring number as a normalized string
     * @param $roundedanswer
     * @param $error accepted error
     * @param $maxfactor maximum factor of ten
     * @return boolean
     */
    public static function wrong_by_a_factor_of_ten($normalizedstring, $roundedanswer,
                                                        $error, $maxfactor) {
        if ($error == '') {
            $error = $roundedanswer * 1e-6;
        }
        for ($wrongby = 1; $wrongby <= $maxfactor; $wrongby++) {
            $multiplier = pow(10, $wrongby);
            if (self::num_within_allowed_error($normalizedstring, $roundedanswer*$multiplier,
                                                    $error*$multiplier)) {
                return true;
            }
            if (self::num_within_allowed_error($normalizedstring, $roundedanswer/$multiplier,
                                                    $error/$multiplier)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
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
        //do the rounding ourselves so we can get it wrong (round down) if requested
        if ($sigfigs != 0) {
            if ($number == 0.0) {
                $poweroften = 0;//avoid NaN result for log10
            } else {
                $poweroften = floor(log10(abs($number)));
            }
            //what power of ten do we multiply by before chopping off bit behind decimal point?
            $digitsafterdecimalpoint = $sigfigs - $poweroften -1;
            $number = $number * pow(10, $digitsafterdecimalpoint);
            if (!$floor) {
                $rounded = round($number);
            } else {
                $rounded = floor($number);
            }
            $number =  $rounded / pow(10, $digitsafterdecimalpoint);
            //change to a string so we can do a string compare and check we have the right no
            //of 0s on the end if necessary.
            if ($scinotation) {
                $f = '%.'.($sigfigs - 1).'e';
            } else if ($digitsafterdecimalpoint >= 0) {
                $f= '%.'.($digitsafterdecimalpoint).'F';
            } else {
                $f= '%.0F'; // no digits after decimal point
            }
            $number = sprintf($f, $number);
        } else {
            if ($scinotation) {
                $f = '%e';
                $number = sprintf($f, $number);
            }
        }
        return (str_replace('+', '', $number)); //remove extra '+' in sci notation
    }

    /**
     *
     * Check to see if $normalizedstring has the correct answer to too many $sigfigs significant
     * figures.
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
        //anything in the student response more than 7 figs is ignored
        $rounded = self::round_to($answerunrounded, 7, $scinotation);
        $roundedfloored = self::round_to($answerunrounded, 7, $scinotation, true);
        $roundednormalizedstring = self::round_to($normalizedstring, 7, $scinotation);

        //we need this test to stop Moodle adding zeroes onto the end of the normalized string and giving a false positive
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
     *
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
            $answerid = reset($args); // itemid is answer id.
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
        $this->calculator->evaluate_variant($variantno-1);
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
        $totalpenalty = 0;
        $trieswithnopenalty = 0;

        $finalresponse = array_pop($responses);

        if (count($responses)) {
            foreach ($responses as $i => $response) {
                list($fraction, $state) = $this->grade_response($response);
                if ($state == question_state::$gradedpartial) {
                    $syserrorpenalty = 1 - $fraction;
                    $totalpenalty += $syserrorpenalty;
                    if (!(isset($this->hints[$i]) && $this->hints[$i]->clearwrong)) {
                        $totalpenalty += $this->penalty;
                    }
                } else {
                    $totalpenalty += $this->penalty;
                }
            }
        }
        list($finalfraction, $finalstate) = $this->grade_response($finalresponse);
        if ($finalstate == question_state::$gradedwrong) {
            return 0;
        }
        $finalfraction -= $totalpenalty;
        return max(0, $finalfraction);
    }

    public function classify_response(array $response) {
        if (empty($response['answer'])) {
            return array($this->id => question_classified_response::no_response());
        }

        $ans = $this->get_matching_answer($response);
        if (!$ans) {
            return array($this->id => question_classified_response::no_response());
        }
        list($responsenormalized, $postorprefix) = self::normalize_number_format($response['answer'], true);
        $calculatorname = $this->qtype->calculator_name();
        $responsehtmlized = $calculatorname::htmlize_exponent($responsenormalized);
        return array($this->id => new question_classified_response(
                $ans->id, $postorprefix[0].$responsehtmlized.$postorprefix[1], $ans->fraction));
    }
}

/**
 * Class to represent a question answer, loaded from the question_answers table
 * in the database.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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