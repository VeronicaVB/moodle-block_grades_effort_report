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
require_once($CFG->dirroot . '/blocks/grades_effort_report/lib.php');

class block_grades_effort_report extends block_base
{
    public function init()
    {
        $this->title = get_string('grades_effort_report', 'block_grades_effort_report');
    }

    public function get_content()
    {
        global $OUTPUT, $COURSE, $DB, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }
        $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);

        $config = get_config('block_grades_effort_report');

        $this->title = get_string('grades_effort_report', 'block_grades_effort_report');

        $config = get_config('block_grades_effort_report');

       profile_load_custom_fields($profileuser);

       // Determing which user role we are rendering to.
       // This block assumes users have custom profile fields for CampusRoles.
       $userroles = array();
        if (isset($profileuser->profile['CampusRoles'])) {
            $userroles = explode(',', $profileuser->profile['CampusRoles']);
            // Do regex checks.
            foreach ($userroles as $reg) {
                $regex = "/${reg}/i";
                if ((preg_match($regex, 'primary') === 1)) {
                    $this->content->text = '';
                    return $this->content;
                }
            }
        }

        // Check DB settings are available.
        if (
            empty($config->dbtype) ||
            empty($config->dbhost) ||
            empty($config->dbuser) ||
            empty($config->dbpass) ||
            empty($config->dbname) ||
            empty($config->dbaccgrades) ||
            empty($config->dbefforthistory) ||
            empty($config->dbperformancetrend)
        ) {
            $notification = new \core\output\notification(
                get_string('nodbsettings', 'block_grades_effort_report'),
                \core\output\notification::NOTIFY_ERROR
            );
            $notification->set_show_closebutton(false);
            return $OUTPUT->render($notification);
        }

        $this->content = new \stdClass();
        $this->content->text = '';
       
        try {
            if (grades_effort_report\can_view_on_profile()) {
                $data = grades_effort_report\get_templates_contexts($profileuser->username, $this->instance->id, $profileuser->id); 
                empty($data) ? $this->content->text = '' : $this->content->text = $OUTPUT->render_from_template('block_grades_effort_report/main', $data);
            }
        } catch (\Exception $e) {
           // var_dump($e);
        }

        return $this->content;
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    public function instance_allow_config()
    {
        return true;
    }

    public function has_config()
    {
        return true;
    }

    public function hide_header()
    {
        return true;
    }

    public function applicable_formats()
    {
        return array('user-profile' => true);
    }
}
