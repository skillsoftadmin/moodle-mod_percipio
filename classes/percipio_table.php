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
 * Percipio report table.
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_percipio;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use table_sql;

/**
 * percipio_table report class.
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class percipio_table extends table_sql {

    /**
     * checks the firstaccessed and returns in date format
     *
     * @param stdClass $record the form data to be modified.
     */
    public function col_firstaccessed($record) {
        if (isset($record->firstaccessed)) {
            return userdate($record->firstaccessed, get_string('strftimedatetime'));
        } else {
            return '';
        }
    }

    /**
     * checks the lastaccessed and returns in date format
     *
     * @param stdClass $record the form data to be modified.
     */
    public function col_lastaccessed($record) {
        if (isset($record->lastaccessed)) {
            return userdate($record->lastaccessed, get_string('strftimedatetime'));
        } else {
            return '';
        }
    }

    /**
     * checks the completiontime and returns in date format
     *
     * @param stdClass $record the form data to be modified.
     */
    public function col_completiontime($record) {
        if (isset($record->completiontime)) {
            return userdate($record->completiontime, get_string('strftimedatetime'));
        } else {
            return '';
        }
    }

    /**
     * checks the score and returns in date format
     *
     * @param stdClass $record the form data to be modified.
     */
    public function col_score($record) {
        if (isset($record->score)) {
            return $record->score;
        } else {
            return '';
        }
    }

    /**
     * checks the status and returns in date format
     *
     * @param stdClass $record the form data to be modified.
     */
    public function col_status($record) {
        if (isset($record->status)) {
            return ucfirst($record->status);
        } else {
            return '';
        }
    }
}
