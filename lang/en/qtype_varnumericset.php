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
 * Strings for component 'qtype_varnumericset', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addmorevariants'] = 'Add {$a} More Blanks for More Variants';
$string['addmorevars'] = 'Add {no} More Blanks for Variables';
$string['addingvarnumericset'] = 'Adding a Variable Numeric Set Question';
$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['ae_numericallycorrect'] = 'Your answer is almost correct. You have the correct value and it is rounded correctly.';
$string['ae_numericallycorrectandwrongformat'] = 'Your answer is almost correct. You have the correct value and it is rounded correctly but it is not in scientific notation.';
$string['ae_roundingincorrect'] = 'Your answer is almost correct. But it is rounded incorrectly.';
$string['ae_roundingincorrectandwrongformat'] = 'Your answer is almost correct. But it is rounded incorrectly and it is not in scientific notation.';
$string['ae_toomanysigfigs'] = 'Your answer is almost correct. But it is given to too many significant figures.';
$string['ae_toomanysigfigsandwrongformat'] = 'Your answer is almost correct. But it is given to too many significant figures and it is not in scientific notation.';
$string['ae_wrongbyfactorof10'] = 'Your answer is almost correct. But you have the factor of 10 wrong.';
$string['ae_wrongbyfactorof10andwrongformat'] = 'Your answer is almost correct. But you have the factor of 10 wrong and it is not in scientific notation.';
$string['answer'] = 'Answer: {$a}';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Answer {$a}';
$string['autofirehdr'] = 'Give feedback and partial credit where answer {$a} is partially wrong';
$string['calculatewhen'] = 'When to calculate calculated values';
$string['checknumerical'] = 'If Numerically Correct';
$string['checkpowerof10'] = 'If Power of 10 is Off';
$string['checkrounding'] = 'If Rounding Incorrect';
$string['checkscinotation'] = 'If Scientific Notation Required But Not Used';
$string['errorreportedbyexpressionevaluator'] = 'Expression Evaluation Error : {$a}';
$string['expressionmustevaluatetoanumber'] = 'You should enter an expression that evaluates to a number here, not an assignment';
$string['correctansweris'] = 'The correct answer is: {$a}';
$string['correctansweriserror'] = '{$a->answer} <sup>+</sup>/<sub>-</sub> {$a->error}';
$string['correctanswerissigfigs'] = '{$a->answer} ({$a->sigfigs} significant figures)';
$string['correctanswers'] = 'Correct answers';
$string['editingvarnumericset'] = 'Editing a Variable Numeric Set Question';
$string['error'] = 'Accepted Error +/-';
$string['expectingassignment'] = 'You must use a mathematical expression to assign a value to a \'Calculated Variable\'.';
$string['expectingvariablename'] = 'Expecting a variable name here';
$string['expressionevaluatesasinfinite'] = 'Result is infinite.';
$string['expressionevaluatesasnan'] = 'Result is not a number.';
$string['filloutoneanswer'] = 'You must provide at least one possible answer. Answers left blank will not be used. \'*\' can be used as a wildcard to match any number. The first matching answer will be used to determine the score and feedback.';
$string['forallanswers'] = 'For all answers';
$string['hintoverride'] = 'If auto-check fires allow another try but do not show this hint or apply this penalty';
$string['illegalthousandseparator'] = 'You have used an illegal thousands separator "{$a->thousandssep}" in your answer. We only accept answers with a decimal separator "{$a->decimalsep}".';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['notvalidnumber'] = 'You have not entered a number in a recognised format.';
$string['options'] = 'Options';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['questiontext'] = 'Question Text and Embedded Variables';
$string['questiontext_help'] = 'You can embed variable names and expressions in question text, general feedback, answer feedback and hints

Anything enclosed in double square brackets will be evaluated before being displayed. Eg. if you enter, for example, [[a]] then the value of the variable a will be displayed. [[log(a)]] will display the log of a.

You can also specify how to display the result using printf codes. For example [[a,.3e]] will display the value of a in scientific notation with 4 significant figures.';
$string['randomseed'] = 'String To Act As a Seed For Randomisation';
$string['recalculateeverytime'] = 'Recalculate Values for Expressions';
$string['recalculateeverytimeno'] = 'only when there are changes or when \'Recalculate Now\' button is pressed';
$string['recalculateeverytimeyes'] = 'every time this form is displayed or the question is attempted';
$string['recalculateeverytime_help'] = 'This setting controls when calculated values are recalculated.

You can select to only calculate calculated values when the defined values or any variables are changed, or when the recalculate button is pressed. Then any random values will be the same for all users.

Or you can recalculate the calculated values every time this form or the question is displayed, then any random values will be different for every user.';
$string['recalculatenow'] = 'Recalculate Now';
$string['requirescinotation'] = 'Require Scientific Notation';
$string['sigfigs'] = 'Significant Figures';
$string['syserrorpenalty'] = 'For Each Error Deduct';
$string['unspecified'] = 'Unspecified';
$string['usesuperscript'] = 'Use Superscript Entry';
$string['varheader'] = 'Variable {no}';
$string['variant'] = 'Value for Variant {$a}';
$string['variants'] = 'Value for Variants';
$string['variants_help'] = 'Enter values for \'Predefined Variables\' here or you will see calculated values displayed here for a \'Calculated Variable\'.

For a predefined variable you must enter a value for the first variant. For other variants if you do not enter a value then the last entered variant value will be used. Ie. if you enter 2.5 for variant 1 but don\'t enter a value for variant 2 or 3 then variants 2 and 3 will have a value of 1.

Moodle will determine how many variants are required by looking at the greatest variant no of the predefined values entered. Ie. if one variable has a value for variant 6, then there must be 6 variants and if the other variables\' variant values have not been entered, they will be assumed to be the same as the last value entered.';
$string['varname'] = 'Name or assignment';
$string['varname_help'] = 'For a \'Predefined Variable\' you enter only a variable name here e.g. \'a\'. Then enter the values for this variable for each question variant below.

Or for a \'Calculated Variable\' enter a variable name and assign it a value from an expression e.g. \'b = a^4\' (where \'a\' is a previously defined variable).';
$string['varnumericset'] = 'Variable Numeric Set';
$string['varnumericset_help'] = 'In response to a question the respondent types a number. Numbers used in the question and used to calculate the answer are chosen from predefined sets which can be precalculated from mathematical expressions.';
$string['varnumericset_link'] = 'question/type/varnumericset';
$string['varnumericsetsummary'] = 'Allows a numeric response';
$string['vartypecalculated'] = 'Calculated Variable';
$string['vartypepredefined'] = 'Predefined Variable';
$string['youmustprovideavalueforfirstvariant'] = 'You must provide a value for at least the first variant for a \'Predefined Variable\'';