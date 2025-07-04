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
 * Question type base class for the variable numeric question types.
 *
 * @package qtype_varnumericset
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/varnumericset/calculator.php');


/**
 * Question type base class for the variable numeric question types.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_varnumeric_base extends question_type {

    /**
     *
     * @return boolean whether expressions are evaluated on the fly or only during
     * question editing.
     */
    abstract public function recalculate_every_time();

    /**
     * Returns the prefix for the database tables used by this question type.
     *
     * @return string the prefix for the database tables used by this question type.
     */
    abstract public function db_table_prefix();

    /**
     * Returns the name of the calculator class used by this question type.
     *
     * @return string the name of the calculator class used by this question type.
     */
    public function calculator_name() {
        return $this->db_table_prefix().'_calculator';
    }

    #[\Override]
    public function extra_question_fields() {
        return [$this->db_table_prefix(), 'randomseed', 'requirescinotation'];
    }

    #[\Override]
    public function extra_answer_fields() {
        return [
            $this->db_table_prefix().'_answers',
            'sigfigs',
            'error',
            'syserrorpenalty',
            'checknumerical',
            'checkscinotation',
            'checkpowerof10',
            'checkrounding',
            'checkscinotationformat'];
    }

    #[\Override]
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    #[\Override]
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    #[\Override]
    public function delete_question($questionid, $contextid) {
        global $DB;
        $prefix = $this->db_table_prefix();
        $DB->delete_records($prefix, ['questionid' => $questionid]);
        $ids = $DB->get_fieldset_sql("SELECT qva.id FROM {{$prefix}_answers} qva
                                        JOIN {question_answers} qa ON qa.id = qva.answerid
                                       WHERE qa.question = ?", [$questionid]);
        if (!empty($ids)) {
            $DB->delete_records_list("{$prefix}_answers", 'id', $ids);
        }
        $ids = $DB->get_fieldset_sql("SELECT va.id FROM {{$prefix}_variants} va
                                        JOIN {{$prefix}_vars} v ON va.varid = v.id
                                       WHERE v.questionid = ?", [$questionid]);
        if (!empty($ids)) {
            $DB->delete_records_list("{$prefix}_variants", 'id', $ids);
        }
        $DB->delete_records("{$prefix}_vars", ['questionid' => $questionid]);

        parent::delete_question($questionid, $contextid);
    }

    #[\Override]
    public function save_question_options($form) {
        global $DB;
        $result = new stdClass();

        $context = $form->context;

        $oldanswers = $DB->get_records('question_answers',
            ['question' => $form->id], 'id ASC');

        if (!empty($oldanswers)) {
            $oldanswerids = array_keys($oldanswers);
            list($oldansweridsql, $oldansweridparams) = $DB->get_in_or_equal($oldanswerids);
            $DB->delete_records_select($this->db_table_prefix().'_answers',
                "answerid $oldansweridsql", $oldansweridparams);
        } else {
            $oldanswers = [];
        }

        $answers = [];
        $maxfraction = -1;

        // Insert all the new answers.
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
            $varnumericanswer->checkscinotationformat = $form->checkscinotationformat[$key];
            $DB->insert_record($this->db_table_prefix().'_answers', $varnumericanswer);
        }
        $form->varname = $form->varname ?? null;
        [$varschanged, $varnotovarid, $assignments, $predefined] = $this->save_vars($form->id, $form->varname);

        $variants = [];
        for ($variantno = 0; $variantno < $form->noofvariants; $variantno++) {
            $propname = 'variant'.$variantno;
            if (isset($form->{$propname})) {
                $variants[$variantno] = $form->{$propname};
            }
        }

        // Process variants.
        if ($this->recalculate_every_time() && count($assignments)) {
            // Remove any old variants in the db that are calculated.
            list($varidsql, $varids) = $DB->get_in_or_equal($assignments);
            $DB->delete_records_select($this->db_table_prefix().'_variants',
                'varid '.$varidsql, $varids);
        }
        $definedvariantschanged = $this->save_variants($predefined, $variants, $varnotovarid);

        // On save don't ever calculate calculated variants if the recalculate every time option
        // is selected but if it is not recalculate whenever there is a change of predefined variants
        // r any variable or when recalculate button is pressed.
        if ((!$this->recalculate_every_time()) && // The recalculate every time option.
            ((!empty($form->recalculatenow)) // The recalculate now option.
                || $definedvariantschanged || $varschanged)) {
            // Precalculate variant values.
            $calculatorname = $this->calculator_name();
            $calculator = new $calculatorname();
            if (empty($form->randomseed)) {
                $questionstamp = $DB->get_field('question', 'stamp', ['id' => $form->id]);
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
            // Parent function returns null if all is OK.
            return $parentresult;
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', ['id' => $oldanswer->id]);
        }

        $this->save_hints($form, true);

        // Perform sanity checks on fractional grades.
        if ($maxfraction != 1) {
            $result->noticeyesno = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        }
    }

    /**
     * Save variant values.
     * @param array $varidstoprocess only save variants for variables with these ids
     * @param array $variants data from form
     * @param array $varnotovarid index is varno and values are qtype_varnumericset_vars.id
     */
    protected function save_variants($varidstoprocess, $variants, $varnotovarid) {
        global $DB;
        if (empty($varidstoprocess)) {
            return false;
        }
        $changed = false;
        list($varidsql, $varids) = $DB->get_in_or_equal($varidstoprocess);
        $oldvariants = $DB->get_records_select($this->db_table_prefix().'_variants',
            'varid '.$varidsql, $varids);
        // Variants are indexed by variantno and then var no.
        foreach ($variants as $variantno => $variant) {
            foreach ($variant as $varno => $value) {
                if ($value === '' || !isset($varnotovarid[$varno]) || !in_array($varnotovarid[$varno], $varidstoprocess)) {
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
                    $variantrec->id =
                        $DB->insert_record($this->db_table_prefix().'_variants', $variantrec);
                    $changed = true;
                } else {
                    if ($variantrec->value != $value) {
                        $variantrec->value = $value;
                        $DB->update_record($this->db_table_prefix().'_variants', $variantrec);
                        $changed = true;
                    }
                }
            }
        }
        // Delete any remaining old variants.
        if (!empty($oldvariants)) {
            $changed = true;
            list($oldvariantsidsql, $oldvariantsids) =
                $DB->get_in_or_equal(array_keys($oldvariants));
            $DB->delete_records_select($this->db_table_prefix().'_variants',
                'id '.$oldvariantsidsql,
                $oldvariantsids);
        }

        return $changed;
    }

    /**
     * Save variables.
     *
     * @param integer $questionid questionid the question id.
     * @param array $varnames the names of the variables to save.
     */
    protected function save_vars($questionid, $varnames) {
        global $DB;
        $changed = false;
        $oldvars = $DB->get_records($this->db_table_prefix().'_vars',
            ['questionid' => $questionid],
            'id ASC');
        $varnotovarid = [];
        $predefined = [];
        $assignments = [];
        if (isset($varnames)) {
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
                    $var->id = $DB->insert_record($this->db_table_prefix().'_vars', $var);
                    $varid = $var->id;
                    $changed = true;
                } else {
                    if ($varfromdb->nameorassignment != $varname) {
                        $varfromdb->nameorassignment = $varname;
                        $DB->update_record($this->db_table_prefix().'_vars', $varfromdb);
                        $changed = true;
                    }
                    $varid = $varfromdb->id;
                }
                $varnotovarid[$varno] = $varid;
                $calculatorname = $this->calculator_name();
                if ($calculatorname::is_assignment($varname)) {
                    $assignments[] = $varid;
                } else {
                    $predefined[] = $varid;
                }

            }
        }
        // Delete any remaining old vars.
        if (!empty($oldvars)) {
            $oldvarids = [];
            foreach ($oldvars as $oldvar) {
                $oldvarids[] = $oldvar->id;
            }
            list($oldvaridsql, $oldvaridslist) = $DB->get_in_or_equal($oldvarids);
            $DB->delete_records_select($this->db_table_prefix().'_vars',
                'id '.$oldvaridsql,
                $oldvaridslist);
            $changed = true;
        }
        return [$changed, $varnotovarid, $assignments, $predefined];
    }

    #[\Override]
    public function finished_edit_wizard($fromform) {
        // Keep browser from moving onto next page after saving question and
        // recalculating variable values.
        if (!empty($fromform->recalculatenow)) {
            return false;
        }

        return true;
    }

    #[\Override]
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_vars_and_variants($question, $questiondata);
        $this->initialise_varnumeric_answers($question, $questiondata);
        $question->requirescinotation = $question->usesupeditor = (bool) $questiondata->options->requirescinotation;
    }

    /**
     * Load variables and variants from the database.
     *
     * @param integer $questionid the question id.
     * @return array [$vars, $variants] where $vars is an array of qtype_varnumericset_vars
     * records and $variants is an array of qtype_varnumericset_variants records.
     */
    public function load_var_and_variants_from_db($questionid) {
        global $DB;
        $vars = $DB->get_records($this->db_table_prefix().'_vars',
            ['questionid' => $questionid],
            'id ASC', 'id, nameorassignment, varno');
        if ($vars) {
            list($varidsql, $varids) = $DB->get_in_or_equal(array_keys($vars));
            $variants = $DB->get_records_select($this->db_table_prefix().'_variants',
                'varid '.$varidsql, $varids);
            if (!$variants) {
                $variants = [];
            }
        } else {
            $vars = [];
            $variants = [];
        }
        return [$vars, $variants];
    }

    /**
     * Initialise question_definition::calculator and load variables and variants from the database.
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_question_vars_and_variants(question_definition $question,
                                                                                $questiondata) {
        global $DB;
        $calculatorname = $this->calculator_name();
        $question->calculator = new $calculatorname();
        $question->calculator->set_random_seed($questiondata->options->randomseed,
            $questiondata->stamp);
        $question->calculator->set_recalculate_rand($this->recalculate_every_time());

        list($vars, $variants) = $this->load_var_and_variants_from_db($question->id);
        $question->calculator->load_data_from_database($vars, $variants);
    }
    /**
     * Initialise question_definition::answers field.
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_varnumeric_answers(question_definition $question, $questiondata) {
        $question->answers = [];
        if (empty($questiondata->options->answers)) {
            return;
        }
        foreach ($questiondata->options->answers as $a) {
            $question->answers[$a->id] = new qtype_varnumericset_answer($a->id, $a->answer,
                $a->fraction, $a->feedback, $a->feedbackformat, $a->sigfigs, $a->error,
                $a->syserrorpenalty, $a->checknumerical, $a->checkscinotation,
                $a->checkpowerof10, $a->checkrounding, $a->checkscinotationformat);
        }
    }

    #[\Override]
    public function get_random_guess_score($questiondata) {
        foreach ($questiondata->options->answers as $aid => $answer) {
            if ('*' == trim($answer->answer)) {
                return $answer->fraction;
            }
        }
        return 0;
    }

    #[\Override]
    public function get_possible_responses($questiondata) {
        $responses = [];

        $starfound = false;
        foreach ($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer,
                $answer->fraction);
            if ($answer->answer === '*') {
                $starfound = true;
            }
        }

        if (!$starfound) {
            $responses[0] = new question_possible_response(
                get_string('didnotmatchanyanswer', 'question'), 0);
        }

        $responses[null] = question_possible_response::no_response();

        return [$questiondata->id => $responses];
    }

    #[\Override]
    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    // IMPORT/EXPORT FUNCTIONS.

    /**
     * Imports question from the Moodle XML format.
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this.
     *
     * @param array $data the question data in Moodle XML format.
     * @param question_definition $question the question to import into.
     * @param qformat_xml $format the format to import from.
     * @param mixed $extra any extra data needed for the import.
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $qo = parent::import_from_xml($data, $question, $format, $extra);
        if (!$qo) {
            return false;
        }

        $qo->noofvariants = 0;
        if (isset($data['#']['var'])) {
            $vars = $data['#']['var'];
            foreach ($vars as $var) {
                $varno = $format->getpath($var, ['#', 'varno', 0, '#'], false);
                $qo->varname[$varno] =
                    $format->getpath($var, ['#', 'nameorassignment', 0, '#'], false);
                $calculatorname = $this->calculator_name();
                if ($calculatorname::is_assignment($qo->varname[$varno])) {
                    $qo->vartype[$varno] = 0;
                } else {
                    $qo->vartype[$varno] = 1;
                }
                if (isset($var['#']['variant'])) {
                    $variants = $var['#']['variant'];
                    foreach ($variants as $variant) {
                        $variantno = $format->getpath($variant, ['#', 'variantno', 0, '#'], false);
                        $variantpropname = 'variant'.$variantno;
                        $qo->{$variantpropname}[$varno] =
                            $format->getpath($variant, ['#', 'value', 0, '#'], false);
                        $qo->noofvariants = max($qo->noofvariants, $variantno + 1);
                    }
                }
            }
        } else {
            $qo->varname = [];
            $qo->vartype = [];
        }
        $format->import_hints($qo, $data, true, false,
            $format->get_format($qo->questiontextformat));
        return $qo;
    }

    /**
     * Export question to the Moodle XML format.
     *
     * Export question using information from extra_question_fields function.
     * If some of you fields contains id's you'll need to reimplement this.
     *
     * @param question_definition $question the question to export.
     * @param qformat_xml $format the format to export to.
     * @param mixed $extra any extra data needed for the export.
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $expout = parent::export_to_xml($question, $format, $extra);
        list($vars, $variants) = self::load_var_and_variants_from_db($question->id);
        foreach ($vars as $var) {
            $expout .= "    <var>\n";
            foreach (['varno', 'nameorassignment'] as $field) {
                $exportedvalue = $format->xml_escape($var->$field);
                $expout .= "      <$field>{$exportedvalue}</$field>\n";
            }
            foreach ($variants as $variant) {
                if ($variant->varid == $var->id) {
                    $expout .= "      <variant>\n";
                    foreach (['variantno', 'value'] as $field) {
                        $exportedvalue = $format->xml_escape($variant->$field);
                        $expout .= "        <$field>{$exportedvalue}</$field>\n";
                    }
                    $expout .= "      </variant>\n";
                }
            }
            $expout .= "    </var>\n";
        }
        return $expout;
    }
}
