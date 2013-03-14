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
 * @package   qtype_varnumericset
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Provides the information to backup varnumericset questions.
 *
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qtype_varnumericset_plugin extends backup_qtype_plugin {

    /**
     * Returns the qtype information to attach to question element
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'varnumericset');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // This qtype uses standard question_answers, add them here
        // to the tree before any other information that will use them.
        $this->add_question_question_answers($pluginwrapper);

        // Extra answer fields for varnumericset question type.
        $this->add_question_qtype_varnumericset_answers($pluginwrapper);

        $this->add_question_qtype_varnumericset_vars($pluginwrapper);

        // Now create the qtype own structures.
        $varnumericset = new backup_nested_element('varnumericset', array('id'), array(
            'randomseed', 'requirescinotation'));

        // Now the own qtype tree.
        $pluginwrapper->add_child($varnumericset);

        // Set source to populate the data.
        $varnumericset->set_source_table('qtype_varnumericset',
                array('questionid' => backup::VAR_PARENTID));

        // Don't need to annotate ids nor files.

        return $plugin;
    }

    protected function add_question_qtype_varnumericset_vars($element) {
        // Check $element is one nested_backup_element.
        if (! $element instanceof backup_nested_element) {
            throw new
                    backup_step_exception('qtype_varnumericset_vars_bad_parent_element', $element);
        }

        // Define the elements.
        $vars = new backup_nested_element('vars');
        $var = new backup_nested_element('var', array('id'),
                                                array('varno', 'nameorassignment'));

        $this->add_question_qtype_varnumericset_variants($var);

        // Build the tree.
        $element->add_child($vars);
        $vars->add_child($var);

        // Set source to populate the data.
        $var->set_source_table('qtype_varnumericset_vars',
                                                array('questionid' => backup::VAR_PARENTID));
    }

    protected function add_question_qtype_varnumericset_variants($element) {
        // Check $element is one nested_backup_element.
        if (! $element instanceof backup_nested_element) {
            throw new backup_step_exception('qtype_varnumericset_variants_bad_parent_element',
                                                                                        $element);
        }

        // Define the elements.
        $variants = new backup_nested_element('variants');
        $variant = new backup_nested_element('variant', array('id'),
                                                array('varid', 'variantno', 'value'));

        // Build the tree.
        $element->add_child($variants);
        $variants->add_child($variant);

        // Set source to populate the data.
        $variant->set_source_table('qtype_varnumericset_variants',
                                                array('varid' => backup::VAR_PARENTID));
    }
    protected function add_question_qtype_varnumericset_answers($element) {
        // Check $element is one nested_backup_element.
        if (! $element instanceof backup_nested_element) {
            throw new backup_step_exception('question_varnumericset_answers_bad_parent_element',
                                                $element);
        }

        // Define the elements.
        $answers = new backup_nested_element('varnumericset_answers');
        $answer = new backup_nested_element('varnumericset_answer', array('id'), array(
            'answerid', 'error', 'sigfigs', 'checknumerical', 'checkscinotation',
            'checkpowerof10', 'checkrounding', 'syserrorpenalty'));

        // Build the tree.
        $element->add_child($answers);
        $answers->add_child($answer);

        // Set the sources.
        $answer->set_source_sql('
                SELECT vans.*
                FROM {question_answers} AS ans, {qtype_varnumericset_answers} AS vans
                WHERE ans.question = :question AND ans.id = vans.answerid
                ORDER BY id',
                array('question' => backup::VAR_PARENTID));
        // Don't need to annotate ids nor files.
    }
}
