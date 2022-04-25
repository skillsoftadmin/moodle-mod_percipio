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

$postdata = data_submitted();

// When report filter form is submitted dateto POST parameter is coming in array.
if (!empty($postdata) && is_array($postdata->dateto) && empty($postdata->dateto) == false) {
    $dateto = optional_param_array('dateto', time(), PARAM_INT);
} else {
    // And in the report table pagination link dateto GET parameter is in timestamp format.
    $dateto = optional_param('dateto', time(), PARAM_INT);
}

// When report filter form is submitted datefrom POST parameter is coming in array.
if (!empty($postdata) && is_array($postdata->datefrom) && empty($postdata->datefrom) == false) {
    $datefrom = optional_param_array('datefrom', (time() - (86400 * 7)), PARAM_INT);
} else {
    // And in the report table pagination link datefrom GET parameter is in timestamp format.
    $datefrom = optional_param('datefrom', (time() - (86400 * 7)), PARAM_INT);
}

$userid = optional_param('userid', 0, PARAM_INT);
$showtable = true;

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


$select = 'concat(u.firstname, \' \', u.lastname) as name, u.email, pa.timecreated as firstaccessed,
                     pa.timerevisited as lastaccessed, pa.timecompleted as completiontime, pa.totalduration,
                     pa.grade as score, pa.completionmessage as status';
$from = '{user} u join {percipio_attempt} pa on pa.userid = u.id';
$where = 'pa.cmid = ?';
$param[] = $id;

if (has_capability('mod/percipio:viewreports', $modulecontext)) {
    $mform = new \mod_percipio\report_filter_form('view.php?id='.$id.'&tab=report');
    if ($fdata = $mform->get_data()) {
        if ($fdata->userid != 0) {
            $where .= " AND pa.userid = ?";
            $param[] = $fdata->userid;
        }
        if ($datefrom != 0) {
            $where .= " AND pa.timecompleted between ? AND ?";
            $datefrom = $param[] = $fdata->datefrom;
            $dateto = $param[] = $fdata->dateto;
        }
    } else {
        // If form validation error after submission do not display table.
        if (data_submitted()) {
            $showtable = false;
        }
    }
    if (!$table->is_downloading()) {
        $mform->display();
    }
} else if (has_capability('mod/percipio:viewmyreports', $modulecontext)) {
    $where .= " AND pa.userid = ?";
    $param[] = $USER->id;
}

if ($showtable) {
    $table->set_sql($select, $from, $where, $param);
    $pageparams .= '&dateto='.$dateto;
    $pageparams .= '&datefrom='.$datefrom;
    if ($userid != 0) {
        $pageparams .= '&userid='.$userid;
    }
    $table->define_baseurl("$CFG->wwwroot/mod/percipio/view.php?id=$id&tab=report$pageparams");
    $table->out(25, true);
}
