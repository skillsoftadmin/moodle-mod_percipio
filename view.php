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
 * Prints an instance of mod_percipio.
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require(__DIR__.'/classes/percipio_table.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$c  = optional_param('c', 0, PARAM_INT);


$tab = optional_param('tab', 'details', PARAM_TEXT);

$download = optional_param('download', '', PARAM_ALPHA);

if ($id) {
    $cm             = get_coursemodule_from_id('percipio', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('percipio', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($c) {
    $moduleinstance = $DB->get_record('percipio', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('percipio', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception(get_string('missingidandcmid', 'mod_percipio'));
}

require_login($course, true, $cm);
global $CFG; $PAGE; $DB; $USER;
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$PAGE->set_url('/mod/percipio/view.php', array('id' => $cm->id));

$table = new percipio_table('percipio_report');
$table->is_downloading($download, $course->shortname, $course->shortname);

$viewtabs = [];

$actdetailurl = new moodle_url('/mod/percipio/view.php', ['id' => $cm->id, 'tab' => 'details']);
$viewtabs[] = new tabobject('details', $actdetailurl->out(), get_string('coursedetails', 'mod_percipio'));

$reporturl = new moodle_url('/mod/percipio/view.php', ['id' => $cm->id, 'tab' => 'report']);
$viewtabs[] = new tabobject('report', $reporturl->out(), get_string('report', 'mod_percipio'));


if (!$table->is_downloading()) {
    $PAGE->set_title(format_string($moduleinstance->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();
    print_tabs([$viewtabs], $tab);
}

if ($tab == 'details') {
    require_once('launch.php');
}

if ($tab == 'report') {
    require_once('score.php');
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
