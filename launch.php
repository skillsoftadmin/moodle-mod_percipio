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
 * Tin Can lauch file
 *
 * @package     mod_percipio
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js('/mod/percipio/js/jquery.min.js');
$PAGE->requires->js('/mod/percipio/js/launch.js');
$PAGE->requires->js_init_call('callajax', [$moduleinstance->launchurl, sesskey()]);

$authenticationmethod = get_config('percipio', 'authenticationmethod');
$bearertoken = get_config('percipio', 'bearertoken');
$orgid = get_config('percipio', 'organizationid');
$redirecturl = get_config('percipio', 'percipiourl');

$clientid = get_config('percipio', 'clientid');
$clientsecret = get_config('percipio', 'clientsecret');
$oauthurl = get_config('percipio', 'oauthurl');

$intro = format_module_intro('percipio', $moduleinstance, $cm->id);

$html = '';

$html .= html_writer::start_tag('div', array('class' => 'loader_background'));
$html .= html_writer::start_tag('div', array('class' => 'loader'));
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

$html .= html_writer::start_tag('div', array('class' => 'row'));
$html .= html_writer::div(html_writer::img(get_course_image(), '', array('class' => 'img-cover')), 'col-md-3');
if (strip_tags($intro) != '') {
    $html .= html_writer::div($intro, 'intro col-md-9');
}
$html .= html_writer::end_tag('div');


if ($moduleinstance->additionalinfo != '') {
    $addinfo = json_decode($moduleinstance->additionalinfo, true);
    if (isset($addinfo["settings"]["showDisplayLabel"]) && $addinfo["settings"]["showDisplayLabel"] == 1) {
        $globaldisplaylabel = true;
    } else {
        $globaldisplaylabel = false;
    }

    if (isset($addinfo["settings"]["showOnSameLine"]) && $addinfo["settings"]["showOnSameLine"] == 1) {
        $inlineclass = 'col-sm-3';
        $html .= html_writer::start_tag('div', array('class' => 'row'));
    } else {
        $inlineclass = '';
    }
    if (isset($addinfo["values"]) && count($addinfo["values"]) > 0) {
        foreach ($addinfo["values"] as $key => $value) {
            if (isset($value["value"]) && $value["value"] != '') {
                if ((isset($value["showDisplayLabel"]) && $value["showDisplayLabel"] == true) ||
                        (!isset($value["showDisplayLabel"]) && $globaldisplaylabel == true)) {
                    $label = (isset($value["customLabel"]) && $value["customLabel"] != '')
                        ? $value["customLabel"] : ucfirst($value["name"]);
                    $displayifno = "<b>".$label."</b> : ".$value["value"];
                } else if ((isset($value["showDisplayLabel"]) && $value["showDisplayLabel"] == false) ||
                        (!isset($value["showDisplayLabel"]) && $globaldisplaylabel == false)) {
                    $displayifno = $value["value"];
                }
                $html .= html_writer::div($displayifno, 'top '.$inlineclass);
            }
        }
    }
    if (isset($addinfo["settings"]["showOnSameLine"]) && $addinfo["settings"]["showOnSameLine"] == 1) {
        $html .= html_writer::end_tag('div');
    }
}

if ($moduleinstance->btntxt != '') {
    $buttontxt = $moduleinstance->btntxt;
} else {
    $buttontxt = get_string('launch', 'mod_percipio');
}

if ($moduleinstance->urltype == 'tincan') {
    if ($authenticationmethod == "tincan" && ($bearertoken == '' || $orgid == '' || $redirecturl == '')) {
        if (has_capability('mod/percipio:manage', $modulecontext)) {
            $html .= html_writer::div(get_string('settingincomplete', 'mod_percipio', $CFG->wwwroot), 'alert alert-danger top');
        }
    } else if ($authenticationmethod == "oauth" && ($clientid == '' || $orgid == '' || $clientsecret == '' || $oauthurl == '')) {
        if (has_capability('mod/percipio:manage', $modulecontext)) {
            $html .= html_writer::div(get_string('settingincomplete', 'mod_percipio', $CFG->wwwroot), 'alert alert-danger top');
        }
    } else {
        $html .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'launch_course top', 'value' => $buttontxt]);
    }
}

$html .= html_writer::start_tag('div', array('class' => 'show_error alert alert-danger top'));
$html .= html_writer::span("Error! Please try again.");
$html .= html_writer::start_tag('button', array('class' => 'close', 'data-dismiss' => 'alert', 'aria-label' => 'Close'));
$html .= html_writer::span('&times;', '', array('aria-hidden' => 'true'));
$html .= html_writer::end_tag('button');
$html .= html_writer::end_tag('div');
echo $html;
