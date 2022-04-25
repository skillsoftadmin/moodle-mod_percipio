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
 * * Define all the restore steps that will be used by the restore_percipio_activity_task
 *
 * @package    mod_percipio
 * @subpackage backup-moodle2
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Structure step to restore one percipio activity
 */
class restore_percipio_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return the structure.
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('percipio', '/activity/percipio');
        if ($userinfo) {
            $paths[] = new restore_path_element('percipio_attempt', '/activity/percipio/attempts/attempt');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the percipio table data.
     *
     * @param array $data
     */
    protected function process_percipio($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insert the percipio record.
        $newitemid = $DB->insert_record('percipio', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore the percipio attempt table data.
     *
     * @param array $data
     */
    protected function process_percipio_attempt($data) {
        global $DB;

        $data = (object)$data;

        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('percipio_attempt', $data);
        // No need to save this mapping as far as nothing depend on it.
        // (child paths, file areas nor links decoder).
    }

    /**
     * After execution add percipio related files.
     *
     */
    protected function after_execute() {
        // Add percipio related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_percipio', 'intro', null);
    }
}
