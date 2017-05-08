<?php
/**
 * Definition of core events engines & actions
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Array of core eventsactions:
$eventsactions = [
    'profile_field_update' => [
        'name' => 'Update User Profile Field',
        'configform' => function(&$mform) {
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->fieldid)) {
                                 data['fieldid'] = $formdata->fieldid;
                             } else {
                                 return false;
                             }
                             if (isset($formdata->value)) {
                                 data['value'] = $formdata->value;
                             } else {
                                 return false;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata) {
                         global $CFG, $DB;
                         require_once($CFG->dirroot.'/user/profile/lib.php');
                         $fieldname = $DB->get_field('user_info_field', 'shortname', ['id' => $actiondata->fieldid]);
                         if (empty($fieldname)) {
                             return;
                         }
                         $user = new stdClass;
                         $user->id = $userid;
                         $user->'profile_field_'.$fieldname = $actiondata->value; // TBD???
                         profile_save_data($user);
                         \core\event\user_updated::create_from_userid($userid)->trigger();
                     }
    ],
    'enrol_into_course' => [
        'name' => 'Enrol into Course',
        'configform' => function(&$mform) {
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 data['courseid'] = $formdata->courseid;
                             } else {
                                 return false;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata) {
                         global $CFG;
                         require_once($CFG->dirroot.'/lib/enrollib.php');
                         static::enrol_try_internal_enrol($actiondata->courseid, $userid);
                     }
    ]
];

// Array of core eventsengine:
$eventsengine = [
        ['\local_elisprogram\event\crlm_class_completed' => ['class_complete_grade_range' => [
            'name' => 'ELIS Class Instance completed with grade',
            'display' = function($enginedata) {
                            global $PAGE;
                            $pgcontext = $PAGE->context;
                            return ($pgcontext == context_system::instance ||
                                    $pgcontext == \local_elisprogram\context\pmclass($enginedata->classid));
                        },
            'available' => function() {
                               $localplugins = core_plugin_manager::instance()->get_plugins_of_type('local');
                               return (!empty($localplugins['elisprogram']) && $localplugins['elisprogram']->is_installed_and_upgraded());
                           },
            'ready' => function($event, $enginedata) {
                           global $CFG;
                           require_once($cfg->dirroot.'/local/elisprogram/lib/data/student.class.php');
                           if (!isset($event->other)) {
                               return;
                           }
                           $student = (object)$event->other;
                           if ($student->completestatusid == 2 &&
                                   block_eventsengine_float_comp($student->grade, $enginedata->mingrade, '>=') &&
                                   block_eventsengine_float_comp($student->grade, $enginedata->maxgrade, '<=')) {
                               $euser = new user($student->userid);
                               $muser = $user->get_moodleuser();
                               return !empty($muser->id) ? $muser->id : false; // return Moodle user id.
                           } else {
                               return false;
                           }
                       },
            'configform' => function(&$mform) {
                            },
            'getformdata' => function($formdata) {
                                 $data = [];
                                 if (isset($formdata->classid)) {
                                     data['classid'] = $formdata->classid;
                                 } else {
                                     return false;
                                 }
                                 if (isset($formdata->mingrade)) {
                                     data['mingrade'] = $formdata->mingrade;
                                 } else {
                                     return false;
                                 }
                                 if (isset($formdata->maxgrade)) {
                                     data['maxgrade'] = $formdata->maxgrade;
                                 } else {
                                     return false;
                                 }
                                 return $data;
                             },
        ]],
        ['\local_elisprogram\event\curriculum_completed' => ['program_complete' => [
            'name' => 'ELIS Program completed',
            'display' = function($enginedata) {
                            global $PAGE;
                            $pgcontext = $PAGE->context;
                            return ($pgcontext == context_system::instance ||
                                    $pgcontext == \local_elisprogram\context\program($enginedata->prgid));
                        },
            'available' => function() {
                               $localplugins = core_plugin_manager::instance()->get_plugins_of_type('local');
                               return (!empty($localplugins['elisprogram']) && $localplugins['elisprogram']->is_installed_and_upgraded());
                           },
            'ready' => function($event, $enginedata) {
                           global $CFG;
                           require_once($cfg->dirroot.'/local/elisprogram/lib/data/curriculumstudent.class.php');
                           if (!isset($event->other)) {
                               return;
                           }
                           $currstu = (object)$event->other;
                           if ($curstu->curriculumid == $enginedata->prgid && $curstu->completed == 2) {
                               $euser = new curriculumstudent($curstu->userid);
                               $muser = $user->get_moodleuser();
                               return !empty($muser->id) ? $muser->id : false; // return Moodle user id.
                           } else {
                               return false;
                           }
                       },
            'configform' => function(&$mform) {
                            },
            'getformdata' => function($formdata) {
                                 $data = [];
                                 if (isset($formdata->prgid)) {
                                     data['prgid'] = $formdata->prgid;
                                 } else {
                                     return false;
                                 }
                             },
       ]],
];
