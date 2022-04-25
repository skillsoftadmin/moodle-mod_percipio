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
 * This file contains all the backup steps that will be used by the backup_percipio_activity_task
 *
 * @package mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_percipio_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return the structure.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $percipio = new backup_nested_element('percipio', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated',
            'timemodified', 'grade', 'urltype', 'btntxt', 'launchurl',
            'percipiotype', 'displaylabel', 'additionalinfo'));

        $attempts = new backup_nested_element('attempts');

        $attempt = new backup_nested_element('attempt', array('id'), array(
            'userid', 'cmid', 'grade', 'completionmessage', 'passingscore', 'lastscore',
            'percentcomplete', 'timecreated', 'timecompleted', 'timerevisited', 'timemodified', 'totalduration'));

        $percipio->add_child($attempts);
        $attempts->add_child($attempt);

        // Define sources.
        $percipio->set_source_table('percipio', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $attempt->set_source_table('percipio_attempt', array('cmid' => backup::VAR_MODID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');

        // Define file annotations.
        $percipio->annotate_files('mod_percipio', 'intro', null); // This file area hasn't itemid.

        // Return the root element (percipio), wrapped into standard activity structure.
        return $this->prepare_activity_structure($percipio);
    }
}
