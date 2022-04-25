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
 * Percipio report filter
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_percipio;
defined('MOODLE_INTERNAL') || die();

use moodleform;
use html_writer;

/**
 * Percipio report filter class
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {

        global $DB, $dateto, $datefrom, $userid, $COURSE;

        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('header', 'headername', get_string('reportcriteria', 'mod_percipio'));

        $users = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname, u.email
                                     FROM {course} c
                                     JOIN {context} ct ON c.id = ct.instanceid
                                     JOIN {role_assignments} ra ON ra.contextid = ct.id
                                     JOIN {user} u ON u.id = ra.userid
                                     JOIN {role} r ON r.id = ra.roleid
                                     where c.id = ?', array($COURSE->id));
        $data = array();
        $data[0] = get_string('selectuser', 'mod_percipio');
        foreach ($users as $user) {
            $data[$user->id] = $user->firstname." ".$user->lastname." (".$user->email.")";
        }
        $mform->addElement('html', html_writer::start_div('col-md-6'));
        $mform->addElement('autocomplete', 'userid', get_string('selectuser', 'mod_percipio'), $data);
        $mform->setDefault('userid', $userid);
        $mform->addElement('html', html_writer::end_div());

        $mform->addElement('html', html_writer::start_div('col-md-6'));
        $mform->addElement('date_selector', 'datefrom', get_string('datefrom', 'mod_percipio'));
        $mform->setDefault('datefrom', $datefrom);
        $mform->addElement('html', html_writer::end_div());

        $mform->addElement('html', html_writer::start_div('col-md-6'));
        $mform->addElement('date_selector', 'dateto', get_string('dateto', 'mod_percipio'));
        $mform->setDefault('dateto', $dateto);
        $mform->addElement('html', html_writer::end_div());

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Filter');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('headername');
    }

    /**
     * Defines the validation of the form elements
     *
     * @param stdClass $data the form data to be modified.
     * @param stdClass $files the file data to be modified.
     */
    public function validation($data, $files) {
        $errors = array();
        $fromdate = $data['datefrom'];
        $todate = $data['dateto'];
        if ($todate < $fromdate) {
            $errors['dateto'] = get_string('dateerror', 'mod_percipio');
        }
        return $errors;
    }
}
