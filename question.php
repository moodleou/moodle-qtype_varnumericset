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
class qtype_varnumeric_question extends question_graded_by_strategy
        implements question_response_answer_comparer {


    /** @var qtype_varnumeric_calculator calculator to deal with expressions,
     *                                    variable and variants.
     */
    public $calculator;

    /** @var array of question_answer. */
    public $answers = array();

    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

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

    public function get_correct_answer() {
        $answer = parent::get_correct_answer();
        $answer->answer = $this->calculator->evaluate($answer->answer);
        return $answer;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        if ($answer->answer == '*') {
            return true;
        }
        return self::compare_num_as_string_with_expression($response['answer'], $answer->answer);
    }

    protected function compare_num_as_string_with_expression($string, $expression) {
        $evaluated = $this->calculator->evaluate($expression);
        $cast = (float)$string;
        if (($evaluated - $cast) < ($evaluated * 1e-6)) {
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