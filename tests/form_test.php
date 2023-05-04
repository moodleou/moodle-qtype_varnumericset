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

/**
 * Unit tests for the varnumericset question edit form.
 *
 * @package qtype_varnumericset
 * @copyright 2023 The Open University
 * @license  https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/varnumericset/question.php');


/**
 * Unit tests for the qtype_varnumericset question edit form.
 *
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group qtype_varnumericset
 * @covers qtype_varnumericset_edit_form
 */
class form_test extends \advanced_testcase {

    /**
     * Prepare test data.
     *
     * @param string $qtype Question type.
     * @return object Moodle form object.
     */
    protected function prepare_test_data(string $qtype): object {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $context = \context_course::instance($course->id);

        $contexts = $this->question_edit_contexts($context);
        $category = question_make_default_categories($contexts->all());

        $question = new \stdClass();
        $question->category = $category->id;
        $question->contextid = $category->contextid;
        $question->qtype = $qtype;
        $question->createdby = 1;
        $question->questiontext = 'varnumericset question type';
        $question->timecreated = '1234567890';
        $question->formoptions = new \stdClass();
        $question->formoptions->canedit = true;
        $question->formoptions->canmove = true;
        $question->formoptions->cansaveasnew = false;
        $question->formoptions->repeatelements = true;

        $qtypeobj = \question_bank::get_qtype($question->qtype);

        $mform = $qtypeobj->create_editing_form('question.php', $question, $category, $contexts, true);
        return $mform;
    }

    /**
     * Retrieve the context object.
     *
     * @param \context $context The current context.
     * @return object The context object.
     */
    private function question_edit_contexts(\context $context): object {
        if (class_exists('\core_question\local\bank\question_edit_contexts')) {
            $contexts = new \core_question\local\bank\question_edit_contexts($context);
        } else {
            $contexts = new \question_edit_contexts($context);
        }

        return $contexts;
    }

    /**
     * Test editing form validation with wrong variables format.
     *
     * @dataProvider form_validation_testcases
     * @param array $fromform Submitted responses.
     * @param array $expectederrors Expected result.
     */
    public function test_form_validation(array $fromform, array $expectederrors): void {
        $mform = $this->prepare_test_data('varnumericset');

        $defaults = [
            'category' => '6,21',
            'answer' => ['y', 'y'],
            'fraction' => ['1.0', '0.0'],
            'varname' => ['x', 'y = x * 299792458 * 86400 * 365.2422'],
            'vartype' => ['1.0', '0.0'],
            'noofvariants' => 5,
            'variant0' => ['1.0'],
            'variant1' => ['2.0'],
            'error' => ['0.05*y', '0.05*y'],
        ];
        $fromform = array_merge($defaults, $fromform);
        $this->assertEquals($expectederrors, $mform->validation($fromform, []));
    }

    /**
     * Data provider for {@see form_validation_testcases()}.
     *
     * @return array List of data sets (test cases).
     */
    public function form_validation_testcases(): array {
        return [
            'All OK' => [
                [
                    'questiontext' => [
                        'text' => '[[x]]',
                    ],
                    'generalfeedback' => [
                        'text' => '[[x,.e]]',
                    ],
                    'feedback' => [
                        ['text' => '[[x,f]]'],
                        ['text' => '[[x,.03f]]'],
                    ],
                    'hint' => [
                        ['text' => '[[x]]'],
                        ['text' => '[[x]]'],
                    ],
                ],
                [],
            ],
            'Different errors' => [
                [
                    'questiontext' => [
                        'text' => 'Bad: [[x.e]]. OK: [[x,.03f]]',
                    ],
                    'generalfeedback' => [
                        'text' => 'Each [[x.e]] different [[x.e]] error [[x.e]] only [[x.e]] reported [[x.e]] once.',
                    ],
                    'feedback' => [
                        ['text' => "No non-breaking space: [[x,&nbsp;.e]]"],
                        ['text' => 'Format missing [[x,]]'],
                    ],
                    'hint' => [
                        ['text' => 'Unknown format specifier [[x,i]]'],
                        ['text' => "Weird special character [[x,.3'.09 f]]"],
                        ['text' => "Multiple errors: [[x,]] [[x,i]]"],
                    ],
                ],
                [
                    'questiontext' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            '[[x.e]] - Expression evaluation error: an unexpected error occurred.'),
                    'generalfeedback' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            '[[x.e]] - Expression evaluation error: an unexpected error occurred.'),
                    'feedback[0]' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            "[[x,&nbsp;.e]] - The format specifier must not contain non-breaking space characters."),
                    'feedback[1]' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            '[[x,]] - Missing format specifier at end of string.'),
                    'hint[0]' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            '[[x,i]] - Unknown format specifier &quot;i&quot;.'),
                    'hint[1]' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            '[[x,.3\'.09 f]] - Unknown format specifier &quot;&#039;&quot;.'),
                    'hint[2]' => get_string('errorvalidationformatnumber', 'qtype_varnumericset',
                                            "<ul>\n<li>[[x,]] - Missing format specifier at end of string.</li>\n" .
                                            "<li>[[x,i]] - Unknown format specifier &quot;i&quot;.</li>\n</ul>"),
                ],
            ],
            [
                'Require at least 1 pre-define variable' => [
                    'varname' => [
                        'x = 4',
                        'y = 2',
                    ],
                    'vartype' => ['0.0', '0.0'],
                    'variant0' => [],
                    'variant1' => [],
                ],
                [
                    'vartype[0]' => 'At least one of the variables must be a predefined variable.'
                ]
            ]
        ];
    }
}
