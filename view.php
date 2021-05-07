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

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/grades_effort_report/lib.php');


global $DB, $OUTPUT, $PAGE, $USER;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
$history = required_param('history', PARAM_TEXT); // 

// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_grades_effort_report', $courseid);
}

require_login($course);

$PAGE->set_url('/blocks/grades_effort_report/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_grades_effort_report'));

$heading = ($history == 'grades') ? get_string('gradehistory', 'block_grades_effort_report') :  get_string('efforthistory', 'block_grades_effort_report'); 
$PAGE->set_heading($heading);

$nav = $PAGE->navigation->add(get_string('profile', 'block_grades_effort_report'), $CFG->wwwroot.'/user/view.php?id='. $id);
$reporturl = new moodle_url('/blocks/grades_effort_report/view.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid, 'history' => $history));
$reportnode = $nav->add(get_string('gradesandeffortreportitle', 'block_grades_effort_report'), $reporturl);
$reportnode->make_active();

echo $OUTPUT->header();


$profileuser = $DB->get_record('user', ['id' => $id]);
$data =  \grades_effort_report\get_templates_context($history, $profileuser->username);

if (is_siteadmin($USER)) {
    $data['studentname'] = $profileuser->firstname . ' ' .  $profileuser->lastname;
} else {
    $data['studentname'] = $USER->firstname . ' ' .  $USER->lastname;
}
if ($history == 'grades') {
    echo $OUTPUT->render_from_template('block_grades_effort_report/grades_history', $data);
} else {
    echo $OUTPUT->render_from_template('block_grades_effort_report/effort_history', $data);
}
echo $OUTPUT->footer();
