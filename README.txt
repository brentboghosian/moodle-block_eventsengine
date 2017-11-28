moodle-block_eventsengine
-------------------------
This is a plugin (block) for MOODLE.

The block_eventsengine allows user configuration of events and actions,
from within the block (when added to a Moodle page).
Included with the block are a few standard engine and action defintions,
 that can be used as examples.

Event Engines:
--------------
'Moodle Course enrolled' - triggers for user when they are enrolled into a Moodle Course.

'Moodle Course completed with grade' - triggers for user if they achieve grade within a specified range in a Moodle Course.

'ELIS Class Instance completed with grade' - triggers for user if they achieve grade within a specified range in an ELIS Class Instance.

'ELIS Course Description completed with grade' - triggers for user if they achieve grade within a specified range in an ELIS Course Description.

'ELIS Program completed' - triggers when the configured ELIS Program is completed.

'Role Assigned' - triggers when configured roles are assigned.

'Role Unassigned' - triggers when configured roles are unassigned.


Actions:
--------
'Update User Field' - Updates a Moodle user's standard or profile field to the configured value.

'Enrol into Course' - Enrols the user into the configured Moodle Course (or events instanceid if course contextlevel).

'Update Enrolment Status' - Updates a user's enrolment status to a configured setting in a configured Course (or events instanceid if course contextlevel).

'Send Email to Users' - Sends email to configured users.

'Make a WebServices call' - Make a REST or XMLRPC webservices call using both configured and dynamic event data.


Config
------
Hidden config 'debug' for block - adds raw engine & action data to engine
instance listing 'details' icons (on mouse over).

Eg. Main Moodle config file
{$CFG->dirroot}/config.php:

// ...

// Forced plugin settings:
$CFG->forced_plugin_settings = [
    'block_eventsengine' => [
        'debug' => '1'
    ]
];

// ...

------------------------------------------------------------------------------
Developer Documentation:
------------------------
Any plugin can add db/eventsengine.php definitions, however, it's the
responsibility of the plugin's developer to ensure:

1. The event(s) used in the $eventsengine[] definitions in db/eventsengine.php
*MUST* ALL HAVE A CORRESPONDING ENTRY IN db/events.php
and set to call: /blocks/eventsengine/lib.php::block_eventsengine_handler()

Eg.
db/events.php:

// Event observers.
$observers = [
        [
            'eventname'   => '\core\event\course_completed',
            'includefile' => '/blocks/eventsengine/lib.php',
            'callback'    => 'block_eventsengine_handler',
            'internal'    => false
        ],
        // ...
];

---
2. The initialization function for the eventsengine is called in the plugin's
db/install.php *and* db/upgrade.php

Eg.
db/install.xml:

function xmldb_{$plugin}_install() {
    global $CFG;

    // Always make sure Events Engine has up-to-date definitions.
    $eventsenginelib = $CFG->dirroot.'/blocks/eventsengine/lib.php';
    if (file_exists($eventsenginelib)) {
        require_once($eventsenginelib);
        block_eventsengine_register($plugin);
    }

    // ...

    return true;
}

---
db/upgrade.php:

function xmldb_{$plugin}_upgrade($oldversion = 0) {
    global $CFG;
    $result = true;

    // Always make sure Events Engine has up-to-date definitions.
    $eventsenginelib = $CFG->dirroot.'/blocks/eventsengine/lib.php';
    if (file_exists($eventsenginelib)) {
        require_once($eventsenginelib);
        block_eventsengine_register($plugin);
    }

    // ...

    return $result;
}

------------------------------------------------------------------------------
Adding Events Engine definitions to your Moodle plugin.
------------------------------------------------------------------------------
db/eventsengine.php::$eventsengine[]
------------------------------------

To define new 'Events Engines' in the plugin's db/eventsengine.php
The API is as follows:

