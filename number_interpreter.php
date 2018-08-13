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

define('QTYPE_VARNUMERICSET_THOUSAND_SEP', ',');
define('QTYPE_VARNUMERICSET_DECIMAL_SEP', '.');

/**
 * Interface for all classes to match parts of number.
 *
 * Interpreting only affects how student input is processed, not how values are displayed.
 *
 * @package    qtype_varnumericset
 * @copyright  2012 The Open University
 * @author     James Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_varnumericset_number_interpreter_base {

    protected $prefix;
    protected $postfix;

    // Public methods.

    /**
     * This method should be called first, and only if it returns true should you call other methods.
     * @param string $string
     * @return bool successful match?
     */
    abstract public function match($string);

    /**
     * @return string anything before the matched part
     */
    public function get_prefix() {
        return $this->prefix;
    }

    /**
     * @return string normalised part of the number
     */
    abstract public function get_normalised();

    /**
     * @return string anything after the matched part
     */
    public function get_postfix() {
        return $this->postfix;
    }

}

abstract class qtype_varnumericset_number_interpreter_part_using_preg_pattern extends
                qtype_varnumericset_number_interpreter_base {
    abstract protected function pattern();

    protected function match_pattern($string) {
        $matches = array();
        if (1 === preg_match($this->pattern(), $string, $matches, PREG_OFFSET_CAPTURE)) {
            $this->prefix = substr($string, 0, $matches[0][1]);
            $this->postfix = substr($string, $matches[0][1] + strlen($matches[0][0]));
            if ($this->postfix === false) {
                $this->postfix = '';
            }
            $this->extract_parts($matches);
            return true;
        } else {
            return false;
        }
    }

    protected function extract_part($matches, $part) {
        if (isset($matches[$part])) {
            $this->$part = $matches[$part][0];
        } else {
            $this->$part = '';
        }
    }


    /**
     * Take the needed bits for the matches expression and make them properties of the class.
     * @param $matches from the preg expression
     */
    protected function extract_parts($matches) {
        foreach ($this->parts_to_extract() as $part) {
            $this->extract_part($matches, $part);
        }
    }

    protected function parts_to_extract() {
        return array('sign');
    }

    protected $sign;

    protected function get_normalised_sign() {
        if ($this->sign === '-') {
            return '-';
        } else {
            return '';
        }
    }

    // Public methods.

    public function match($string) {
        return $this->match_pattern($string);
    }

}

/**
 * Floating point number with or without sign and decimal point
 */
class qtype_varnumericset_number_interpreter_number_with_optional_decimal_place extends
    qtype_varnumericset_number_interpreter_part_using_preg_pattern {

    protected function pattern() {
        $thousandsep = preg_quote(QTYPE_VARNUMERICSET_THOUSAND_SEP, '!');
        $decsep = preg_quote(QTYPE_VARNUMERICSET_DECIMAL_SEP, '!');
        return '!(?<sign>[+-]?)\s*'.
                '(?<predecpoint>[0-9][0-9'.$thousandsep.']*)?'.
                '(\s*'.$decsep.'\s*(?<postdecpoint>[0-9]*))?!i';
    }

    protected function parts_to_extract() {
        return array_merge(parent::parts_to_extract(), array('predecpoint', 'postdecpoint'));
    }

    protected $predecpoint;

    protected $postdecpoint;

    public function get_pre_dec_point() {
        return str_replace(QTYPE_VARNUMERICSET_THOUSAND_SEP, '', $this->predecpoint);
    }

    public function set_pre_dec_point($predecpoint) {
        $this->predecpoint = $predecpoint;
    }

    public function get_post_dec_point() {
        return $this->postdecpoint;
    }

    public function set_post_dec_point($postdecpoint) {
        $this->postdecpoint = $postdecpoint;
    }

    public function get_normalised() {
        $normalised = $this->get_pre_dec_point();

        // Strip all leading zeroes but make sure we don't end up with an empty string if all zeroes and nothing else.
        $normalised = ltrim($normalised, '0');
        if ($normalised === '') {
            $normalised = '0';
        }

        // Add decimal point and stuff after decimal point if there is stuff
        // after decimal point.
        if ($this->get_post_dec_point() !== '') {
            $normalised .= '.'.$this->get_post_dec_point();
        }

        // Normalise -0 to just 0. Rare case but possible.
        if ($normalised != 0) {
            $normalised = $this->get_normalised_sign().$normalised;
        }
        return $normalised;
    }

    protected function match_pattern($string) {
        $result = parent::match_pattern($string);
        if ($result && $this->predecpoint === '' && $this->postdecpoint === '') {
            return false;
        }
        return $result;
    }
}


/**
 * Exponent part of number in scientific notation, expected to follow immediately after
 * @link qtype_varnumericset_number_interpreter_number_with_optional_decimal_place
 */
