<?php
/**
 * Definition of core events engines & actions
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// File path of plugin's eventsengine definition file.
define('BLOCK_EVENTSENGINE_FILE', '/db/eventsengine.php');

/**
 * Floating point comparison method that will try to use bcmath lib if present
 *
 * @throws coding_exception If input numbers aren't numbers, or comparison operator isn't valid.
 * @param string|int|float $num1 The first number
 * @param string $op The math operation to perform, i.e. $num1 $op $num2 where $op maybe '<', '>', '=='('='), '>=', '<=', '!='
 * @param string|int|float $num2 The second number
 * @param bool $nobcmath Optional param if true, forces function not to use bcmath (for testing), defaults to false
 * @return bool The outcome of the float comparison: true or false
 */
function block_eventsengine_float_comp($num1, $op, $num2, $nobcmath = false) {
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
 * Substitute string variables.
 *
 * @param string $in The input string.
 * @param array|object The variables and values.
 * @return string The result string.
 */
function block_eventsengine_sub_str_vars($in, $a) {
    $search = [];
    $replace = [];
    $a = (array)$a;
    foreach ($a as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $search[] = '{$a->'.$key.'}';
            $replace[] = (string)$value;
        } else if (is_array($value) || is_object($value)) {
            $value = (array)$value;
            foreach ($value as $k2 => $v2) {
                if (is_string($v2) || is_numeric($v2)) {
                    $search[] = '{$a->'.$key.'->'.$k2.'}'; // TBD: just '_' for 2nd '->'?
                    $replace[] = (string)$v2;
                }
            }
        }
    }
    if (!empty($search)) {
        return str_replace($search, $replace, $in);
    }
    return $in;
}

/**
 * Custom logging for eventsengine.
 *
 * @param string $msg The message to log.
 */
function block_eventsengine_log($msg) {
    global $CFG;

    $timestamp = strftime('%Y-%m-%d %T');
    list($datepart, $timepart) = explode(' ', $timestamp);
    $dir = "{$CFG->dataroot}/eventsengine";
    if (!is_dir($dir)) {
        @mkdir($dir);
    }
    $logfile = "{$dir}/{$datepart}.log";
    file_put_contents($logfile, "[{$timestamp}] {$msg}\n", FILE_APPEND);
    // TBD: compress previous log file(s)?
}

/**
 * Registers a plugin contains events handlers for a specific type of event.
 *
 * @param string $plugin
 * @param array &$eventsengine the returned eventsengine array.
 * @param array &$eventsactions the returned eventsactions array.
 * @return bool true on success, false on not found or error.
 */
function block_eventsengine_load_for_plugin($plugin, &$evtsengine, &$evtsactions) {
    $plugintypename = explode('_', $plugin, 2);
    $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).BLOCK_EVENTSENGINE_FILE;
    if (file_exists($eventsenginefile)) {
        try {
            require($eventsenginefile);
            $evtsactions = $eventsactions;
            $evtsengine = $eventsengine;
            return true;
        } catch (Exception $e) {
            block_eventsengine_log("block_eventsengine_load_for_plugin({$plugin}): Exception loading ".
                    BLOCKEVENTS_ENGINE_FILE.' :'.$e->getMessage());
        }
    } else {
        block_eventsengine_log("block_eventsengine_load_for_plugin({$plugin}): File not found: {$eventsenginefile}");
    }
    return false;
}

/**
 * Registers a plugin contains events handlers for a specific type of event.
 *
 * @param string $plugin
 */
