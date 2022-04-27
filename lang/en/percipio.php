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
 * Plugin strings are defined here.
 *
 * @package     mod_percipio
 * @category    string
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Percipio';
$string['modulename'] = 'Percipio';
$string['modulenameplural'] = 'Percipio';
$string['percipioname'] = 'Name';
$string['percipioname_help'] = 'Name of the assessment';
$string['pluginadministration'] = 'Percipio administration';
$string['percipio:addinstance'] = 'Add percipio';
$string['percipio:reviewmyreports'] = 'View report';
$string['percipio:view'] = 'Percipio assessment';
$string['percipio:viewreports'] = 'View reports';
$string['search:activity'] = 'Percipio';
$string['reportlink'] = 'Report link';
$string['message'] = 'Message';
$string['percentcomplete'] = 'Percentage complete';
$string['duration'] = 'Duration';
$string['name'] = 'Name';
$string['email'] = 'Email';
$string['firstaccessed'] = 'First accessed';
$string['lastaccessed'] = 'Last accessed';
$string['completiontime'] = 'Completion time';
$string['totalduration'] = 'Total duration';
$string['score'] = 'Score';
$string['status'] = 'Status';
$string['selectuser'] = 'Select user';
$string['datefrom'] = 'Date from';
$string['dateto'] = 'Date to';
$string['authenticationmethod'] = 'Authentication method';
$string['authenticationmethod_help'] = 'Select authentication method, OAuth or launch with service account bearer token';
$string['clientid'] = 'Client id';
$string['scope'] = 'Scope';
$string['clientid_help'] = 'The client id.';
$string['scope_help'] = 'The scope.';
$string['clientsecret'] = 'Client secret';
$string['clientsecret_help'] = 'The client secret.';
$string['bearertoken'] = 'Bearer token';
$string['bearertoken_help'] = 'Bearer token for percipio services.';
$string['organizationid'] = 'Organization id';
$string['organizationid_help'] = 'The organization id from percipio.';
$string['percipiourl'] = 'Percipio API endpoint URL';
$string['percipiourl_help'] = 'The percipio API endpoint URL.';
$string['oauthurl'] = 'Percipio OAuth URL';
$string['oauthurl_help'] = 'The percipio OAuth URL.';
$string['missingidandcmid'] = 'Missing id and cmid';
$string['coursedetails'] = 'Course details';
$string['report'] = 'Report';
$string['launch'] = 'Launch';
$string['settingincomplete'] = 'Percipio settings incomplete! please complete the
<a href="{$a}/admin/settings.php?section=modsettingpercipio">Percipio settings</a>.';
$string['dateerror'] = 'End date cannot be less than start Date';
$string['courseupdated'] = 'Course updated successfully';
$string['coursecreated'] = 'Course created successfully';
$string['reportcriteria'] = 'Select report criteria';
$string['deleteallattempts'] = 'Delete all percipio attempts';
$string['attemptsdeleted'] = 'Percipio attempts and grades deleted';
$string['curlerror'] = 'Error! please try again.';
$string['nonewmodules'] = 'No percipio activity found.';
$string['courseimportparam'] = 'JSON parameters from percipio to import course in moodle';
$string['courseid'] = 'Moodle course id';
$string['shortname'] = 'Percipio course id';
$string['resmessage'] = 'Response message';
$string['code'] = 'Response code';
$string['oauth'] = 'OAuth';
$string['serviceaccount'] = 'Service account bearer token';
$string['trackingimportparam'] = 'Details of user grade to be
updated with respect to moodle course - in JSON format';
$string['nouser'] = 'No user found!';
$string['nocourse'] = 'No matching course found!';
$string['success'] = 'Success';
$string['privacy:metadata:percipio_attempt'] = 'In order to integrate with percipio, user data needs to be exchanged with that percipio.';
$string['privacy:metadata:percipio_attempt:userid'] = 'The userid is sent from moodle to allow you to access your percipio content on the remote system.';
$string['privacy:metadata:percipio_attempt:grade'] = 'Your grade which is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:completionmessage'] = 'The completion message which is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:passingscore'] = 'The passings core which is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:lastscore'] = 'Your last score which is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:percentcomplete'] = 'Your percentage completion which is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:timecreated'] = 'The time when the entry is recorded is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:timecompleted'] = 'The time when you completed the content is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:timerevisited'] = 'The time when you revisited the content is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:timemodified'] = 'The modified time you revisited the content is sent from the remote system to moodle.';
$string['privacy:metadata:percipio_attempt:totalduration'] = 'The total duration which you took to complete the content is sent from the remote system to moodle.';
$string['privacy:metadata:percipio:externalpurpose'] = 'This plugin only sends the moodle userid to the percipio.';
$string['privacy:metadata:percipio:userid'] = 'Userid passed from moodle to percipio.';
