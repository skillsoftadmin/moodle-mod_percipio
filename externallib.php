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
 * mod_percipio api functions
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot.'/mod/scorm/mod_form.php');
require_once($CFG->dirroot.'/mod/scorm/lib.php');

/**
 * Percipio external functions
 *
 * @package    mod_percipio
 * @category   external
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_percipio_api_external extends external_api {

    /**
     * Describes the parameters for percipio_import_course_parameters.
     *
     * @return external_function_parameters
     */
    public static function percipio_import_course_parameters() {
        return new external_function_parameters(
            array('data' => new external_value(PARAM_RAW, get_string('courseimportparam', 'mod_percipio')))
        );
    }

    /**
     * Create courses from Percipio and adds the percipio activity inside the course
     *
     * @param JSON $coursedata
     * @return array resultcourses (moodle_course_id, percipioid, message and code)
     */
    public static function percipio_import_course($coursedata) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir."/filelib.php");
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/percipio/mod_form.php');
        require_once($CFG->dirroot . '/mod/percipio/lib.php');

        $params = self::validate_parameters(self::percipio_import_course_parameters(), array('data' => $coursedata));
        $course = json_decode($params["data"], true);

        $syscontext = context_system::instance();

        $enableself = false;

        $course['newsitems'] = 1;
        $course['startdate'] = round($course['startdate'] / 1000);
        $course['lang'] = 'en';
        $course["visible"] = (int) $course["visible"];

        $availablelangs = get_string_manager()->get_list_of_translations();

        // Check and create category if needed.
        if (!isset($course['category']) || $course['category'] == '') {
            $course['categoryid'] = 1;
        }

        if (isset($course['category']) && $course['category'] != '') {
            $checkcategory = $DB->get_record('course_categories', array('name' => $course['category']));
            if ($checkcategory) {
                $course['categoryid'] = $checkcategory->id;
                $paramcatname = $checkcategory->name;
            } else {
                self::validate_context($syscontext);
                require_capability('moodle/category:manage', $syscontext);
                $category = ['name' => $course['category'], 'descriptionformat' => 0, 'parent' => 0];
                external_validate_format($category['descriptionformat']);
                $newcategory = core_course_category::create($category);
                $getnewcategory = $DB->get_record('course_categories', array('name' => $course['category']));
                $course['categoryid'] = $getnewcategory->id;
                $paramcatname = $getnewcategory->name;
            }
        }

        // Fullname and short name are required to be non-empty.
        if (trim($course['fullname']) === '') {
            throw new moodle_exception('errorinvalidparam', 'webservice', '', 'fullname');
        } else if (trim($course['shortname']) === '') {
            throw new moodle_exception('errorinvalidparam', 'webservice', '', 'shortname');
        }

        $course['category'] = $course['categoryid'];
        $checkexistingcourse = $DB->get_record('course', array('shortname' => $course['shortname']));

        if (!$checkexistingcourse) {
            $context = context_coursecat::instance($course['categoryid'], IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->catid = $course['categoryid'];
                throw new moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:create', $context);
        } else {
            $course['id'] = $checkexistingcourse->id;
            $context = context_course::instance($course['id'], MUST_EXIST);
            self::validate_context($context);
            $oldcourse = course_get_format($course['id'])->get_course();
            require_capability('moodle/course:update', $context);

            // Check if user can change category.
            if ($oldcourse->category != $course['categoryid']) {
                require_capability('moodle/course:changecategory', $context);
                $course['category'] = $course['categoryid'];
            }

            // Check if the user can change fullname.
            if ($oldcourse->fullname != $course['fullname']) {
                require_capability('moodle/course:changefullname', $context);
            }

            // Check if the user can change shortname.
            if ($oldcourse->shortname != $course['shortname']) {
                require_capability('moodle/course:changeshortname', $context);
            }

            // Check if the user can change the idnumber.
            if (array_key_exists('idnumber', $course) && ($oldcourse->idnumber != $course['idnumber'])) {
                require_capability('moodle/course:changeidnumber', $context);
            }

            // Check if user can change summary.
            if (array_key_exists('summary', $course) && ($oldcourse->summary != $course['summary'])) {
                require_capability('moodle/course:changesummary', $context);
            }

            // Summary format.
            if (array_key_exists('summaryformat', $course) && ($oldcourse->summaryformat != $course['summaryformat'])) {
                require_capability('moodle/course:changesummary', $context);
                $course['summaryformat'] = external_validate_format($course['summaryformat']);
            }

            // Check if user can change visibility.
            if (array_key_exists('visible', $course) && ($oldcourse->visible != $course['visible'])) {
                require_capability('moodle/course:visibility', $context);
            }
        }

        // Make sure lang is valid.
        if (array_key_exists('lang', $course)) {
            if (empty($availablelangs[$course['lang']])) {
                throw new moodle_exception('errorinvalidparam', 'webservice', '', 'lang');
            }
            if (!has_capability('moodle/course:setforcedlanguage', $context)) {
                unset($course['lang']);
            }
        }

        // Force visibility if ws user doesn't have the permission to set it.
        $category = $DB->get_record('course_categories', array('id' => $course['categoryid']));
        if (!has_capability('moodle/course:visibility', $context)) {
            $course['visible'] = $category->visible;
        }

        // Set default value for completion.
        $courseconfig = get_config('moodlecourse');
        if (completion_info::is_enabled_for_site()) {
            if (!array_key_exists('enablecompletion', $course)) {
                $course['enablecompletion'] = $courseconfig->enablecompletion;
            }
        } else {
            $course['enablecompletion'] = 0;
        }

        // Make sure maxbytes are less then CFG->maxbytes.
        if (array_key_exists('maxbytes', $course)) {
            // We allow updates back to 0 max bytes, a special value denoting the course uses the site limit.
            // Otherwise, either use the size specified, or cap at the max size for the course.
            if ($course['maxbytes'] != 0) {
                    $course['maxbytes'] = get_max_upload_file_size($CFG->maxbytes, $course['maxbytes']);
            }
        }

        // Summary format.
        $course['summaryformat'] = external_validate_format($course['summaryformat']);

        if (!empty($course['courseformatoptions'])) {
            foreach ($course['courseformatoptions'] as $option) {
                $course[$option['name']] = $option['value'];
            }
        }

        // Custom fields.
        if (!empty($course['customfields'])) {
            foreach ($course['customfields'] as $field) {
                    $course['customfield_'.$field['shortname']] = $field['value'];
            }
        }
        $percipiomodule = $DB->get_record('modules', array('name' => $course["courseformatoptions"][0]["value"]));
        try {
            $getpercipioentry = $DB->get_record('percipio_entries', array('courseid' => $course['id']));
            if ($course["courseformatoptions"][0]["value"] == 'percipio' && $course['link'] != '') {
                $mformclassname = 'mod_percipio_mod_form';
                $fromform = [
                    "name" => $course['fullname'],
                    "launchurl" => $course["xapiActivityId"],
                    "introeditor" => ["text" => $course["summary"], "format" => 1],
                    "showdescription" => 0,
                    "urltype" => "tincan", // Harcoded as of now, later.
                    // It can be 'link' or 'tincan' depending upon feature enhancement from Percipio.
                    "additionalinfo" => json_encode($course["additionalMetadata"]),
                    "percipiotype" => $course['percipiotype'],
                    "displaylabel" => $course['displaylabel'],
                    "gradecat" => 9,
                    "visible" => 1,
                    "visibleoncoursepage" => 1,
                    "grade" => 100,
                    "groupmode" => 0,
                    "groupingid" => 0,
                    "cmidnumber" => '',
                    "availabilityconditionsjson" => '{"op":"&","c":[],"showc":[]}',
                    "completionunlocked" => 1,
                    "completion" => 2,
                    "completionusegrade" => 1,
                    "completionexpected" => 0,
                    "tags" => [],
                    "course" => $course['id'],
                    "coursemodule" => !$checkexistingcourse ? 0 : $getpercipioentry->cmid,
                    "section" => 0,
                    "module" => $percipiomodule->id,
                    "modulename" => $course["courseformatoptions"][0]["value"],
                    "instance" => !$checkexistingcourse ? 0 : $cm->instance,
                    "add" => !$checkexistingcourse ? $course["courseformatoptions"][0]["value"] : 0,
                    "update" => !$checkexistingcourse ? 0 : $getpercipioentry->cmid,
                    "return" => 0,
                    "sr" => 0,
                    "competencies" => [],
                    "competency_rule" => 0,
                ];
            } else if ($course["percipiotype"] == 'COMPLIANCE' && $course['aicclaunch'] != '') {
                $mformclassname = 'mod_scorm_mod_form';
                $fromform = [
                    "name" => $course['fullname'],
                    "introeditor" => ["text" => $course["summary"], "format" => 1],
                    "mform_isexpanded_id_packagehdr" => 1,
                    "scormtype" => 'aiccurl',
                    "packageurl" => $course["aicclaunch"],
                    "reference" => $course["aicclaunch"],
                    "updatefreq" => 0,
                    "popup" => 0,
                    "width" => 100,
                    "height" => 500,
                    "displayactivityname" => 1,
                    "skipview" => 0,
                    "hidebrowse" => 1,
                    "displaycoursestructure" => 0,
                    "displayattemptstatus" => 1,
                    "timeopen" => 0,
                    "timeclose" => 0,
                    "grademethod" => 1,
                    "maxgrade" => 100,
                    "maxattempt" => 0,
                    "whatgrade" => 1,
                    "forcenewattempt" => 0,
                    "lastattemptlock" => 0,
                    "forcecompleted" => 0,
                    "auto" => 0,
                    "autocommit" => 0,
                    "masteryoverride" => 1 ,
                    "datadir" => 6 ,
                    "pkgtype" => 'aicc',
                    "launch" => 0,
                    "visible" => 1,
                    "visibleoncoursepage" => 1,
                    "groupmode" => 0,
                    "groupingid" => 0,
                    "cmidnumber" => '',
                    "availabilityconditionsjson" => '{"op":"&","c":[],"showc":[]}',
                    "completionunlocked" => 1,
                    "completion" => 1,
                    "completionscorerequired" => '',
                    "completionexpected" => 0,
                    "tags" => [],
                    "course" => $course['id'],
                    "coursemodule" => !$checkexistingcourse ? 0 : $getpercipioentry->cmid,
                    "section" => 0,
                    "module" => $percipiomodule->id,
                    "modulename" => $course["courseformatoptions"][0]["value"],
                    "instance" => !$checkexistingcourse ? 0 : $cm->instance,
                    "add" => !$checkexistingcourse ? $course["courseformatoptions"][0]["value"] : 0,
                    "update" => !$checkexistingcourse ? 0 : $getpercipioentry->cmid,
                    "return" => 0,
                    "sr" => 0,
                    "competencies" => [],
                    "competency_rule" => 0,
                ];
            }
            $transaction = $DB->start_delegated_transaction();
            if (!$checkexistingcourse) {
                // Additional check to delete any redundant/existing percipio course entry.
                // From {percipio_entries} when moodle course does not exists.
                $DB->delete_records('percipio_entries', array('percipioid' => $course['shortname']));
                // Create a course.
                $course['id'] = custom_create_course((object) $course)->id; // Using custom percipio create course function
                $msg = get_string('coursecreated', 'mod_percipio');
                $mform = new $mformclassname((object)$fromform, 0, null, (object)$course);
                $addmodule = add_moduleinfo((object)$fromform, (object)$course, $mform);
                // Upload image from url in course overviewfiles.
                $coursecontext = context_course::instance($course['id'], MUST_EXIST);
                $fs = get_file_storage();
                $overviewfilesoptions = course_overviewfiles_options($course['id']);
                $filetypesutil = new \core_form\filetypes_util();
                $whitelist = $filetypesutil->normalize_file_types($overviewfilesoptions['accepted_types']);
                $url = $CFG->wwwroot.'/mod/percipio/pix/percipiologo.png';
                $url = new moodle_url($url);
                $filename = pathinfo($url->get_path(), PATHINFO_BASENAME);

                if ($filetypesutil->is_allowed_file_type($filename, $whitelist)) {
                    $checkexistingimage = $fs->get_file($coursecontext->id, 'course', 'overviewfiles', 0, '/', $filename);
                    if (!$checkexistingimage || ($checkexistingimage->get_filename() != $filename)) {
                        $fileinfo = [
                            'filename' => $filename,
                            'filepath' => '/',
                            'itemid' => 0,
                            'contextid' => $coursecontext->id,
                            'component' => 'course',
                            'filearea' => 'overviewfiles',
                        ];
                        $urlparams = [
                            'calctimeout' => false,
                            'timeout' => 5,
                            'skipcertverify' => true,
                            'connecttimeout' => 5,
                        ];
                        $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
                        $fs->create_file_from_url($fileinfo, $url, $urlparams);
                    }
                }
                $record = new stdClass();
                $record->courseid = $course['id'];
                $record->percipioid = $course['shortname'];
                $record->cmid = $addmodule->coursemodule;
                $record->imageurl = $course['imageurl'];
                $record->timemodified = time();
                $DB->insert_record('percipio_entries', $record);
                $enableself = true;
            } else {
                // Additional check to delete any redundant/existing course entry.
                // From {percipio_entries} when moodle course does not exists.
                $delparams = ['percipioid' => $course['shortname'], 'courseid' => $course['id']];
                $DB->delete_records_select('percipio_entries', "percipioid = :percipioid AND courseid != :courseid", $delparams);
                // Update course if user has all required capabilities.
                update_course((object) $course);
                $msg = get_string('courseupdated', 'mod_percipio');

                $getpercipioentry = $DB->get_record('percipio_entries', array('courseid' => $course['id']));
                $cm = get_coursemodule_from_id('', $getpercipioentry->cmid, 0, false, MUST_EXIST);

                $mform = new $mformclassname((object)$fromform, 0, null, (object)$course);
                update_moduleinfo($cm, (object)$fromform, (object)$course, $mform);
                if ($getpercipioentry) {
                    $record = new stdClass();
                    $record->id = $getpercipioentry->id;
                    $record->percipioid = $course['shortname'];
                    $record->imageurl = $course['imageurl'];
                    $record->imageuploaded = 0;
                    $record->timemodified = time();
                    $DB->update_record('percipio_entries', $record);
                }
            }
            $coursecontext = context_course::instance($course['id'], MUST_EXIST);
            // Enable self enrolment in the newly created course.
            if ($enableself) {
                require_capability('enrol/self:config', $coursecontext);
                $getcourseselfenrol = $DB->get_record('enrol', array('courseid' => $course['id'], 'enrol' => 'self'));
                if ($getcourseselfenrol) {
                    $updatecourseselfenrol = new stdClass();
                    $updatecourseselfenrol->id = $getcourseselfenrol->id;
                    $updatecourseselfenrol->status = 0;
                    $DB->update_record('enrol', $updatecourseselfenrol);
                } else {
                    $updatecourseselfenrol = new stdClass();
                    $updatecourseselfenrol->enrol = 'self';
                    $updatecourseselfenrol->status = 0;
                    $updatecourseselfenrol->courseid = $course['id'];
                    $updatecourseselfenrol->sortorder = 2;
                    $updatecourseselfenrol->expirythreshold = 86400;
                    $updatecourseselfenrol->roleid = 5;
                    $updatecourseselfenrol->customint1 = 0;
                    $updatecourseselfenrol->customint2 = 0;
                    $updatecourseselfenrol->customint3 = 0;
                    $updatecourseselfenrol->customint4 = 1;
                    $updatecourseselfenrol->customint5 = 0;
                    $updatecourseselfenrol->customint6 = 1;
                    $updatecourseselfenrol->timecreated = time();
                    $updatecourseselfenrol->timemodified = time();
                    $DB->insert_record('enrol', $updatecourseselfenrol);
                }
            }

            $transaction->allow_commit();
            $resultcourses = array('moodlecourseid' => $course['id'], 'percipioid' => $course['shortname'],
                'message' => $msg, 'code' => 200);
            return $resultcourses;
        } catch (Exception $e) {
            http_response_code(422);
            // Extra cleanup steps.
            $transaction->rollback($e); // Rethrows exception.
        }
    }

    /**
     * Describes the percipio_import_course_returns return value
     *
     * @return external_single_structure
     */
    public static function percipio_import_course_returns() {
        return new external_single_structure(
            array(
                'moodlecourseid'       => new external_value(PARAM_INT, get_string('courseid', 'mod_percipio')),
                'percipioid' => new external_value(PARAM_ALPHANUMEXT, get_string('shortname', 'mod_percipio')),
                'message' => new external_value(PARAM_TEXT, get_string('resmessage', 'mod_percipio')),
                'code' => new external_value(PARAM_INT, get_string('code', 'mod_percipio')),
            )
        );
    }

    /**
     * Describes the parameters for percipio_update_activity_progress_parameters.
     *
     * @return external_function_parameters
     */
    public static function percipio_update_activity_progress_parameters() {
        return new external_function_parameters(
            array('data' => new external_value(PARAM_RAW, get_string('trackingimportparam', 'mod_percipio')))
        );
    }

    /**
     * Imports user grades w.r.t to Moodle course from Percipio
     *
     * @param JSON $params
     * @return array (message and code)
     */
    public static function percipio_update_activity_progress($params) {
        global  $CFG, $DB;

        $params = self::validate_parameters(self::percipio_update_activity_progress_parameters(), array('data' => $params));
        $trackingdata = json_decode($params["data"], true);

        if (trim($trackingdata['courseshortname']) === '') {
            throw new moodle_exception('errorinvalidparam', 'webservice', '', 'courseshortname');
        } else if (trim($trackingdata['username']) === '') {
            throw new moodle_exception('errorinvalidparam', 'webservice', '', 'username');
        }

        $getcourse = $DB->get_record('percipio_entries', array('percipioid' => $trackingdata['courseshortname']));
        if (!$getcourse) {
            throw new moodle_exception('error', 'webservice', '', get_string('nocourse', 'mod_percipio'));
        }

        $getuser = $DB->get_record('user', array('id' => $trackingdata['username']));
        if (!$getuser) {
            throw new moodle_exception('error', 'webservice', '', get_string('nouser', 'mod_percipio'));
        }

        $context = context_course::instance($getcourse->courseid, MUST_EXIST);
        self::validate_context($context);
        require_capability('moodle/grade:manage', $context);
        $cm = get_coursemodule_from_id('', $getcourse->cmid, 0, false, MUST_EXIST);

        if (!is_enrolled($context, $getuser)) {
            $enrol = enrol_get_plugin('self');
            $enrolinstance = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $getcourse->courseid));
            $enrol->enrol_user($enrolinstance, $getuser->id, 5, time());
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            $getcmcompletion = $DB->get_record('course_modules_completion',
                array('coursemoduleid' => $getcourse->cmid, 'userid' => $getuser->id));
            if (!$getcmcompletion) {
                $cmrecord = new stdClass();
                $cmrecord->coursemoduleid = $getcourse->cmid;
                $cmrecord->userid = $getuser->id;
                $cmrecord->completionstate = $trackingdata['completionstate'];
                $cmrecord->viewed = 1;
                $cmrecord->overrideby = null;
                $cmrecord->timemodified = time();
                $DB->insert_record('course_modules_completion', $cmrecord);
            } else {
                $cmrecord = new stdClass();
                $cmrecord->id = $getcmcompletion->id;
                $cmrecord->completionstate = $trackingdata['completionstate'];
                $cmrecord->viewed = 1;
                $cmrecord->timemodified = time();
                $DB->update_record('course_modules_completion', $cmrecord);
            }

            $getpercipioattempt = $DB->get_record('percipio_attempt', array('cmid' => $getcourse->cmid, 'userid' => $getuser->id));
            if (!$getpercipioattempt) {
                $attemprecord = new stdClass();
                $attemprecord->cmid = $getcourse->cmid;
                $attemprecord->userid = $getuser->id;
                $attemprecord->grade = $trackingdata['finalgrade'];
                $attemprecord->grade = $trackingdata['finalgrade'];
                $attemprecord->completionmessage = $trackingdata['completionmessage'];
                $attemprecord->passingscore = $trackingdata['passingscore'];
                $attemprecord->lastscore = $trackingdata['lastscore'];
                $attemprecord->percentcomplete = $trackingdata['percentcomplete'];
                $attemprecord->totalduration = $trackingdata['totalduration'];
                $attemprecord->timecreated = ($trackingdata['timecreated'] != null) ?
                    round($trackingdata['timecreated'] / 1000) : time();
                $attemprecord->timerevisited = ($trackingdata['timerevisited'] != null) ?
                    round($trackingdata['timerevisited'] / 1000) : time();
                $attemprecord->timecompleted = ($trackingdata['timecompleted'] != null) ?
                    round($trackingdata['timecompleted'] / 1000) : time();
                $attemprecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $DB->insert_record('percipio_attempt', $attemprecord);
            } else {
                $attemprecord = new stdClass();
                $attemprecord->id = $getpercipioattempt->id;
                $attemprecord->grade = $trackingdata['finalgrade'];
                $attemprecord->grade = $trackingdata['finalgrade'];
                $attemprecord->completionmessage = $trackingdata['completionmessage'];
                $attemprecord->passingscore = $trackingdata['passingscore'];
                $attemprecord->lastscore = $trackingdata['lastscore'];
                $attemprecord->percentcomplete = $trackingdata['percentcomplete'];
                $attemprecord->totalduration = $trackingdata['totalduration'];
                $attemprecord->timecreated = ($trackingdata['timecreated'] != null) ?
                    round($trackingdata['timecreated'] / 1000) : time();
                $attemprecord->timerevisited = ($trackingdata['timerevisited'] != null) ?
                    round($trackingdata['timerevisited'] / 1000) : time();
                $attemprecord->timecompleted = ($trackingdata['timecompleted'] != null) ?
                    round($trackingdata['timecompleted'] / 1000) : time();
                $attemprecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $DB->update_record('percipio_attempt', $attemprecord);
            }

            $gradeitemid = $DB->get_record('grade_items', array('courseid' => $cm->course,
                'iteminstance' => $cm->instance, 'itemmodule' => 'percipio'))->id;
            $getpercipiograde = $DB->get_record('grade_grades', array('userid' => $getuser->id, 'itemid' => $gradeitemid));
            if (!$getpercipiograde) {
                $graderecord = new stdClass();
                $graderecord->itemid = $gradeitemid;
                $graderecord->userid = $getuser->id;
                $graderecord->rawgrade = $trackingdata['finalgrade'];
                $graderecord->finalgrade = $trackingdata['finalgrade'];
                $graderecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $graderecord->aggregationstatus = 'used';
                $graderecord->aggregationweight = 1;
                $graderecord->feedback = get_string('message', 'mod_percipio').": ".
                    ucfirst($trackingdata['completionmessage']).PHP_EOL.
                    get_string('percentcomplete', 'mod_percipio').": ".$trackingdata['percentcomplete'].PHP_EOL.
                    get_string('duration', 'mod_percipio').": ".$trackingdata['totalduration'];
                $DB->insert_record('grade_grades', $graderecord);
            } else {
                $graderecord = new stdClass();
                $graderecord->id = $getpercipiograde->id;
                $graderecord->userid = $getuser->id;
                $graderecord->rawgrade = $trackingdata['finalgrade'];
                $graderecord->finalgrade = $trackingdata['finalgrade'];
                $graderecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $graderecord->aggregationstatus = 'used';
                $graderecord->aggregationweight = 1;
                $graderecord->feedback = get_string('message', 'mod_percipio').": ".
                    ucfirst($trackingdata['completionmessage']).PHP_EOL.
                    get_string('percentcomplete', 'mod_percipio').": ".$trackingdata['percentcomplete'].PHP_EOL.
                    get_string('duration', 'mod_percipio').": ".$trackingdata['totalduration'];
                $DB->update_record('grade_grades', $graderecord);
            }

            $getcoursegradeitem = $DB->get_record('grade_items',
                array('courseid' => $getcourse->courseid, 'itemtype' => 'course'))->id;
            $getcoursegrade = $DB->get_record('grade_grades', array('userid' => $getuser->id, 'itemid' => $getcoursegradeitem));
            if (!$getcoursegrade) {
                $coursegraderecord = new stdClass();
                $coursegraderecord->itemid = $getcoursegradeitem;
                $coursegraderecord->userid = $getuser->id;
                $coursegraderecord->finalgrade = $trackingdata['finalgrade'];
                $coursegraderecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $coursegraderecord->aggregationstatus = 'used';
                $coursegraderecord->aggregationweight = 1;
                $DB->insert_record('grade_grades', $coursegraderecord);
            } else {
                $coursegraderecord = new stdClass();
                $coursegraderecord->id = $getcoursegrade->id;
                $coursegraderecord->userid = $getuser->id;
                $coursegraderecord->finalgrade = $trackingdata['finalgrade'];
                $coursegraderecord->timemodified = ($trackingdata['timemodified'] != null) ?
                    round($trackingdata['timemodified'] / 1000) : time();
                $DB->update_record('grade_grades', $coursegraderecord);
            }

            $transaction->allow_commit();
            return array('message' => get_string('success', 'mod_percipio'), 'code' => 200);
        } catch (Exception $e) {
            http_response_code(422);
            // Extra cleanup steps.
            $transaction->rollback($e); // Rethrows exception.
        }
    }

    /**
     * Describes the percipio_update_activity_progress_returns return value
     *
     * @return external_single_structure
     */
    public static function percipio_update_activity_progress_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, get_string('resmessage', 'mod_percipio')),
                'code' => new external_value(PARAM_INT, get_string('code', 'mod_percipio')),
            )
        );
    }

    /**
     * Describes the parameters for percipio_image_upload_parameters.
     *
     * @return external_function_parameters
     */
    public static function percipio_upload_image_parameters() {
        return new external_function_parameters(
            array('data' => new external_value(PARAM_RAW, get_string('courseimageparam', 'mod_percipio')))
        );
    }

    /**
     * Image upload w.r.t to Moodle course from Percipio
     *
     * @param JSON $coursedata
     * @return array (message and code)
     */
    public static function percipio_upload_image($coursedata) {
        global  $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir."/filelib.php");
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/percipio/mod_form.php');

        $params = self::validate_parameters(self::percipio_upload_image_parameters(), array('data' => $coursedata));

        // Get previously failed image upload content uuid and merge with received $json data.
        $getpreviouslyfailedimages = $DB->get_records('percipio_entries', array('imageuploaded' => 0));

        try {
            foreach ($getpreviouslyfailedimages as $data) {
                $imagedownloaderror = false;
                if (empty($data)) {
                    throw new moodle_exception('errorinvalidparam', 'webservice', '', 'shortname');
                }
                // Upload image from url in course overviewfiles.
                $coursecontext = context_course::instance($data->courseid, MUST_EXIST);
                $fs = get_file_storage();
                $overviewfilesoptions = course_overviewfiles_options($data->courseid);

                $filetypesutil = new \core_form\filetypes_util();
                $whitelist = $filetypesutil->normalize_file_types($overviewfilesoptions['accepted_types']);

                $url = new moodle_url($data->imageurl);
                $filename = pathinfo($url->get_path(), PATHINFO_BASENAME);

                if ($filetypesutil->is_allowed_file_type($filename, $whitelist)) {
                    $checkexistingimage = $fs->get_file($coursecontext->id, 'course', 'overviewfiles', 0, '/', $filename);
                    if (!$checkexistingimage || ($checkexistingimage->get_filename() != $filename)) {
                        $fileinfo = [
                            'filename' => $filename,
                            'filepath' => '/',
                            'itemid' => 0,
                            'contextid' => $coursecontext->id,
                            'component' => 'course',
                            'filearea' => 'overviewfiles',
                        ];
                        $urlparams = [
                            'calctimeout' => false,
                            'timeout' => 5,
                            'skipcertverify' => true,
                            'connecttimeout' => 5,
                        ];
                        try {
                            $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
                            $fs->create_file_from_url($fileinfo, $url, $urlparams);
                        } catch (Exception $err) {
                            // Upload percipio image.
                            $url = $CFG->wwwroot.'/mod/percipio/pix/percipiologo.png';
                            $url = new moodle_url($url);
                            $filename = pathinfo($url->get_path(), PATHINFO_BASENAME);

                            if ($filetypesutil->is_allowed_file_type($filename, $whitelist)) {
                                $checkexistingimage = $fs->get_file($coursecontext->id, 'course', 'overviewfiles', 0, '/', $filename);
                                if (!$checkexistingimage || ($checkexistingimage->get_filename() != $filename)) {
                                    $fileinfo = [
                                        'filename' => $filename,
                                        'filepath' => '/',
                                        'itemid' => 0,
                                        'contextid' => $coursecontext->id,
                                        'component' => 'course',
                                        'filearea' => 'overviewfiles',
                                    ];
                                    $urlparams = [
                                        'calctimeout' => false,
                                        'timeout' => 5,
                                        'skipcertverify' => true,
                                        'connecttimeout' => 5,
                                    ];
                                    $fs->delete_area_files($coursecontext->id, 'course', 'overviewfiles');
                                    $fs->create_file_from_url($fileinfo, $url, $urlparams);
                                }
                            }
                            $imagedownloaderror = true;
                        }
                    }
                }
                $imageuploaded = new stdClass();
                $imageuploaded->id = $data->id;
                $imageuploaded->imageuploaded = ($imagedownloaderror) ? 0 : 1;
                $DB->update_record('percipio_entries', $imageuploaded);
            }

            $result = array('percipioid' => $data->percipioid, 'message' => get_string('success', 'mod_percipio'), 'code' => 200);
            return $result;
        } catch (Exception $e) {
            http_response_code(422);
        }
    }

    /**
     * Describes the percipio_image_upload_returns return value
     *
     * @return external_single_structure
     */
    public static function percipio_upload_image_returns() {

        return new external_single_structure(
            array(
                'percipioid' => new external_value(PARAM_ALPHANUMEXT, get_string('shortname', 'mod_percipio')),
                'message' => new external_value(PARAM_TEXT, get_string('resmessage', 'mod_percipio')),
                'code' => new external_value(PARAM_INT, get_string('code', 'mod_percipio')),
            )
        );
    }
}
