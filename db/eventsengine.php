<?php
/**
 * Definition of core events engines & actions
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// For Documentation see: blocks/eventsengine/README.txt

// Array of core eventsactions:
$eventsactions = [
    'user_field_update' => [
        'context' => 'user',
        'configform' => function(&$mform, $assign) {
                            global $CFG, $DB;
                            require_once($CFG->dirroot.'/user/lib.php');
                            $fields = user_get_default_fields();
                            $badfields = ['id', 'username', 'idnumber', 'fullname', 'groups', 'roles', 'enrolledcourses',
                                    'customfields', 'preferences'];
                            $choices = [];
                            foreach ($fields as $field) {
                                if (!in_array($field, $badfields)) {
                                    $choices[$field] = $field;
                                }
                            }
                            $customfields = $DB->get_recordset('user_info_field', null, '', 'shortname,name');
                            foreach ($customfields as $id => $field) {
                                $choices['profile_field_'.$id] = $field->name;
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'fieldid', get_string('fieldid', 'block_eventsengine').
                                    ':&nbsp;', $choices);
                            // TBD: add element of field control type: text, advcheckbox, select, datetime;
                            $group[] =& $mform->createElement('text', 'value', get_string('value', 'block_eventsengine').':&nbsp;');
                            return $group;
                        },
        'getformdata' => function($formdata, $enginecontext) {
                             $data = [];
                             if (isset($formdata->fieldid)) {
                                 $data['fieldid'] = $formdata->fieldid;
                             }
                             if (isset($formdata->value)) {
                                 $data['value'] = $formdata->value;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata, $event) {
                         global $CFG, $DB;
                         require_once($CFG->dirroot.'/user/profile/lib.php');
                         $fieldname = $actiondata->fieldid; // TBD: validation?
                         $user = new stdClass;
                         $user->id = $userid;
                         if (strpos($fieldname, 'profile_field_') === 0) {
                             if ($DB->record_exists('user_info_field', ['shortname' => substr($actiondata->fieldid,
                                     strlen('profile_field_'))])) {
                                 profile_load_data($user);
                             } else {
                                 return;
                             }
                         }
                         $user->$fieldname = $actiondata->value; // TBD???
                         if (strpos($fieldname, 'profile_field_') === 0) {
                             profile_save_data($user);
                         } else {
                             $DB->update_record('user', $user);
                         }
                         \core\event\user_updated::create_from_userid($userid)->trigger();
                     }
    ],
    'enrol_into_course' => [
        'context' => 'user',
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $courses = $DB->get_recordset('course', null, '', 'id,shortname');
                            $choices = [];
                            if (strpos($assign->event, '\core\event\course_') === 0 ||
                                    strpos($assign->event, '\core\event\role_') === 0 ||
                                    strpos($assign->event, '\core\event\user_enrolment_') === 0) {
                                $choices[-1] = get_string('eventscourse', 'block_eventsengine');
                            }
                            foreach ($courses as $id => $course) {
                                if ($id > 1) {
                                    $choices[$id] = $course->shortname;
                                }
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'courseid', get_string('mcourse', 'block_eventsengine').':&nbsp;', $choices);

                            $roles = $DB->get_recordset_sql(
                                    'SELECT r.*
                                       FROM {role} r
                                       JOIN {role_context_levels} rcl ON r.id = rcl.roleid
                                      WHERE rcl.contextlevel = '.CONTEXT_COURSE);
                            $choices = ['0' => get_string('norole', 'block_eventsengine')];
                            foreach ($roles as $id => $role) {
                                $choices[$id] = $role->shortname; // TBD?
                            }
                            $group[] =& $mform->createElement('select', 'roleid', get_string('role', 'block_eventsengine').':&nbsp;',
                                    $choices);
                            return $group;
                        },
        'getformdata' => function($formdata, $enginecontext) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 $data['courseid'] = $formdata->courseid;
                             }
                             if (isset($formdata->roleid)) {
                                 $data['roleid'] = $formdata->roleid;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata, $event) {
                         global $CFG, $DB;
                         require_once($CFG->dirroot.'/lib/enrollib.php');
                         $courseid = $actiondata->courseid;
                         if ($courseid == -1) {
                             if ($event->contextlevel == CONTEXT_COURSE) {
                                 $courseid = $event->contextinstanceid;
                             } else {
                                 return;
                             }
                         }
                         enrol_try_internal_enrol($courseid, $userid, $actiondata->roleid);
                     }
    ],
    'update_enrolment_status' => [
        'context' => 'user',
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $courses = $DB->get_recordset('course', null, '', 'id,shortname');
                            $choices = [];
                            if (strpos($assign->event, '\core\event\course_') === 0 ||
                                    strpos($assign->event, '\core\event\role_') === 0 ||
                                    strpos($assign->event, '\core\event\user_enrolment_') === 0) {
                                $choices[-1] = get_string('eventscourse', 'block_eventsengine');
                            }
                            foreach ($courses as $id => $course) {
                                if ($id > 1) {
                                    $choices[$id] = $course->shortname;
                                }
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'courseid', get_string('mcourse', 'block_eventsengine').':&nbsp;', $choices);
                            $group[] =& $mform->createElement('advcheckbox', 'suspend', null, get_string('suspend', 'block_eventsengine').':&nbsp;');
                            return $group;
                        },
        'getformdata' => function($formdata, $enginecontext) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 $data['courseid'] = $formdata->courseid;
                             }
                             if (isset($formdata->suspend)) {
                                 $data['suspend'] = $formdata->suspend;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata, $event) {
                         global $CFG, $DB;
                         require_once($CFG->dirroot.'/lib/enrollib.php');

                         $courseid = $actiondata->courseid;
                         if ($courseid == -1) {
                             if ($event->contextlevel == CONTEXT_COURSE) {
                                 $courseid = $event->contextinstanceid;
                             } else {
                                 return;
                             }
                         }
                         // Enrolment must already exist.
                         $context = context_course::instance($courseid);
                         if (!is_enrolled($context, $userid)) {
                             return;
                         }
                         $roleid = $DB->get_field('role_assignments', 'roleid', [
                             'contextid' => $context->id,
                             'userid' => $userid,
                             'component' => '',
                             'itemid' => 0], IGNORE_MULTIPLE); // TBD?
                         if (empty($roleid)) {
                             return;
                         }
                         if (!enrol_is_enabled('manual') || !($enrol = enrol_get_plugin('manual'))) {
                             return;
                         }
                         $enrolinsts = $DB->get_records('enrol', ['enrol' => 'manual', 'courseid' => $courseid,
                             'status' => ENROL_INSTANCE_ENABLED], 'sortorder,id ASC');
                         if (empty($enrolinsts)) {
                             return;
                         }
                         $enrolinst = reset($enrolinsts);
                         if (!isset($enrolinst->id) || !($rec = $DB->get_record('user_enrolments', [
                             'enrolid' => $enrolinst->id,
                             'userid' => $userid]))) {
                             return;
                         }
                         $enrol->enrol_user($enrolinst, $userid, $roleid, $rec->timestart, $rec->timeend,
                                 $actiondata->suspend ? ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE);
                     }
    ],
    'email_users' => [
        'context' => 'any',
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $group[] =& $mform->createElement('textarea', 'users', get_string('users', 'block_eventsengine').':&nbsp;');
                            $group[] =& $mform->createElement('text', 'subject', get_string('subject', 'block_eventsengine').':&nbsp;');
                            $group[] =& $mform->createElement('textarea', 'body', get_string('body', 'block_eventsengine').':&nbsp;');
                            return $group;
                        },
        'getformdata' => function($formdata, $enginecontext) {
                             $data = [];
                             if (isset($formdata->users)) {
                                 $data['users'] = $formdata->users;
                                 if ($enginecontext != 'user') {
                                     $data['users'] = str_replace('?', '', $data['users']);
                                 }
                             }
                             if (isset($formdata->subject)) {
                                 $data['subject'] = $formdata->subject;
                             }
                             if (isset($formdata->body)) {
                                 $data['body'] = $formdata->body;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata, $event) {
                         global $DB;
                         $users = trim($actiondata->users);
                         if (empty($users)) { // Nothing to do?
                             return;
                         }
                         $a = (object)$event->get_data();
                         if ($userid && ($rec = $DB->get_record('user', ['id' => $userid]))) {
                             $a->user = fullname($rec);
                         } else {
                             $a->user = '?';
                         }
                         if ($event->courseid > 1 && ($crsname = $DB->get_field('course', 'shortname',
                                 ['id' => $event->courseid]))) {
                             $a->course = $crsname;
                         } else {
                             $a->course = '?';
                         }
                         $subject = block_eventsengine_sub_str_vars($actiondata->subject, $a);
                         $bodytext = block_eventsengine_sub_str_vars($actiondata->body, $a);
                         if (strpos($bodytext, '<') !== false) {
                             $bodyhtml = $bodytext;
                             $bodytext = '';
                         } else {
                             $bodyhtml = '';
                         }
                         $users = explode(',', $users);
                         foreach ($users as $user) {
                              $user = trim($user);
                              if (empty($user)) {
                                 continue;
                              }
                              if ($user == '?') {
                                  $user = $userid;
                                  $key = 'id';
                              } else if (strpos($user, '@') !== false) {
                                  $key = 'email';
                              } else {
                                  $key = 'username';
                              }
                              $emailuser = $DB->get_record('user', [$key => $user]);
                              if ($emailuser) {
                                  email_to_user($emailuser, get_admin(), $subject, $bodytext, $bodyhtml);
                              }
                         }
                     }
    ],
    'make_webservice_call' => [
        'context' => 'any',
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $group[] =& $mform->createElement('select', 'calltype',
                                    get_string('wscalltype', 'block_eventsengine').':&nbsp;', [
                                'xmlrpc' => 'XMLRPC', 'rest' => 'REST']);
                            $group[] =& $mform->createElement('text', 'url', get_string('url', 'block_eventsengine').':&nbsp;');
                            $group[] =& $mform->createElement('textarea', 'curlopts', get_string('options', 'block_eventsengine').':&nbsp;');
                            $group[] =& $mform->createElement('textarea', 'data', get_string('data', 'block_eventsengine').':&nbsp;');
                            $mform->setDefault('actiondata[curlopts]', ' '); // TBD?
                            return $group;
                        },
        'getformdata' => function($formdata, $enginecontext) {
                             $data = [];
                             if (isset($formdata->calltype)) {
                                 $data['calltype'] = $formdata->calltype;
                             }
                             if (isset($formdata->url)) {
                                 $data['url'] = $formdata->url;
                             }
                             if (isset($formdata->curlopts)) {
                                 $data['curlopts'] = $formdata->curlopts;
                             }
                             if (isset($formdata->data)) {
                                 $data['data'] = $formdata->data;
                             }
                             return $data;
                         },
        'trigger' => function($userid, $actiondata, $event) {
                         global $CFG, $DB;
                         require_once($CFG->dirroot.'/lib/filelib.php');
                         $trimchars = ";\"' \t\n\r\0\x0B";
                         $a = (object)$event->get_data();
                         $users = ['user' => ['username', 'email', 'idnumber'], 'relateduser' => ['username', 'email', 'idnumber']];
                         foreach ($users as $key => $userfields) {
                             foreach ($userfields as $attr) {
                                 $val = $DB->get_field('user', $attr, ['id' => $event->{$key.'id'}]);
                                 $a->{$key.'_'.$attr} = !empty($val) ? $val : '?';
                             }
                         }
                         if ($event->courseid > 1 && ($crsname = $DB->get_field('course', 'shortname',
                                 ['id' => $event->courseid]))) {
                             $a->course = $crsname;
                         } else {
                             $a->course = '?';
                         }
                         $curlopts = [];
                         $data = [];
                         $fields = ['curlopts', 'data'];
                         foreach ($fields as $field) {
                             $fieldlines = explode("\n", $actiondata->$field);
                             foreach ($fieldlines as $fieldline) {
                                 if (strpos($fieldline, ':') !== false) {
                                     list($key, $val) = explode(':', $fieldline, 2);
                                     $key = trim($key, $trimchars);
                                     ${$field}[$key] = block_eventsengine_sub_str_vars(trim($val, $trimchars), $a);
                                 }
                             }
                         }
                         if (debugging('', DEBUG_DEVELOPER)) {
                             ob_start();
                             var_dump($curlopts);
                             var_dump($data);
                             $tmp = ob_get_contents();
                             ob_end_clean();
                             error_log('block_eventsengine::make_webservice_call: curlopts,data = '.$tmp);
                         }
                         $settings = [];
                         if (!empty($data['cookie'])) {
                             $settings['cookie'] = $data['cookie'];
                             unset($data['cookie']);
                         }
                         $curl = new curl($settings);
                         $ret = '';
                         switch ($actiondata->calltype) {
                             case 'rest':
                                 $ret = $curl->get($actiondata->url, $data, $curlopts);
                                 break;
                             case 'xmlrpc':
                                 if (!empty($data['method'])) {
                                     $method = $data['method'];
                                     unset($data['method']);
                                     $post = xmlrpc_encode_request($method, $data);
                                     $ret = $curl->post($actiondata->url, $post, $curlopts);
                                 }
                                 break;
                         }
                         // TBD: return?, logging?
                         if ($ret && debugging('', DEBUG_DEVELOPER)) {
                             error_log('block_eventsengine::make_webservice_call: response(0..255) = '.substr($ret, 0, 255));
                         }
                     }
    ]
];

// Array of core eventsengine:
$eventsengine = [
    '\core\event\user_enrolment_created' => ['moodle_course_enrolled' => [
        'context' => 'user',
        'display' => function($enginedata) {
                         global $PAGE, $USER;
                         $pgcontext = $PAGE->context;
                         return ($pgcontext == context_system::instance() || $pgcontext == context_user::instance($USER->id) ||
                                 $pgcontext == context_course::instance($enginedata->courseid));
                     },
        'ready' => function($event, $enginedata) {
                       global $DB;
                       if ($enginedata->courseid == $event->courseid) {
                           return $event->relateduserid;
                       }
                       return false;
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $mcourses = $DB->get_recordset('course', null, '', 'id,shortname');
                            $choices = [];
                            foreach ($mcourses as $id => $mcourse) {
                                if ($id > 1) {
                                    $choices[$id] = $mcourse->shortname;
                                }
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'courseid', get_string('mcourse', 'block_eventsengine').':&nbsp;', $choices);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 $data['courseid'] = $formdata->courseid;
                             }
                             return $data;
                         }
    ]],
    '\core\event\course_completed' => ['moodle_course_complete_grade_range' => [
        'context' => 'user',
        'display' => function($enginedata) {
                         global $PAGE, $USER;
                         $pgcontext = $PAGE->context;
                         return ($pgcontext == context_system::instance() || $pgcontext == context_user::instance($USER->id) ||
                                 $pgcontext == context_course::instance($enginedata->courseid));
                     },
        'ready' => function($event, $enginedata) {
                       global $DB;
                       $courseid = $event->courseid;
                       $userid = $event->relateduserid;
                       if ($userid && $courseid == $enginedata->courseid) {
                           $finalgrade = $DB->get_field_sql(
                                   'SELECT gg.finalgrade
                                      FROM {grade_grades} gg
                                INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                                           AND gi.itemtype = "course"
                                     WHERE gg.userid = ?
                                           AND gi.courseid = ?', [$userid, $courseid]);
                           if (block_eventsengine_float_comp($finalgrade, '>=', $enginedata->mingrade) &&
                                   block_eventsengine_float_comp($finalgrade, '<=', $enginedata->maxgrade)) {
                               return $userid; // return Moodle user id.
                           }
                       }
                       return false;
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $mcourses = $DB->get_recordset('course', null, '', 'id,shortname');
                            $choices = [];
                            foreach ($mcourses as $id => $mcourse) {
                                if ($id > 1) {
                                    $choices[$id] = $mcourse->shortname;
                                }
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'courseid', get_string('mcourse', 'block_eventsengine').':&nbsp;', $choices);
                            $group[] =& $mform->createElement('text', 'mingrade', get_string('mingrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            $group[] =& $mform->createElement('text', 'maxgrade', get_string('maxgrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 $data['courseid'] = $formdata->courseid;
                             }
                             if (isset($formdata->mingrade)) {
                                 $data['mingrade'] = $formdata->mingrade;
                             }
                             if (isset($formdata->maxgrade)) {
                                 $data['maxgrade'] = $formdata->maxgrade;
                             }
                             return $data;
                         }
    ]],
    '\local_elisprogram\event\crlm_class_completed' => ['class_complete_grade_range' => [
        'context' => 'user',
        'display' => function($enginedata) {
                         global $PAGE, $USER;
                         $pgcontext = $PAGE->context;
                         return ($pgcontext == context_system::instance() || $pgcontext == context_user::instance($USER->id) ||
                                 $pgcontext == \local_elisprogram\context\pmclass::instance($enginedata->classid));
                     },
        'available' => function() {
                           $localplugins = core_plugin_manager::instance()->get_plugins_of_type('local');
                           return (!empty($localplugins['elisprogram']) && $localplugins['elisprogram']->is_installed_and_upgraded());
                       },
        'ready' => function($event, $enginedata) {
                       global $CFG;
                       require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');
                       if (!isset($event->other)) {
                           return;
                       }
                       $student = (object)$event->other;
                       if ($student->classid == $enginedata->classid && $student->completestatusid > 0 &&
                               block_eventsengine_float_comp($student->grade, '>=', $enginedata->mingrade) &&
                               block_eventsengine_float_comp($student->grade, '<=', $enginedata->maxgrade)) {
                           $euser = new user($student->userid);
                           $muser = $euser->get_moodleuser();
                           return !empty($muser->id) ? $muser->id : false; // return Moodle user id.
                       } else {
                           return false;
                       }
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $eclasses = $DB->get_recordset('local_elisprogram_cls', null, '', 'id,idnumber');
                            $choices = [];
                            foreach ($eclasses as $id => $eclass) {
                                $choices[$id] = $eclass->idnumber;
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'classid', get_string('eclass', 'block_eventsengine').':&nbsp;', $choices);
                            $group[] =& $mform->createElement('text', 'mingrade', get_string('mingrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            $group[] =& $mform->createElement('text', 'maxgrade', get_string('maxgrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->classid)) {
                                 $data['classid'] = $formdata->classid;
                             }
                             if (isset($formdata->mingrade)) {
                                 $data['mingrade'] = $formdata->mingrade;
                             }
                             if (isset($formdata->maxgrade)) {
                                 $data['maxgrade'] = $formdata->maxgrade;
                             }
                             return $data;
                         }
    ], 'course_complete_grade_range' => [
        'context' => 'user',
        'display' => function($enginedata) {
                         global $PAGE, $USER;
                         $pgcontext = $PAGE->context;
                         return ($pgcontext == context_system::instance() || $pgcontext == context_user::instance($USER->id) ||
                                 $pgcontext == \local_elisprogram\context\course::instance($enginedata->courseid));
                     },
        'available' => function() {
                           $localplugins = core_plugin_manager::instance()->get_plugins_of_type('local');
                           return (!empty($localplugins['elisprogram']) && $localplugins['elisprogram']->is_installed_and_upgraded());
                       },
        'ready' => function($event, $enginedata) {
                       global $CFG;
                       require_once($CFG->dirroot.'/local/elisprogram/lib/data/student.class.php');
                       if (!isset($event->other)) {
                           return;
                       }
                       $student = (object)$event->other;
                       $pmclass = new pmclass($student->classid);
                       if (!empty($pmclass) && $pmclass->courseid == $enginedata->courseid && $student->completestatusid > 0 &&
                               block_eventsengine_float_comp($student->grade, '>=', $enginedata->mingrade) &&
                               block_eventsengine_float_comp($student->grade, '<=', $enginedata->maxgrade)) {
                           $euser = new user($student->userid);
                           $muser = $euser->get_moodleuser();
                           return !empty($muser->id) ? $muser->id : false; // return Moodle user id.
                       } else {
                           return false;
                       }
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $ecourses = $DB->get_recordset('local_elisprogram_crs', null, '', 'id,idnumber');
                            $choices = [];
                            foreach ($ecourses as $id => $ecourse) {
                                $choices[$id] = $ecourse->idnumber;
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'courseid', get_string('ecourse', 'block_eventsengine').'&nbsp;', $choices);
                            $group[] =& $mform->createElement('text', 'mingrade', get_string('mingrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            $group[] =& $mform->createElement('text', 'maxgrade', get_string('maxgrade', 'block_eventsengine').':&nbsp;', ['size' => 6]);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->courseid)) {
                                 $data['courseid'] = $formdata->courseid;
                             }
                             if (isset($formdata->mingrade)) {
                                 $data['mingrade'] = $formdata->mingrade;
                             }
                             if (isset($formdata->maxgrade)) {
                                 $data['maxgrade'] = $formdata->maxgrade;
                             }
                             return $data;
                         }
    ]],
    '\local_elisprogram\event\curriculum_completed' => ['program_complete' => [
        'context' => 'user',
        'display' => function($enginedata) {
                         global $PAGE, $USER;
                         $pgcontext = $PAGE->context;
                         return ($pgcontext == context_system::instance() || $pgcontext == context_user::instance($USER->id) ||
                                 $pgcontext == \local_elisprogram\context\program::instance($enginedata->prgid));
                     },
        'available' => function() {
                           $localplugins = core_plugin_manager::instance()->get_plugins_of_type('local');
                           return (!empty($localplugins['elisprogram']) && $localplugins['elisprogram']->is_installed_and_upgraded());
                       },
        'ready' => function($event, $enginedata) {
                       global $CFG;
                       require_once($CFG->dirroot.'/local/elisprogram/lib/data/curriculumstudent.class.php');
                       if (!isset($event->other)) {
                           return;
                       }
                       $curstu = (object)$event->other;
                       if ($curstu->curriculumid == $enginedata->prgid && $curstu->completed == 2) {
                           $euser = new user($curstu->userid);
                           $muser = $euser->get_moodleuser();
                           return !empty($muser->id) ? $muser->id : false; // return Moodle user id.
                       } else {
                           return false;
                       }
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $prgs = $DB->get_recordset('local_elisprogram_pgm', null, '', 'id,idnumber');
                            $choices = [];
                            foreach ($prgs as $id => $prg) {
                                $choices[$id] = $prg->idnumber;
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'prgid', get_string('program', 'block_eventsengine').':&nbsp;', $choices);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->prgid)) {
                                 $data['prgid'] = $formdata->prgid;
                             }
                             return $data;
                         }
    ]],
    '\core\event\role_assigned' => ['role_assigned' => [
        'context' => 'user',
        'ready' => function($event, $enginedata) {
                       if (in_array($event->objectid, $enginedata->roles)) {
                           return !empty($event->relateduserid) ? $event->relateduserid : false; // return Moodle user id.
                       } else {
                           return false;
                       }
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $roles = $DB->get_recordset('role', null, '', 'id,shortname,name');
                            $choices = [];
                            foreach ($roles as $id => $role) {
                                $choices[$id] = $role->shortname; // TBD?
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'roles', get_string('roles', 'block_eventsengine').':&nbsp;',
                                    $choices, ['multiple' => 'multiple']);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->roles)) {
                                 $data['roles'] = $formdata->roles;
                             }
                             return $data;
                         }
    ]],
    '\core\event\role_unassigned' => ['role_unassigned' => [
        'context' => 'user',
        'ready' => function($event, $enginedata) {
                       if (in_array($event->objectid, $enginedata->roles)) {
                           return !empty($event->relateduserid) ? $event->relateduserid : false; // return Moodle user id.
                       } else {
                           return false;
                       }
                   },
        'configform' => function(&$mform, $assign) {
                            global $DB;
                            $roles = $DB->get_recordset('role', null, '', 'id,shortname,name');
                            $choices = [];
                            foreach ($roles as $id => $role) {
                                $choices[$id] = $role->shortname; // TBD?
                            }
                            $group = [];
                            $group[] =& $mform->createElement('select', 'roles', get_string('roles', 'block_eventsengine').':&nbsp;',
                                    $choices, ['multiple' => 'multiple']);
                            return $group;
                        },
        'getformdata' => function($formdata) {
                             $data = [];
                             if (isset($formdata->roles)) {
                                 $data['roles'] = $formdata->roles;
                             }
                             return $data;
                         }
    ]]
];
