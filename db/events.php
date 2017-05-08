<?php
/**
 * Definition of events
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Event observers.
$observers = [
        [
            'eventname'   => '\local_elisprogram\event\crlm_class_completed',
            'includefile' => '/blocks/eventsengine/lib.php',
            'callback'    => 'block_eventsengine_handler',
            'internal'    => false
        ],[
            'eventname'   => '\local_elisprogram\event\curriculum_completed',
            'includefile' => '/blocks/eventsengine/lib.php',
            'callback'    => 'block_eventsengine_handler',
            'internal'    => false
        ];
];