abstract class qtype_varnumericset_number_interpreter_exponent_following_float_base extends
    qtype_varnumericset_number_interpreter_part_using_preg_pattern {

    protected function parts_to_extract() {
        return array_merge(parent::parts_to_extract(), array('exp'));
    }

    protected $exp;

    public function set_value($exponent) {
        if ($exponent < 0) {
            $this->sign = '-';
        } else if ($exponent >= 0) {
            $this->sign = '';
        }
        $this->exp = abs($exponent);
    }

    public function get_value() {
        if ($this->exp != 0) {
            return (int)($this->get_normalised_sign().$this->exp);
        } else {
            return 0;
        }
    }

    public function get_normalised() {
        return 'e'.$this->get_normalised_sign().$this->exp;
    }
}

class qtype_varnumericset_number_interpreter_nonhtml_exponent_following_float extends
    qtype_varnumericset_number_interpreter_exponent_following_float_base {

    protected function pattern() {
        return '!\s*e\s*'.
            '(?<sign>[+-]?)\s*'.
            '(?<exp>[0-9]+)!iA';
    }
}

/**
 * Exponent part of number in scientific notation, expected to follow immediately after
 * @link qtype_varnumericset_number_interpreter_number_with_optional_decimal_place
 */
class qtype_varnumericset_number_interpreter_html_exponent_following_float extends
    qtype_varnumericset_number_interpreter_exponent_following_float_base {

    protected function pattern() {
        return '!\s*[\*x×]\s*10\s*'.
            '<sup>\s*'.
            '(?<sign>[+-]?)\s*'.
            '(?<exp>[0-9]+)\s*'.
            '</sup>'.
            '!iuA';
    }
}

/**
 * Main matching class to match numbers accepted in student response.
 */
class qtype_varnumericset_number_interpreter_number_with_optional_sci_notation extends
    qtype_varnumericset_number_interpreter_base {

    /**
     * @var bool
     */
    protected $accepthtml;

    protected $normalised;

    public function __construct($accepthtml) {
        $this->accepthtml = $accepthtml;
    }

    public function match($string) {
        $num = new qtype_varnumericset_number_interpreter_number_with_optional_decimal_place();
        if ($this->accepthtml) {
            // Get rid of all tags except sup.
            $string = strip_tags($string, '<sup>');
        }
        if ($num->match($string)) {
            $exp = new qtype_varnumericset_number_interpreter_nonhtml_exponent_following_float();
            if ($exp->match($num->get_postfix())) {
                $this->normalise_coeff($num, $exp);
                $this->postfix = $exp->get_postfix();
                $this->normalised = $num->get_normalised() . $exp->get_normalised();
            } else if ($this->accepthtml) {
                $exp = new qtype_varnumericset_number_interpreter_html_exponent_following_float();
                if ($exp->match($num->get_postfix())) {
                    $this->normalise_coeff($num, $exp);
                    $this->normalised = $num->get_normalised() . $exp->get_normalised();
                    $this->postfix = $exp->get_postfix();
                } else {
                    $this->normalised = $num->get_normalised();
                    $this->postfix = $num->get_postfix();
                }
            } else {
                $this->normalised = $num->get_normalised();
                $this->postfix = $num->get_postfix();
            }
            $this->prefix = $num->get_prefix();
            return true;
        } else {
            return false;
        }
    }
    /**
     * For scientific notation make sure that coefficient is between 1 and 10.
     */
    protected function normalise_coeff(qtype_varnumericset_number_interpreter_number_with_optional_decimal_place $num,
                                       qtype_varnumericset_number_interpreter_exponent_following_float_base $exp) {
        $coeffpredecpoint = $num->get_pre_dec_point();
        $coeffpostdecpoint = $num->get_post_dec_point();
        $exponent = $exp->get_value();
        $coeffpredecpoint = ltrim($coeffpredecpoint, '0');
        if (strlen($coeffpredecpoint) > 1) {
            $exponent += (int) (strlen($coeffpredecpoint) - 1);
            $coeffpostdecpoint = substr($coeffpredecpoint, 1).$coeffpostdecpoint;
            $coeffpredecpoint = substr($coeffpredecpoint, 0, 1);
        }
        while ((strlen($coeffpostdecpoint) !== 0) && ($coeffpredecpoint === '' || $coeffpredecpoint === '0')) {
            $exponent--;
            $coeffpredecpoint = substr($coeffpostdecpoint, 0, 1);
            $coeffpostdecpoint = substr($coeffpostdecpoint, 1);
        }
        $num->set_pre_dec_point($coeffpredecpoint);
        $num->set_post_dec_point($coeffpostdecpoint);
        $exp->set_value($exponent);
    }

    public function get_normalised() {
        return $this->normalised;
    }
}
