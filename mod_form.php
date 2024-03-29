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
 * The main mod_percipio configuration form.
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_percipio_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('percipioname', 'mod_percipio'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'percipioname', 'mod_percipio');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('hidden', 'launchurl');
        $mform->setType('launchurl', PARAM_URL);

        $mform->addElement('hidden', 'urltype');
        $mform->setDefault('urltype', 'tincan');
        $mform->setType('urltype', PARAM_TEXT);

        $mform->addElement('hidden', 'btntxt');
        $mform->setType('btntxt', PARAM_TEXT);

        $mform->addElement('hidden', 'percipiotype');
        $mform->setType('percipiotype', PARAM_TEXT);

        $mform->addElement('hidden', 'displaylabel');
        $mform->setType('displaylabel', PARAM_TEXT);

        $mform->addElement('hidden', 'additionalinfo');
        $mform->setType('additionalinfo', PARAM_RAW);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        if (property_exists($this->current, 'grade')) {
            $currentgrade = $this->current->grade;
        } else {
            $currentgrade = 100;
        }
        $mform->addElement('hidden', 'grade', $currentgrade);
        $mform->setType('grade', PARAM_FLOAT);

        $mform->removeElement('gradecat');
        $mform->removeElement('gradepass');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Defines the validation of the form elements
     *
     * @param stdClass $data the form data to be modified.
     * @param stdClass $files the file data to be modified.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
