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
 * varnumeric question definition class.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Represents a varnumeric question.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_question extends question_graded_automatically {


    /** @var qtype_varnumeric_calculator calculator to deal with expressions,
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

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) &&
                ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', 'qtype_varnumeric');
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
        $answer = $this->get_correct_answer();
        if (!$answer) {
            return array();
        }

        return array('answer' => $answer->answer);
    }

    public function get_correct_answer() {
        foreach ($this->get_answers() as $answer) {
            $state = question_state::graded_state_for_fraction($answer->fraction);
            if ($state == question_state::$gradedright) {
                $answer->answer = $this->calculator->evaluate($answer->answer);
                return $answer;
            }
        }
        return null;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        if ($answer->answer == '*') {
            return $answer;
        }
        list($penalty, $feedback) =
                        self::compare_num_as_string_with_answer($response['answer'], $answer);
        $answer->fraction = $answer->fraction - $penalty;
        if (!empty($feedback)) {
            $answer->feedback = $feedback;
        }
        $state = question_state::graded_state_for_fraction($answer->fraction);
        if ($state == question_state::$gradedwrong) {
            return null;
        } else {
            return $answer;
        }
    }

    protected function compare_num_as_string_with_answer($string, qtype_varnumeric_answer $answer) {
        $autofireerrorfeedback = '';
        $evaluated = $this->calculator->evaluate($answer->answer);
        $rounded = (float)self::round_to($evaluated, $answer->sigfigs, true);
        $string = self::normalize_number_format($string, $this->requirescinotation);
        if (self::num_within_allowed_error($string, $rounded, $answer->error) &&
                (($answer->sigfigs == 0)
                        || self::has_number_of_sig_figs($string, $answer->sigfigs)) &&
                (!$this->requirescinotation || self::is_sci_notation($string))) {
            return array(0, ''); //this answer is a perfect match 0% penalty
        } else if ($answer->checknumerical &&
                        self::num_within_allowed_error($string, $rounded, $answer->error)) {
            //numerically correct
            $autofireerrorfeedback = 'numericallycorrect';
        } else if (($answer->sigfigs != 0) &&
                        self::has_too_many_sig_figs($string, $evaluated, $answer->sigfigs)) {
            $autofireerrorfeedback = 'toomanysigfigs';
        } else if (self::wrong_by_a_factor_of_ten($string, $rounded,
                                                        $answer->error, $answer->checkpowerof10)) {
            $autofireerrorfeedback = 'wrongbyfactorof10';
        } else if (self::rounding_incorrect($string, $evaluated, $answer->sigfigs)) {
            $autofireerrorfeedback = 'roundingincorrect';
        } else {
            return array(1, '');//this answer is not a match 100% penalty
        }
        if (!empty($autofireerrorfeedback) && $this->requirescinotation && !self::is_sci_notation($string)){
            $autofireerrorfeedback = $autofireerrorfeedback.'andwrongformat';
            $autofireerrors = 2;
        } else {
            $autofireerrors = 1;
        }
        $penalty = ($answer->syserrorpenalty * $autofireerrors);
        return array($penalty, get_string('ae_'.$autofireerrorfeedback, 'qtype_varnumeric'));
    }

    public static function num_within_allowed_error($string, $answer, $allowederror) {
        $cast = (float)$string;
        if ($allowederror == '') {
            $allowederror = $answer * 1e-6;
        }
        $errorduetofloatprecision = $answer * 1e-15;
        if (abs($answer - $cast) <= $allowederror + $errorduetofloatprecision) {
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
     * @return string number in standardised formt with / without standard php scientific notation.
     */
    public static function normalize_number_format($string, $normalizescinotation){
        if ($normalizescinotation) {
            //strip any extra tags added by html editor that are not sup
            $string = strip_tags($string, '<sup>');
            //Convert html used to write exponential to standard php way.
            $string = preg_replace('!\s*[x*]\s*10\s*<sup>\s*([+-]?[0-9])+\s*</sup>\s*!i', 'e$1',
                                    $string, 1);
        }
        //remove any redundant characters
        $string = str_replace(array(' ', '+'), '', $string);//remove all spaces and any + signs.
        $string = str_replace('E', 'e', $string); // use lower case e
        return $string;
    }

    /**
     *
     * Check to see if $normalizedstring has $sigfigs significant figures.
     * @param string $normalizedstring number as a normalized string
     * @param integer $sigfigs
     * @return boolean
     */
    public static function wrong_by_a_factor_of_ten($normalizedstring, $roundedanswer,
                                                        $error, $maxfactor) {
        if ($maxfactor == 0) {
            return false;
        }
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
        if ($number == 0.0) {
            $poweroften = 0;//avoid NaN result for log10
        } else {
            $poweroften = floor(log10(abs($number)));
        }
        $digitsafterdecimalpoint = $sigfigs - $poweroften - 1;
        $number = $number * pow(10, $digitsafterdecimalpoint);
        if (!$floor) {
            $rounded = round($number);
        } else {
            $rounded = floor($number);
        }
        $rounded =  $rounded / pow(10, $digitsafterdecimalpoint);
        //change to a string so we can do a string compare and check we have the right no
        //of 0s on the end if necessary.
        if ($scinotation){
            $f = '%.'.($sigfigs - 1).'e';
        } else if ($digitsafterdecimalpoint >= 0) {
            $f= '%.'.($digitsafterdecimalpoint).'F';
        } else {
            $f= '%.0F'; // no digits after decimal point
        }
        $rounded = sprintf($f, $rounded);
        return (str_replace('+', '', $rounded)); //remove extra '+' in sci notation
    }

    /**
     *
     * Check to see if $normalizedstring has the correct answer to too many $sigfigs significant
     * figures.
     * @param string $normalizedstring number as a normalized string
     * @param integer $answerunrounded
     * @param integer $sigfigs correct ammount of sigfigs
     * @return boolean
     */
    public static function has_too_many_sig_figs($normalizedstring, $answerunrounded, $sigfigs) {
        $scinotation = self::is_sci_notation($normalizedstring);
        for ($roundto = ($sigfigs +1); $roundto <= ($sigfigs + 10); $roundto++) {
            $rounded = self::round_to($answerunrounded, $roundto, $scinotation);
            if ($rounded === $normalizedstring){
                return true;
            }
        }
        return false;
    }

    public static function rounding_incorrect($normalizedstring, $answerunrounded, $sigfigs) {
        $scinotation = self::is_sci_notation($normalizedstring);
        $incorrectlyrounded = self::round_to($answerunrounded, $sigfigs, $scinotation, true);
        return ($normalizedstring === $incorrectlyrounded);
    }

    /**
     *
     * Check to see if $normalizedstring uses scientific notation.
     * @param string $normalizedstring number as a normalized string
     * @return boolean
     */
    public static function is_sci_notation($normalizedstring) {
        if (strpos($normalizedstring, 'e') !== false){
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

    public function get_num_variants() {
        return $this->calculator->get_num_variants();
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
}

/**
 * Class to represent a question answer, loaded from the question_answers table
 * in the database.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_answer extends question_answer {
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