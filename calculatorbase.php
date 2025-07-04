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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/evalmath/evalmath.class.php');

/**
 * Class for evaluating variants for varnumericset question type.
 *
 * @package   qtype_varnumericset
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_varnumeric_calculator_base {

    /**
     * @var bool whether assignments to variables should be evaluated on each
     * question load.
     */
    protected $recalculateeverytime = false;

    /**
     * @var string used to randomize random functions. The variant no and variable
     *      name will be appended to this.
     */
    protected $randomseed;

    /** @var EvalMath $ev evaluation class instance to use. **/
    protected $ev;

    /**
     * @var array two dimensional array first key is varno,
     *      2nd is variant no, contents is value of variant.
     */
    protected $predefinedvariants = [];

    /**
     * @var array two dimensional array first key is varno,
     *      2nd is variant no, contents is value of variant
     */
    protected $calculatedvariants = [];

    /** @var array one dimensional array key is varno. **/
    protected $variables = [];

    /** @var array one dimensional array first key is varno. **/
    protected $vartypes = [];

    /**
     * @var int the number of variants in the form.
     */
    protected $noofvariants = 0;

    /**
     * @var array An associative array of answers, where the keys are answer numbers.
     */
    protected $answers = [];

    /**
     * @var array An associative array of texts with embedded variables.
     */
    protected $textswithembeddedvars = [];

    /**
     * @var array An associative array of errors encountered during evaluation.
     */
    protected $errors = [];

    /**
     * Add a variable to the calculator.
     *
     * @param int $varno the variable number.
     * @param string $variablenameorassignment the name of the variable or an assignment to it.
     */
    public function add_variable($varno, $variablenameorassignment) {
        $this->variables[$varno] = $variablenameorassignment;
    }

    /**
     * Add a predefined variant to the calculator.
     *
     * This is used to store the predefined values for each variable in each question variant.
     *
     * @param int $varno the variable number.
     * @param int $variantno the variant number.
     * @param string $value the value of the variable in this variant.
     */
    public function add_defined_variant($varno, $variantno, $value) {
        $this->noofvariants = max($this->noofvariants, $variantno + 1);
        if (!isset($this->predefinedvariants[$variantno])) {
            $this->predefinedvariants[$variantno] = [];
        }
        $this->predefinedvariants[$variantno][$varno] = $value;
    }

    /**
     * Add an answer to the calculator.
     *
     * This is used to store the answers for the question variants.
     *
     * @param int $answerno the answer number.
     * @param string $answer the answer text.
     * @param string $error any error message associated with the answer.
     */
    public function add_answer($answerno, $answer, $error) {
        $answerobj = new stdClass();
        $answerobj->answer = $answer;
        $answerobj->error = $error;
        $this->answers[$answerno] = $answerobj;
    }

    /**
     * Add a text with embedded variables to the calculator.
     *
     * This is used for question text, general feedback, and other texts that may contain
     * variables that need to be evaluated.
     *
     * @param array $form the form data containing the text.
     * @param array $keys the keys to traverse in the form data to find the text.
     */
    public function add_text_with_embedded_variables($form, $keys) {
        $value = $form;
        $fromformfield = '';
        do {
            $key = array_shift($keys);
            if ($fromformfield === '') {
                $fromformfield = $key;
            } else {
                $fromformfield .= "[$key]";
            }
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return;
            }
        } while (count($keys));
        if (isset($value['text'])) {
            $value = $value['text'];
        } else {
            return;
        }
        $this->textswithembeddedvars[$fromformfield] = $value;
    }

    /**
     * Get the number of variants in the form.
     *
     * This is used to determine how many variants to show in the question creation form.
     *
     * @return int the number of variants in the form.
     */
    public function get_num_variants_in_form() {
        if ($this->noofvariants == 0) {
            // If there are no predefined variables at all then have a set
            // amount of 5 variants.
            return 5;
        }
        return $this->noofvariants;
    }

    /**
     * Get the errors that have been encountered during evaluation.
     *
     * @return array An associative array of errors, where the keys are the error.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get the calculated variants for the form.
     *
     * @return array An array of calculated variants, indexed by variant number.
     */
    public function get_calculated_variants() {
        return $this->calculatedvariants;
    }

    /**
     * Get the number of variants in the form.
     *
     * @param int $varno the variable number.
     * @param int $variantno the variant number.
     * @return int the number of variants in the form.
     */
    protected function get_defined_variant($varno, $variantno) {
        if (!isset($this->predefinedvariants[$variantno][$varno])) {
            throw new coding_exception(
                "Predefined variant no {$variantno} for var no {$varno} has not been loaded!");
        }
        return $this->predefinedvariants[$variantno][$varno];
    }

    /**
     * Evaluate everything loaded into caculator. Used for error checking and to calculate values
     * for variables in every question variant.
     * @param boolean $forcerecalculate
     */
    public function evaluate_all($forcerecalculate = false) {
        for ($variantno = 0; $variantno < $this->get_num_variants_in_form(); $variantno++) {
            $this->evaluate_variant($variantno, $forcerecalculate);
            $this->calculatedvariants[$variantno]
                            = $this->calculate_calculated_variant_values($variantno);
            foreach ($this->answers as $answerno => $answer) {
                foreach (['answer', 'error'] as $prop) {
                    if ($prop === 'error' && $answer->{$prop} === '') {
                        continue; // No error messages for blank allowed error fields in answer.
                    }
                    if (self::is_assignment($answer->{$prop})) {
                        // This is an assignment not legal here.
                        $this->errors["{$prop}[{$answerno}]"] =
                            get_string('expressionmustevaluatetoanumber', 'qtype_varnumericset');
                    } else {
                        $this->evaluate($answer->{$prop}, "{$prop}[{$answerno}]");
                    }
                }
            }
            foreach ($this->textswithembeddedvars as $wherefrom => $textwithembeddedvars) {
                $this->evaluate_variables_in_text($textwithembeddedvars, $wherefrom);
            }
        }
    }

    /**
     * Evaluate an expression or variable assignment.
     *
     * @param string $item the expression or variable assignment to evaluate.
     * @param string|null $placetoputanyerror where to put the error message, if any.
     * @return float|int|false the evaluated result, or false if there was an error.
     */
    public function evaluate($item, $placetoputanyerror = null) {
        $result = $this->ev->evaluate($item);
        $error = '';
        if ($result === false) {
            $error = get_string('errorreportedbyexpressionevaluator', 'qtype_varnumericset',
                    $this->ev->last_error);
        }
        if (is_nan($result)) {
            $error = get_string('expressionevaluatesasnan', 'qtype_varnumericset');
        }
        if (is_infinite($result)) {
            $error = get_string('expressionevaluatesasinfinite', 'qtype_varnumericset');
        }
        if ($error) {
            $this->errors[$placetoputanyerror] = $error;
        }
        return $result;
    }

    /**
     * Load all variable assignments for a given variant.
     *
     * @param int $variantno
     * @param boolean $forcerecalculate force recalculate calculated values
     *                        or load calculated values as predefined values?
     */
    public function evaluate_variant($variantno, $forcerecalculate = false) {
        if ((!$this->recalculateeverytime) && (!$forcerecalculate)) {
            $recalculatecalculated = false;
        } else {
            $recalculatecalculated = true;
        }
        $this->ev = new EvalMath(true, true);
        $this->ev->suppress_errors = true;
        foreach ($this->variables as $varno => $variablenameorassignment) {
            if (!$recalculatecalculated || !self::is_assignment($variablenameorassignment)) {
                $varname = self::var_in_assignment($variablenameorassignment);
                $this->evaluate($varname.'='.$this->get_defined_variant($varno, $variantno),
                        "variant{$variantno}[{$varno}]");
            } else {
                $varname = self::var_in_assignment($variablenameorassignment);
                EvalMathFuncs::set_random_seed($this->randomseed.$variantno.$varname);
                $this->evaluate($variablenameorassignment, "varname[$varno]");
            }
        }
    }

    /**
     * Calculate the values of all calculated variants for a given variant.
     *
     * @param int $variantno
     * @return array An array of calculated variant values, indexed by variable number.
     */
    protected function calculate_calculated_variant_values($variantno) {
        $calculatedvariants = [];
        foreach ($this->variables as $varno => $variablenameorassignment) {
            if (self::is_assignment($variablenameorassignment)) {
                $varname = self::var_in_assignment($variablenameorassignment);
                $calculatedvariants[$varno]
                            = $this->evaluate($varname, "variant$variantno[$varno]");
            }
        }
        return $calculatedvariants;
    }

    /**
     * Save internal state of calculator as question type step data.
     *
     * @param question_attempt_step $step The question attempt step to save the state to.
     */
    public function save_state_as_qt_data($step) {
        foreach ($this->variables as $varno => $variablenameorassignment) {
            $varname = self::var_in_assignment($variablenameorassignment);
            $step->set_qt_var('_var' . $varname, $this->evaluate($varname));
            $step->set_qt_var('_var' . $varname, $this->evaluate($varname));
        }
    }

    /**
     * Load internal state of calculator from question type step data.
     *
     * @param question_attempt_step $step
     */
    public function load_state_from_qt_data($step) {
        $this->ev = new EvalMath(true, true);
        foreach ($this->variables as $varno => $variablenameorassignment) {
            $varname = self::var_in_assignment($variablenameorassignment);
            $this->evaluate("{$varname}=" . $step->get_qt_var("_var{$varname}"));
        }
    }

    /**
     * Load data from the form.
     *
     * @param array $formdata the data from the question creation form.
     */
    public function load_data_from_form($formdata) {
        if (isset($formdata['varname'])) {
            foreach ($formdata['varname'] as $varno => $varname) {
                if ($varname !== '') {
                    $this->add_variable($varno, $varname);
                }
            }
        }

        for ($variantno = 0; $variantno < $formdata['noofvariants']; $variantno++) {
            $key = 'variant' . $variantno;
            if (isset($formdata[$key])) {
                $variants = $formdata[$key];
                foreach ($variants as $varno => $value) {
                    if ($formdata['vartype'][$varno] == 1) {
                        if ($value !== '') {
                            $this->add_defined_variant($varno, $variantno, $value);
                        }
                    }
                }
            }
        }

        foreach ($formdata['answer'] as $answerno => $answer) {
            if (!empty($answer) && '*' !== $answer) {
                $this->add_answer($answerno, $answer, $formdata['error'][$answerno]);
            }
        }

        $this->add_text_with_embedded_variables($formdata, ['questiontext']);
        $this->add_text_with_embedded_variables($formdata, ['generalfeedback']);

        foreach (['feedback', 'hint'] as $itemname) {
            if (isset($formdata[$itemname])) {
                foreach ($formdata[$itemname] as $indexno => $item) {
                    $this->add_text_with_embedded_variables($formdata, [$itemname, $indexno]);
                }
            }
        }
    }

    /**
     *
     * Set the portion of the random seed shared by all variants and variables.
     * @param string $randomseed from the question creation form
     * @param string $questionstamp autogenerated unique value for each question from
     *                              question object
     */
    public function set_random_seed($randomseed, $questionstamp) {
        if ($randomseed !== '') {
            $this->randomseed = $randomseed;
        } else {
            $this->randomseed = $questionstamp;
        }
    }

    /**
     * Get the portion of the random seed shared by all variants and variables.
     */
    public function get_random_seed() {
        return $this->randomseed;
    }

    /**
     * Set whether to recalculate the values of variables every time the question is loaded.
     *
     * @param boolean $recalculateeverytime whether to recalculate the values of variables every time question loaded.
     */
    public function set_recalculate_rand($recalculateeverytime) {
        $this->recalculateeverytime = $recalculateeverytime;
    }

    /**
     * Load data from the database.
     *
     * @param array $vars the variables loaded from the database.
     * @param array $variants the variants loaded from the database.
     */
    public function load_data_from_database($vars, $variants) {
        // Declare and load data whether or not we will use calculator.
        $varidtovarno = [];
        foreach ($vars as $varid => $var) {
            if (self::is_assignment($var->nameorassignment)) {
                $this->vartypes[$var->varno] = 0;
            } else {
                $this->vartypes[$var->varno] = 1;
            }
            $this->add_variable($var->varno, $var->nameorassignment);
            $varidtovarno[$varid] = $var->varno;
        }
        foreach ($variants as $variant) {
            $this->add_defined_variant($varidtovarno[$variant->varid],
                                        $variant->variantno, $variant->value);
        }
    }

    /**
     * Get the data for the form to create or edit a question.
     *
     * @param stdClass $dataforform the data object to fill in.
     * @return stdClass the filled in data object.
     */
    public function get_data_for_form($dataforform) {
        if ($this->recalculateeverytime) {
            $this->evaluate_all(true);
        }
        $dataforform->randomseed = $dataforform->options->randomseed;
        $dataforform->vartype = array_values($this->vartypes);
        $dataforform->varname = array_values($this->variables);
        for ($variantno = 0; $variantno < $this->get_num_variants_in_form(); $variantno++) {
            $propname = 'variant'.$variantno;
            $dataforform->{$propname} = [];
            if (isset($this->predefinedvariants[$variantno])) {
                $dataforform->{$propname} += array_values($this->predefinedvariants[$variantno]);
            }
            if (isset($this->calculatedvariants[$variantno])) {
                $dataforform->{$propname} += array_values($this->calculatedvariants[$variantno]);
            }
        }
        return $dataforform;
    }

    /**
     * Get the variable types.
     *
     * @return array one-dimensional array of variable types, 0 for calculated, 1 for predefined.
     */
    public function get_var_types() {
        return $this->vartypes;
    }

    /**
     * Get the variable names.
     *
     * @return array one-dimensional array of variable names.
     */
    public function get_var_names() {
        return $this->variables;
    }

    /**
     * Get the predefined variants.
     *
     * @return array two-dimensional array first key is variant no, second is varno, contents is value of variant.
     */
    public function get_defined_variants() {
        return $this->predefinedvariants;
    }

    /**
     * Check if a string is an assignment to a variable.
     *
     * @param string $string the string to check, e.g. 'x = 5'.
     * @return boolean true if it is an assignment, false otherwise.
     */
    public static function is_assignment($string) {
        $parts = explode('=', $string);
        if (count($parts) != 2) {
            return false;
        }
        return EvalMath::is_valid_var_or_func_name(trim($parts[0]));
    }

    /**
     * Get the variable name from an assignment string.
     *
     * @param string $assignment the assignment string, e.g. 'x = 5'.
     * @return string the variable name, e.g. 'x'.
     */
    public static function var_in_assignment($assignment) {
        $parts = explode('=', $assignment);
        return trim($parts[0]);
    }

    /**
     * Evaluate all variables in a text, replacing them with their values.
     *
     * @param string $text the text to evaluate.
     * @param string|null $wheretoputerror where to put errors, if any.
     * @return string the text with variables replaced by their values.
     */
    public function evaluate_variables_in_text($text, $wheretoputerror = null) {
        $done = [];
        $errors = [];

        // Match anything surrounded by [[ ]].
        preg_match_all('~\[\[(.+?)(\s*,\s*(.*?))?]]~', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            // Since the format may, or may not, be present, append an extra empty string to $match.
            [$placeholder, $variableorexpression, $hasformat, $format] = array_merge($match, ['', '']);
            if (isset($done[$placeholder])) {
                // The same placeholder always gets replaced by the same value.
                continue;
            }

            if (self::is_assignment($variableorexpression)) {
                // This is an assignment, not legal here.
                $errors[] = get_string('expressionmustevaluatetoanumber', 'qtype_varnumericset');
                continue;
            } else {
                $this->errors['temp'] = '';
                $evaluated = $this->evaluate($variableorexpression, 'temp');
                if ($this->errors['temp']) {
                    $errors[] = get_string('errorvalidationissue', 'qtype_varnumericset',
                        ['placeholder' => $placeholder, 'message' => $this->errors['temp']]);
                }
                unset($this->errors['temp']);
            }

            if ($hasformat) {
                if (strpos($format, '&nbsp') !== false) {
                    $errors[] = get_string('errorvalidationformatnumbernonbsp', 'qtype_varnumericset', $placeholder);
                    continue;
                } else {
                    try {
                        $numberasstring = self::format_number($evaluated, $format);
                    } catch (Throwable $e) {
                        $errors[] = get_string('errorvalidationissue', 'qtype_varnumericset',
                            ['placeholder' => $placeholder, 'message' => s($e->getMessage())]);
                        continue;
                    }
                }
            } else {
                $numberasstring = (string) $evaluated;
            }

            $numberasstring = self::htmlize_exponent($numberasstring);

            $text = str_replace($placeholder, $numberasstring, $text);
            $done[$placeholder] = true;
        }

        // Store errors, if any, and if required.
        if ($wheretoputerror && $errors) {
            if (count($errors) == 1) {
                $message = $errors[0];
            } else {
                $message = html_writer::alist($errors);
            }
            $this->errors[$wheretoputerror] = get_string(
                    'errorvalidationformatnumber', 'qtype_varnumericset', $message);
        }

        return $text;
    }

    /**
     * Format a number using a sprintf code.
     *
     * @param float|int $number the number to format.
     * @param string $sprintfcode a printf code, without the leading '%'.
     * @return string the formatted number.
     */
    public static function format_number($number, string $sprintfcode): string {
        return sprintf('%' . $sprintfcode, $number);
    }

    /**
     * Typeset any scientific notation in the formatted number string.
     *
     * @param string|null $numberasstring the number to improve the display of.
     * @return string prettier string.
     */
    public static function htmlize_exponent(?string $numberasstring): string {
        return preg_replace('!e([+-]?\d+)$!i', ' × 10<sup>$1</sup>', $numberasstring ?? '');
    }
}
