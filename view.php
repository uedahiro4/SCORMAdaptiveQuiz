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
 * Prints a particular instance of scormadaptivequiz
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_scormadaptivequiz
 * @copyright  2016 国立情報学研究所/National Institute of Informatics
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace scormadaptivequiz with the name of your module and remove this line.

//require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/scormadaptivequiz/locallib.php');

//New Chart.JS
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/ChartNew.js'));
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/shapesInChart.js'));
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/specialInChartData.js'));
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/rinChart.js'));
$PAGE->requires->css( new moodle_url('/mod/scormadaptivequiz/js/Chart.css'));
//
/*
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/Chart.Core.js'));
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/Chart.Doughnut.js'));
$PAGE->requires->js( new moodle_url('/mod/scormadaptivequiz/js/rinChart.js'));
$PAGE->requires->css( new moodle_url('/mod/scormadaptivequiz/js/Chart.css'));
*/

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... scormadaptivequiz instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('scormadaptivequiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $scormadaptivequiz  = $DB->get_record('scormadaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $scormadaptivequiz  = $DB->get_record('scormadaptivequiz', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $scormadaptivequiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('scormadaptivequiz', $scormadaptivequiz->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

// Log this request.
//add_to_log($course->id, 'scormadaptivequiz', 'view', 'view.php?id=' . $cm->id, $scormadaptivequiz->id, $cm->id);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/* Moodle 2.9,3.0
$event = \mod_scormadaptivequiz\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $scormadaptivequiz);
$event->trigger();
*/


// Print the page header.

$PAGE->set_url('/mod/scormadaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($scormadaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('scormadaptivequiz-'.$somevar);
 */


/*
 * 機能2:
 * (1) 環境変数 $COURSE から courseid を取得 : $COURSE->id
 * (2) {scormadaptivequiz} からマッピング設定された {quiz} quizid と {scorm} scormid を取得
 */
//環境変数  : $COURSE->idからコースに設定された{scormadaptivequiz}を取得
$rinrin = $DB->get_record('scormadaptivequiz', array('course'=>$COURSE->id));
//$quiz = $DB->get_record('quiz', array('id'=>$rinrin->quiz));
$quizid = $rinrin->quiz;
//$quizname = $quiz->name;

//$scorm = $DB->get_record('scorm', array('id'=>$rinrin->scorm));
$scormid = $rinrin->scorm;
//$scormname = $scorm->name;

/*
 * (3) Quiz結果{question_attempts}の対象者を特定するIDで questionusageid を取得 : $quiz_attempts->uniqueid
 * (a) 検索条件: {question_attempts} questionusageid は {quiz_attempts} uniqueid : userid と quizid から取得
 * 対象SCOのtrackデータがあるかを確認
 */
 
if ($DB->record_exists_select('quiz_attempts',
		'userid = :userid AND quiz = :quiz',
		array(	'userid'=>$USER->id, 
				'quiz'=>$quizid)))
{
	//データが「ある」場合はデータを取得: 最後に実施した quiz_attempt の uniqueid を選択する
	$quiz_attempts = $DB->get_records_sql(
	'SELECT *
		FROM {quiz_attempts} 
		WHERE userid =? AND quiz=? ORDER BY attempt DESC LIMIT 1', array($USER->id,$quizid));
	foreach ($quiz_attempts as $value) {
		$quizuniqueid = $value->uniqueid;
	}	
}else{
	//データが「ない」場合はテストを受けるようにメッセージ
	echo get_string('pretestnotattempted','scormadaptivequiz');
	return;
}


/*
 * (4) Quiz結果{question_attempts} から正解した問題リスト {question_attempts} quetionidを取得
 * (a) 検索条件: questionusageid = ? AND responsesummary = ?', [{quiz_attempts} uniqueid,'True]
 * (5) 問題リストから対象SCOリスト(scoid)を取得
 * (a) {question_attempts} quetionid から {question} category を取得
 * (b) {question} category から {question_categories} name を取得
 * (c) {scorm_scoes} scorm を取得 : 検索条件 {question_categories} name = {scorm_scoes} identifier
 * 機能3:
 * (1) 対象SCO項目{scorm_scoes} scorm の学習状態の変更{scorm_scoes_track}
 * キー項目: userid, scormid, scoid
 */

 //SKIP対象リスト,SKIP対象外リスト取得: 正解問題項目のステータス更新 locallib function call
list($scoTrueList, $scoFalseList) = scormadaptivequiz($USER, $course, $scormid, $quizuniqueid);
$skipValue = count((array)$scoTrueList);//number of correct answers 
$nonskipValue = count((array)$scoFalseList);// number of wrong answers
$totalskipableValue = $skipValue + $nonskipValue;
//var_dump($scoTrueList);
// Output starts here.
//$strdata = 'TestStr';
//$PAGE->navbar->add($strdata, new moodle_url('/course/view.php', array('id'=>$course->id)));
echo $OUTPUT->header();
//「コースに戻る」「教材に進む」ボタン
echo html_writer::start_tag('table', array('id' => 'navigationbtn'));
echo html_writer::start_tag('tr', array('id' => 'navigationbtn2'));
echo html_writer::start_tag('td', array('id' => 'navigationbtn3'));
echo $OUTPUT->single_button(new moodle_url('/course/view.php', array('id'=>$course->id)), get_string('backtocoursebtn','scormadaptivequiz'), 'get');
echo html_writer::end_tag('td');
echo html_writer::start_tag('td', array('id' => 'navigationbtn4'));
echo $OUTPUT->single_button(new moodle_url('/mod/scorm/view.php', array('a'=>$rinrin->scorm)), get_string('movetoscormbtn','scormadaptivequiz'), 'get');
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');				
echo html_writer::end_tag('table');		

// Conditions to show the intro can change to look for own settings or whatever.
//if ($scormadaptivequiz->intro) {
//    echo $OUTPUT->box(format_module_intro('scormadaptivequiz', $scormadaptivequiz, $cm->id), 'generalbox mod_introbox', 'scormadaptivequizintro');
//}

// Replace the following lines with you own code.
//機能2:'
//echo $OUTPUT->heading('scormadaptivequiz Module! Development Release No. 1');
//echo $OUTPUT->heading($CFG->lang.' , '.$SESSION->lang.' , '.$COURSE->lang.' , '.$USER->lang);
//$COURSE->lang = 'ko_utf8';//'ja','en','ko','zh'
//$CFG->lang   =  'ko_utf8';
//$SESSION->lang = 'ko_utf8';
//$USER->lang = 'ko_utf8';
//echo $OUTPUT->heading("current_language: " . current_language());
//echo $OUTPUT->heading('USER id: '.$USER->id .' , Name: '. $USER->username);
//echo $OUTPUT->heading('Course id: '.$course->id .' , Name: '. $course->fullname);
//echo $OUTPUT->heading('Quiz id: '.$quizid .' , Name: '. $quizname);
//echo $OUTPUT->heading('SCORM id: '.$scormid .' , Name: '. $scormname);
//echo $OUTPUT->heading('quiz_attempts uniqueid: '.$quiz_attempts->uniqueid );
//echo '=====================================================================';

//アニメーション・グラフ表示設定
//<div id="canvas-holder">
//	<canvas id="chart-area" width="300" height="300"/>
//  <div id="pieLegend"></div>
//</div>
$mainmessage1 = get_string('mainmessage1','scormadaptivequiz').' '.$skipValue;
$mainmessage2 = get_string('mainmessage2','scormadaptivequiz').' '.$totalskipableValue;
$skipLabel = get_string('skipLabel','scormadaptivequiz');
$nonskipLabel = get_string('nonskipLabel','scormadaptivequiz');
echo $OUTPUT->heading($mainmessage1);
echo $OUTPUT->heading($mainmessage2);
echo html_writer::start_tag('div', array('id' => 'canvas-holder'));
echo html_writer::tag('canvas', '', array('id' => 'chart-area','width' => '700','height' => '300'));
echo html_writer::tag('div', '', array('id' => 'chart_legend'));
echo html_writer::end_tag('div');
//echo "<button onclick='rinrinchart(".'"'.$skipValue.'","'.$nonskipValue.'","'.$skipLabel.'","'.$nonskipLabel.'"'.")' type='button'>View Chart</button>";
$PAGE->requires->js_function_call('rinrinchart',array($skipValue, $nonskipValue,$skipLabel,$nonskipLabel));
echo '';
//スキップ項目テーブル表示
if($skipValue>0){
	echo $OUTPUT->heading(get_string('mainskipmessage','scormadaptivequiz'));
	$table = new html_table();
	$table->head = array(get_string('skip','scormadaptivequiz'),get_string('title','scormadaptivequiz'),get_string('scoidentifier','scormadaptivequiz')); //array( 'skip','title','SCO identifier');
	$tabledata = array();
	foreach ($scoTrueList as $value) {
		$tabledata[] = array('<img src="./pix/grade_correct.png" />',$value->title,$value->name);
	}
	$table->tablealign = 'center';
	$table->data = $tabledata;
	echo html_writer::table($table);
}
//スキップしない項目テーブル表示
if($nonskipValue>0){
	echo '';
	echo $OUTPUT->heading(get_string('mainnonskipmessage','scormadaptivequiz'));
	$table2 = new html_table();
	$table2->head = array(get_string('skip','scormadaptivequiz'),get_string('title','scormadaptivequiz'),get_string('scoidentifier','scormadaptivequiz')); //array( 'skip','title','SCO identifier');
	$tabledata2 = array();
	foreach ($scoFalseList as $value) {
		$tabledata2[] = array('<img src="./pix/grade_incorrect.png" />',$value->title,$value->name);
	}
	$table2->tablealign = 'center';	
	$table2->data = $tabledata2;
	echo html_writer::table($table2);
}
//「コースに戻る」「教材に進む」ボタン
echo html_writer::start_tag('table', array('id' => 'navigationbtn'));
echo html_writer::start_tag('tr', array('id' => 'navigationbtn2'));
echo html_writer::start_tag('td', array('id' => 'navigationbtn3'));
echo $OUTPUT->single_button(new moodle_url('/course/view.php', array('id'=>$course->id)), get_string('backtocoursebtn','scormadaptivequiz'), 'get');
echo html_writer::end_tag('td');
echo html_writer::start_tag('td', array('id' => 'navigationbtn4'));
echo $OUTPUT->single_button(new moodle_url('/mod/scorm/view.php', array('a'=>$rinrin->scorm)), get_string('movetoscormbtn','scormadaptivequiz'), 'get');
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');				
echo html_writer::end_tag('table');				
// Finish the page.




echo $OUTPUT->footer();
