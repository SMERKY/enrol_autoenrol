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
 * autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol
 * @subpackage autoenrol
 * @author     Mark Ward - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @date       July 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$enrolid = required_param('enrolid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'autoenrol'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login();
if (!is_enrolled($context)) {
    redirect(new moodle_url('/'));
}
require_login($course);

$plugin = enrol_get_plugin('autoenrol');

// Security defined inside following function.
if (!$plugin->get_unenrolself_link($instance)) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
}

$PAGE->set_url('/enrol/autoenrol/unenrolself.php', array('enrolid' => $instance->id));
$PAGE->set_title($plugin->get_instance_name($instance));

if ($confirm and confirm_sesskey()) {
    //unenroll the user
    $plugin->unenrol_user($instance, $USER->id);

    //if this instance is enrollment on login
    if($instance->customint1 > 0){
    	//add the opt out row
    	$opted_out_row = new stdClass();
    	$opted_out_row->userid = $USER->id;
    	$opted_out_row->instanceid = $instance->id;
    	$opted_out_row->timestamp = time();
    	$row_id = $DB->insert_record('enrol_autoenrol', $opted_out_row, $returnid=true, $bulk=false);

    	if($row_id == false){
    		throw new coding_exception('Cannot opt out of course');
    	}
    }

    redirect(new moodle_url('/index.php'));
}

echo $OUTPUT->header();
$yesurl = new moodle_url($PAGE->url, array('confirm' => 1, 'sesskey' => sesskey()));
$nourl = new moodle_url('/course/view.php', array('id' => $course->id));
$message = get_string('unenrolselfconfirm', 'enrol_autoenrol', format_string($course->fullname));
echo $OUTPUT->confirm($message, $yesurl, $nourl);
echo $OUTPUT->footer();
