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
 * @package    moodlecore
 * @subpackage backup-moodle2
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * restore plugin class that provides the necessary information
 * needed to restore one varnumericset qtype plugin
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_varnumericset_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {

        $paths = array();

        // This qtype uses question_answers, add them
        $this->add_question_question_answers($paths);

        $elements = array('qtype_varnumericset' => '/varnumericset',
                    'qtype_varnumericset_answer' => '/varnumericset_answers/varnumericset_answer',
                    'qtype_varnumericset_var' => '/vars/var',
                    'qtype_varnumericset_variant' => '/vars/var/variants/variant');
        foreach ($elements as $elename => $path) {
            $elepath = $this->get_pathfor($path);
            $paths[] = new restore_path_element($elename, $elepath);
        }

        return $paths; // And we return the interesting paths
    }


    /**
     * Process the qtype/varnumericset element
     */
    public function process_qtype_varnumericset($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // If the question has been created by restore, we need to create its
        // question_varnumericset too
        if ($questioncreated) {
            // Adjust some columns
            $data->questionid = $newquestionid;

            // Insert record
            $newitemid = $DB->insert_record('qtype_varnumericset', $data);
            // Create mapping
            $this->set_mapping('qtype_varnumericset', $oldid, $newitemid);
        }
    }

    /**
     * Process the qtype/varnumericset_answer element
     */
    public function process_qtype_varnumericset_answer($data) {
        global $DB;

        $data = (object)$data;

        $data->answerid = $this->get_mappingid('question_answer', $data->answerid);

        // Detect if the question is created
        $oldquestionid   = $this->get_old_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;
        if ($questioncreated) {
            // Insert record
            $newitemid = $DB->insert_record('qtype_varnumericset_answers', $data);
            // Create mapping
            $this->set_mapping('qtype_varnumericset_answer', $data->id, $newitemid);
        }
    }
    public function process_qtype_varnumericset_var($data) {
        global $DB;

        $data = (object)$data;

        // Detect if the question is created
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;
        if ($questioncreated) {
            $data->questionid = $newquestionid;
            // Insert record
            $newitemid = $DB->insert_record('qtype_varnumericset_vars', $data);
            // Create mapping
            $this->set_mapping('qtype_varnumericset_var', $data->id, $newitemid);
        }
    }

    public function process_qtype_varnumericset_variant($data) {
        global $DB;

        $data = (object)$data;

        $data->varid = $this->get_new_parentid('qtype_varnumericset_var');

        // Detect if the question is created
        $oldquestionid   = $this->get_old_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;
        if ($questioncreated) {

            // Insert record
            $newitemid = $DB->insert_record('qtype_varnumericset_variants', $data);
            // Create mapping
            $this->set_mapping('qtype_varnumericset_variant', $data->id, $newitemid);
        }
    }

}
