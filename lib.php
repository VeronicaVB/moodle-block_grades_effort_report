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

function get_academic_grades($username, $campus)
{

    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB.
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        if ($campus == 'Primary') {
            $sql = 'EXEC ' . $config->dbaccgradesprimary . ' :id';
        } else {

            $sql = 'EXEC ' . $config->dbaccgrades . ' :id';
        }

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

function get_academic_efforts($username, $campus)
{
    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
        // Connect to external DB
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');
        
        if ($campus == 'Primary') {
            $sql = 'EXEC ' . $config->dbefforthistoryprimary . ' :id';
        } else {
            $sql = 'EXEC ' . $config->dbefforthistory . ' :id';
        }

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

function get_performance_trend($username, $campus)
{
    try {

        $config = get_config('block_grades_effort_report');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');
      
        if ($campus == "Senior") {
            $sql = 'EXEC ' . $config->dbperformancetrend . ' :id';
        } else { 
            $sql = 'EXEC ' . $config->dbprimaryperformancetrend . ' :id';
        }

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

function get_templates_contexts($username, $instanceid, $userid, $campus)
{
    global $COURSE, $DB;
  
    $context =  get_performance_trend_context($username, $campus);
   
    $efforturlparams = array('blockid' => $instanceid, 'courseid' => $COURSE->id, 'id' => $userid, 'history' => 'effort', 'campus' => $campus);
    $gradeurlparams = array('blockid' => $instanceid, 'courseid' => $COURSE->id, 'id' => $userid, 'history' => 'grades', 'campus' => $campus);

    $ghurl =  new \moodle_url('/blocks/grades_effort_report/view.php', $gradeurlparams);
    $ehurl = new \moodle_url('/blocks/grades_effort_report/view.php', $efforturlparams);

    $username = ($DB->get_record('user', ['id' => $userid]))->fullname;
    $urlanduserdetails = ['username' => $username, 'campus' => $campus, 'instanceid' => $instanceid, 'userid' => $userid, 'gradeurl' => $ghurl, 'efforturl' => $ehurl];

    $context = array_merge($urlanduserdetails, $context);

    return $context;
}



function get_templates_context($tabletorender, $username, $campus) {

    if ($campus == 'Primary') {
        return get_templates_context_primary($tabletorender, $username, $campus);
    } else {
        return get_templates_context_senior($tabletorender, $username, $campus);
    }
}

function get_templates_context_primary($tabletorender, $username, $campus) {
    
    $gradesdata = $tabletorender == 'grades' ?  get_academic_grades($username, $campus) : get_academic_efforts($username, $campus);
  
    if ($tabletorender == 'grades') { 

        $gradesdata = get_academic_grades($username, $campus) ;
        return get_template_primary_grade_history($gradesdata);

    } else {

        $gradesdata = get_academic_efforts($username, $campus);
        return get_template_primary_effort_history($gradesdata);;
    }
    
}


function get_template_primary_grade_history($gradesdata) {

    $filesemesterlabel = [
        '3' => 'Report 2',
        '4' => 'Report 3'
    ]; // terms  are called reports. Report 1  is a welcome letter. It doesnt appear here.


    $ibscales = [
        5 => ['', 'Below expectations', '#FF0000'],
        4 => ['GS', 'Good Start', '#FFA07A'],
        3 => ['MS', 'Making strides', '#F3FCD6'],
        2 => ['GRWI', 'Go run with it', '#90EE90'],
        1 => ['', 'Above expectations', '#90EE90']
    ];
     
  
    foreach ($gradesdata as $data) {
        //Year, semester, term, learning area
        $subject = new \stdClass();
        $subject->assessment = ( strtolower($data->assessareaheading) == 'grade' ? '' : $data->assessareaheading);
        $subject->report = $filesemesterlabel[$data->filesemester];
        $subject->grade = $data->assessresultdescription;
        $subject->filesemester = $data->filesemester;
        $context[$data->fileyear][$data->assessheading][$data->filesemester][] = $subject;
      
    }
  
    $years = [];
    $assessments = [];
    $contexts = [];
    $lareas = [];
    $learningareas = [];
    $reports = [];
    $reportsaux = [];
    $assessmenttitlesandgrades = [];

    foreach($context as $year => $subjects) {
        
        $y = new \stdClass();
        $y->year = $year;
        $years['years'][] = $y;

        foreach ($subjects as $area => $assigments) {

            array_push($lareas, $area);

            foreach ($assigments as $i => $assignment) {
                array_push($reports, $filesemesterlabel[$i]);
              
                foreach ($assignment as $j => $assess) {
                    $details = new \stdClass();
                    $details->assessment = $assess->assessment;
                    $details->grade =  $ibscales[$assess->grade][1];
                    $details->area = $area;
                    $details->year = $year;
                    $details->ibscale = $ibscales[$assess->grade][0]; //The label to display
                    $details->bgcolour = $ibscales[$assess->grade][2];
                    $details->filesemester = $assess->filesemester;
                    // Group by Area, assessment
                    if (!in_array($assess->assessment,  $assessmenttitlesandgrades[$area])) {
                        $assessmenttitlesandgrades[$area][$assess->assessment][] = $details;
                        $assessmenttitlesaux[$area][$assess->assessment][$year][$assess->filesemester][] = $details;
                    }
                  
                }
             
            }
        }
    
    }

    if (count($reports) == 2) {
        if ($reports[0] == $reports[1]) { // There are cases where there is only one term info, rename the first column
            $reports[0] = $filesemesterlabel[3];
        }
    }
  
    $reports = array_slice($reports, 0, (count($context) * 2) );

    $lareas = array_unique($lareas);

    // Get the first year we have records of and the last.
    $minyear = array_key_first($context);
    $maxyear = array_key_last($context);
    $columnstofill = (count($context) * 2);

    $dummygrade1 = new \stdClass();
    $dummygrade1->grade = '';
    $dummygrade1->year = '';
    $dummygrade1->filesemester = '';

    $dummygrade2 = new \stdClass();
    $dummygrade2->grade = '';
    $dummygrade1->year = '';
    $dummygrade2->filesemester = '4';

    $dummyrow = new \stdClass(); // Fill the rest of the rows of the title area i.e: English, Mathematics 
    $dummyrow->dummyvalue = '';
    $dummyrows = [];
  
    for($a = 0; $a < $columnstofill; $a++) {
        $dummyrows[] = $dummyrow;
    }

    $dummyrepo = new \stdClass();
    $dummyrepo->report = '';
    $reportsaux['repos'][0] = $dummyrepo;

    foreach($reports as $report) {
        $repo = new \stdClass();
        $repo->report = $report;
        $reportsaux['repos'][] = $repo;
    }
  
    foreach ($lareas as $area) {
        $la = new \stdClass();
        $la->area = $area;
        $la->dummyrows =$dummyrows;
        $assesmentdetails = [];
        
        foreach ($assessmenttitlesandgrades[$area] as $assessname => $assessments) {
            $gradesdetails = new \stdClass();
            $gradesdetails->assessname = $assessname;
            $grades = [];

            if (count($assessments) < $columnstofill) { 
                $tofill = $columnstofill - count($assessments);
                for($i = 0; $i < $tofill; $i++) {
                    array_push($assessments, $dummygrade1);
                }

            }
           $assessments = sort_years_for_primary($assessments, $minyear, $maxyear);
            foreach ($assessments as $y =>  $assessment) {
                $grades['assesmentgrades'][] = $assessment;
            }

            $gradesdetails->grades = $grades;
            $assesmentdetails['assesmentdets'][] = $gradesdetails;
            $la->assesmentdetails = $assesmentdetails;
        }
        
        $learningareas['areas'][] = $la;
    }

    $contexts = [
        'yearlabels' => $years,
        'reports' => $reportsaux,       
        'learningareas' => $learningareas,
    ];

    return $contexts;
}

function sort_years_for_primary($assessments, $minyear, $maxyear) {

    $filledyears = [];
    $dummygrade1 = new \stdClass();
    $dummygrade1->grade = '';
    $dummygrade1->year = '';
    $dummygrade1->filesemester = '';

    foreach($assessments as $assessment) {
        if (!empty($assessment->year)) {
            $filledyears[$assessment->year][] = $assessment;
        }

    }

    for($i = $minyear; $i <= $maxyear; $i++) {
        if (empty($filledyears[$i]) ) {
            $filledyears[$i] = [$dummygrade1, $dummygrade1];
        } else if (count($filledyears[$i]) < 2) {
            $fyearaux = $filledyears[$i];
            list($ce) = $fyearaux;
            if($ce->filesemester == '4') { // We have to put a dummy grade on term 3
                $dummygrade1->filesemester = 3;
                array_unshift($filledyears[$i], $dummygrade1);
            } else if ($ce->filesemester == '3') { // Dummy grade on term 4
                $dummygrade1->filesemester = 4;
                array_push($filledyears[$i], $dummygrade1);
            }

        }
        $dummygrade1->filesemester = '';
    }
    ksort($filledyears);
    $yearsfilled = [];
    foreach ($filledyears as $years => $assess) {
        foreach($assess as $a) {
            array_push($yearsfilled, $a);
        }
     
    }
    return $yearsfilled;
}

function get_template_primary_effort_history($gradesdata) {

    $bgcolour[] = '';
    foreach ($gradesdata as $data) {
           
        if ($data->filesemester == 3 ) {
            $filesemesterlabel[$data->filesemester] = 'Report 2';
        } else if ($data->filesemester == 4) {
            $filesemesterlabel[$data->filesemester] = 'Report 3';
        } else {
            $filesemesterlabel[$data->filesemester] = '';
        }
        if ($data->assessresultsresult == 'A') {
            $bgcolour[$data->assessresultsresult] = "#F3FCD6";          
        } else if ($data->assessresultsresult == 'E' || $data->assessresultsresult == "VG") {
            $bgcolour[$data->assessresultsresult] = '#90EE90';
        }
    }

    $filesemesterlabel = array_unique($filesemesterlabel);
    foreach ($gradesdata as $data) {
        //Year, semester, term, learning area
        $subject = new \stdClass();
        $subject->assessment =  $data->classdescription;
        $subject->report = $filesemesterlabel[$data->filesemester];
        $subject->grade = $data->assessresultsresult;
        $subject->title = end(explode(' ', $data->assessresultdescription));
        $subject->bgcolour =  $bgcolour[ $subject->grade];
        $subject->filesemester = $data->filesemester;
        $context[$data->fileyear][$data->assessheading][$data->filesemester][] = $subject;
        
        
    }


    $years = [];
    $assessments = [];
    $contexts = [];
    $lareas = [];
    $learningareas = [];
    $reports = [];
    $reportsaux = [];
    $assessmenttitlesandgrades = [];

    foreach($context as $year => $subjects) {
        
        $y = new \stdClass();
        $y->year = $year;
        $years['years'][] = $y;

        foreach ($subjects as $area => $assigments) {

            array_push($lareas, $area);

            foreach ($assigments as $i => $assignment) {
                array_push($reports, $filesemesterlabel[$i]);
              
                foreach ($assignment as $j => $assess) {
                    $details = new \stdClass();
                    $details->assessment = $assess->assessment;
                    $details->grade =  $assess->grade . " ($assess->title) ";
                    $details->area = $area;
                    $details->year = $year;
                    $details->ibscale = $assess->grade; //The label to display
                    $details->bgcolour = $bgcolour[$assess->grade];
                    $details->filesemester = $assess->filesemester;
                    // Group by Area, assessment
                    if (!in_array($assess->assessment,  $assessmenttitlesandgrades[$area])) {
                        $assessmenttitlesandgrades[$area][$assess->assessment][] = $details;
                        $assessmenttitlesaux[$area][$assess->assessment][$year][$assess->filesemester][] = $details;
                    }
                  
                }
             
            }
        }
    
    }

    $reports = array_slice($reports, 0, (count($context) * 2) );
    $lareas = array_unique($lareas);

    // Get the first year we have records of and the last.
    $minyear = array_key_first($context);
    $maxyear = array_key_last($context);
    $columnstofill = (count($context) * 2);

    $dummygrade1 = new \stdClass();
    $dummygrade1->grade = '';
    $dummygrade1->year = '';
    $dummygrade1->filesemester = '';

    $dummygrade2 = new \stdClass();
    $dummygrade2->grade = '';
    $dummygrade1->year = '';
    $dummygrade2->filesemester = '4';

    $dummyrow = new \stdClass(); // Fill the rest of the rows of the title area i.e: English, Mathematics 
    $dummyrow->dummyvalue = '';
    $dummyrows = [];
  
    for($a = 0; $a < $columnstofill; $a++) {
        $dummyrows[] = $dummyrow;
    }

    $dummyrepo = new \stdClass();
    $dummyrepo->report = '';
    $reportsaux['repos'][0] = $dummyrepo;

    foreach($reports as $report) {
        $repo = new \stdClass();
        $repo->report = $report;
        $reportsaux['repos'][] = $repo;
    }
  
    foreach ($lareas as $area) {
        $la = new \stdClass();
        $la->area = $area;
        $la->dummyrows =$dummyrows;
        $assesmentdetails = [];
        
        foreach ($assessmenttitlesandgrades[$area] as $assessname => $assessments) {
            $gradesdetails = new \stdClass();
            $gradesdetails->assessname = $assessname;
            $grades = [];

            if (count($assessments) < $columnstofill) { 
                $tofill = $columnstofill - count($assessments);
                for($i = 0; $i < $tofill; $i++) {
                    array_push($assessments, $dummygrade1);
                }

            }
           $assessments = sort_years_for_primary($assessments, $minyear, $maxyear);
            foreach ($assessments as $y =>  $assessment) {
                $grades['assesmentgrades'][] = $assessment;
            }

            $gradesdetails->grades = $grades;
            $assesmentdetails['assesmentdets'][] = $gradesdetails;
            $la->assesmentdetails = $assesmentdetails;
        }
        
        $learningareas['areas'][] = $la;
    }

    $contexts = [
        'yearlabels' => $years,
        'reports' => $reportsaux,       
        'learningareas' => $learningareas,
    ];

  return $contexts;
}



function get_templates_context_senior($tabletorender, $username, $campus) {

    $gradesdata = $tabletorender == 'grades' ?  get_academic_grades($username, $campus) : get_academic_efforts($username, $campus);

    if (empty($gradesdata)) {
        return;
    }

    $colours = [
        'LightGreen' => '#8fd9a8',
        'LightSalmon' => '#ffba93',
        '#F3FCD6' => '#F3FCD6',
        'HotPink' => '#e05297',
        'whitesmoke' => 'whitesmoke'
    ];

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
            'effort' => $tabletorender == 'effort' ? $data->effortstuff : '',
            'bcolour' => $colours[$data->backgroundcolour],
            'fcolour' => $data->fontcolour
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
            if ($tabletorender == 'effort') {
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
//for senior table
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

// for senior table
function fill_dummy_grades($grades, $yearlabels, $effort = false)
{
    $countyears = count($yearlabels['labels']);
    $totaltermstograde = $countyears * 4; // Each year has 4 terms;
    $missingterms = ($totaltermstograde - count($grades['grade']));

    $earliestyear = explode(' ', (current($yearlabels['labels']))->label);
    $earliestyear = end($earliestyear);
    $latestyear = explode(' ', (end($yearlabels['labels']))->label);
    $latestyear = end($latestyear);

    if ($missingterms > 0) {
        $grades =  add_dummy_grade_position($grades['grade'], $earliestyear, $latestyear, $totaltermstograde, $effort);
    }

    return $grades;
}

// $earliestyear = The first year the sp brings back.
// $latestyear = The last year the sp brings back.
//for senior table
function add_dummy_grade_position($grades, $earliestyear, $latestyear, $totaltermstograde, $effort = false)
{
    $counttermspergrade = [];
    $dummygrade = new \stdClass();
    $dummygrade->grade = '';
    $dummygrade->bcolour = '#FFFFFF';
    $dummygrade->fcolour = '#FFFFFF';

    foreach ($grades as  $grade) {

        foreach ($grade as $gr => $gra) {

            if ($gr == 'year') {

                if (!$effort) {
                    $g = new \stdClass();
                    $g->grade =  $grade['grade'];
                    $counttermspergrade[$gra][$grade['term']] = ['g' => $grade['grade'], 'bcolour' => $grade['bcolour'], 'fcolour' => $grade['fcolour']];
                } else {
                    $counttermspergrade[$gra][$grade['term']] = ['g' => $grade['grade'], 'e' => $grade['effort'], 'bcolour' => $grade['bcolour'], 'fcolour' => $grade['fcolour']];
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
                        $dummygrade->bcolour = '#FFFFFF';
                        $dummygrade->fcolour = '#FFFFFF';
                        $terms[$j] = $dummygrade;
                    }
                }
            }
            ksort($terms);
        }

        $grades = [];
        // Rearange the array to feed the template.
        if ($effort) {

            foreach ($results as $r => &$terms) {
                foreach ($terms as $t => $term) {
                    $gradeaux = new \stdClass();
                    $gradeaux->grade = '';
                    $gradeaux->bcolour = '#FFFFFF';
                    $gradeaux->fcolour = '#FFFFFF';

                    if (is_array($term)) {
                        if (isset($term['e'])) {

                            $gradeaux->grade = $term['g'];
                            $gradeaux->bcolour = $term['bcolour'];
                            $gradeaux->fontcolour = $term['fcolour'];
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

                    if (is_array($term)) {
                        $gradeaux->grade = $term['g'];
                        $gradeaux->bcolour = $term['bcolour'];
                        $gradeaux->fcolour = $term['fcolour'];
                    }
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
        
        $mentor = get_mentor($profileuser);
        // Students are allowed to see timetables in their own profiles.
        
        if ($profileuser->username == $USER->username && !is_siteadmin($USER) && $mentor) {
            return true;
        }

        if (!empty($mentor)) {
            return true;
        }
    }

    return false;
}

function get_mentor($profileuser)
{
    global $DB, $USER;
    // Parents are allowed to view block in their mentee profiles.
    $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));
    $mentor = null;

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
    }

    return $mentor;
}

function get_performance_trend_context($username, $campus) {
  
    if ($campus == 'Senior') {
        return get_performance_trend_senior($username);
    }  else {
        return get_performance_trend_primary($username);

    }
}


function get_performance_trend_senior($username) {
    $results = get_performance_trend($username, 'Senior');
    $trends = [];


    if (empty($results)) {
        return $trends;
    }

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

            // Avgs/year.
            if (!empty($summary->assessresultsresultcalc)) {
                $details->avggrades = floatval(round($summary->assessresultsresultcalc / $summary->subjects, 2));
            }

            if (!empty($summary->effortmark)) {
                $details->avgeffort =  floatval(round($summary->effortmark / $summary->subjects, 2));
            }

            if (!empty($summary->classattendperterm)) {
                $details->avgattendance = floatval(round(($summary->classattendperterm / $summary->classcountperterm) * 100, 2));
            }

            $context[] = ['details' => $details];
        }
    }

    return ['performance' => json_encode($context)];
}

function get_performance_trend_primary($username) {

    $results = get_performance_trend($username, 'Primary');

    $trends = [];


    if (empty($results)) {
        return $trends;
    }
 
    foreach ($results as $i => $result) {
        $summary = new \stdClass();
        $summary->fileyear = $result->fileyear;
        $summary->term = $result->filesemester;
        $summary->percentageattended = $result->percentageattended;
        $summary->gradeaverage = $result->gradeaverage;
        $summary->effortaverage = $result->effortaverage;
        $trends[$result->fileyear][$result->filesemester] = $summary;
        $trends[$result->fileyear][$result->filesemester] = $summary;
    }

    foreach ($trends as $year => $summaries) {

        foreach ($summaries as $term => $summary) {
            $details = new \stdClass();
            $details->year = $year;
            $details->term = $term;
            $details->avggrades = $summary->gradeaverage;
            // $details->gradeavgdesc = $summary->gradeavgdesc;
            // $details->effortvgdesc = $summary->effortvgdesc;
            $details->effortaverage = $summary->effortaverage;
            $details->percentageattended =  $summary->percentageattended;
            $context[] = ['details' => $details];
        }
    }
   // print_object($context); exit;
    return ['performance' => json_encode($context)];

}