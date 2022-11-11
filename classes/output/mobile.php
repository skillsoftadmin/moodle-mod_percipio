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
 * This file contains percipio mobile code
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



namespace mod_percipio\output;


defined('MOODLE_INTERNAL') || die;

use context_module;
use html_writer;
/**
 * Percipio mobile class
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Returns the percipio course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $CFG, $OUTPUT, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('percipio', $args->cmid);
        require_once($CFG->dirroot . '/mod/percipio/lib.php');
        // Capabilities check.
        require_login($args->courseid, false, $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability('mod/percipio:view', $context);
        // Right now we're just implementing basic viewing, otherwise we may
        // need to check other capabilities.
        $percipio = $DB->get_record('percipio', array('id' => $cm->instance));
        $launchurl = percipio_get_launchurl($percipio->launchurl);

        $data = array(
            'percipio' => $percipio,
            'btntext' => 'Launch',
            'cmid' => $cm->id,
            'courseid' => $args->courseid
        );

        if ($percipio->additionalinfo != '') {
            $addinfo = json_decode($percipio->additionalinfo, true);
            if (isset($addinfo["settings"]["showDisplayLabel"]) && $addinfo["settings"]["showDisplayLabel"] == 1) {
                $globaldisplaylabel = true;
            } else {
                $globaldisplaylabel = false;
            }
            if (isset($addinfo["values"]) && count($addinfo["values"]) > 0) {
                foreach ($addinfo["values"] as $key => $value) {
                    if (isset($value["value"]) && $value["value"] != '') {
                        if ((isset($value["showDisplayLabel"]) && $value["showDisplayLabel"] == true) ||
                                (!isset($value["showDisplayLabel"]) && $globaldisplaylabel == true)) {
                            $label = (isset($value["customLabel"]) && $value["customLabel"] != '')
                                ? $value["customLabel"] : ucfirst($value["name"]);
                            $displayifno = "  ".$label. " : " .$value["value"];
                        } else if ((isset($value["showDisplayLabel"]) && $value["showDisplayLabel"] == false) ||
                                (!isset($value["showDisplayLabel"]) && $globaldisplaylabel == false)) {
                            $displayifno = $value["value"];
                        }
                        array_push($data, $displayifno);
                    }
                }
            }
        }
        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("mod_percipio/mobile_view_page", $data),
                ),
            ),
            'javascript' => "this.launch = function(result) { window.location.href = '$launchurl'; };",
            // This JS will redirect to a launch url.
            'otherdata' => '',
            'files' => ''
        );
    }

     /**
      * Returns the percipio report view for the mobile app.
      * @param  array $args Arguments from tool_mobile_get_content WS
      *
      * @return array       HTML, javascript and otherdata
      */
    public static function mobile_report_view($args) {
        global  $COURSE, $OUTPUT, $DB, $USER;
        $args = (object) $args;
        $getpercipioentry = $DB->get_record('percipio_entries', array('courseid' => $args->courseid));
        $context = context_module::instance($getpercipioentry->cmid);
        if (has_capability('mod/percipio:viewreports', $context)) {
            $getpercipioattempt = $DB->get_records_sql("SELECT u.firstname as name, u.email, pa.timecreated as firstaccessed,
            pa.timerevisited as lastaccessed, pa.timecompleted as completiontime, pa.totalduration,pa.grade as score,
            pa.completionmessage as status FROM {user} u join {percipio_attempt} pa on  pa.userid = u.id
            WHERE pa.cmid = ?", array($getpercipioentry->cmid));
        } else {
            $getpercipioattempt = $DB->get_records_sql("SELECT u.firstname as name, u.email, pa.timecreated as firstaccessed,
            pa.timerevisited as lastaccessed, pa.timecompleted as completiontime, pa.totalduration,pa.grade as score,
            pa.completionmessage as status FROM {user} u join {percipio_attempt} pa on pa.userid = u.id
            WHERE pa.cmid = ? AND pa.userid = ?", array($getpercipioentry->cmid, $USER->id));
        }
        $p = [];
        foreach ($getpercipioattempt as $key => $val) {
            $val->firstaccessed = userdate($val->firstaccessed, get_string('strftimedatetime'));
            $val->lastaccessed = userdate($val->lastaccessed, get_string('strftimedatetime'));
            $val->completiontime = userdate($val->completiontime, get_string('strftimedatetime'));
            $p[] = (array) $val;
        }
        $data = array(
        'report' => $p
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("mod_percipio/report_view_page", $data),
                ),
            ),
            'javascript' => "
                var template = document.getElementById('template').innerHTML;
                var data = $data;
                var replacements = data ;
                Mustache.parse(template);
                var rendered = Mustache.render(template, replacements);
                document.getElementById('target').innerHTML = rendered;
            ",
            'otherdata' => '',
            'files' => ''
        );
    }

}
