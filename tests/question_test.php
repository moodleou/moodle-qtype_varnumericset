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

namespace qtype_varnumericset;

use advanced_testcase;
use qtype_varnumeric_question_base;
use qtype_varnumericset_answer;
use qtype_varnumericset_question;
use question_attempt_step;
use test_question_maker;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/varnumericset/question.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for the varnumericset question definition class.
 *
 * @package   qtype_varnumericset
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_varnumeric_question_base
 * @covers    \qtype_varnumericset_question
 */
final class question_test extends advanced_testcase {

    /**
     * Test-cases for test_num_within_allowed_error
     *
     * @return array of arrays of arguments for test_num_within_allowed_error
     */
    public static function num_within_allowed_error_cases(): array {
        return [
            ['1.23000000000001e4', 1.23e4, '', true],
            ['1.23000000000002e4', 1.23e4, '', false],
            ['-1.23000000000001e4', -1.23e4, '', true],
            ['-1.23000000000002e4', -1.23e4, '', false],
            ['-9.00000000000009e-4', -9e-4, '', true],
            ['-9.00000000000010e-4', -9e-4, '', false],
            ['1.2301e4', 1.23e4, '1', true],
            ['1.23015e4', 1.23e4, '1', false],
            ['12299', 1.23e4, '1', true],
            ['1.2985e4', 1.23e4, '1', false],
            ['1.2299', 1.23, '0.001', true],
            ['1.2985', 1.23, '0.001', false],
            ['-12299', -1.23e4, '1', true],
            ['-1.2985e4', -1.23e4, '1', false],
            ['-1.2299', -1.23, '0.001', true],
            ['-1.2985', -1.23, '0.001', false],
            ['12301', 1.23e4, '1', true],
            ['12301.5', 1.23e4, '1', false],
            ['-4', -4, '', true],
            ['4', -4, '', false],
            ['-4', -4, '0.0001', true],
            ['4', -4, '0.0001', false],
            [-4.20, -4.2, 0, true],
            [12, 12, 0, true],
            ['9437183', 9437184, '', false],
            ['9437184', 9437184, '', true],
            ['9437185', 9437184, '', false],
            ['75497471', 75497472, '', false],
            ['75497472', 75497472, '', true],
            ['75497473', 75497472, '', false],
        ];
    }

    /**
     * Test the num_within_allowed_error function.
     *
     * @dataProvider num_within_allowed_error_cases
     * @param string|float $response The response to check.
     * @param string|float $answer The answer to check against.
     * @param string|float $allowederror The allowed error.
     * @param bool $shouldmatch Whether the response should match the criteria.
     */
    public function test_num_within_allowed_error($response, $answer, $allowederror, $shouldmatch): void {
        if ($shouldmatch) {
            $this->assertTrue(
                    qtype_varnumeric_question_base::num_within_allowed_error($response, $answer, $allowederror));
        } else {
            $this->assertFalse(
                    qtype_varnumeric_question_base::num_within_allowed_error($response, $answer, $allowederror));
        }
    }

    /**
     * Test-cases for test_num_within_allowed_error
     *
     * @return array of arrays of arguments for test_num_within_allowed_error
     */
    public static function wrong_by_a_factor_of_ten_cases(): array {
        return [
            ['1.23e4', 1.23e5, '', 1, true],
            ['1.23e4', 1.23e6, '', 1, false],
            ['1.231', 12.3, 0.01, 1, true],
            ['1.232', 12.3, 0.01, 1, false],
            ['151000', 150, 1, 3, true],
            ['152000', 150, 1, 3, false],
        ];
    }

    /**
     * Test the wrong_by_a_factor_of_ten function.
     *
     * @dataProvider wrong_by_a_factor_of_ten_cases
     * @param string $response The response to check.
     * @param string $roundedanswer The rounded answer to check against.
     * @param string $allowederror The allowed error.
     * @param int $maxfactor The maximum factor to check.
     * @param bool $shouldmatch Whether the response should match the criteria.
     */
    public function test_wrong_by_a_factor_of_ten($response, $roundedanswer, $allowederror, $maxfactor, $shouldmatch): void {
        if ($shouldmatch) {
            $this->assertTrue(
                    qtype_varnumeric_question_base::wrong_by_a_factor_of_ten(
                            $response, $roundedanswer, $allowederror, $maxfactor));
        } else {
            $this->assertFalse(
                    qtype_varnumeric_question_base::wrong_by_a_factor_of_ten(
                            $response, $roundedanswer, $allowederror, $maxfactor));
        }
    }

