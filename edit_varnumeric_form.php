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
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Short answer question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_edit_form extends question_edit_form {

    protected function definition_inner($mform) {

        $mform->addElement('text', 'randomseed', get_string('randomseed', 'qtype_varnumeric'));
        $mform->setType('randomseed', PARAM_RAW);

        $noofvariants = optional_param('noofvariants', 0, PARAM_INT);
        $addvariants = optional_param('addvariants', '', PARAM_TEXT);
        if ($addvariants){
            $noofvariants += 2;
        }
        $answersoption = '';

        $repeated = array();
        $repeated[] = $mform->createElement('header', 'varhdr', get_string('varheader', 'qtype_varnumeric'));
        $repeated[] = $mform->createElement('text', 'varname',
                get_string('varname', 'qtype_varnumeric'), array('size' => 40));

        $mform->setType('varname', PARAM_RAW_TRIMMED);

        $noofvariants = max($noofvariants, 5);
        for ($i=0; $i < $noofvariants; $i++){
            $repeated[] = $mform->createElement('text', "variant[$i]",
                    get_string('variant', 'qtype_varnumeric', $i+1), array('size' => 40));
        }
        $mform->setType('variant', PARAM_RAW_TRIMMED);

        $this->repeat_elements($repeated, $noofvariants, array(),
                'novars', 'addvars', 2, get_string('addmorevars', 'qtype_varnumeric'));

        $mform->registerNoSubmitButton('addvariants');
        $addvariantel = $mform->createElement('submit', 'addvariants', get_string('addmorevariants', 'qtype_varnumeric', 2));
        $mform->insertElementBefore($addvariantel, 'varhdr[1]');
        $mform->addElement('hidden', 'noofvariants', $noofvariants);
        $mform->setConstant('noofvariants', $noofvariants);
        $mform->setType('noofvariants', PARAM_INT);

        $mform->addElement('submit', 'recalculatevars', get_string('recalculatevars', 'qtype_varnumeric', 2));
        //we are using a hook in questiontype to resdisplay the form and it expects a parameter wizard, which
        //we won't actually use but we need to pass to avoid an error message.
        $mform->addElement('hidden', 'wizard', '');

        $menu = array(
            get_string('recalculaterandno', 'qtype_varnumeric'),
            get_string('recalculaterandyes', 'qtype_varnumeric')
        );
        $mform->addElement('select', 'recalculaterand', get_string('recalculaterand', 'qtype_varnumeric'), $menu);

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


        if (!empty($question->id)) {
            $vars = $DB->get_records('qtype_varnumeric_vars', array('questionid' => $question->id), 'id ASC', 'id, nameorassignment, varno');
            if ($vars) {
                $varidtovarno = array();
                $question->varname = array();
                foreach ($vars as $varid => $var){
                    $question->varname[$var->varno] = $var->nameorassignment;
                    $varidtovarno[$varid] = $var->varno;
                }
                list($varidsql, $varids) = $DB->get_in_or_equal(array_keys($vars));
                $variants = $DB->get_records_select('qtype_varnumeric_variants', 'varid '.$varidsql, $varids);
                $question->variant = array();
                foreach ($variants as $variant){
                    if (!isset($question->variant[$variant->variantno])){
                        $question->variant[$variant->variantno] = array();
                    }
                    $question->variant[$variant->variantno][$varidtovarno[$variant->varid]] = $variant->value;
                }
            }
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
