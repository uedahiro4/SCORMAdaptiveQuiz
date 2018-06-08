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
 * The main scormadaptivequiz configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_scormadaptivequiz
 * @copyright  2016 国立情報学研究所/National Institute of Informatics
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/scormadaptivequiz/locallib.php');

/**
 * Module instance settings form
 *
 * @package    mod_scormadaptivequiz
 * @copyright  2016 国立情報学研究所/National Institute of Informatics
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scormadaptivequiz_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $COURSE, $CFG, $DB, $PAGE;
		//echo $PAGE->url;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('scormadaptivequizname', 'scormadaptivequiz'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'scormadaptivequizname', 'scormadaptivequiz');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of scormadaptivequiz settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'scormadaptivequizfieldset', get_string('scormadaptivequizfieldset', 'scormadaptivequiz'));
		//$mform->addElement('hidden', 'pageurl', $PAGE->url);
        // Target Scorm content.
		$mform->addElement('select', 'scorm', get_string('targetscorm1', 'scormadaptivequiz'), targetscorm_array());
		$mform->addRule('scorm', null, 'required', null, 'client');
        // Target Quiz content.
        $mform->addElement('select', 'quiz', get_string('targetquiz1', 'scormadaptivequiz'), targetquiz_array());
		$mform->addRule('quiz', null, 'required', null, 'client');
		//$mform->addElement('submit','mybutton','setting & reload');
		
		$rinrin = $DB->get_record('scormadaptivequiz', array('course'=>$COURSE->id));
		if($rinrin){
			if ($DB->record_exists_select('scormadaptivequiz_scoes',
					'scormadaptivequiz = :id',array('id'=>$rinrin->id)))
			{
				//データが「ある」場合はデータを取得
				//スキップ項目リストと閾値の表示
				$scormadaptivequiz_scoes = $DB->get_records('scormadaptivequiz_scoes', array('scormadaptivequiz'=>$rinrin->id));
				$mform->addElement('html','<table class="generaltable boxaligncenter">');
				$mform->addElement('html','<thead><tr><th class="header c0" style="" scope="col">'.get_string('bindinfo1','scormadaptivequiz').'</th>');
				$mform->addElement('html','<th class="header c1" style="" scope="col">'.get_string('bindinfo2','scormadaptivequiz').'</th>');
				$mform->addElement('html','<th class="header c2 lastcol" style="" scope="col">'.get_string('bindinfo3','scormadaptivequiz').'</th></tr></thead>');
				$mform->addElement('html','<tbody>');
				foreach ($scormadaptivequiz_scoes as $value) {
					$mform->addElement('html','<tr>');
					$mform->addElement('html','<td style="vertical-align:middle">'.$value->scotitle.'</td>');
					$mform->addElement('html','<td style="vertical-align:middle">'.$value->scoidentifier.'</td>');
					$mform->addElement('html','<td>');
					$mform->addElement('text', $value->scoidentifier, '', array('value'=>$value->passvalue,'maxlength'=>'3','size'=>'3'));
					$mform->addElement('html','</td>');
					$mform->addElement('html','</tr>');
				}
				$mform->addElement('html','</tbody></table>');
			}
		}

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }
	
	
	
	public function validation($data, $files) {
        global $CFG, $USER;
        $errors = parent::validation($data, $files);

        $type = $data['scorm'];
		
		        return $errors;
    }
	
	public function set_data($defaultvalues) {
        $defaultvalues = (array)$defaultvalues;



        //$this->data_preprocessing($defaultvalues);
        parent::set_data($defaultvalues);
    }
	
	public function data_preprocessing(&$defaultvalues) {
		global $COURSE;
	}
	
    function get_data($slashed = true) {
        $data = parent::get_data($slashed);
        if (!$data) {
            return false;
        }


        return $data;
    }
		


}