    public function test_has_number_of_sig_figs(): void {
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.23e4', 3));

        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.2345e4', 6));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.2345e4', 5));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.2345e4', 4));

        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.231', 5));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1.231', 4));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1.231', 3));

        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1232', 5));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1232', 4));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('1232', 3));

        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('1230', 3));

        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('151000', 7));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('151000', 6));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('151000', 3));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('152000', 2));

        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('15.0', 4));
        $this->assertTrue(
            qtype_varnumericset_question::has_number_of_sig_figs('15.0', 3));
        $this->assertFalse(
            qtype_varnumericset_question::has_number_of_sig_figs('15.0', 2));
    }

    public function test_has_too_many_sig_figs(): void {
        $this->assertTrue(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 2));
        $this->assertTrue(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 3));
        $this->assertTrue(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 4));
        $this->assertTrue(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 5));
        $this->assertFalse(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 6));
        $this->assertTrue(qtype_varnumericset_question::has_too_many_sig_figs('1.234560e5', 123456, 6));
        $this->assertFalse(qtype_varnumericset_question::has_too_many_sig_figs('1.23456e5', 123456, 6));
        // Should only return true when extra sig figs in response are correct.
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
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('7.89e-4', 7.89e-4, 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('7.891e-4', 7.891e-4, 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('7.891e-4', 789.10e-6, 3));
        $this->assertTrue(
            qtype_varnumericset_question::has_too_many_sig_figs('7.891e-4', 007.891e-4, 3));
        $this->assertFalse(
            qtype_varnumericset_question::has_too_many_sig_figs('-1.23456e-12', -1.2346e-12, 4));
    }

    public function test_rounding_incorrect(): void {
        $this->assertTrue(
            qtype_varnumericset_question::rounding_incorrect('1.234', 1.2345, 4));
        $this->assertTrue(
            qtype_varnumericset_question::rounding_incorrect('1.2345', 1.23456, 5));
        // This routine is not meant to catch incorrect rounding up.
        $this->assertFalse(
            qtype_varnumericset_question::rounding_incorrect('1.3', 1.23, 2));
        $this->assertFalse(
            qtype_varnumericset_question::rounding_incorrect('1.23', 1.23456, 2));

    }

    /**
     * Test-cases for test_num_within_allowed_error
     *
     * @return array of arrays of arguments for test_num_within_allowed_error
     */
    public static function round_to_cases(): array {
        return [
            ['0.123', 0.12345, 3, false, false],
            ['0.1235', 0.12345, 4, false, false],
            // Incorrect rounding.
            ['1.235e-1', 0.12345, 4, true, false],
            // Incorrect rounding.
            ['1.234e-1', 0.12345, 4, true, true],
            ['1234.57', 1234.5678, 6, false, false],
            ['1.23457e3', 1234.5678, 6, true, false],
            // Incorrect rounding.
            ['1234.56', 1234.5678, 6, false, true],
            ['1.23456e3', 1234.5678, 6, true, true],
            // Always round down when incorrect rounding requested.
            ['1234.56', 1234.5600, 6, false, true],
            ['1.23456e3', 1234.5600, 6, true, true],
            // Test default precision.
            ['1234.56', 1234.5600, 0, false, false],
            ['1.234560e3', 1234.5600, 0, true, false],
            ['75497472', 75497472, 0, false, false],
        ];
    }

    /**
     * Test the round_to function.
     *
     * @param string $expected The expected result.
     * @param float $number The number to round.
     * @param int $sigfigs The number of significant figures to round to.
     * @param bool $scinotation Whether to use scientific notation.
     * @param bool $floor Whether to floor the result.
     * @dataProvider round_to_cases
     */
    public function test_round_to($expected, $number, $sigfigs, $scinotation, $floor): void {
        $this->assertSame($expected,
                qtype_varnumeric_question_base::round_to($number, $sigfigs, $scinotation, $floor));
    }

    /**
     * Grade one response ot one question, and return the fraction.
     *
     * @param qtype_varnumericset_question $question
     * @param string $enteredresponse
     * @return float the fraction (mark out of 1).
     */
    protected function grade(qtype_varnumericset_question $question, string $enteredresponse): float {
        [$fraction] = $question->grade_response(['answer' => $enteredresponse]);
        return $fraction;
    }

    public function test_compare_response_with_answer(): void {
        /** @var qtype_varnumericset_question $q */
        $q = test_question_maker::make_question('varnumericset'); // Does not matter which one.

        $answer = new qtype_varnumericset_answer(12345, // Id.
                                                 '-4.2',  // Answer.
                                                 1,       // Fraction.
                                                 '<p>Your answer is correct.</p>', // Feedback.
                                                 FORMAT_HTML,  // Feedbackformat.
                                                 '3',     // Sigfigs.
                                                 '',      // Error.
                                                 '0.1',   // Syserrorpenalty.
                                                 '0',     // Checknumerical.
                                                 '0',     // Checkscinotation.
                                                 '0',     // Checkpowerof10.
                                                 '0',     // Checkrounding.
                                                 '0');    // Checkscinotationformat.

        $answertoreturn = $q->compare_response_with_answer(['answer' => '-4.20'], $answer);
        $this->assertNotNull($answertoreturn);
        $this->assertEquals(12345, $answertoreturn->id);

        $answer = new qtype_varnumericset_answer(12345, // Id.
                                                 '12.0',  // Answer.
                                                 1,       // Fraction.
                                                 '<p>Your answer is correct.</p>', // Feedback.
                                                 FORMAT_HTML,  // Feedbackformat.
                                                 '3',     // Sigfigs.
                                                 '',      // Error.
                                                 '0.1',   // Syserrorpenalty.
                                                 '0',     // Checknumerical.
                                                 '0',     // Checkscinotation.
                                                 '0',     // Checkpowerof10.
                                                 '0',     // Checkrounding.
                                                 '0');    // Checkscinotationformat.

        $answertoreturn = $q->compare_response_with_answer(['answer' => '12.0'], $answer);
        $this->assertNotNull($answertoreturn);
        $this->assertEquals(12345, $answertoreturn->id);
        // Check the answer is empty string.
        $answertoreturn = $q->compare_response_with_answer(['answer' => ''], $answer);
        $this->assertNull($answertoreturn);
        // Check the answer is null.
        $answertoreturn = $q->compare_response_with_answer([], $answer);
        $this->assertNull($answertoreturn);
    }

    public function test_compare_num_as_string_with_answer(): void {
        /** @var qtype_varnumericset_question $q */
        $q = test_question_maker::make_question('varnumericset'); // Does not matter which one.

        $answer = new qtype_varnumericset_answer(12345, // Id.
                                                 '-4.2',  // Answer.
                                                 1,       // Fraction.
                                                 '<p>Your answer is correct.</p>', // Feedback.
                                                 FORMAT_HTML,  // Feedbackformat.
                                                 '3',     // Sigfigs.
                                                 '',      // Error.
                                                 '0.1',   // Syserrorpenalty.
                                                 '0',     // Checknumerical.
                                                 '0',     // Checkscinotation.
                                                 '0',     // Checkpowerof10.
                                                 '0',     // Checkrounding.
                                                 '0');    // Checkscinotationformat.

        [$penalty] = $q->compare_num_as_string_with_answer(
                '-4.20', $answer);
        $this->assertEquals(0, $penalty);

        $answer->answer = '12.00';
        $answer->sigfigs = 4;
        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.00', $answer);
        $this->assertEquals(0, $penalty);

        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.0', $answer);
        $this->assertEquals(1, $penalty);

        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.000', $answer);
        $this->assertEquals(0.1, $penalty);

        $answer->answer = '12.0';
        $answer->sigfigs = 3;
        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.0', $answer);
        $this->assertEquals(0, $penalty);

        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.00', $answer);
        $this->assertEquals(0.1, $penalty);

        [$penalty] = $q->compare_num_as_string_with_answer(
                '12.', $answer);
        $this->assertEquals(1, $penalty);

        // Test check scinotation format.
        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', 'sci_notation_formatted');
        $answer = new qtype_varnumericset_answer(12345, // Id.
            '12',     // Answer.
            1.0000000,     // Fraction.
            '<p>Your answer is correct.</p>',     // Feedback.
            FORMAT_HTML,  // Feedbackformat.
            '0',     // Sigfigs.
            '',      // Error.
            '0.25',  // Syserrorpenalty.
            '0',     // Checknumerical.
            '1',     // Checkscinotation.
            '0',     // Checkpowerof10.
            '0',     // Checkrounding.
            '1');    // Checkscinotationformat.
        [$penalty, $autofireerrorfeedback, $warning] = $question->compare_num_as_string_with_answer(
            '1.200000 × 10<sup>1</sup>', $answer);
        $this->assertEquals(0, $penalty);
        $this->assertStringContainsString('', $autofireerrorfeedback);
        $this->assertStringContainsString('', $warning);

        [$penalty, $autofireerrorfeedback, $warning] = $question->compare_num_as_string_with_answer(
            '1.200000 ×     10<sup>1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
        $this->assertStringContainsString(get_string('ae_scinotationformatted', 'qtype_varnumericset'),
            $autofireerrorfeedback);
        $this->assertStringContainsString('', $warning);

        [$penalty] = $question->compare_num_as_string_with_answer(
            '1.200000 × 10<sup>+1</sup>', $answer);
        $this->assertEquals(0, $penalty);
        [$penalty] = $question->compare_num_as_string_with_answer(
            '1.200000  ×10<sup>1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
        [$penalty] = $question->compare_num_as_string_with_answer(
            '1.200000×10<sup>1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
        [$penalty] = $question->compare_num_as_string_with_answer(
            '1.200000 × 10<sup>   1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
        [$penalty] = $question->compare_num_as_string_with_answer(
            '+   1.200000 × 10<sup>1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
        [$penalty] = $question->compare_num_as_string_with_answer(
            '1.200000 × 10<sup>+  1</sup>', $answer);
        $this->assertEquals(0.25, $penalty);
    }

    public function test_compare_num_as_string_with_answer_no_rounding(): void {
        /** @var qtype_varnumericset_question $q */
        $q = test_question_maker::make_question('varnumericset'); // Does not matter which one.

        $answer = new qtype_varnumericset_answer(12345, // Id.
                '123456789', // Answer.
                1,           // Fraction.
                '<p>Your answer is correct.</p>', // Feedback.
                FORMAT_HTML, // Feedbackformat.
                '',          // Sigfigs.
                '',          // Error.
                '0.1',       // Syserrorpenalty.
                '0',         // Checknumerical.
                '0',         // Checkscinotation.
                '0',         // Checkpowerof10.
                '0',         // Checkrounding.
                '0');        // Checkscinotationformat.

        [$penalty] = $q->compare_num_as_string_with_answer('123456789', $answer);
        $this->assertEquals(0, $penalty);
    }

    public function test_grade_response(): void {
        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', 'no_accepted_error');
        $this->assertEquals(1, $this->grade($question, '-4.2'));
        $this->assertEquals(0, $this->grade($question, '4.2'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', 'numeric_accepted_error');
        $this->assertEquals(1, $this->grade($question, '-4.2'));
        $this->assertEquals(0, $this->grade($question, '4.2'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '3_sig_figs');
        $this->assertEquals(1, $this->grade($question, '12300'));
        $this->assertEquals(1, $this->grade($question, '0012300'));
        $this->assertEquals(1, $this->grade($question, '123e2'));
        $this->assertEquals(1, $this->grade($question, '00123e2'));
        $this->assertEquals(1, $this->grade($question, '1.23e4'));
        $this->assertEquals(1, $this->grade($question, '123.e2'));
        $this->assertEquals(1, $this->grade($question, '12.3e3'));
        $this->assertEquals(1, $this->grade($question, '1.23e4'));
        $this->assertEquals(1, $this->grade($question, '0.123e5'));
        $this->assertEquals(1, $this->grade($question, '0.0123e6'));
        $this->assertEquals(1, $this->grade($question, '0.000123e8'));
        $this->assertEquals(0.9, $this->grade($question, '123450e-1'));
        $this->assertEquals(0.9, $this->grade($question, '123450000e-4'));
        $this->assertEquals(0, $this->grade($question, '123450000e-3'));
        $this->assertEquals(0.9, $this->grade($question, '001235e1')); // Correct to wrong amount of sig figs.
        $this->assertEquals(0, $this->grade($question, '001234e1'));   // Incorrect rounding.
        $this->assertEquals(0.9, $this->grade($question, '1235e1'));   // Correct to wrong amount of sig figs.
        $this->assertEquals(0.9, $this->grade($question, '123.5e2'));  // Correct to wrong amount of sig figs.
        $this->assertEquals(0.9, $this->grade($question, '0012345'));  // Correct to wrong amount of sig figs.
        $this->assertEquals(0.9, $this->grade($question, '12350'));    // Correct to wrong amount of sig figs.
        $this->assertEquals(0.9, $this->grade($question, '12345'));    // Correct to wrong amount of sig figs.

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '3_sig_figs_2');
        $this->assertEquals(1, $this->grade($question, '1.23'));
        $this->assertEquals(1, $this->grade($question, '01.23'));
        $this->assertEquals(0, $this->grade($question, '1.230'));   // Wrong.
        $this->assertEquals(0.9, $this->grade($question, '1.235')); // Wrong no of sig figs.
        $this->assertEquals(0.9, $this->grade($question, '1.2346'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '3_sig_figs_trailing_zero');
        $this->assertEquals(1, $this->grade($question, '0.0720'));
        $this->assertEquals(1, $this->grade($question, '00.0720'));
        $this->assertEquals(0.9, $this->grade($question, '00.07200'));
        $this->assertEquals(0.9, $this->grade($question, '+00.07200'));
        $this->assertEquals(1, $this->grade($question, '+0.0720'));
        $this->assertEquals(0, $this->grade($question, '+0.072'));
        $this->assertEquals(0, $this->grade($question, '0.072'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '3_sig_figs_trailing_zero_negative_answer');
        $this->assertEquals(1, $this->grade($question, '-0.0720'));
        $this->assertEquals(1, $this->grade($question, '-00.0720'));
        $this->assertEquals(0.9, $this->grade($question, '-00.07200'));
        $this->assertEquals(0.9, $this->grade($question, '-00.07200'));
        $this->assertEquals(0, $this->grade($question, '-0.072'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '3_sig_figs_point_0');
        $this->assertEquals(1, $this->grade($question, '12.0'));
        $this->assertEquals(1, $this->grade($question, '012.0'));
        $this->assertEquals(0, $this->grade($question, '12'));
        $this->assertEquals(0.9, $this->grade($question, '12.00'));
        $this->assertEquals(0, $this->grade($question, '13'));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', '1_sig_fig');
        $this->assertEquals(1, $this->grade($question, '1e9'));
        $this->assertEquals(1, $this->grade($question, '1x10<sup>9</sup>'));
        $this->assertEquals(1, $this->grade($question, '+1x10<sup>+9</sup>'));
        $question->answers[1]->answer = '-1.0e9';
        $this->assertEquals(1, $this->grade($question, '-1e9'));
        $this->assertEquals(1, $this->grade($question, '-1x10<sup>9</sup>'));
        $this->assertEquals(1, $this->grade($question, '-1x10<sup>+9</sup>'));
        $this->assertEquals(0, $this->grade($question, ''));

        /** @var qtype_varnumericset_question $question */
        $question = test_question_maker::make_question('varnumericset', 'sci_notation_formatted');
        $this->assertEquals(1, $this->grade($question, '1.200000 x 10<sup>1</sup>'));
        $this->assertEquals(1, $this->grade($question, '1.200000 x 10<sup>+1</sup>'));
        $this->assertEquals(0.75, $this->grade($question, '1.200000x 10<sup>1</sup>'));
        $this->assertEquals(0.75, $this->grade($question, '1.200000 x10<sup>1</sup>'));
        $this->assertEquals(0.75, $this->grade($question, '1.200000 x 10<sup>     1</sup>'));
        $this->assertEquals(0.75, $this->grade($question, '+  1.200000 x 10<sup>1</sup>'));
        $this->assertEquals(0.75, $this->grade($question, '1.200000 x 10<sup>+    1</sup>'));
    }

    public function test_get_question_summary(): void {
        $question = test_question_maker::make_question('varnumericset', 'with_variables');
        $question->start_attempt(new question_attempt_step(), 1);
        $this->assertEquals('What is 2 + 3?', $question->get_question_summary());
    }
}
