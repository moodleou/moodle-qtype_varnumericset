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
    /** @var EvalMath $ev evaluation class instance to use. **/
    protected $ev;

    /** @var array two dimensional array first key is varid, 2nd is variant no, contents is value of variant**/
    protected $predefinedvariants = array();

    protected $calculatedvariants = array();

    /** @var array two dimensional array first key is varid **/
    protected $variables = array();

    protected $noofvariants = 0;

    protected $answers = array();

    protected $errors = array();

    public function add_variable($varno, $variablenameorassignment){
        $this->variables[$varno] = $variablenameorassignment;
    }

    public function add_defined_variant($varno, $variantno, $value){
        $this->noofvariants = max($this->noofvariants, $variantno + 1);
        if (!isset($this->predefinedvariants[$varno])){
            $this->predefinedvariants[$varno] = array();
        }
        $this->predefinedvariants[$varno][$variantno] = $value;
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
        while (!isset($this->predefinedvariants[$varno][$variantno])){
            $variantno--;
        }
        return $this->predefinedvariants[$varno][$variantno];
    }


    public function evaluate_all(){
        error_log(print_r(array('evaluate_all' => func_get_args()), true));
        for ($variantno = 0; $variantno < $this->noofvariants; $variantno++){
            $this->evaluate_variant($variantno);
            $this->calculatedvariants[$variantno] = $this->get_calculated_variant_values($variantno);
            foreach ($this->answers as $answerno => $answer){
                $this->evaluate($answer, "answer[$answerno]");
            }
        }
    }

    public function evaluate($item, $placetoputanyerror = null){
        error_log(print_r(array('evaluate' => func_get_args()), true));
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
        error_log(print_r(array('evaluate_variant' => func_get_args()), true));
        $this->ev = new EvalMath(true, true);
        foreach ($this->variables as $varno => $variablenameorassignment){
            if (!self::is_assignment($variablenameorassignment)){
                $varname = self::var_in_assignment($variablenameorassignment);
                $this->evaluate($variablenameorassignment.'='.$this->get_defined_variant($varno, $variantno),
                        "variant$variantno[$varno]");
            } else {
                $this->evaluate($variablenameorassignment, "variable[$varno]");
            }
        }
    }


    public function get_calculated_variant_values($variantno){
        $calculatedvariants = array();
        foreach ($this->variables as $varno => $variablenameorassignment){
            if (self::is_assignment($variablenameorassignment)){
                $varname = self::var_in_assignment($variablenameorassignment);
                $calculatedvariants[$varno] = $this->evaluate($variablenameorassignment,
                                                               "variant$variantno[$varno]");
            }
        }
        return $calculatedvariants;
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