function block_eventsengine_register($plugin) {
    global $DB;
    $DB->delete_records('block_eventsengine_actions', ['plugin' => $plugin]);
    $DB->delete_records('block_eventsengine_events', ['plugin' => $plugin]);
    $eventsengines = [];
    $eventsactions = [];
    if (block_eventsengine_load_for_plugin($plugin, $eventsengines, $eventsactions)) {
        foreach ($eventsactions as $action => $actiondata) {
            $DB->insert_record('block_eventsengine_actions', (object)['plugin' => $plugin, 'action' => $action,
                'context' => $actiondata['context']]);
        }
        foreach ($eventsengines as $event => $engines) {
            foreach ($engines as $enginekey => $engine) {
                $DB->insert_record('block_eventsengine_events', (object)['plugin' => $plugin, 'event' => $event,
                    'engine' => $enginekey, 'context' => $engine['context']]);
                // Note: Developer MUST manually ensure all required events are observed to call
                // blocks/eventsengine/lib.php::block_eventsengine_handler in plugin's db/events.php
            }
        }
    }
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
    $eventsengine = [];
    $eventsactions = [];
    if (block_eventsengine_load_for_plugin($pluginengs[0], $eventsengine, $eventsactions)) {
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
    $eventsengine = [];
    $eventsactions = [];
    if (block_eventsengine_load_for_plugin($pluginacts[0], $eventsengine, $eventsactions)) {
        if (!empty($eventsactions[$pluginacts[1]])) {
            return $eventsactions[$pluginacts[1]];
        }
    }
    return null;
}

/**
 * Get engine or action name..
 *
 * @param string $engineoraction Format 'pluginname:enginename' or 'pluginname:actionname'
 * @return string The name of the entity or empty string if not found.
 */
function block_eventsengine_get_name($engineoraction) {
    $parts = explode(':', $engineoraction, 2);
    return get_string_manager()->string_exists($parts[1], $parts[0]) ? get_string($parts[1], $parts[0]) : '';
}

/**
 * Single event handler to hook into dispatched events.
 *
 * @param object $event
 */
function block_eventsengine_handler($event) {
    global $DB;
    // Need an array to track events because another observed event could be triggered,
    // [indirectly] by this event handler, delaying/unsequencing the event order.
    static $previousevents = [];
    // block_eventsengine_log("block_eventsengine_handler({$event->eventname}): INFO: Begin");
    $encodedevent = @json_encode($event->get_data());
    if (in_array($encodedevent, $previousevents)) {
        if (debugging('', DEBUG_DEVELOPER)) {
            block_eventsengine_log("block_eventsengine_handler({$event->eventname}):".
                ' Aborting multiple trigger. (stored '.count($previousevents).')');
        }
        return true; // Prevent multiple plugins triggering same callback (this).
    }
    $previousevents[] = $encodedevent;
    $assigns = $DB->get_records('block_eventsengine_assign', ['event' => $event->eventname]);
    if (debugging('', DEBUG_DEVELOPER)) {
        ob_start();
        var_dump($previousevents);
        $tmp = ob_get_contents();
        ob_end_clean();
        block_eventsengine_log("block_eventsengine_handler({$event->eventname}): INFO: previousevents[] = {$tmp}");
    }
    foreach ($assigns as $assign) {
        // block_eventsengine_log("block_eventsengine_handler({$event->eventname}): INFO: assignid = {$assign->id}");
        if ($assign->disabled) {
            continue;
        }
        $pluginengs = explode(':', $assign->engine, 2);
        $enginedisabled = $DB->get_field('block_eventsengine_events', 'disabled', ['engine' => $pluginengs[1],
            'plugin' => $pluginengs[0]]);
        if (!empty($enginedisabled)) {
            continue;
        }
        $engine = block_eventsengine_get_engine_def($assign->engine, $event->eventname);
        if (empty($engine)) {
            continue;
        }
        $pluginacts = explode(':', $assign->action, 2);
        $actiondisabled = $DB->get_field('block_eventsengine_actions', 'disabled', ['action' => $pluginacts[1],
            'plugin' => $pluginacts[0]]);
        if (!empty($actiondisabled)) {
            continue;
        }
        $actiondef = block_eventsengine_get_action_def($assign->action);
        if (empty($actiondef)) {
            continue;
        }
        try {
            $available = (empty($engine['available']) || $engine['available']()) && (empty($actiondef['available']) || $actiondef['available']());
        } catch (Exception $e) {
            $available = false;
            block_eventsengine_log("block_eventsengine_handler({$event->eventname}): assign id = {$assign->id}".
                    ' - Exception in available(): '.$e->getMessage());
        }
        if ($available) {
            try {
                $enginedata = !empty($assign->enginedata) ? (object)@unserialize($assign->enginedata) : null;
                if (($contextid = $engine['ready']($event, $enginedata))) {
                    $actiondata = !empty($assign->actiondata) ? (object)@unserialize($assign->actiondata) : null;
                    $actiondef['trigger']($contextid, $actiondata, $event);
                }
            } catch (Exception $e) {
                block_eventsengine_log("block_eventsengine_handler({$event->eventname}): assign id = {$assign->id} -".
                        ' Exception in engine::ready() or action::trigger(): '.$e->getMessage());
            }
        }
    }
    // block_eventsengine_log("block_eventsengine_handler({$event->eventname}): INFO: Exit");
    return true;
}

