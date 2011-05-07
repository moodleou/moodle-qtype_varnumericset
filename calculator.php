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
 * Class for evaluating variants for varnumeric question type.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class qtype_varnumeric_calculator {

    /** @var boolean whether assignments to variables should be evaluated on each question load. */
    protected $recalculateeverytime = false;

    /** @var string used to randomize random functions. The variant no and variable name will be appended to this. **/
    protected $randomseed;

    /** @var EvalMath $ev evaluation class instance to use. **/
    protected $ev;

    /** @var array two dimensional array first key is varno, 2nd is variant no, contents is value of variant**/
    protected $predefinedvariants = array();

    /** @var array two dimensional array first key is varno, 2nd is variant no, contents is value of variant**/
    protected $calculatedvariants = array();

    /** @var array one dimensional array key is varno **/
    protected $variables = array();

    /** @var array one dimensional array first key is varno **/
    protected $vartypes = array();

    protected $noofvariants = 0;

    protected $answers = array();

    protected $errors = array();

    public function add_variable($varno, $variablenameorassignment){
        $this->variables[$varno] = $variablenameorassignment;
    }

    public function add_defined_variant($varno, $variantno, $value){
        $this->noofvariants = max($this->noofvariants, $variantno + 1);
        if (!isset($this->predefinedvariants[$variantno])){
            $this->predefinedvariants[$variantno] = array();
        }
        $this->predefinedvariants[$variantno][$varno] = $value;
    }
    public function add_answer($answerno, $answer){
        $this->answers[$answerno] = $answer;
    }

    public function get_errors(){
        return $this->errors;
    }

    public function get_calculated_variants(){
        return $this->calculatedvariants;
    }

    protected function get_defined_variant($varno, $variantno){
        while (!isset($this->predefinedvariants[$variantno][$varno])){
            $variantno--;
        }
        return $this->predefinedvariants[$variantno][$varno];
    }


    public function evaluate_all(){
        if ($this->noofvariants == 0){
            $this->noofvariants = 5;
        }
        for ($variantno = 0; $variantno < $this->noofvariants; $variantno++){
            $this->evaluate_variant($variantno);
            $this->calculatedvariants[$variantno] = $this->calculate_calculated_variant_values($variantno);
            foreach ($this->answers as $answerno => $answer){
                if (true === $this->evaluate($answer, "answer[$answerno]")){
                    //this is an assignment not legal here
                    $this->errors["answer[$answerno]"] =
                                get_string('expressionmustevaluatetoanumber', 'qtype_varnumeric');
                }
            }
        }
    }

    public function evaluate($item, $placetoputanyerror = null){
        $result = $this->ev->evaluate($item);
        if ($result === false && !is_null($placetoputanyerror)){
            $this->errors[$placetoputanyerror] = get_string('errorreportedbyexpressionevaluator',
                                                                'qtype_varnumeric', $this->ev->last_error);
        }
        return $result;
    }
    /**
     *
     * Load all variable assignments.
     * @param integer $variantno
     */
    public function evaluate_variant($variantno){
        $this->ev = new EvalMath(true, true);
        foreach ($this->variables as $varno => $variablenameorassignment){
            if (!self::is_assignment($variablenameorassignment)){
                $varname = self::var_in_assignment($variablenameorassignment);
                $this->evaluate($variablenameorassignment.'='.$this->get_defined_variant($varno, $variantno),
                        "variant{$variantno}[{$varno}]");
            } else {
                $this->evaluate($variablenameorassignment, "variable[$varno]");
            }
        }
    }


    protected function calculate_calculated_variant_values($variantno){
        $calculatedvariants = array();
        foreach ($this->variables as $varno => $variablenameorassignment){
            if (self::is_assignment($variablenameorassignment)){
                $varname = self::var_in_assignment($variablenameorassignment);
                EvalMathCalcEmul_randomised::set_random_seed($this->randomseed.$variantno.$varname);
                $calculatedvariants[$varno] = $this->evaluate($varname, "variant$variantno[$varno]");
            }
        }
        return $calculatedvariants;
    }
    public function load_data_from_form($formdata){
        foreach ($formdata['varname'] as $varno => $varname){
            if ($varname!==''){
                $this->add_variable($varno, $varname);
            }
        }
        for ($variantno = 0; $variantno < $formdata['noofvariants']; $variantno++) {
            if (isset($formdata['variant'.$variantno])){
                $variants = $formdata['variant'.$variantno];
                foreach ($variants as $varno => $value){
                    if ($formdata['vartype'][$varno] == 1) {
                        if ($value!==''){
                            $this->add_defined_variant($varno, $variantno, $value);
                        }
                    }
                }
            }
        }
        foreach ($formdata['answer'] as $answerno => $answer) {
            if (!empty($answer) && '*' != $answer){
                $this->add_answer($answerno, $answer);
            }
        }
    }

    /**
     *
     * Set the portion of the random seed shared by all variants and variables.
     * @param string $randomseed from the question creation form
     * @param string $questionstamp autogenerated unique value for each question from question object
     */
    public function set_random_seed($randomseed, $questionstamp){
        global $USER;
        if (!empty($randomseed)){
            $this->randomseed = $randomseed;
        } else {
            $this->randomseed = $questionstamp;
        }
        $this->randomseed .= $USER->id;
    }

    public function set_recalculate_rand($recalculateeverytime){
        $this->recalculateeverytime = $recalculateeverytime;
    }


    public function load_data_from_database($questionid){
        global $DB;
        $vars = $DB->get_records('qtype_varnumeric_vars', array('questionid' => $questionid), 'id ASC', 'id, nameorassignment, varno');
        if ($vars) {
            //declare and load data whether or not we will use calculator.
            $varidtovarno = array();
            foreach ($vars as $varid => $var){
                if (self::is_assignment($var->nameorassignment)){
                    $this->vartypes[$var->varno] = 0;
                } else {
                    $this->vartypes[$var->varno] = 1;
                }
                $this->add_variable($var->varno, $var->nameorassignment);
                $varidtovarno[$varid] = $var->varno;
            }
            list($varidsql, $varids) = $DB->get_in_or_equal(array_keys($vars));
            $variants = $DB->get_records_select('qtype_varnumeric_variants', 'varid '.$varidsql, $varids);
            foreach ($variants as $variant){
                $this->add_defined_variant($varidtovarno[$variant->varid], $variant->variantno, $variant->value);
            }
            if ($this->recalculateeverytime){
                $this->evaluate_all();
            }
        }
    }
    public function get_data_for_form($dataforform){
        $dataforform->recalculateeverytime = $this->recalculateeverytime;
        $dataforform->randomseed = $dataforform->options->randomseed;
        $dataforform->vartype = $this->vartypes;
        $dataforform->varname = $this->variables;
        for ($variantno=0; $variantno < $this->noofvariants; $variantno++){
            $propname = 'variant'.$variantno;
            $dataforform->{$propname} = array();
            if (isset($this->predefinedvariants[$variantno])){
                $dataforform->{$propname} += $this->predefinedvariants[$variantno];
            }
            if (isset($this->calculatedvariants[$variantno])){
                $dataforform->{$propname} += $this->calculatedvariants[$variantno];
            }
        }
        return $dataforform;
    }

    public function get_var_types(){
        return $this->vartypes;
    }

    public function get_var_names(){
        return $this->variables;
    }

    public function get_defined_variants(){
        return $this->predefinedvariants;
    }

    public static function is_assignment($string) {
        $parts = explode('=', $string);
        if (count($parts) != 2){
            return false;
        }
        return EvalMath::is_valid_var_or_func_name(trim($parts[0]));
    }

    public static function var_in_assignment($assignment) {
        $parts = explode('=', $assignment);
        return trim($parts[0]);
    }

}