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
 * varnumeric question type renderer class.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for varnumeric question types.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_renderer_base extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');
        $generalattributes = array(
            'id' => $inputname,
            'class' => 'answer'
        );

        $size = 40;

        $feedbackimg = '';
        if ($options->correctness) {
            list($fraction, ) = $question->grade_response(array('answer' => $currentanswer));
            $generalattributes['class'] .= ' '.$this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        if (preg_match('/_____+/', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $size = round(strlen($placeholder) * 1.1);
        }

        $usehtml = false;
        if ($question->usesupeditor) {
            $editor = get_texteditor('supsub');
            if ($editor !== false) {
                $usehtml = true;
            }
        }

        if ($usehtml && $options->readonly) {
            $input = html_writer::tag('span', $currentanswer, $generalattributes);
        } else if ($usehtml) {
            $textareaattributes = array('name' => $inputname, 'rows' => 2, 'cols' => $size);
            $input = html_writer::tag('span', html_writer::tag('textarea', $currentanswer,
                    $textareaattributes + $generalattributes), array('class'=>'answerwrap'));
            $supsuboptions = array(
                'supsub' => 'sup'
            );
            $editor->use_editor($generalattributes['id'], $supsuboptions);
        } else {
            $inputattributes = array(
                'type' => 'text',
                'size' => $size,
                'name' => $inputname,
                'value' => $currentanswer
            );
            if ($options->readonly) {
                $inputattributes['readonly'] = 'readonly';
            }
            $input = html_writer::empty_tag('input', $inputattributes + $generalattributes);
        }
        $input .= $feedbackimg;

        if ($placeholder) {
            $questiontext = substr_replace($questiontext, $input,
                    strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$placeholder) {
            $result .= html_writer::start_tag('div', array('class' => 'ablock'));
            $result .= get_string('answer', 'qtype_varnumericset',
                    html_writer::tag('div', $input, array('class' => 'answer')));
            $result .= html_writer::end_tag('div');
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
        if (!$answer || !$answer->feedback) {
            return '';
        }

        return $question->format_text($answer->feedback, $answer->feedbackformat,
                $qa, 'question', 'answerfeedback', $answer->id);
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_correct_answer();
        if (!$answer) {
            return '';
        }

        return get_string('correctansweris', 'qtype_varnumericset', $answer->answer);
    }
}
