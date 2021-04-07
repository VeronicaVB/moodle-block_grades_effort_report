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
 * Grades and performance block
 *
 * @package   block_grades_effort_report
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Call to the SP Class_Attendance_By_Term
 */

namespace grades_effort_report;

use stdClass;

function get_academic_grades($username)
{

    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB.
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        $sql = 'EXEC ' . $config->dbaccgrades . ' :id';

        $params = array(
            'id' => $username,
        );

        $result = $externalDB->get_recordset_sql($sql, $params);
        $academicgrades = [];

        foreach ($result as $grades) {
            $academicgrades[] = $grades;
        }

        $result->close();


        return $academicgrades;
    } catch (\Exception $ex) {
        throw $ex;
    }
}

function get_academic_efforts($username)
{
    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        $sql = 'EXEC ' . $config->dbefforthistory . ' :id';

        $params = array(
            'id' => $username,
        );

        $result = $externalDB->get_recordset_sql($sql, $params);
        $academiceffort = [];

        foreach ($result as $effort) {
            $academiceffort[] = $effort;
        }

        $result->close();

        return $academiceffort;
    } catch (\Exception $ex) {
        throw $ex;
    }
}

function get_performance_trend($username)
{
    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        $sql = 'EXEC ' . $config->dbperformancetrend . ' :id';

        $params = array(
            'id' => $username,
        );

        $result = $externalDB->get_recordset_sql($sql, $params);
        $performancetrends = [];

        foreach ($result as $performance) {
            $performancetrends[] = $performance;
        }

        $result->close();

        return $performancetrends;
    } catch (\Exception $ex) {
        throw $ex;
    }
}

function get_templates_contexts($username)
{

    $context = array_merge(
        get_templates_context('grades', $username),
        get_templates_context('effort', $username),
        get_performance_trend_context($username)
    );
    return $context;
}

function get_templates_context($tabletorender, $username)
{

    $gradesdata = $tabletorender == 'grades' ?  get_academic_grades($username) : get_academic_efforts($username);

    if (empty($gradesdata)) {
        return [];
    }
    $yearlevels['year'] = [];
    $yearlabels['labels'] = [];
    $subjects['subjects'] = [];
    $areas['areas'] = [];
    $subjectdetails['classess'] = [];
    $termlabels = new \stdClass();
    $termlabels->t1 = 'T1';
    $termlabels->t2 = 'T2';
    $termlabels->t3 = 'T3';
    $termlabels->t4 = 'T4';

    foreach ($gradesdata as $data) {

        // Get the years.
        if (!in_array($data->studentyearlevel, $yearlevels['year'])) {
            $yearlevels['year'][] = $data->studentyearlevel;
            $yl = new \stdClass();
            $yl->label = "Year  $data->studentyearlevel";
            $yl->termlabels = $termlabels;
            $yearlabels['labels'][] = $yl;
        }

        $grades[$data->classdescription]['grade'][] = [
            'year' => $data->studentyearlevel,
            'term' => $data->filesemester,
            'grade' => $data->assessresultsresult,
            'performance' => $tabletorender == 'performance' ? $data->performancestuff : ''
        ];

        $subjects['subjects'][$data->classlearningareadescription][$data->classdescription][] = [
            'year' => $data->studentyearlevel,
            'term' => $data->filesemester,
        ];
    }

    foreach ($subjects['subjects'] as $area => $subjects) {

        foreach ($subjects as $s => $subject) {
            $classdetails = new \stdClass();
            $classdetails->name = $s;
            if ($tabletorender == 'performance') {
                $classdetails->grades = fill_dummy_grades($grades[$s], $yearlabels, true);
            } else {
                $classdetails->grades = fill_dummy_grades($grades[$s], $yearlabels);
            }
            $subjectdetails['classess'][] = $classdetails;
            $subjectdetails =  find_subject($subjectdetails, $s);
        }
    }
    $s = [];
    $s['classes'] =  array_merge($subjectdetails['classess']); // Reset the grades array index to be able to render in the template.
    $context = ['years' . '_' . $tabletorender => $yearlabels, 'subjectdetails' . '_' . $tabletorender => $s];
    return $context;
}

