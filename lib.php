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
 * Library of interface functions and constants.
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function percipio_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_percipio into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_percipio_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function percipio_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $moduleinstance->id = $DB->insert_record('percipio', $moduleinstance);

    percipio_grade_item_update($moduleinstance);

    return $moduleinstance->id;
}

/**
 * Updates an instance of the mod_percipio in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_percipio_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function percipio_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $oldpercipio = $DB->get_record('percipio', array('id' => $moduleinstance->instance));
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $DB->update_record('percipio', $moduleinstance);

    percipio_grade_item_update($moduleinstance, false);
    return true;
}

/**
 * Removes an instance of the mod_percipio from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function percipio_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('percipio', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('percipio', array('id' => $id));

    percipio_grade_item_delete($exists);

    return true;
}

/**
 * Is a given scale used by the instance of mod_percipio?
 *
 * This function returns if a scale is being used by one mod_percipio
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_percipio instance.
 */
function percipio_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('percipio', array('id' => $moduleinstanceid, 'grade' => $scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_percipio.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_percipio instance.
 */
function percipio_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('percipio', array('grade' => $scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_percipio instance.
 *
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void.
 */
function percipio_grade_item_update($moduleinstance, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (property_exists($moduleinstance, 'cmidnumber')) { // May not be always present.
        $params = array('itemname' => $moduleinstance->name, 'idnumber' => $moduleinstance->cmidnumber);
    } else {
        $params = array('itemname' => $moduleinstance->name);
    }

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $moduleinstance->grade;
        $item['grademin'] = 0;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if (property_exists($moduleinstance, 'visible')) {
        // Saving the percipio form, and cm not yet updated in the database.
        $item['hidden'] = !$moduleinstance->visible;
    } else {
        $cm = get_coursemodule_from_instance('percipio', $moduleinstance->id);
        $item['hidden'] = !$cm->visible;
    }

    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/percipio', $moduleinstance->course, 'mod', 'percipio', $moduleinstance->id, 0, $grades, $item);
}

/**
 * Delete grade item for given mod_percipio instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function percipio_grade_item_delete($moduleinstance) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $getmodule = $DB->get_record('modules', array('name' => 'percipio'));
    $getcmid = $DB->get_record('course_modules', array('course' => $moduleinstance->course,
        'instance' => $moduleinstance->id, 'module' => $getmodule->id));

    $DB->delete_records('percipio_attempt', array('cmid' => $getcmid->id));

    return grade_update('mod/percipio', $moduleinstance->course, 'mod', 'percipio',
        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_percipio grades in the gradebook.
 *
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function percipio_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('mod/percipio', $moduleinstance->course, 'mod', 'percipio', $moduleinstance->id, 0, $grades);
}


/**
 * Get the Percipio course module information
 *
 * @param stdClass $coursemodule the percipio course module object.
 */
function percipio_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat';
    if (!$percipio = $DB->get_record('percipio', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $percipio->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('percipio', $percipio, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionpass'] = 0;
    }

    return $result;
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the percipio.
 *
 * @param object $mform the course reset form that is being built.
 */
function percipio_reset_course_form_definition($mform) {
    $mform->addElement('header', 'percipioheader', get_string('pluginname', 'percipio'));
    $mform->addElement('advcheckbox', 'reset_percipios_attempts',
        'Delete all Percipio attempts');
}

/**
 * Course reset form defaults.
 *
 * @param object $course the course object.
 * @return array the defaults.
 */
function percipio_reset_course_form_defaults($course) {
    return array('reset_percipios_attempts' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string $type optional type
 */
function percipio_reset_gradebook($courseid, $type = '') {
    global $CFG, $DB;

    $percipio = $DB->get_records_sql("
            SELECT cv.*, cm.idnumber as cmidnumber, cv.course as courseid
            FROM {modules} m
            JOIN {course_modules} cm ON m.id = cm.module
            JOIN {percipio} cv ON cm.instance = cv.id
            WHERE m.name = 'percipio' AND cm.course = ?", array($courseid));

    foreach ($percipio as $key) {
        percipio_grade_item_update($key, 'reset');
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * percipio attempts for course $data->courseid, if $data->reset_percipio_attempts is
 * set and true.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function percipio_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('pluginname', 'percipio');
    $status = array();

    // Delete attempts.
    if (!empty($data->reset_percipios_attempts)) {

        $getmodule = $DB->get_record('modules', array('name' => 'percipio'));
        $getcmid = $DB->get_records('course_modules', array('course' => $data->courseid, 'module' => $getmodule->id));

        foreach ($getcmid as $getcmidkey => $getcmidval) {

            $DB->delete_records('percipio_attempt', array('cmid' => $getcmidval->id));
        }

        if (empty($data->reset_gradebook_grades)) {
            percipio_reset_gradebook($data->courseid);
        }
        $status[] = array(
            'component' => $componentstr,
            'item' => 'Percipio attempts and grades deleted',
            'error' => false);
    }

    return $status;
}

/**
 * Fucntion to get course image
 * @return the image url.
 */
function get_course_image() {
    global $COURSE, $CFG;
    $url = '';
    require_once($CFG->libdir . '/filelib.php');

    $context = context_course::instance($COURSE->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);

    foreach ($files as $f) {
        if ($f->is_valid_image()) {
            $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(),
                $f->get_filearea(), null, $f->get_filepath(), $f->get_filename(), false);
        }
    }

    return $url;
}

/**
 * Fucntion to generate the Tin Can launch URL
 *
 * @param string $activityurl the Tin Can launch URL of this activity to be appended.
 * @return the launch url.
 */
function getlaunchurl($activityurl) {
    global $USER, $CFG;
    $oauthtoken = '';
    $contenttoken = '';
    $errormsg = false;
    $bearertoken = get_config('percipio', 'bearertoken');
    $orgid = get_config('percipio', 'organizationid');
    $redirecturl = get_config('percipio', 'percipiourl');
    $actor = '{"objectType":"Agent","account":{"homePage":"' . $CFG->wwwroot . '","name":"' . $USER->id . '"}}';

    $authenticationmethod = get_config('percipio', 'authenticationmethod');
    if ($authenticationmethod == 'oauth') {
        $oauthtoken = get_config('percipio', 'oauthToken');
        $tokenexpirytime = get_config('percipio', 'tokenExpiryTime');
        if (($oauthtoken != '') && (time() < $tokenexpirytime)) {
            $contenttoken = getcontenttoken($oauthtoken, $activityurl);
        } else {
            $resp = getoauthtoken();
            $oauthtoken = get_config('percipio', 'oauthToken');
            $contenttoken = getcontenttoken($oauthtoken, $activityurl);
        }
    } else {
        $contenttoken = getcontenttoken($bearertoken, $activityurl);
    }
    if (!$errormsg) {
        $launchurl = $redirecturl . '/content-integration/v1/tincan/launch?actor='.
            $actor . '&activity_id=' . $activityurl . '&content_token=' . $contenttoken;
        return $launchurl;
    } else {
        return false;
    }
}

/**
 * Fucntion to generate the content token
 *
 * @param string $token the bearer token from percipio settings.
 * @param string $activityurl the Tin Can launch URL of this activity to be appended.
 * @return the dynamic generated content token.
 */
function getcontenttoken($token, $activityurl) {
    global $USER, $CFG;
    $errormsg = false;
    $actor = '{"objectType":"Agent","account":{"homePage":"' . $CFG->wwwroot . '","name":"' . $USER->id . '"}}';
    $redirecturl = get_config('percipio', 'percipiourl');
    $orgid = get_config('percipio', 'organizationid');
    $endpointurl = $redirecturl . '/content-integration/v1/organizations/'.
        $orgid . '/content-token?actor=' . $actor . '&activity_id=' . $activityurl;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpointurl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token,
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $errormsg = curl_error($curl);
    }
    curl_close($curl);
    if (!$errormsg) {
        $resp = json_decode($response);
        if (isset($resp->contentToken)) {
            return $resp->contentToken;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Fucntion to generate the OAuth token
 *
 * @return the decoded response.
 */
function getoauthtoken() {
    global $USER, $CFG;
    $errormsg = false;
    $orgid = get_config('percipio', 'organizationid');
    $redirecturl = get_config('percipio', 'percipiourl');
    $clientid = get_config('percipio', 'clientid');
    $clientsecret = get_config('percipio', 'clientsecret');
    $oauthurl = get_config('percipio', 'oauthurl');
    $endpointurl = $oauthurl . '/oauth2-provider/token';
    $data = array(
        "client_id" => $clientid,
        "client_secret" => $clientsecret,
        "grant_type" => "client_credentials",
        "scope" => "api"
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpointurl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json',
        ),
    ));
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $errormsg = curl_error($curl);
    }
    curl_close($curl);
    if (!$errormsg) {
        $decoded = json_decode($response);
        $tokenexpirytime = time() + ($decoded->expires_in);
        set_config('oauthToken', $decoded->access_token, 'percipio');
        set_config('oauthTokenExpiry', $decoded->expires_in, 'percipio');
        set_config('tokenExpiryTime', $tokenexpirytime, 'percipio');
        return $decoded;
    } else {
        return false;
    }
}
