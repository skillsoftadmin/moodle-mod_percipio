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
 * This file contains percipio mobile config
 *
 * @package    mod_percipio
 * @copyright  2022 Skillsoft Ireland Limited - All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$addons = array(
    "mod_percipio" => array(
          "handlers" => array(
              'percipiodetails' => array(
                  'displaydata' => array(
                  'title' => 'pluginname',
                      'icon' => $CFG->wwwroot . '/mod/percipio/pix/icon.png',
                      'class' => '',
                  ),
                  'delegate' => 'CoreCourseModuleDelegate',
                  'method' => 'mobile_course_view', // Main function in \mod_percipio\output\mobile.
                  'offlinefunctions' => array(
                      'mobile_course_view' => array(),
                  ),
                ),
                'reportdetails' => array(
                    'displaydata' => array(
                    'title' => 'report',
                        'class' => '',
                    ),
                    'priority' => 200,
                    'delegate' => 'CoreCourseOptionsDelegate',
                    'method' => 'mobile_report_view', // Report view function in \mod_percipio\output\mobile.
                    'offlinefunctions' => array(
                        'mobile_report_view' => array(),
                    ),
                )
          ),
          'lang' => array(
              array('pluginname', 'percipio'),
              array('report', 'percipio'),
              array('publisheddate', 'percipio'),
              array('duration', 'percipio'),
              array('expertiselevel', 'percipio'),
              array('area', 'percipio'),
              array('modality', 'percipio'),
              array('subject', 'percipio'),
              array('launch', 'percipio')
          )
      )
  );