function find_subject($classes, $name)
{
    $i = 0;
    $keys = [];
    foreach ($classes['classess'] as $j => $class) {

        if ($class->name == $name) {
            $i++;
        }

        if ($i > 1) {
            $keys[] = $j;
        }
    }

    foreach ($keys as $key) {
        unset($classes['classess'][$key]);
    }

    return $classes;
}


function fill_dummy_grades($grades, $yearlabels, $performance = false)
{
    $countyears = count($yearlabels['labels']);
    $totaltermstograde = $countyears * 4; // Each year has 4 terms;
    $missingterms = ($totaltermstograde - count($grades['grade']));

    $earliestyear = explode(' ', (current($yearlabels['labels']))->label);
    $earliestyear = end($earliestyear);
    $latestyear = explode(' ', (end($yearlabels['labels']))->label);
    $latestyear = end($latestyear);

    if ($missingterms > 0) {
        $grades =  add_dummy_grade_position($grades['grade'], $earliestyear, $latestyear, $totaltermstograde, $performance);
    }

    return $grades;
}

// $earliestyear = The first year the sp brings back.
// $latestyear = The last year the sp brings back.
function add_dummy_grade_position($grades, $earliestyear, $latestyear, $totaltermstograde, $performance = false)
{
    $counttermspergrade = [];
    $dummygrade = new \stdClass();
    $dummygrade->grade = '';

    foreach ($grades as $g => $grade) {

        foreach ($grade as $gr => $gra) {

            if ($gr == 'year') {

                if (!$performance) {
                    $g = new \stdClass();
                    $g->grade =  $grade['grade'];
                    $counttermspergrade[$gra][$grade['term']] = $g;
                } else {
                    $counttermspergrade[$gra][$grade['term']] = ['g' => $grade['grade'], 'e' => $grade['performance']];
                }
            }
        }
    }

    //get the first year and last year the subject has data. 
    $earliest = array_key_first($counttermspergrade);
    $latest = array_key_last($counttermspergrade);
    $aux = $counttermspergrade;

    foreach ($counttermspergrade as $t => $terms) {

        $totaltoadd = $totaltermstograde - count($terms);
        $dummyyearsandgrades = [];

        if ($earliestyear == $latest) { //  Only the first year has data.

            $index = $earliestyear++;
            $dummyyearsandgrades = [$index => []];

            for ($i = 0; $i <= $totaltoadd; $i += 4) {
                $dummyyearsandgrades[$index] = [$dummygrade, $dummygrade, $dummygrade, $dummygrade];
                $index++;
            }
        } else if ($latestyear == $earliest) { // Only last year has data, fill the previous years.
            $index = $earliestyear;
            for ($i = 0; $i < $totaltoadd; $i += 4) {
                $dummyyearsandgrades[$index] = [$dummygrade, $dummygrade, $dummygrade, $dummygrade];
                $index++;
            }
        } else if ($earliest > $earliestyear) {  // Years in between.

            $dummyyearsandgrades[$earliestyear] = [$dummygrade, $dummygrade, $dummygrade, $dummygrade];

            for ($q = ($earliestyear + 1); $q <= $latestyear; $q++) {
                if (!array_key_exists($q, $counttermspergrade)) {
                    $dummyyearsandgrades[$q] = [$dummygrade, $dummygrade, $dummygrade, $dummygrade];
                }
            }
        } else if ($earliest < $latestyear) {  // Fill future years.
            $p = $earliest;
            for ($p; $p <= $latestyear; $p++) {
                if (!array_key_exists($p, $counttermspergrade)) {
                    $dummyyearsandgrades[$p] = [$dummygrade, $dummygrade, $dummygrade, $dummygrade];
                }
            }
        }

        $results = $aux + $dummyyearsandgrades;
        ksort($results);

        foreach ($results as $year => &$terms) {
            if (count($terms) < 4) {
                for ($j = 1; $j < 5; $j++) {
                    if (!array_key_exists($j, $terms)) {
                        $dummygrade = new stdClass();
                        $dummygrade->grade = '';
                        $terms[$j] = $dummygrade;
                    }
                }
            }
            ksort($terms);
        }
        $grades = [];
        // Rearange the array to feed the template.
        if ($performance) {
            foreach ($results as $r => &$terms) {
                foreach ($terms as $t => $term) {
                    $gradeaux = new \stdClass();
                    $gradeaux->grade = '';

                    if (is_array($term)) {
                        if (isset($term['e'])) {
                            $gradeaux->grade = $term['g'];
                            $gradeaux->notes = (str_replace("[:]", "<br>", $term['e']));
                        }
                    }
                    $grades['grade'][] = $gradeaux;
                }
            }
        } else {

            foreach ($results as $year => &$terms) {
                foreach ($terms as $t => $term) {
                    $gradeaux = new \stdClass();
                    $gradeaux->grade = $term->grade;
                    $grades['grade'][] = $gradeaux;
                }
            }
        }


        return  $grades;
    }
}

