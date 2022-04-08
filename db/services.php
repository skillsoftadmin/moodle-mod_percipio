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
 * mod_percipio api external functions and service definitions.
 *
 * @package    mod_percipio
 * @copyright  2022 <Parthajeet.C@harbingergroup.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// We defined the web service functions to install.
$functions = array(
    'percipio_asset_import' => array(
        'classname'   => 'mod_percipio_api_external',
        'methodname'  => 'percipio_import_course',
        'classpath'   => 'mod/percipio/externallib.php',
        'description' => 'Import course from Percipio into Moodle',
        'type'        => 'write',
        'capabilities' => 'moodle/category:manage, moodle/course:create, moodle/course:visibility, moodle/course:update, '
            . 'moodle/course:changecategory, moodle/course:changeshortname, '
            . 'moodle/course:changeidnumber, enrol/self:config'
    ),
    'percipio_progress_tracking' => array(
        'classname'   => 'mod_percipio_api_external',
        'methodname'  => 'percipio_update_activity_progress',
        'classpath'   => 'mod/percipio/externallib.php',
        'description' => 'Import user grades from Percipio into Moodle w.r.t a course',
        'type'        => 'write',
        'capabilities' => 'moodle/grade:manage'
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Percipio services' => array(
    'functions' => array ('percipio_asset_import', 'percipio_progress_tracking'),
    'restrictedusers' => 0,
    'enabled' => 1,
    'shortname' => 'Percipio-API'
  ),
);
