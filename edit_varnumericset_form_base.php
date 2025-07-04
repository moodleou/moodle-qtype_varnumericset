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
 * @package   qtype_varnumericset
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumericset/calculator.php');


/**
 * varnumeric question editing form definition base.
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_varnumeric_edit_form_base extends question_edit_form {

    #[\Override]
    protected function definition_inner($mform) {
        global $DB;

        $mform->addHelpButton('questiontext', 'questiontext', 'qtype_varnumericset');

        $mform->addElement('text', 'randomseed', get_string('randomseed', 'qtype_varnumericset'));
        $mform->setType('randomseed', PARAM_RAW);

        $answersoption = '';

        $typemenu = [
            0 => get_string('vartypecalculated', 'qtype_varnumericset'),
            1 => get_string('vartypepredefined', 'qtype_varnumericset')
        ];

        $repeated = [];
        $repeatedoptions = [];
        $mform->addElement('header', 'variables',
                                get_string('variables', 'qtype_varnumericset'));
        $repeated[] = $mform->createElement('select', 'vartype', '', $typemenu);
        $repeated[] = $mform->createElement('text', 'varname',
                                get_string('varheader', 'qtype_varnumericset'), ['size' => 40]);

        $mform->setType('varname', PARAM_RAW_TRIMMED);
        $repeatedoptions['varname']['helpbutton'] = ['varname', 'qtype_varnumericset'];

        list($repeated, $repeatedoptions) =
                                $this->add_value_form_fields($mform, $repeated, $repeatedoptions);

        if (isset($this->question->id)) {
            $prefix = $this->db_table_prefix();
            $sql = "SELECT COUNT(id)
                      FROM {{$prefix}_vars} vars
                     WHERE questionid = ?";
            $noofvarsatstart = $DB->get_field_sql($sql, [$this->question->id]);
        } else {
            $noofvarsatstart = 5;
        }

        $this->repeat_elements($repeated, $noofvarsatstart, $repeatedoptions,
                'novars', 'addvars', 2, get_string('addmorevars', 'qtype_varnumericset'), true);

        $mform->addElement('submit', 'recalculatenow',
                                        get_string('recalculatenow', 'qtype_varnumericset', 2));

        // We are using a hook in questiontype to resdisplay the form and it expects a parameter
        // wizard, which we won't actually use but we need to pass it to avoid an error message.
        $mform->addElement('hidden', 'wizard', '');
        $mform->setType('wizard', PARAM_ALPHANUM);

        $mform->addElement('header', 'answershdr',
                                get_string('answers', 'question'));
        $mform->setExpanded('answershdr', 1);
        $mform->addElement('static', 'forallanswers',
                                get_string('forallanswers', 'qtype_varnumericset'));
        $mform->addElement('selectyesno', 'requirescinotation',
                                get_string('requirescinotation', 'qtype_varnumericset'));

        $mform->addElement('static', 'answersinstruct',
                get_string('correctanswers', 'qtype_varnumericset'),
                get_string('filloutoneanswer', 'qtype_varnumericset'));

        $this->add_answer_form_part($mform);

        $this->add_interactive_settings($mform, $repeated, $repeatedoptions);
    }

    /**
     * Add more variant button.
     *
     * @param MoodleQuickForm $mform The form to which the button will be added.
     */
    protected function add_add_more_variant_button(MoodleQuickForm $mform) {
        // Add a button to add more form fields for variants.
        $addvariantel = $mform->createElement('submit', 'addvariants',
                get_string('addmorevariants', 'qtype_varnumericset', 2));
        $mform->registerNoSubmitButton('addvariants');

        if ($mform->elementExists('vartype[1]')) {
            $mform->insertElementBefore($addvariantel, 'vartype[1]');
        } else if ($mform->elementExists('vartype[0]')) {
            $mform->insertElementBefore($addvariantel, 'addvars');
        }
    }


    /**
     * Add answer section of the form. In varnumeric unit question type this is overridden to add also the units to this
     * section of the form.
     * @param MoodleQuickForm $mform
     */
    protected function add_answer_form_part($mform) {
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_varnumericset', '{no}'),
            question_bank::fraction_options());
    }

    /**
     * Add the form fields for the value part of the question.
     *
     * This method is used to add the fields for the variants of the variables in the question.
     * It can be overridden by subclasses to customize the fields added.
     *
     * @param MoodleQuickForm $mform The form to which the fields will be added.
     * @param array $repeated The repeated elements array.
     * @param array $repeatedoptions The options for the repeated elements.
     */
    protected function add_value_form_fields($mform, $repeated, $repeatedoptions) {
        global $DB;
        $noofvariants = optional_param('noofvariants', 0, PARAM_INT);
        $addvariants = optional_param('addvariants', false, PARAM_BOOL);
        if ($addvariants) {
            $noofvariants += 2;
        }
        if (isset($this->question->id)) {
            $prefix = $this->db_table_prefix();
            $sql = "SELECT MAX(vari.variantno)+1
                      FROM {{$prefix}_variants} vari, {{$prefix}_vars} vars
                     WHERE vars.questionid = ? AND vars.id = vari.varid";
            $noofvariants = max($noofvariants, $DB->get_field_sql($sql, [$this->question->id]));
        } else {
            $noofvariants = max($noofvariants, 5);
        }
        for ($i = 0; $i < $noofvariants; $i++) {
            $repeated[] = $mform->createElement('text', "variant$i",
                get_string('variant', 'qtype_varnumericset', $i + 1), ['size' => 40]);
            $repeatedoptions["variant$i"]['disabledif'] = ['vartype', 'eq', 0];
            if ($i === 0) {
                $repeatedoptions["variant$i"]['helpbutton']
                    = ['variants', 'qtype_varnumericset'];
            }
            $mform->setType("variant$i", PARAM_RAW_TRIMMED);
        }

        $this->add_value_form_last_field($mform, $repeated, $repeatedoptions);
        $mform->addElement('hidden', 'noofvariants', $noofvariants);
        $mform->setConstant('noofvariants', $noofvariants);
        $mform->setType('noofvariants', PARAM_INT);
        return [$repeated, $repeatedoptions];
    }

    /**
     * Add a last field to the value form.
     *
     * This is used to add a field that can be styled as the last variant field.
     * It is not used for any functional purpose.
     *
     * @param MoodleQuickForm $mform The form to which the field will be added.
     * @param array $repeated The repeated elements array.
     * @param array $repeatedoptions The options for the repeated elements.
     */
    protected function add_value_form_last_field($mform, &$repeated, &$repeatedoptions) {
        /*
         * Adding a field element so we can style variants properly. Not what we want.
         * Couldn't find a way to identify the last variant field using css. Can't find the
         * add more elements and work backwards.
         *
         * Need an element with an id to work with. Hidden fields have no id and are inserted at
         * the start of the form.
         */
        $repeated[] = $mform->createElement('text', "variant_last", 'last variant', '', ['class' => 'last']);
        $mform->setType('variant_last', PARAM_TEXT);
    }

    #[\Override]
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
                                             &$repeatedoptions, &$answersoption) {
        $repeated = [];
        $answeroptions = [];

        // For this question type we are using a per answer header, a header for all answers won't really work.
        $mform->removeElement('answerhdr');

        $answeroptions[] = $mform->createElement('text', 'answer', '', ['size' => 8]);
        $answeroptions[] = $mform->createElement('text', 'error', get_string('error', 'qtype_varnumericset'), ['size' => 8]);
        $sigfigsoptions = [0 => get_string('unspecified', 'qtype_varnumericset'),
                                1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        $answeroptions[] = $mform->createElement('select', 'sigfigs', get_string('sigfigs', 'qtype_varnumericset'),
                                                 $sigfigsoptions);

        $answeroptions[] = $mform->createElement('select', 'fraction', get_string('gradenoun'), $gradeoptions);
        $repeated[] = $mform->createElement('group', 'answeroptions',
                                            $label, $answeroptions, null, false);

        $repeated[] = $mform->createElement('editor', 'feedback',
                                            get_string('feedback', 'question'), ['rows' => 5], $this->editoroptions);
        $repeated[] = $mform->createElement('static', 'autofirehdr', '',
                                                 get_string('autofirehdr', 'qtype_varnumericset', ''));
        $autofirerow1 = [];
        $autofirerow1[] = $mform->createElement('selectyesno', 'checknumerical',
                                                 get_string('checknumerical',  'qtype_varnumericset'));
        $checkpowerof10options = [0 => get_string('no'),
                                       1 => '+/- 1', 2 => '+/- 2', 3 => '+/- 3',
                                       4 => '+/- 4', 5 => '+/- 5', 6 => '+/- 6'];
        $autofirerow1[] = $mform->createElement('selectyesno', 'checkscinotation',
                                                 get_string('checkscinotation', 'qtype_varnumericset'));
        $repeated[] = $mform->createElement('group', 'autofirerow1', '',
                                            $autofirerow1, null, false);

        $autofirerow2 = [];
        $autofirerow2[] = $mform->createElement('selectyesno', 'checkscinotationformat',
            get_string('checkscinotationformat', 'qtype_varnumericset'));
        $autofirerow2[] = $mform->createElement('select', 'checkpowerof10',
                                            get_string('checkpowerof10', 'qtype_varnumericset'), $checkpowerof10options);
        $repeated[] = $mform->createElement('group', 'autofirerow2', '',
                                            $autofirerow2, null, false);

        $autofirerow3 = [];
        $autofirerow3[] = $mform->createElement('selectyesno', 'checkrounding',
            get_string('checkrounding', 'qtype_varnumericset'));
        $autofirerow3[] = $mform->createElement('select', 'syserrorpenalty',
                                                 get_string('syserrorpenalty', 'qtype_varnumericset'), $gradeoptions);
        $repeated[] = $mform->createElement('group', 'autofirerow3', '',
                                            $autofirerow3, null, false);

        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $repeatedoptions['error']['type'] = PARAM_RAW;
        $answersoption = 'answers';
        return $repeated;
    }

    #[\Override]
    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $mform = $this->_form;

        list($repeated, $repeatedoptions) =
                            parent::get_hint_fields(false, false);
        $repeated[] = $mform->createElement('advcheckbox', 'hintclearwrong',
                            get_string('options', 'qtype_varnumericset'),
                            get_string('hintoverride', 'qtype_varnumericset'), null, [0, 1]);
        $repeatedoptions['hintclearwrong']['type'] = PARAM_BOOL;

        return [$repeated, $repeatedoptions];
    }

    #[\Override]
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
     * @param bool $withanswerfiles whether to include answer files in the preprocessing.
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


    #[\Override]
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
                if (strip_tags($trimmedanswer) !== $trimmedanswer) {
                    // Check there is not HTML in the answer. (Numbers must be 3.e8, not 3 x 10<sup>8</sup>.).
                    $errors["answeroptions[$key]"] = get_string('errorvalidationinvalidanswer', 'qtype_varnumericset');
                }
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
                if ($trimmedanswer === '*') {
                    if ($data['error'][$key] !== '') {
                        $errors["answeroptions[$key]"] = get_string('notolerancehere', 'qtype_varnumericset');
                    }
                }
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answeroptions[$key]"] = get_string('answermustbegiven', 'qtype_varnumericset');
                $answercount++;
            }
        }
        $maxvariantno = -1;
        $countvariable = 0;
        if (isset($data['varname'])) {
            foreach ($data['varname'] as $varno => $varname) {
                if (trim($varname) !== '') {
                    $isvalidvar = EvalMath::is_valid_var_or_func_name($varname);
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
                    $countvariable++;
                }
            }
        }
        if ($maxvariantno !== -1) {
            foreach ($data['varname'] as $varno => $varname) {
                if ($data['vartype'][$varno] == 1 && trim($varname) !== '') {
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
        if (count($errors) === 0) {
            $calculator = new $calculatorname();
            // Don't need to bother setting the random seed here as the
            // results of the evaluation are not important, we are just seeing
            // if the expressions evaluate without errors.
            $calculator->load_data_from_form($data);
            $calculator->evaluate_all(true);

            $errors = $calculator->get_errors();
        }
        if ($answercount == 0) {
            $errors['answeroptions[0]'] = get_string('notenoughanswers', 'qtype_varnumericset', 1);
        }
        $errors += $this->validate_variables($countvariable, $maxvariantno);
        if ($maxgrade == false) {
            $errors['answeroptions[0]'] = get_string('fractionsnomax', 'question');
        }
        if (!empty($data['recalculatenow']) && empty($errors)) {
            $errors['recalculatenow'] = get_string('cannotrecalculate', 'qtype_varnumericset');
        }
        return $errors;
    }

    /**
     * Varnumunit/Varnumericset must have at least 1 predefined vartype.
     *
     * @param int $countvariable number of valid variables in the form.
     * @param int $maxvariantno The maximum number of variants in the form.
     * @return array $errors Error message.
     */
    protected function validate_variables(int $countvariable, int $maxvariantno): array {
        $errors = [];
        if ($countvariable > 0 && $maxvariantno === -1) {
            $errors['vartype[0]'] = get_string('errorvalidationatleastonepredefinedvariables',
                'qtype_varnumericset');
        }
        return $errors;
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function get_more_choices_string() {
        return get_string('addmoreanswerblanks', 'qtype_varnumericset');
    }

    /**
     * Returns the question type object for this question type.
     * This is used to access methods and properties specific to the question type.
     */
    protected function qtype_obj() {
        return question_bank::get_qtype($this->qtype());
    }

    /**
     * Returns the database table prefix for the question type.
     *
     * This is used to ensure that the correct table prefix is used when
     * accessing the database tables related to this question type.
     */
    protected function db_table_prefix() {
        return $this->qtype_obj()->db_table_prefix();
    }
}
