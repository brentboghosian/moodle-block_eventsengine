<?php
/**
 * Definition of core events engines & actions
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Floating point comparison method that will try to use bcmath lib if present
 *
 * @throws coding_exception If input numbers aren't numbers, or comparison operator isn't valid.
 * @param string|int|float $num1 The first number
 * @param string|int|float $num2 The second number
 * @param string $op The math operation to perform, i.e. $num1 $op $num2 where $op maybe '<', '>', '=='('='), '>=', '<=', '!='
 * @param bool $nobcmath Optional param if true, forces function not to use bcmath (for testing), defaults to false
 * @return bool The outcome of the float comparison: true or false
 */
function block_eventsengine_float_comp($num1, $num2, $op, $nobcmath = false) {
    // Valid comparison operations, and their associated bcmath returns.
    static $validopsmap = array(
        '<' => array(-1),
        '>' => array(1),
        '==' => array(0),
        '<=' => array(-1, 0),
        '>=' => array(1, 0),
        '!=' => array(-1, 1)
    );

    if ($op == '=') {
        $op = '==';
    }

    // Check for valid inputs.
    if (!is_numeric($num1) || !is_numeric($num2) || !isset($validopsmap[$op])) {
        throw new coding_exception('block_eventsengine_float_comp() invalid input(s) coding error encountered - please fix code!');
    }

    // Determine number of decimal places in $num1.
    $deci1 = 0;
    if (($point = strpos((string)$num1, '.')) !== false) {
        $deci1 = strlen((string)$num1) - $point - 1;
    }

    // Determine number of decimal places in $num2.
    $deci2 = 0;
    if (($point = strpos((string)$num2, '.')) !== false) {
        $deci2 = strlen((string)$num2) - $point - 1;
    }

    // Scale is the largest number of decimals between $num1 and $num2.
    $scale = max($deci1, $deci2, 1);

    if (!$nobcmath && extension_loaded('bcmath') && function_exists('bccomp')) {
        $result = bccomp($num1, $num2, $scale);
        return in_array($result, $validopsmap[$op], true);
    } else {
        // Epsilon is the precision we use to determine if two floats are equal. If their difference is less that this amount,
        // they are considered equal. We use $scale to generate a float that is one order of magnitude more precise that the
        // max. precision of the two numbers.
        $epsilon = (float)('0.'.str_repeat(0, $scale).'1');

        // Convert $num1 to desired precision.
        if ($deci1 < $scale) {
            $num1 = sprintf("%.{$scale}F", (float)$num1);
        }

        // Convert $num2 to desired precision.
        if ($deci2 < $scale) {
            $num2 = sprintf("%.{$scale}F", (float)$num2);
        }

        // Ensure we're dealing with floats.
        $num1 = (float)$num1;
        $num2 = (float)$num2;

        // Compare numbers using the first part of the comparison operator.
        // This handles the "less-than", "greater-than", "not-equals", and "equals" cases, without worrying about "or-equals" yet.
        switch ($op{0}) {
            // Check if $num1 is less than $num2 without being equal.
            case '<':
                if ($num1 < $num2 && !(abs($num1 - $num2) < $epsilon)) {
                    return true;
                }
                break;

            // Check if $num1 is greater than $num2 without being equal.
            case '>':
                if ($num1 > $num2 && !(abs($num1 - $num2) < $epsilon)) {
                    return true;
                }
                break;
            // Check if $num1 is not equal to $num2, within given precision.
            case '!':
                if (!(abs($num1 - $num2) < $epsilon)) {
                    return true;
                }
                break;

            // Check if $num1 is equal to $num2, within given precision.
            case '=':
                if (abs($num1 - $num2) < $epsilon) {
                    return true;
                }
                break;
        }

        // If we are dealing with a less-than-or-equals or a greater-than-or-equals case, handle the "or-equals" portion.
        if (strlen($op) == 2 && ($op{0} === '>' || $op{0} === '<') && $op{1} === '=' && abs($num1 - $num2) < $epsilon) {
            return true;
        }

        return false;
    }
}

/**
 * Registers a plugin contains events handlers for a specific type of event.
 *
 * @param string $plugin
 */
