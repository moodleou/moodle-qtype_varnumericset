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
 * Question type class for the short answer question type.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/varnumeric/question.php');
require_once($CFG->libdir . '/evalmath/evalmath.class.php');
require_once($CFG->dirroot . '/question/type/varnumeric/calculator.php');


/**
 * The short answer question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric extends question_type {
    public function extra_question_fields() {
        return array('qtype_varnumeric', 'randomseed', 'recalculateeverytime', 'requirescinotation');
    }

    protected function extra_answer_fields() {
        return array('qtype_varnumeric_answers',
                        'sigfigs',
                        'error',
                        'syserrorpenalty',
                        'checknumerical',
                        'checkscinotation',
                        'checkpowerof10',
                        'checkrounding');
    }

    protected function questionid_column_name() {
        return 'questionid';
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_varnumeric', array('questionid' => $questionid));
        $DB->execute("DELETE FROM {qtype_varnumeric_answers} AS qva USING {question_answers} AS qa".
                       " WHERE qa.id = qva.answerid AND qa.question = ?", array($questionid));

        $DB->execute("DELETE FROM {qtype_varnumeric_variants} AS va ".
                       "USING {qtype_varnumeric_vars} AS v ".
                       "WHERE va.varid = v.id AND v.questionid = ?", array($questionid));
        $DB->delete_records('qtype_varnumeric_vars', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function save_question_options($form) {
        global $DB;
        $result = new stdClass();

        $context = $form->context;

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $form->id), 'id ASC');

        if (!empty($oldanswers)){
            $oldanswerids = array_keys($oldanswers);
            list($oldansweridsql, $oldansweridparams) = $DB->get_in_or_equal($oldanswerids);
            $DB->delete_records_select('qtype_varnumeric_answers', "answerid $oldansweridsql",
                                                                        $oldansweridparams);
        } else  {
            $oldanswers = array();
        }

        $answers = array();
        $maxfraction = -1;

        // Insert all the new answers
        foreach ($form->answer as $key => $answerdata) {
            // Check for, and ignore, completely blank answer from the form.
            if (trim($answerdata) == '' && $form->fraction[$key] == 0 &&
                    html_is_blank($form->feedback[$key]['text'])) {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $form->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer   = trim($answerdata);
            $answer->fraction = $form->fraction[$key];
            $answer->feedback = $this->import_or_save_files($form->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $form->feedback[$key]['format'];
            $DB->update_record('question_answers', $answer);

            $answers[] = $answer->id;
            if ($form->fraction[$key] > $maxfraction) {
                $maxfraction = $form->fraction[$key];
            }
            $varnumericanswer = new stdClass();
            $varnumericanswer->answerid = $answer->id;
            $varnumericanswer->sigfigs = $form->sigfigs[$key];
            $varnumericanswer->error = $form->error[$key];
            $varnumericanswer->syserrorpenalty = $form->syserrorpenalty[$key];
            $varnumericanswer->checknumerical = $form->checknumerical[$key];
            $varnumericanswer->checkscinotation = $form->checkscinotation[$key];
            $varnumericanswer->checkpowerof10 = $form->checkpowerof10[$key];
            $varnumericanswer->checkrounding = $form->checkrounding[$key];
            $DB->insert_record('qtype_varnumeric_answers', $varnumericanswer);
        }

        list ($varschanged, $varnotovarid, $assignments, $predefined) =
                                                    $this->save_vars($form->id, $form->varname);

        $variants = array();
        for ($variantno = 0; $variantno < $form->noofvariants; $variantno++) {
            $propname = 'variant'.$variantno;
            $variants[$variantno] = $form->{$propname};
        }

        //process variants
        if ($form->recalculateeverytime) {
            //remove any old variants in the db that are calculated
            list($varidsql, $varids) = $DB->get_in_or_equal($assignments);
            $DB->delete_records_select('qtype_varnumeric_variants', 'varid '.$varidsql, $varids);
        }
        $definedvariantschanged = $this->save_variants($predefined, $variants, $varnotovarid);

        //on save don't ever calculate calculated variants if the recalculate every time option
        //is selected but if it is not recalculate whenever there is a change of predefined variants
        //or any variable or when recalculate button is pressed.
        if ((!$form->recalculateeverytime) && // the recalculate every time option
                ((!empty($form->recalculatenow)) // the recalculate now option
                || $definedvariantschanged || $varschanged)) {
            //precalculate variant values
            $calculator = new qtype_varnumeric_calculator();
            if (empty($form->randomseed)) {
                $questionstamp = $DB->get_field('question', 'stamp', array('id' => $form->id));
            } else {
                $questionstamp = '';
            }
            $calculator->set_random_seed($form->randomseed, $questionstamp);
            $calculator->load_data_from_form((array)$form);
            $calculator->evaluate_all(true);
            $calculatedvariants = $calculator->get_calculated_variants();
            $this->save_variants($assignments, $calculatedvariants, $varnotovarid);
        }

        $parentresult = parent::save_question_options($form);
        if ($parentresult !== null) {
            // Parent function returns null if all is OK
            return $parentresult;
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $this->save_hints($form, true);

        // Perform sanity checks on fractional grades
        if ($maxfraction != 1) {
            $result->noticeyesno = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        }
    }

    /**
     * Save variant values.
     * @param array varidstoprocess only save variants for variables with these ids
     * @param integer questionid
     * @param array variants data from form
     * @param array varnotovarid index is varno and values are qtype_varnumeric_vars.id
     */
    protected function save_variants($varidstoprocess, $variants, $varnotovarid) {
        global $DB;
        if (empty($varidstoprocess)) {
            return false;
        }
        $changed = false;
        list($varidsql, $varids) = $DB->get_in_or_equal($varidstoprocess);
        $oldvariants =
                $DB->get_records_select('qtype_varnumeric_variants', 'varid '.$varidsql, $varids);
        //variants are indexed by variantno and then var no
        foreach ($variants as $variantno => $variant) {
            foreach ($variant as $varno => $value) {
                if ($value === '' || !in_array($varnotovarid[$varno], $varidstoprocess)) {
                    continue;
                }
                $foundold = false;
                foreach ($oldvariants as $oldvariantid => $oldvariant) {
                    if ($oldvariant->varid == $varnotovarid[$varno]
                             && $oldvariant->variantno == $variantno) {
                        $foundold = true;
                        $variantrec = $oldvariant;
                        unset($oldvariants[$oldvariantid]);
                        break;
                    }
                }
                if (!$foundold) {
                    $variantrec = new stdClass();
                    $variantrec->varid = $varnotovarid[$varno];
                    $variantrec->variantno = $variantno;
                    $variantrec->value = $value;
                    $variantrec->id = $DB->insert_record('qtype_varnumeric_variants', $variantrec);
                    $changed = true;
                } else {
                    if ($variantrec->value != $value) {
                        $variantrec->value = $value;
                        $DB->update_record('qtype_varnumeric_variants', $variantrec);
                        $changed = true;
                    }
                }
            }
        }
        //delete any remaining old variants
        if (!empty($oldvariants)) {
            $changed = true;
            list($oldvariantsidsql, $oldvariantsids) =
                                                $DB->get_in_or_equal(array_keys($oldvariants));
            $DB->delete_records_select('qtype_varnumeric_variants',
                                            'id '.$oldvariantsidsql,
                                            $oldvariantsids);
        }
        return $changed;

    }

    /**
     * Save variables.
     * @param integer questionid
     * @param array varnames
     */
    protected function save_vars($questionid, $varnames) {
        global $DB;
        $changed = false;
        $oldvars = $DB->get_records('qtype_varnumeric_vars',
                                       array('questionid' => $questionid),
                                       'id ASC');
        $varnotovarid = array();
        $predefined = array();
        $assignments = array();
        foreach ($varnames as $varno => $varname) {
            if ($varname == '') {
                continue;
            }
            $foundold = false;
            // Update an existing var if possible.
            foreach ($oldvars as $oldvarid => $var) {
                if ($var->varno == $varno) {
                    $foundold = true;
                    $varfromdb = $var;
                    unset($oldvars[$oldvarid]);
                    break;
                }
            }
            if (!$foundold) {
                $var = new stdClass();
                $var->questionid = $questionid;
                $var->varno = $varno;
                $var->nameorassignment = $varname;
                $var->id = $DB->insert_record('qtype_varnumeric_vars', $var);
                $varid = $var->id;
                $changed = true;
            } else {
                if ($varfromdb->nameorassignment != $varname) {
                    $varfromdb->nameorassignment = $varname;
                    $DB->update_record('qtype_varnumeric_vars', $varfromdb);
                    $changed = true;
                }
                $varid = $varfromdb->id;
            }
            $varnotovarid[$varno] = $varid;
            if (qtype_varnumeric_calculator::is_assignment($varname)) {
                $assignments[] = $varid;
            } else {
                $predefined[] = $varid;
            }

        }
        //delete any remaining old vars
        if (!empty($oldvars)) {
            $oldvarids = array();
            foreach ($oldvars as $oldvar) {
                $oldvarids[] = $oldvar->id;
            }
            list($oldvaridsql, $oldvaridslist) = $DB->get_in_or_equal($oldvarids);
            $DB->delete_records_select('qtype_varnumeric_vars', 'id '.$oldvaridsql, $oldvaridslist);
            $changed = true;
        }
        return array($changed, $varnotovarid, $assignments, $predefined);
    }

    public function finished_edit_wizard($fromform) {
        //keep browser from moving onto next page after saving question and
        //recalculating variable values.
        if (!empty($fromform->recalculatenow)) {
            return false;
        } else {
            return true;
        }
    }
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_vars_and_variants($question, $questiondata);
        $this->initialise_question_answers($question, $questiondata);
    }
    protected function initialise_question_vars_and_variants(question_definition $question,
                                                                                $questiondata) {
        $question->calculator = new qtype_varnumeric_calculator();
        $question->calculator->set_random_seed($questiondata->options->randomseed,
                                                $questiondata->stamp);
        $question->calculator->set_recalculate_rand($questiondata->options->recalculateeverytime);
        $question->calculator->load_data_from_database($question->id);
        $question->requirescinotation = $questiondata->options->requirescinotation;
    }
    /**
     * Initialise question_definition::answers field.
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_question_answers(question_definition $question, $questiondata) {
        $question->answers = array();
        if (empty($questiondata->options->answers)) {
            return;
        }
        foreach ($questiondata->options->answers as $a) {
            $question->answers[$a->id] = new qtype_varnumeric_answer($a->id, $a->answer,
                    $a->fraction, $a->feedback, $a->feedbackformat, $a->sigfigs, $a->error,
                    $a->syserrorpenalty, $a->checknumerical, $a->checkscinotation,
                    $a->checkpowerof10, $a->checkrounding);
        }
    }
    public function get_random_guess_score($questiondata) {
        foreach ($questiondata->options->answers as $aid => $answer) {
            if ('*' == trim($answer->answer)) {
                return $answer->fraction;
            }
        }
        return 0;
    }

    public function get_possible_responses($questiondata) {
        $responses = array();

        foreach ($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer,
                    $answer->fraction);
        }
        $responses[null] = question_possible_response::no_response();

        return array($questiondata->id => $responses);
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }
}
