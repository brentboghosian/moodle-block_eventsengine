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
 * Definition of block eventsengine language string.
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action'] = 'Action';
$string['actiondata'] = 'Action Parameters';
$string['addengine'] = 'Add Engine';
$string['assigndeleted'] = 'Events Engine instance {$a} - deleted!';
$string['assigndisabled'] = 'Events Engine instance {$a} - disabled!';
$string['assignenabled'] = 'Events Engine instance {$a} - enabled!';
$string['available'] = 'Available';
$string['blockname'] = 'EventsEngine';
$string['body'] = 'Message';
$string['choose'] = 'Choose ...';
$string['class_complete_grade_range'] = 'ELIS Class complete in grade range';
$string['class_complete_grade_range_help'] = 'Select the ELIS Class and the grade range the user must achieve to trigger.';
$string['context'] = 'Context';
$string['course_complete_grade_range'] = 'ELIS Course complete in grade range';
$string['course_complete_grade_range_help'] = 'Select the ELIS Course and the grade range the user must achieve to trigger.';
$string['course'] = 'Course';
$string['courses'] = 'Courses';
$string['createnewengine'] = 'Create a new <i>Engine Instance</i>';
$string['data'] = 'Data';
$string['disable'] = 'Disable';
$string['eclass'] = 'ELIS Class Instance';
$string['ecourse'] = 'ELIS Course Description';
$string['editcancelled'] = 'Edit Engine cancelled.';
$string['editengine'] = 'Edit Engine';
$string['editengineform'] = 'Edit Engine Form';
$string['email_users'] = 'Send Email to Users';
$string['email_users_help'] = 'Enter a comma-separate list of valid user\'s email, username or \'?\' for target user (user context only).<br/><br/>
The message subject & body may contain the following parameters: {$a->...<br/>
user - The fullname of the subject user being acted upon.<br/>
course - The shortname of the event\'s course.<br/>
eventname - The event class.<br/>
component - The event plugin type.<br/>
action - The event action.<br/>
target - The event target.<br/>
objecttable - The event DB table.<br/>
crud - The event parameter.<br/>
edulevel - The event parameter.<br/>
contextid - The event context id.<br/>
contextlevel - The event context level.<br/>
contextinstanceid - The event context instance id.<br/>
userid - The event userid (usually the user that triggered the event).<br/>
courseid - The event courseid.<br/>
relateduserid - The event related userid (usually the subject user).<br/>
anonymous - The event parameter.<br/>
other->... - More event dependent parameters.';
$string['enable'] = 'Enable';
$string['engine'] = 'Engine';
$string['enginedata'] = 'Engine Parameters';
$string['enginesaved'] = 'Engine saved.';
$string['enrol_into_course'] = 'Enrol into Moodle Course';
$string['enrol_into_course_help'] = 'Select the Moodle Course to enrol the user into, when the Engine triggers.';
$string['event'] = 'Event';
$string['eventscourse'] = 'Event\'s course context';
$string['eventsengine:addinstance'] = 'Add a new Events Engine block';
$string['eventsengine:myaddinstance'] = 'Add a new Events Engine block to My home page';
$string['fieldid'] = 'Profile field';
$string['listingtitle'] = 'Events Engine Instances';
$string['make_webservice_call'] = 'Make a WebServices call';
$string['make_webservice_call_help'] = 'The URL must contain all required parameters; REST Eg. URL: http://site/webservice/rest/server.php?wstoken={YourToken}&wsfunction={YourMethod}&moodlewsrestformat=json<br/><br/>
Options & data must be formatted as one "key: value" pair per line.<br/>
A data line with \'method\' is required for XMLRPC.<br/><br/>
The data may contain the following parameters: {$a->...<br/>
course - The shortname of the event\'s course.<br/>
eventname - The event class.<br/>
component - The event plugin type.<br/>
action - The event action.<br/>
target - The event target.<br/>
objecttable - The event DB table.<br/>
crud - The event parameter.<br/>
edulevel - The event parameter.<br/>
contextid - The event context id.<br/>
contextlevel - The event context level.<br/>
contextinstanceid - The event context instance id.<br/>
userid - The event userid (usually the user that triggered the event).<br/>
user_username - The user\'s username.<br/>
user_idnumber - The user\'s idnumber.<br/>
user_email - The user\'s email.<br/>
courseid - The event courseid.<br/>
relateduserid - The event related userid (usually the subject user).<br/>
relateduser_username - The subject user\'s username.<br/>
relateduser_idnumber - The subject user\'s idnumber.<br/>
relateduser_email - The subject user\'s email.<br/>
anonymous - The event parameter.<br/>
other->... - More event dependent parameters.';
$string['maxgrade'] = 'Maximum grade';
$string['mcourse'] = 'Moodle Course';
$string['mingrade'] = 'Minimum grade';
$string['moodle_course_complete_grade_range'] = 'Moodle Course complete in grade range';
$string['moodle_course_complete_grade_range_help'] = 'Select the Moodle Course and the grade range the user must achieve to trigger.';
$string['moodle_course_enrolled'] = 'Moodle Course enrolled';
$string['moodle_course_enrolled_help'] = 'Select the Moodle Course the user must be enrolled into to trigger.';
$string['name'] = 'Name';
$string['noengines'] = 'No EventEngines found.';
$string['norole'] = 'No role';
$string['notpermitted'] = 'You do not have permissions to edit EventsEngine.';
$string['options'] = 'CURL options';
$string['owner'] = 'Owner';
$string['plugin'] = 'Plugin';
$string['pluginname'] = 'EventsEngine';
$string['program'] = 'ELIS Program';
$string['program_complete'] = 'ELIS Program Complete';
$string['program_complete_help'] = 'Select the ELIS Program the user must complete to trigger.';
$string['rawdata'] = 'Raw Data';
$string['role'] = 'Role';
$string['role_assigned'] = 'Role Assigned';
$string['role_assigned_help'] = 'Select the role(s) the user must be assigned to, to trigger';
$string['role_unassigned'] = 'Role Unassigned';
$string['role_unassigned_help'] = 'Select the role(s) the user must be unassigned from, to trigger';
$string['roles'] = 'Roles';
$string['selectaction'] = 'Event Action';
$string['selectcontext'] = 'Select new Event Context';
$string['selectengine'] = 'Event Engine';
$string['subject'] = 'Subject';
$string['suspend'] = 'Suspend';
$string['update_enrolment_status'] = 'Update Enrolment Status';
$string['update_enrolment_status_help'] = 'Select the Moodle Course and whether to suspend or unsuspend the user when the Engine triggers.';
$string['url'] = 'URL';
$string['user_field_update'] = 'Update User Field';
$string['user_field_update_help'] = 'Select the user field to update and the value to set when the Engine triggers.';
$string['users'] = 'Email Users';
$string['value'] = 'Value';
$string['wscalltype'] = 'WS call type';