function block_eventsengine_register($plugin) {
    global $DB;
    delete_records('block_eventsengine_actions', ['plugin' => $plugin]);
    delete_records('block_eventsengine_events', ['plugin' => $plugin]);
    $plugintypename = explode('_', $plugin, 2);
    $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).'/db/eventsengine.php';
    if (file_exists($eventsenginefile)) {
        require($eventsenginefile);
        foreach ($eventsengine as $action => $actiondata) {
            $DB->insert_record('block_eventsengine_actions', (object)['plugin' => $plugin, 'action' => $action]);
        }
        foreach ($eventsengine as $event => $engine) {
            $DB->insert_record('block_eventsengine_events', (object)['plugin' => $plugin, 'event' => $event]);
            // Note: Developer MUST manually ensure all required events are observed to call
            // blocks/eventsengine/lib.php::block_eventsengine_handler in plugin's db/events.php
        }
    }
}

/**
 * Get engine event.
 *
 * @param string $pluginengine Format 'pluginname:enginename' or empty for all.
 * @return string"bool The event name or false if $pluginengine not found
 */
function block_eventsengine_get_engine_event($pluginengine) {
    // ToDO: add caching.
    $pluginengs = explode(':', $pluginengine, 2);
    $plugintypename = explode('_', $pluginengs[0], 2);
    $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).'/db/eventsengine.php';
    if (file_exists($eventsenginefile)) {
        require($eventsenginefile);
        foreach ($eventsengine as $eventname => $engines) {
            foreach ($engines as $enginename => $engine) {
                if ($enginename == $pluginengine) {
                    return $eventname;
                }
            }
        }
    }
    return false;
}

/**
 * Get engine def.
 *
 * @param string $pluginengine Format 'pluginname:enginename' or empty for all.
 * @param string $event
 * @return object The eventsengine def
 */
function block_eventsengine_get_engine_def($pluginengine, $event) {
    // ToDO: add caching.
    $pluginengs = explode(':', $pluginengine, 2);
    $plugintypename = explode('_', $pluginengs[0], 2);
    $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).'/db/eventsengine.php';
    if (file_exists($eventsenginefile)) {
        require($eventsenginefile);
        if (!empty($eventsengine[$event][$pluginengs[1]])) {
            return $eventsengine[$event][$pluginengs[1]];
        }
    }
    return null;
}

/**
 * Get action def.
 *
 * @param string $pluginaction Format 'pluginname:actionname'
 * @return object The eventsengine action def
 */
function block_eventsengine_get_action_def($pluginaction) {
    // ToDO: add caching.
    $pluginacts = explode(':', $pluginaction, 2);
    $plugintypename = explode('_', $pluginacts[0], 2);
    $actionfile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).'/db/eventsengine.php';
    if (file_exists($actionfile)) {
        require($actionfile);
        if (!empty($eventsactions[$pluginacts[1]])) {
            return $eventsactions[$pluginacts[1]];
        }
    }
    return null;
}

/**
 * Single event handler to hook into dispatched events.
 *
 * @param object $event
 */
function block_eventsengine_handler($event) {
    global $DB;
    $assigns = $DB->get_records('block_eventsengine_assign', ['event' => $event->eventname]);
    foreach ($assigns as $assign) {
        $pluginengs = explode(':', $assign->engine, 2);
        $engine = block_eventsengine_get_engine_def($assign->engine, $event->eventname)
        if (empty($engine)) {
            continue;
        }
        $pluginacts = explode(':', $assign->action, 2);
        $action = $DB->get_record('block_eventsengine_actions', ['action' => $pluginacts[1]]);
        if (empty($action)) {
            continue;
        }
        $actiondef = block_eventsengine_get_action_def($assign->action);
        if (empty($actiondef)) {
            continue;
        }
        if ((empty($engine['available']) || $engine['available']()) &&
                (empty($actiondef['available']) || $actiondef['available']()) &&
                ($muserid = $engine['ready']($event, @unserialize($assign->enginedata)))) {
            $actiondef['trigger']($muserid, @unserialize($assign->actiondata));
        }
    }
}