// Parent view of own child's activity functionality
function can_view_on_profile()
{
    global $DB, $USER, $PAGE;


    $config = get_config('block_attendance_report');
    if ($PAGE->url->get_path() ==  $config->profileurl) {
        // Admin is allowed.
        $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);

        if (is_siteadmin($USER) && $USER->username != $profileuser->username) {
            return true;
        }

        // Students are allowed to see timetables in their own profiles.
        if ($profileuser->username == $USER->username && !is_siteadmin($USER)) {
            return true;
        }

        // Parents are allowed to view timetables in their mentee profiles.
        $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));

        if ($mentorrole) {
            $sql = "SELECT ra.*, r.name, r.shortname
                FROM {role_assignments} ra
                INNER JOIN {role} r ON ra.roleid = r.id
                INNER JOIN {user} u ON ra.userid = u.id
                WHERE ra.userid = ?
                AND ra.roleid = ?
                AND ra.contextid IN (SELECT c.id
                    FROM {context} c
                    WHERE c.contextlevel = ?
                    AND c.instanceid = ?)";
            $params = array(
                $USER->id, //Where current user
                $mentorrole->id, // is a mentor
                CONTEXT_USER,
                $profileuser->id, // of the prfile user
            );
            $mentor = $DB->get_records_sql($sql, $params);
            if (!empty($mentor)) {
                return true;
            }
        }
    }

    return false;
}

function get_performance_trend_context($username)
{
    $results = get_performance_trend($username);
    $trends = [];

    foreach ($results as $i => $result) {
        $summary = new \stdClass();
        $summary->assessresultsresultcalc = $result->assessresultsresultcalc;
        $summary->effortmark = $result->effortmark;
        $summary->classcountperterm = $result->classcountperterm;
        $summary->classattendperterm = $result->classattendperterm;
        $summary->term = $result->filesemester;
        $summary->subjects = 1;

        if (empty($trends[$result->fileyear][$result->filesemester])) {
            $trends[$result->fileyear][$result->filesemester] = $summary;
        } else {
            $aux = $trends[$result->fileyear][$result->filesemester];
            $summary->effortmark +=  $aux->effortmark;
            $summary->assessresultsresultcalc += $aux->assessresultsresultcalc;
            $summary->classcountperterm += $aux->classcountperterm;
            $summary->classattendperterm += $aux->classattendperterm;
            $summary->subjects += $aux->subjects;
            $trends[$result->fileyear][$result->filesemester] = $summary;
        }
    }
    $context = [];

    foreach ($trends as $year => $summaries) {

        foreach ($summaries as $term => $summary) {
            $details = new \stdClass();
            $details->year = $year;
            $details->term = $term;
            $details->avggrades = floatval(round($summary->assessresultsresultcalc / $summary->subjects, 2));
            $details->avgeffort =  floatval(round($summary->effortmark / $summary->subjects, 2));
            $details->avgattendance = floatval(round(($summary->classattendperterm / $summary->classcountperterm) * 100, 2));
            $context[] = ['details' => $details];
        }
    }

    return ['performance' => json_encode($context)];
}
