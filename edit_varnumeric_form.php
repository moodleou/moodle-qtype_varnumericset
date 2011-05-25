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
 * Defines the editing form for the varnumeric question type.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2011 Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumeric/calculator.php');

/**
 * Short answer question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        global $DB;

        $mform->addElement('text', 'randomseed', get_string('randomseed', 'qtype_varnumeric'));
        $mform->setType('randomseed', PARAM_RAW);

        $noofvariants = optional_param('noofvariants', 0, PARAM_INT);
        $addvariants = optional_param('addvariants', '', PARAM_TEXT);
        if ($addvariants){
            $noofvariants += 2;
        }
        $answersoption = '';

        $typemenu = array(0 => get_string('vartypecalculated', 'qtype_varnumeric'),
                            1 => get_string('vartypepredefined', 'qtype_varnumeric'));

        $repeated = array();
        $repeatedoptions = array();
        $repeated[] = $mform->createElement('header', 'varhdr', get_string('varheader', 'qtype_varnumeric'));
        $repeated[] = $mform->createElement('select', 'vartype', '', $typemenu);
        $repeated[] = $mform->createElement('text', 'varname', get_string('varname', 'qtype_varnumeric'), array('size' => 40));

        $mform->setType('varname', PARAM_RAW_TRIMMED);
        $repeatedoptions['varname']['helpbutton'] = array('varname', 'qtype_varnumeric');

        if (isset($this->question->id)) {
            $sql = 'SELECT MAX(vari.variantno)+1 '.
                    'FROM {qtype_varnumeric_variants} vari, {qtype_varnumeric_vars} vars '.
                    'WHERE vars.questionid = ? AND vars.id = vari.varid';
            $noofvariantsindb = $DB->get_field_sql($sql, array($this->question->id));
            $sql = 'SELECT MAX(varno)+1 '.
                    'FROM {qtype_varnumeric_vars} vars '.
                    'WHERE questionid = ?';
            $noofvarsindb = $DB->get_field_sql($sql, array($this->question->id));
        } else {
            $noofvariantsindb = 0;
            $noofvarsindb = 0;
        }

        if ($this->question->formoptions->repeatelements) {
            $noofvariants = max($noofvariants, 5, $noofvariantsindb + 2);
            $noofvarsatstart = max($noofvarsindb + 2, 5);
        } else {
            $noofvariants = max(5, $noofvariantsindb);
            $noofvarsatstart = $noofvarsindb;
        }
        for ($i=0; $i < $noofvariants; $i++){
            $repeated[] = $mform->createElement('text', "variant$i",
                    get_string('variant', 'qtype_varnumeric', $i+1), array('size' => 40));
            $repeatedoptions["variant$i"]['disabledif'] = array('vartype', 'eq', 0);
            if ($i == 0){
                $repeatedoptions["variant$i"]['helpbutton'] = array('variants', 'qtype_varnumeric');
            }
        }
        $mform->setType('variant', PARAM_RAW_TRIMMED);

        $this->repeat_elements($repeated, $noofvarsatstart, $repeatedoptions,
                'novars', 'addvars', 2, get_string('addmorevars', 'qtype_varnumeric'));

        $mform->registerNoSubmitButton('addvariants');
        $addvariantel = $mform->createElement('submit', 'addvariants', get_string('addmorevariants', 'qtype_varnumeric', 2));
        $mform->insertElementBefore($addvariantel, 'varhdr[1]');
        $mform->addElement('hidden', 'noofvariants', $noofvariants);
        $mform->setConstant('noofvariants', $noofvariants);
        $mform->setType('noofvariants', PARAM_INT);

        $mform->addElement('header', 'calculatewhen', get_string('calculatewhen', 'qtype_varnumeric'));

        $menu = array(
            get_string('recalculateeverytimeno', 'qtype_varnumeric'),
            get_string('recalculateeverytimeyes', 'qtype_varnumeric')
        );
        $mform->addElement('select', 'recalculateeverytime', get_string('recalculateeverytime', 'qtype_varnumeric'), $menu);
        $mform->addHelpButton('recalculateeverytime', 'recalculateeverytime', 'qtype_varnumeric');

        $mform->addElement('submit', 'recalculatenow', get_string('recalculatenow', 'qtype_varnumeric', 2));
        $mform->disabledIf('recalculatenow', 'recalculateeverytime', 'eq', 1);

        //we are using a hook in questiontype to resdisplay the form and it expects a parameter wizard, which
        //we won't actually use but we need to pass it to avoid an error message.
        $mform->addElement('hidden', 'wizard', '');

        $mform->addElement('static', 'answersinstruct',
                get_string('correctanswers', 'qtype_varnumeric'),
                get_string('filloutoneanswer', 'qtype_varnumeric'));
        $mform->closeHeaderBefore('answersinstruct');

        $creategrades = get_grade_options();
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_varnumeric', '{no}'),
                $creategrades->gradeoptions);

        $this->add_interactive_settings();
    }

    protected function data_preprocessing($question) {
        global $DB;
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);

        if (isset($question->id)){
            $calculator = new qtype_varnumeric_calculator();
            $calculator->set_random_seed($question->options->randomseed, $question->stamp);
            $calculator->set_recalculate_rand($question->options->recalculateeverytime);
            $calculator->load_data_from_database($question->id);
            $question = $calculator->get_data_for_form($question);
        }

        return $question;
    }

    public function validation($data, $files) {
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
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_varnumeric');
                $answercount++;
            }
        }
        foreach ($data['varname'] as $varno => $varname){
            if ($varname!==''){
                $isvalidvar =  EvalMath::is_valid_var_or_func_name($varname);
                $isvalidassignment = qtype_varnumeric_calculator::is_assignment($varname);
                if ($data['vartype'][$varno] == 1 &&  !$isvalidvar){
                    $errors["varname[$varno]"] = get_string('expectingvariablename', 'qtype_varnumeric');
                }
                if ($data['vartype'][$varno] == 0) {
                    if (!$isvalidassignment){
                        $errors["varname[$varno]"] = get_string('expectingassignment', 'qtype_varnumeric');
                    }
                }
                if ($data['vartype'][$varno] == 1 && empty($data['variant0'][$varno])) {
                    $errors["variant0[$varno]"] = get_string('youmustprovideavalueforfirstvariant', 'qtype_varnumeric');
                }
            }
        }
        if (count($errors) == 0) {
            $calculator = new qtype_varnumeric_calculator();
            //don't need to bother setting the random seed here as the
            //results of the evaluation are not important, we are just seeing
            //if the expressions evaluate without errors.
            $calculator->load_data_from_form($data);
            $calculator->evaluate_all(true);
            $errors = $calculator->get_errors();
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_varnumeric', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
        return $errors;
    }

    public function qtype() {
        return 'varnumeric';
    }
}
