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
 * Defines the editing form for the varnumericset question type.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumericset/calculator.php');

/**
 * varnumeric question editing form definition base.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_varnumeric_edit_form_base extends question_edit_form {

    protected function definition_inner($mform) {
        global $DB;

        $mform->addHelpButton('questiontext', 'questiontext', 'qtype_varnumericset');

        $mform->addElement('text', 'randomseed', get_string('randomseed', 'qtype_varnumericset'));
        $mform->setType('randomseed', PARAM_RAW);

        $answersoption = '';

        $typemenu = array(0 => get_string('vartypecalculated', 'qtype_varnumericset'),
                            1 => get_string('vartypepredefined', 'qtype_varnumericset'));

        $repeated = array();
        $repeatedoptions = array();
        $repeated[] = $mform->createElement('header', 'varhdr',
                                get_string('varheader', 'qtype_varnumericset'));
        $repeated[] = $mform->createElement('select', 'vartype', '', $typemenu);
        $repeated[] = $mform->createElement('text', 'varname',
                                get_string('varname', 'qtype_varnumericset'), array('size' => 40));

        $mform->setType('varname', PARAM_RAW_TRIMMED);
        $repeatedoptions['varname']['helpbutton'] = array('varname', 'qtype_varnumericset');

        list($repeated, $repeatedoptions) =
                                $this->add_value_form_fields($mform, $repeated, $repeatedoptions);

        if (isset($this->question->id)) {
            $prefix = $this->db_table_prefix();
            $sql = 'SELECT MAX(varno)+1 '.
                    "FROM {{$prefix}_vars} vars ".
                    'WHERE questionid = ?';
            $noofvarsindb = $DB->get_field_sql($sql, array($this->question->id));
        } else {
            $noofvarsindb = 0;
        }

        if ($this->question->formoptions->repeatelements) {
            $noofvarsatstart = max($noofvarsindb + 2, 5);
        } else {
            $noofvarsatstart = $noofvarsindb;
        }

        $this->repeat_elements($repeated, $noofvarsatstart, $repeatedoptions,
                'novars', 'addvars', 2, get_string('addmorevars', 'qtype_varnumericset'));

        $mform->addElement('submit', 'recalculatenow',
                                        get_string('recalculatenow', 'qtype_varnumericset', 2));
        $mform->closeHeaderBefore('recalculatenow');

        //we are using a hook in questiontype to resdisplay the form and it expects a parameter
        //wizard, which we won't actually use but we need to pass it to avoid an error message.
        $mform->addElement('hidden', 'wizard', '');

        $mform->addElement('header', 'forallanswers',
                                get_string('forallanswers', 'qtype_varnumericset'));
        $mform->addElement('selectyesno', 'requirescinotation',
                                get_string('requirescinotation', 'qtype_varnumericset'));

        $mform->addElement('static', 'answersinstruct',
                get_string('correctanswers', 'qtype_varnumericset'),
                get_string('filloutoneanswer', 'qtype_varnumericset'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_varnumericset', '{no}'),
                question_bank::fraction_options());

        $this->add_interactive_settings($mform, $repeated, $repeatedoptions);
    }

    abstract protected function add_value_form_fields($mform, $repeated, $repeatedoptions);

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $parentansweroptions = parent::get_per_answer_fields($mform, $label, $gradeoptions,
                                                        $repeatedoptions, $answersoption);
        $sigfigsoptions = array(0 => get_string('unspecified', 'qtype_varnumericset'),
                                1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6);
        $answeroptions = array();

        $answeroptions[] = $parentansweroptions[0]; // header
        $answeroptions[] = $parentansweroptions[1]; // answer text box

        $answeroptions[] = $mform->createElement('text', 'error',
                                get_string('error', 'qtype_varnumericset'), array('size' => 80));
        $answeroptions[] = $mform->createElement('select', 'sigfigs',
                                get_string('sigfigs', 'qtype_varnumericset'), $sigfigsoptions);

        $answeroptions[] = $parentansweroptions[2]; // grade
        $answeroptions[] = $parentansweroptions[3]; // feedback

        $answeroptions[] = $mform->createElement('header', 'autofirehdr',
                            get_string('autofirehdr', 'qtype_varnumericset', '{no}'));
        $answeroptions[] = $mform->createElement('selectyesno', 'checknumerical',
                            get_string('checknumerical', 'qtype_varnumericset'));
        $checkpowerof10options = array(0 => get_string('no'),
                                1 => '+/- 1', 2 => '+/- 2', 3 => '+/- 3',
                                4 => '+/- 4', 5 => '+/- 5', 6 => '+/- 6');
        $answeroptions[] = $mform->createElement('selectyesno', 'checkscinotation',
                            get_string('checkscinotation', 'qtype_varnumericset'));
        $answeroptions[] = $mform->createElement('select', 'checkpowerof10',
                            get_string('checkpowerof10', 'qtype_varnumericset'),
                            $checkpowerof10options);
        $answeroptions[] = $mform->createElement('selectyesno', 'checkrounding',
                            get_string('checkrounding', 'qtype_varnumericset'));
        $answeroptions[] = $mform->createElement('select', 'syserrorpenalty',
                            get_string('syserrorpenalty', 'qtype_varnumericset'), $gradeoptions);
        $repeatedoptions['syserrorpenalty']['default'] = '0.1';
                                return $answeroptions;
    }

    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $mform = $this->_form;

        list($repeated, $repeatedoptions) =
                            parent::get_hint_fields(false, false);
        $repeated[] = $mform->createElement('advcheckbox', 'hintclearwrong',
                            get_string('options', 'qtype_varnumericset'),
                            get_string('hintoverride', 'qtype_varnumericset'), null, array(0, 1));
        $repeatedoptions['hintclearwrong']['type'] = PARAM_BOOL;

        return array($repeated, $repeatedoptions);
    }

    protected function data_preprocessing($question) {
        global $DB;
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question, true);

        if (isset($question->id)) {
            $qtypeobj = $this->qtype_obj();
            $calculatorname = $qtypeobj->calculator_name();
            $calculator = new $calculatorname();
            $calculator->set_random_seed($question->options->randomseed, $question->stamp);
            $qtypeobj = $this->qtype_obj();
            $calculator->set_recalculate_rand($qtypeobj->recalculate_every_time());
            list($vars, $variants) = $qtypeobj->load_var_and_variants_from_db($question->id);
            $calculator->load_data_from_database($vars, $variants);

            $question = $calculator->get_data_for_form($question);
            $question->requirescinotation = $question->options->requirescinotation;
        }

        return $question;
    }

    /**
     * Perform the necessary preprocessing for the fields added by
     * {@link add_per_answer_fields()}.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {
        $question = parent::data_preprocessing_answers($question);
        if (empty($question->options->answers)) {
            return $question;
        }
        $key = 0;
        foreach ($question->options->answers as $answer) {
            $question->sigfigs[$key] = $answer->sigfigs;
            $question->error[$key] = $answer->error;
            $question->syserrorpenalty[$key] = $answer->syserrorpenalty;
            $question->checknumerical[$key] = $answer->checknumerical;
            $question->checkscinotation[$key] = $answer->checkscinotation;
            $question->checkpowerof10[$key] = $answer->checkpowerof10;
            $question->checkrounding[$key] = $answer->checkrounding;
            $key++;
        }
        return $question;
    }


    public function validation($data, $files) {
        $qtypeobj = $this->qtype_obj();
        $calculatorname = $qtypeobj->calculator_name();
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
                if ($trimmedanswer == '*') {
                    if ($data['error'][$key] !== '') {
                        $errors["error[$key]"] = get_string('notolerancehere', 'qtype_varnumericset');
                    }
                }
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_varnumericset');
                $answercount++;
            }
        }
        $maxvariantno = -1;
        foreach ($data['varname'] as $varno => $varname) {
            if (trim($varname) !=='') {
                $isvalidvar =  EvalMath::is_valid_var_or_func_name($varname);
                $isvalidassignment = $calculatorname::is_assignment($varname);
                if ($data['vartype'][$varno] == 1 &&  !$isvalidvar) {
                    $errors["varname[$varno]"] =
                            get_string('expectingvariablename', 'qtype_varnumericset');
                }
                if ($data['vartype'][$varno] == 0) {
                    if (!$isvalidassignment) {
                        $errors["varname[$varno]"] =
                            get_string('expectingassignment', 'qtype_varnumericset');
                    }
                }
                if ($data['vartype'][$varno] == 1 && empty($data['variant0'][$varno])) {
                    $errors["variant0[$varno]"] =
                                get_string('youmustprovideavalueforatleastonevariant',
                                                                'qtype_varnumericset');
                }
                if ($data['vartype'][$varno] == 1) {
                    for ($i = 0; $i < $data['noofvariants']; $i++) {
                        if (!empty($data["variant{$i}"][$varno])) {
                            $maxvariantno = max($maxvariantno, $i);
                        }
                    }
                }
            }
        }
        if ($maxvariantno !== -1) {
            foreach ($data['varname'] as $varno => $varname) {
                if ($data['vartype'][$varno] == 1 && trim($varname) !=='') {
                    for ($variantno = 1; $variantno <= $maxvariantno; $variantno++) {
                        if (empty($data["variant{$variantno}"][$varno])) {
                            $errors["variant{$variantno}[{$varno}]"] =
                                get_string('youmustprovideavalueforallvariants',
                                                                'qtype_varnumericset');
                        }
                    }
                }
            }
        }
        if (count($errors) == 0) {
            $calculator = new $calculatorname();
            //don't need to bother setting the random seed here as the
            //results of the evaluation are not important, we are just seeing
            //if the expressions evaluate without errors.
            $calculator->load_data_from_form($data);
            $calculator->evaluate_all(true);

            $errors = $calculator->get_errors();
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_varnumericset', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
        if (!empty($data['recalculatenow']) && count($errors)) {
            $errors['recalculatenow'] = get_string('cannotrecalculate', 'qtype_varnumericset');
        }
        return $errors;
    }

    protected function qtype_obj() {
        return question_bank::get_qtype($this->qtype());
    }

    protected function db_table_prefix() {
        return $this->qtype_obj()->db_table_prefix();
    }
}
