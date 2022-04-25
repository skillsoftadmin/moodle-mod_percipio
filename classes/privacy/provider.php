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
 * Privacy Subsystem implementation for mod_percipio
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_percipio\privacy;
defined('MOODLE_INTERNAL') || die();

use context;
use context_helper;
use context_module;
use moodle_recordset;
use stdClass;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Implementation of the privacy subsystem plugin provider for mod_percipio.
 *
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('percipio_attempt', [
            'userid' => 'privacy:metadata:percipio_attempt:userid',
            'grade' => 'privacy:metadata:percipio_attempt:grade',
            'completionmessage' => 'privacy:metadata:percipio_attempt:completionmessage',
            'passingscore' => 'privacy:metadata:percipio_attempt:passingscore',
            'lastscore' => 'privacy:metadata:percipio_attempt:lastscore',
            'percentcomplete' => 'privacy:metadata:percipio_attempt:percentcomplete',
            'timecreated' => 'privacy:metadata:percipio_attempt:timecreated',
            'timecompleted' => 'privacy:metadata:percipio_attempt:timecompleted',
            'timerevisited' => 'privacy:metadata:percipio_attempt:timerevisited',
            'timemodified' => 'privacy:metadata:percipio_attempt:timemodified',
            'totalduration' => 'privacy:metadata:percipio_attempt:totalduration',
        ], 'privacy:metadata:percipio_attempt');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "
            SELECT DISTINCT ctx.id
              FROM {percipio} p
              JOIN {modules} m
                ON m.name = :percipio
              JOIN {course_modules} cm
                ON cm.instance = p.id
               AND cm.module = m.id
              JOIN {context} ctx
                ON ctx.instanceid = cm.id
               AND ctx.contextlevel = :modulelevel
              JOIN {percipio_attempt} pa
                ON pa.cmid = cm.id
             WHERE pa.userid = :userid";

        $params = [
            'percipio' => 'percipio',
            'modulelevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'instanceid'    => $context->instanceid,
            'modulename'    => 'percipio',
        ];

        $sql = "SELECT pa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {percipio} p ON p.id = cm.instance
                  JOIN {percipio_attempt} pa ON pa.cmid = cm.id
                 WHERE cm.id = :instanceid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        $percipioidstocmids = static::get_percipio_ids_to_cmids_from_cmids($cmids);
        $percipiocmids = array_values($percipioidstocmids);

        // Export the attempts.
        list($insql, $inparams) = $DB->get_in_or_equal($percipiocmids, SQL_PARAMS_NAMED);

        $params = array_merge($inparams, ['userid' => $userid]);
        $recordset = $DB->get_recordset_select('percipio_attempt', "cmid $insql AND userid = :userid", $params, 'timemodified, id');
        static::recordset_loop_and_export($recordset, 'cmid', [], function($carry, $record) use ($user, $percipioidstocmids) {
            $carry[] = [
                'grade' => $record->grade,
                'completionmessage' => $record->completionmessage,
                'lastscore' => $record->lastscore,
                'percentcomplete' => $record->percentcomplete,
                'timecompleted' => transform::datetime($record->timecompleted),
                'timerevisited' => transform::datetime($record->timerevisited),
            ];
            return $carry;

        }, function($percipiocmid, $data) use ($user, $percipioidstocmids) {
            $context = context_module::instance($percipiocmid);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = (object) array_merge((array) $contextdata, ['attempts' => $data]);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('percipio', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records_select('percipio_attempt', 'cmid = :cmid', ['cmid' => $cm->id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        $percipioidstocmids = static::get_percipio_ids_to_cmids_from_cmids($cmids);
        $percipiocmids = array_values($percipioidstocmids);

        // Export the attempts.
        list($insql, $inparams) = $DB->get_in_or_equal($percipiocmids, SQL_PARAMS_NAMED);

        $params = array_merge($inparams, ['userid' => $userid]);
        $sql = "cmid $insql AND userid = :userid";

        $DB->delete_records_select('percipio_attempt', $sql, $params);
    }


    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['cmid' => $cm->id], $userinparams);
        $sql = "cmid = :cmid AND userid {$userinsql}";

        $DB->delete_records_select('percipio_attempt', $sql, $params);
    }

    /**
     * Return a dict of percipio IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$percipioid => $cmid].
     */
    protected static function get_percipio_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT p.id, cm.id AS cmid
              FROM {percipio} p
              JOIN {modules} m
                ON m.name = :percipio
              JOIN {course_modules} cm
                ON cm.instance = p.id
               AND cm.module = m.id
             WHERE cm.id $insql";
        $params = array_merge($inparams, ['percipio' => 'percipio']);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    protected static function recordset_loop_and_export(moodle_recordset $recordset, $splitkey, $initial,
            callable $reducer, callable $export) {

        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }

}