// Array of eventsengine:
$eventsengine = [
    {Moodle_eventclass} => [{EventsEngine_shortname (*)} => [
        'context' => {event_context: i.e. 'user' or 'course'},

        /**
         * Available method - Optional (assumes true if absent)
         * whether the engine is available for use.
         * @return bool true if the Engine is available.
         */
        'available' => function() {},

        /**
         * Display method - Optional (assumes true & will display if absent)
         * whether the engine should be displayed on the current page's
         * block listing.
         * @param object $enginedata The engine data for an instance.
         * @return bool true if the instance should be displayed.
         */
        'display' => function($enginedata) {},

        /**
         * Ready method - REQUIRED!
         * If the Engine 'is ready' - should process the event and trigger
         * the associated action.
         * @param object $event The Moodle event object.
         * @param object $enginedata The engine data for an instance.
         * @return int|bool The instance id for the Engine's context if
         * triggered or false.
         */
        'ready' => function($event, $enginedata) {},

        /**
         * Configure Form method - REQUIRED!
         * The form parameters associated with the Events Engine.
         * @param object $mform The Moodle form object.
         * @param object $assign The EventsEngine instance base params,
         *     which allows 'peeking' into assigned action.
         * @return array An mform group array.
         */
        'configform' => function(&$mform, $assign) {},

        /**
         * Get Form Data method - REQUIRED!
         * Method to read the form's 'configform' parameters associated with
         * the Events Engine.
         * @param object $formdata The Moodle form data.
         * @return array|bool A data array or false on error.
         */
        'getformdata' => function($formdata) {}

    ], ...
  ], ...
];
------------------------------------------------------------------------------
db/eventsengine.php::$eventsactions[]
-------------------------------------

To define new 'Events Engine' Actions in the plugin's db/eventsengine.php
The API is as follows:

// Array of eventsactions:
$eventsactions = [
    {Action_shortname (*)} => [
        'context' => {Action_context: i.e. 'user', 'course' or 'any' (for all, otherwise must match engine's context)},

        /**
         * Available method - Optional (assumes true if absent)
         * whether the Action is available for use.
         * @return bool true if the Action is available.
         */
        'available' => function() {},

        /**
         * Configure Form method - REQUIRED!
         * The form parameters associated with the Action.
         * @param object $mform The Moodle form object.
         * @param object $assign The EventsEngine instance base params,
         *     which allows 'peeking' into assigned event & engine.
         * @return array An mform group array.
         */
        'configform' => function(&$mform, $assign) {},

        /**
         * Get Form Data method - REQUIRED!
         * Method to read the form's 'configform' parameters associated with
         * the Action.
         * @param object $formdata The Moodle form data.
         * @param string $enginecontext The engine context; eg. 'user', 'course', ...
         * @return array|bool A data array or false on error.
         */
        'getformdata' => function($formdata, $enginecontext) {},

        /**
         * Trigger method - REQUIRED!
         * If the associated Engine should process the event, it will trigger
         * this function
         * @param int $contextinstanceid The Moodle context's instanceid
         *                               Eg. Moodle user id.
         * @param object $actiondata The action data for an instance.
         * @param object $event The Moodle event object, that triggered this.
         */
        'trigger' => function($contextinstanceid, $actiondata, $event) {}

    ], ...
];

(*) For all engine/action shortnames - MUST BE VALID VARIABLE NAME (no spaces, no special chars, etc.). If a language string exists (and it should!), in the plugin, with the same shortname it will be used as the 'name' and the language string + '_help' will be used for the group help/tooltip, in the edit form.

Eg.  /lang/en/{$plugin}.php

$strings[{EventsEngine_shortname}] = '{EventsEngine_name}';
$strings[{EventsEngine_shortname}.'_help'] = 'Explanation of parameters, etc.';
$strings[{Action_shortname}] = '{Action_name}';
$strings[{Action_shortname}.'_help'] = 'Explanation of parameters, etc.';

------------------------------------------------------------------------------
Notes:
------
This block was conceived as an improvement to the ELIS ResultsEngine - 
which uses cron not events - which is why some included engines are for 
ELIS entities.

by Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
The Last ELIS Architect (? 2017)
