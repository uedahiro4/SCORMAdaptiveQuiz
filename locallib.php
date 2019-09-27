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
 * Internal library of functions for module scormadaptivequiz
 *
 * All the scormadaptivequiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_scormadaptivequiz
 * @copyright  2016 国立情報学研究所/National Institute of Informatics
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
/**
 * (4) Quiz結果{question_attempts} から正解した問題リスト {question_attempts} quetionidを取得
 * (a) 検索条件: questionusageid = ? AND responsesummary = ?', [{quiz_attempts} uniqueid,'True]
 * (5) 問題リストから対象SCOリスト(scoid)を取得
 * (a) {question_attempts} quetionid から {question} category を取得
 * (b) {question} category から {question_categories} name を取得
 * (c) {scorm_scoes} scorm を取得 : 検索条件 {question_categories} name = {scorm_scoes} identifier
 * 機能3:
 * (1) 対象SCO項目{scorm_scoes} scorm の学習状態の変更{scorm_scoes_track}
 * キー項目: userid, scormid, scoid
 *
 * @param USER				$USER
 * @param course			$course
 * @param scorm				$scorm
 * @param quiz				$quiz　削除
 * @param quiz_attempts		$quiz_attempts
 * @return object			scoSkipList object with unique name and id set.
 */
function scormadaptivequiz($USER, $course, $scormid, $uniqueid) {
    global $COURSE, $CFG, $DB, $PAGE;
	//スキップ対象SCOリスト sco identifier と　スキップ閾値(何問正解すればスキップするか)　例　1:1問
	$rinrin = $DB->get_record('scormadaptivequiz', array('course'=>$COURSE->id));
	$scormadaptivequiz_scoes = $DB->get_records('scormadaptivequiz_scoes', array('scormadaptivequiz'=>$rinrin->id));
	
	$scoscorecorrect = array();
	$scoscoretotal = array();
	//スキップ対象全SCOリストデータ
	$allSkipableScoid ="";
	foreach ($scormadaptivequiz_scoes as $value) {
		$allSkipableScoid .= "'{$value->scoidentifier}',";
		$scoscore[$value->scoidentifier] = $value->passvalue;
	}
	$allSkipableScoid = substr($allSkipableScoid, 0, -1); 	
	$allSkipableScoList = $DB->get_records_sql(
	'SELECT cc.name,ss.id,ss.title 
		FROM {scorm_scoes} ss 
		join {question_categories} cc on ss.identifier = cc.name
		where ss.identifier in ('.$allSkipableScoid.') and ss.scorm = '.$scormid.';');
	 
	//正解問題のリスト $quiz_attempts->uniqueidはクイズ&Userにユニーク
	$scoTrueList = $DB->get_records_sql(
	'SELECT cc.name , ss.id , ss.title
	 FROM {question} qq 
	 JOIN {question_categories} cc ON qq.category = cc.id 
	 JOIN {scorm_scoes} ss ON ss.identifier = cc.name 
	 WHERE qq.id IN 
        ( SELECT aa.questionid FROM {question_attempts} aa 
           WHERE aa.questionusageid =:questionusageid AND aa.rightanswer=aa.responsesummary)
        AND ss.scorm =:scormid',array('questionusageid'=>$uniqueid, 'scormid' => $scormid));

	//全問題のリスト $quiz_attempts->uniqueidはクイズ&Userにユニーク
	$scoAllList = $DB->get_records_sql(
	'SELECT cc.name , ss.id , ss.title
	 FROM {question} qq 
	 JOIN {question_categories} cc ON qq.category = cc.id 
	 JOIN {scorm_scoes} ss ON ss.identifier = cc.name 
	 WHERE qq.id IN 
        ( SELECT aa.questionid FROM {question_attempts} aa 
           WHERE aa.questionusageid =:questionusageid) 
        AND ss.scorm =:scormid',array('questionusageid'=>$uniqueid, 'scormid' => $scormid))
        ;
//	echo var_dump($allSkipableScoList);	
//	echo '===============================================<br />';
//	echo var_dump($scoTrueList);		
	//スキップ対象のSCOリスト
	$scoSkipList = array();
	//スキップ対象だが正解数が閾値に達していないのでスキップしないSCOリスト
	$scoNonSkipList = array();
	//スキップ閾値の計算
	foreach ($scoAllList as $value2) {
		$scoscoretotal[$value2->name] = 0;
		$scoscorecorrect[$value2->name] = 0;
	}
	foreach ($scoAllList as $value2) {
		$scoscoretotal[$value2->name] += 1;
	}
	foreach ($scoTrueList as $value2) {
		$scoscorecorrect[$value2->name] += 1;
	}
	foreach ($allSkipableScoList as $value) {
		//echo 'scoscorecorrect:'.$scoscorecorrect[$value->name].' , scoscoretotal:'.$scoscoretotal[$value->name].' , scoscore:'.$scoscore[$value->name].'<br />';
		if($scoscoretotal[$value->name]>0){
			if(($scoscorecorrect[$value->name]/$scoscoretotal[$value->name])*100>=$scoscore[$value->name]){
				$record = new stdClass();
				$record->name = $value->name;
				$record->id = $value->id;
				$record->title = $value->title;
				$scoSkipList[$value->name] = $record;	
			}else{
				$record = new stdClass();
				$record->name = $value->name;
				$record->id = $value->id;
				$record->title = $value->title;
				$scoNonSkipList[$value->name] = $record;	
			}			
		}else{
				$record = new stdClass();
				$record->name = $value->name;
				$record->id = $value->id;
				$record->title = $value->title;
				$scoNonSkipList[$value->name] = $record;	
		}
	}
		
	//機能3:
	//(1) 対象SCO項目:$scoSkipList , {scorm_scoes} scorm の学習状態の変更{scorm_scoes_track}
	//キー項目: userid, scormid, scoid
		
	// scorm_scoes_trackのattemptを設定: 基本的に1回しか受講しないが複数の場合は最後のattemptを対象にする
	//キー項目: userid, scormid, scoid
	$record = new stdClass();
	$record->userid = $USER->id;
	$record->scormid = $scormid;
	$record->scoid = $value->id;
	if ($DB->record_exists_select('scorm_scoes_track',
		'userid = :userid AND scormid = :scormid AND scoid = :scoid',
		array(	'userid'=>$record->userid, 
				'scormid'=>$record->scormid, 
				'scoid'=>$record->scoid)))
	{
		$scorm_scoes_track_attempts = $DB->get_records_sql(
		'SELECT *
			FROM {scorm_scoes_track} 
			WHERE userid =? AND scormid=? AND scoid=? ORDER BY attempt DESC LIMIT 1', array($USER->id,$scormid,$value->id));
		foreach ($scorm_scoes_track_attempts as $value) {
			$scormattempt = $value->attempt;
		}
	}else{
		$scormattempt = 1;
	}

	foreach ($scoSkipList as $value) { 
		//キー項目: userid, scormid, scoid
		$record->userid = $USER->id;
		$record->scormid = $scormid;
		$record->scoid = $value->id;
		//時間設定
		$record->timemodified = time();
		//設定定数
		$record->attempt = $scormattempt; //1;
		
		//「cmi.core.lesson_status」の追加・更新
		$record->element = 'cmi.core.lesson_status';
		$record->value = 'completed';
		//var_dump($record);
		//対象SCOのtrackデータがあるかを確認
		if ($DB->record_exists_select('scorm_scoes_track',
				'userid = :userid AND scormid = :scormid AND scoid = :scoid AND attempt = :attempt AND element = :element',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element)))
		{
			//データが「ある」場合はデータをUpdate
			//Updateするために該当IDを取得する
			$sqlReturn = $DB->get_record('scorm_scoes_track',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element));
			$record->id = $sqlReturn->id;
			//Update
			//print_r('update_record cmi.core.lesson_status');
			$lastinsertid = $DB->update_record('scorm_scoes_track', $record);
			//Insertの場合不要なのでIDを削除
			unset($record->id);
		}else{
			//データが「ない」場合はデータをInsert
			$lastinsertid = $DB->insert_record('scorm_scoes_track', $record, false);
		}
		
		//「cmi.core.total_time」の追加・更新
		$record->element = 'cmi.core.total_time';
		$record->value = '00:00:00.00';
		//対象SCOのtrackデータがあるかを確認
		if ($DB->record_exists_select('scorm_scoes_track',
				'userid = :userid AND scormid = :scormid AND scoid = :scoid AND attempt = :attempt AND element = :element',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element)))
		{
			//データが「ある」場合はデータをUpdate
			//Updateするために該当IDを取得する
			$sqlReturn = $DB->get_record('scorm_scoes_track',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element));
			$record->id = $sqlReturn->id;
			//Update
			//print_r('update_record cmi.core.total_time');
			$lastinsertid = $DB->update_record('scorm_scoes_track', $record);
			//Insertの場合不要なのでIDを削除
			unset($record->id);
		}else{
			//データが「ない」場合はデータをInsert
			$lastinsertid = $DB->insert_record('scorm_scoes_track', $record, false);
		}
	/*		
	//「cmi.core.exit」の追加・更新場合 : このデータをsuspendでInsertすると「Completed - Suspended」としてstatus iconがmoonになる
		$record->element = 'cmi.core.exit';
		$record->value = 'suspend';
		//対象SCOのtrackデータがあるかを確認
		if ($DB->record_exists_select('scorm_scoes_track',
				'userid = :userid AND scormid = :scormid AND scoid = :scoid AND attempt = :attempt AND element = :element',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element)))
		{
			//データが「ある」場合はデータをUpdate
			//Updateするために該当IDを取得する
			$sqlReturn = $DB->get_record('scorm_scoes_track',
				array(	'userid'=>$record->userid, 
						'scormid'=>$record->scormid, 
						'scoid'=>$record->scoid, 
						'attempt'=>$record->attempt, 
						'element'=>$record->element));
			$record->id = $sqlReturn->id;
			//Update
			$lastinsertid = $DB->update_record('scorm_scoes_track', $record);
			//Insertの場合不要なのでIDを削除
			unset($record->id);
		}else{
			//データが「ない」場合はデータをInsert
			$lastinsertid = $DB->insert_record('scorm_scoes_track', $record, false);
		}
		*/
	
	} //foreach end:::

    return array($scoSkipList,$scoNonSkipList);
}

/**
 * Returns an array of the array of scorm contents in  this course
 *
 * @return array an array of update frequency options
 */
 
function targetscorm_array() {
	global $COURSE, $CFG, $DB, $PAGE;
	
	//echo 'course'.$COURSE->id;
	$scorm = $DB->get_records('scorm', array('course'=>$COURSE->id));
	$retAry = array();
	$retAry[null] = get_string('never');
	foreach ($scorm as $value) {
		$retAry[$value->id] = $value->name;
	}	
    return $retAry;
}


/**
 * Returns an array of the array of scorm contents in  this course
 *
 * @return array an array of update frequency options
 */
function targetquiz_array() {
	global $COURSE, $CFG, $DB, $PAGE;
	//echo 'course'.$COURSE->id;
	$quiz = $DB->get_records('quiz', array('course'=>$COURSE->id));
	$retAry = array();
	$retAry[null] = get_string('never');
	foreach ($quiz as $value) {
		$retAry[$value->id] = $value->name;
	}	
    return $retAry;
}