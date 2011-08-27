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
 * Unit tests for the varnumericset question definition class.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumericset/question.php');
require_once($CFG->dirroot . '/question/engine/simpletest/helpers.php');


/**
 * Unit tests for the varnumericset question definition class.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumericset_question_test extends UnitTestCase {
    public function test_num_within_allowed_error() {
        $this->assertTrue(
                qtype_varnumericset_question::num_within_allowed_error('1.230001e4', 1.23e4, ''));
        $this->assertFalse(
                qtype_varnumericset_question::num_within_allowed_error('1.230002e4', 1.23e4, ''));
        $this->assertTrue(
                qtype_varnumericset_question::num_within_allowed_error('1.2301e4', 1.23e4, '1'));
        $this->assertFalse(
                qtype_varnumericset_question::num_within_allowed_error('1.23015e4', 1.23e4, '1'));
        $this->assertTrue(
                qtype_varnumericset_question::num_within_allowed_error('12301', 1.23e4, '1'));
        $this->assertFalse(
                qtype_varnumericset_question::num_within_allowed_error('12301.5', 1.23e4, '1'));
    }
    public function test_wrong_by_a_factor_of_ten() {
        $this->assertTrue(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('1.23e4', 1.23e5, '', 1));
        $this->assertFalse(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('1.23e4', 1.23e6, '', 1));
        $this->assertTrue(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('1.231', 12.3, 0.01, 1));
        $this->assertFalse(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('1.232', 12.3, 0.01, 1));
        $this->assertTrue(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('151000', 150, 1, 3));
        $this->assertFalse(
            qtype_varnumericset_question::wrong_by_a_factor_of_ten('152000', 150, 1, 3));
    }
    public function test_has_number_of_sig_figs() {
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.23e4', 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.23456e4', 6));
         $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.2345e4', 6));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.231', 4));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.231', 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1232', 4));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1230', 3));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1232', 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('151000', 3));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('152000', 2));
    }
    public function test_has_too_many_sig_figs() {
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('1.23456', 1.23456, 2));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('1.2346', 1.23456, 2));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('1.2345', 1.23456, 2));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('1.23', 1.23456, 2));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('1.24', 1.23456, 2));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('1.23457', 1.23456, 2));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('1.23456e4', 1.23456e4, 2));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('1.23456e4', 1.33456e4, 2));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('7.89e-4', 7.890123e-4, 2));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('-1.23456e-12', -1.2346e-12, 4));
    }
    public function test_rounding_incorrect() {
        $this->assertTrue(
            qtype_varnumericset_question::rounding_incorrect('1.234', 1.2345, 4));
        $this->assertTrue(
            qtype_varnumericset_question::rounding_incorrect('1.2345', 1.23456, 5));
        //this routine is not meant to catch incorrect rounding up
        $this->assertFalse(
            qtype_varnumericset_question::rounding_incorrect('1.3', 1.23, 2));
        $this->assertFalse(
            qtype_varnumericset_question::rounding_incorrect('1.23', 1.23456, 2));

    }
    public function test_round_to() {
        $this->assertIdentical('0.123', qtype_varnumericset_question::round_to(0.12345, 3, false));
        $this->assertIdentical('0.1235', qtype_varnumericset_question::round_to(0.12345, 4, false));
        //incorrect rounding
        $this->assertIdentical('1.235e-1', qtype_varnumericset_question::round_to(0.12345, 4, true));
        //incorrect rounding
        $this->assertIdentical('1.234e-1',
                                    qtype_varnumericset_question::round_to(0.12345, 4, true, true));
        $this->assertIdentical('1234.57',
                                    qtype_varnumericset_question::round_to(1234.5678, 6, false));
        $this->assertIdentical('1.23457e3',
                                    qtype_varnumericset_question::round_to(1234.5678, 6, true));
        //incorrect rounding
        $this->assertIdentical('1234.56',
                                    qtype_varnumericset_question::round_to(1234.5678, 6, false, true));
        $this->assertIdentical('1.23456e3',
                                    qtype_varnumericset_question::round_to(1234.5678, 6, true, true));
        //always round down when incorrect rounding requested
        $this->assertIdentical('1234.56',
                                    qtype_varnumericset_question::round_to(1234.5600, 6, false, true));
        $this->assertIdentical('1.23456e3',
                                    qtype_varnumericset_question::round_to(1234.5600, 6, true, true));
    }


}
