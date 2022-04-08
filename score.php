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
 * Percipio report file
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

// Hack to convert datefrom array to timestamp as after submitting filter mform it is sending datefrom in array format.
if (isset($_POST["datefrom"]) && is_array($_POST["datefrom"])) {
    $_POST["datefrom"] = strtotime($_POST["datefrom"]["day"].'-'.$_POST["datefrom"]["month"].'-'.$_POST["datefrom"]["year"]);
}

// Hack to convert dateto array to timestamp as after submitting filter mform it is sending dateto in array format.
if (isset($_POST["dateto"]) && is_array($_POST["dateto"])) {
    $_POST["dateto"] = strtotime($_POST["dateto"]["day"].'-'.$_POST["dateto"]["month"].'-'.$_POST["dateto"]["year"]);
}

$dateto = optional_param('dateto', time(), PARAM_ALPHANUM);
$datefrom = optional_param('datefrom', (time() - (86400 * 7)), PARAM_ALPHANUM);
$userid = optional_param('userid', 0, PARAM_INT);

$pageparams = '';
$columns = $headers = $param = [];

$columns[] = 'name';
$headers[] = get_string('name', 'mod_percipio');

$columns[] = 'email';
$headers[] = get_string('email', 'mod_percipio');

$columns[] = 'firstaccessed';
$headers[] = get_string('firstaccessed', 'mod_percipio');

$columns[] = 'lastaccessed';
$headers[] = get_string('lastaccessed', 'mod_percipio');

$columns[] = 'completiontime';
$headers[] = get_string('completiontime', 'mod_percipio');

$columns[] = 'totalduration';
$headers[] = get_string('totalduration', 'mod_percipio');

$columns[] = 'score';
$headers[] = get_string('score', 'mod_percipio');

$columns[] = 'status';
$headers[] = get_string('status', 'mod_percipio');


$table->define_columns($columns);
$table->define_headers($headers);


/**
 * Percipio report filter class
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class percipio_report extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {

        global $DB, $dateto, $datefrom, $userid, $COURSE;

        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('header', 'headername', 'Select Report Criteria');

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

$select = 'concat(u.firstname, \' \', u.lastname) as name, u.email, pa.timecreated as firstaccessed,
                     pa.timerevisited as lastaccessed, pa.timecompleted as completiontime, pa.totalduration,
                     pa.grade as score, pa.completionmessage as status';
$from = '{user} u join {percipio_attempt} pa on pa.userid = u.id';
$where = 'pa.cmid = ?';
$param[] = $id;

if (has_capability('mod/percipio:viewreports', $modulecontext)) {
    $mform = new percipio_report('view.php?id='.$id.'&tab=report');
    if ($fdata = $mform->get_data()) {
        if ($userid != 0) {
            $where .= " AND pa.userid = ?";
            $param[] = $userid;
        }
        if ($datefrom != 0) {
            $where .= " AND pa.timecompleted between ? AND ?";
            $param[] = $datefrom;
            $param[] = $dateto;
        }
    }
    if (!$table->is_downloading()) {
        $mform->display();
    }
} else if (has_capability('mod/percipio:viewmyreports', $modulecontext)) {
    $where .= " AND pa.userid = ?";
    $param[] = $USER->id;
}


$table->set_sql($select, $from, $where, $param);

$pageparams .= '&dateto='.$dateto;
$pageparams .= '&datefrom='.$datefrom;
if ($userid != 0) {
    $pageparams .= '&userid='.$userid;
}

$table->define_baseurl("$CFG->wwwroot/mod/percipio/view.php?id=$id&tab=report$pageparams");

$table->out(25, true);
