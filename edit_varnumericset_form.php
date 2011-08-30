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
 * Defines the editing form for the varnumericset question type.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumericset/edit_varnumericset_form_base.php');

/**
 * variable numeric set question editing form definition.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumericset_edit_form extends qtype_varnumeric_edit_form_base {
    public function qtype() {
        return 'varnumericset';
    }

    protected function add_value_form_fields($mform, $repeated, $repeatedoptions) {
        global $DB;
        $noofvariants = optional_param('noofvariants', 0, PARAM_INT);
        $addvariants = optional_param('addvariants', '', PARAM_TEXT);
        if ($addvariants) {
            $noofvariants += 2;
        }
        if (isset($this->question->id)) {
            $prefix = $this->db_table_prefix();
            $sql = 'SELECT MAX(vari.variantno)+1 '.
                    "FROM {{$prefix}_variants} vari, {{$prefix}_vars} vars ".
                    'WHERE vars.questionid = ? AND vars.id = vari.varid';
            $noofvariantsindb = $DB->get_field_sql($sql, array($this->question->id));
        } else {
            $noofvariantsindb = 0;
        }

        if ($this->question->formoptions->repeatelements) {
            $noofvariants = max($noofvariants, 5, $noofvariantsindb + 2);
        } else {
            $noofvariants = max(5, $noofvariantsindb);
        }
        for ($i=0; $i < $noofvariants; $i++) {
            $repeated[] = $mform->createElement('text', "variant$i",
                    get_string('variant', 'qtype_varnumericset', $i+1), array('size' => 40));
            $repeatedoptions["variant$i"]['disabledif'] = array('vartype', 'eq', 0);
            if ($i == 0) {
                $repeatedoptions["variant$i"]['helpbutton']
                                                        = array('variants', 'qtype_varnumericset');
            }
            $mform->setType("variant$i", PARAM_RAW_TRIMMED);
        }
        $mform->addElement('hidden', 'noofvariants', $noofvariants);
        $mform->setConstant('noofvariants', $noofvariants);
        $mform->setType('noofvariants', PARAM_INT);
        return array($repeated, $repeatedoptions);
    }

    protected function definition_inner($mform) {
        parent::definition_inner($mform);
        //add a button to add more form fields for variants
        $mform->registerNoSubmitButton('addvariants');
        $addvariantel = $mform->createElement('submit', 'addvariants',
                                        get_string('addmorevariants', 'qtype_varnumericset', 2));
        $mform->insertElementBefore($addvariantel, 'varhdr[1]');
    }
}
