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
 * Display information about all the mod_percipio modules in the requested course.
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/percipio/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_percipio');
echo $OUTPUT->heading($modulenameplural);

$percipios = get_all_instances_in_course('percipio', $course);

if (empty($percipios)) {
    notice(get_string('nonewmodules', 'mod_percipio'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'), get_string('reportlink', 'mod_percipio'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'), get_string('reportlink', 'mod_percipio'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'), get_string('reportlink', 'mod_percipio'));
    $table->align = array('left', 'left', 'left');
}

foreach ($percipios as $percipio) {
    if (!$percipio->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/percipio/view.php', array('id' => $percipio->coursemodule)),
            format_string($percipio->name, true),
            array('class' => 'dimmed'));
        $reportlink = html_writer::link(
            new moodle_url('/mod/percipio/view.php', array('id' => $percipio->coursemodule, 'tab' => 'report')),
            'View',
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/percipio/view.php', array('id' => $percipio->coursemodule)), format_string($percipio->name, true));
        $reportlink = html_writer::link(
            new moodle_url('/mod/percipio/view.php', array('id' => $percipio->coursemodule, 'tab' => 'report')), 'View');
    }
    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($percipio->section, $link, $reportlink);
    } else {
        $table->data[] = array($link, $reportlink);
    }
}
echo html_writer::table($table);
echo $OUTPUT->footer();
