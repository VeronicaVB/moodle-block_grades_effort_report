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
 * Grades and Effort block
 *
 * @package   block_grades_effort_report
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
            'block_grades_effort_report',
            '',
            get_string('pluginname_desc', 'block_grades_effort_report')
    ));

    $options = array('', "mysqli", "oci", "pdo", "pgsql", "sqlite3", "sqlsrv");
    $options = array_combine($options, $options);

    $settings->add(new admin_setting_configselect(
            'block_grades_effort_report/dbtype',
            get_string('dbtype', 'block_grades_effort_report'),
            get_string('dbtype_desc', 'block_grades_effort_report'),
            '',
            $options
    ));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbhost', get_string('dbhost', 'block_grades_effort_report'), get_string('dbhost_desc', 'block_grades_effort_report'), 'localhost'));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbuser', get_string('dbuser', 'block_grades_effort_report'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('block_grades_effort_report/dbpass', get_string('dbpass', 'block_grades_effort_report'), '', ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbname', get_string('dbname', 'block_grades_effort_report'), get_string('dbname_desc', 'block_grades_effort_report'), 'localhost'), '');

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbaccgrades', get_string('dbaccgradessenior', 'block_grades_effort_report'), get_string('dbaccgrades_desc', 'block_grades_effort_report'), ''));
    
    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbaccgradesprimary', get_string('dbaccgradesprimary', 'block_grades_effort_report'), get_string('dbaccgrades_desc', 'block_grades_effort_report'), ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbefforthistory', get_string('dbefforthistorysenior', 'block_grades_effort_report'), get_string('dbefforthistory_desc', 'block_grades_effort_report'), ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbefforthistoryprimary', get_string('dbefforthistoryprimary', 'block_grades_effort_report'), get_string('dbefforthistory_desc', 'block_grades_effort_report'), ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbperformancetrend', get_string('dbperformancetrend', 'block_grades_effort_report'), get_string('dbperformancetrend_desc', 'block_grades_effort_report'), ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/dbprimaryperformancetrend', get_string('dbprimaryperformancetrend', 'block_grades_effort_report'), get_string('dbperformancetrend_desc', 'block_grades_effort_report'), ''));

    $settings->add(new admin_setting_configtext('block_grades_effort_report/profileurl', get_string('profileurl', 'block_grades_effort_report'), get_string('profileurl_desc', 'block_grades_effort_report'), ''));
    

    

    
}
