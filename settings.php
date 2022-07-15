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
 * Plugin administration pages are defined here.
 *
 * @package     mod_percipio
 * @category    admin
 * @copyright   2022 Skillsoft Ireland Limited - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // TODO: Define the plugin settings page.
    // https://docs.moodle.org/dev/Admin_settings.

    $settings->add(new admin_setting_configselect('percipio/authenticationmethod',
        get_string('authenticationmethod', 'mod_percipio'), get_string('authenticationmethod_help', 'mod_percipio'),
        'service_account_bearer_token', ['oauth' => get_string('oauth', 'mod_percipio'),
        'service_account_bearer_token' => get_string('serviceaccount', 'mod_percipio')] ));

    $settings->add(new admin_setting_configtext('percipio/clientid',
        get_string('clientid', 'mod_percipio'), get_string('clientid_help', 'mod_percipio'), '', PARAM_ALPHANUMEXT));
    $settings->hide_if('percipio/clientid', 'percipio/authenticationmethod', 'eq', 'service_account_bearer_token');

    $settings->add(new admin_setting_configtext('percipio/clientsecret',
        get_string('clientsecret', 'mod_percipio'), get_string('clientsecret_help', 'mod_percipio'), '', PARAM_ALPHANUMEXT));
    $settings->hide_if('percipio/clientsecret', 'percipio/authenticationmethod', 'eq', 'service_account_bearer_token');

    $settings->add(new admin_setting_configtext('percipio/scope',
    get_string('scope', 'mod_percipio'), get_string('scope_help', 'mod_percipio'), '', PARAM_TEXT));
    $settings->hide_if('percipio/scope', 'percipio/authenticationmethod', 'eq', 'service_account_bearer_token');

    $settings->add(new admin_setting_configtext('percipio/bearertoken',
        get_string('bearertoken', 'mod_percipio'), get_string('bearertoken_help', 'mod_percipio'), '', PARAM_TEXT));
    $settings->hide_if('percipio/bearertoken', 'percipio/authenticationmethod', 'eq', 'oauth');

    $settings->add(new admin_setting_configtext('percipio/oauthurl',
        get_string('oauthurl', 'mod_percipio'), get_string('oauthurl_help', 'mod_percipio'), '', PARAM_URL));
    $settings->hide_if('percipio/oauthurl', 'percipio/authenticationmethod', 'eq', 'service_account_bearer_token');

    $settings->add(new admin_setting_configtext('percipio/percipiourl',
        get_string('percipiourl', 'mod_percipio'), get_string('percipiourl_help', 'mod_percipio'),
        '', PARAM_URL));
    
    $settings->add(new admin_setting_configtext('percipio/organizationid',
    get_string('organizationid', 'mod_percipio'), get_string('organizationid_help', 'mod_percipio'), '', PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configselect('percipio/piiinfo',
        get_string('piiinfo', 'mod_percipio'),'',
        'no', ['yes' => get_string('pii_yes', 'mod_percipio'),
        'no' => get_string('pii_no', 'mod_percipio')] ));
}
