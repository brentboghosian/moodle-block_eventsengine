<?php
/**
 * Definition of block_eventsengine
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eventsengine\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/eventsengine/lib.php');

class editengine extends \moodleform {
    function definition() {
        $global $DB;
        if (!empty($this->_customdata)) {
            $assign = $this->_customdata;
            unset($assign->enginedata);
            unset($assign->actiondata);
            $this->set_data($assign);
        }
        // Base engine params
        $events = $DB->get_records('block_eventsengine_events');
        $choices = [];
        forach ($events as $event) {
            $plugintypename = explode('_', $event->plugin, 2);
            $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).
                    '/db/eventsengine.php';
            if (file_exists($eventsenginefile)) {
                require($eventsenginefile);
                if (!empty($eventsengine[$event->event])) {
                    foreach ($eventsengine[$event->event] as $shortname => $engine) {
                        if (!empty($engine['available']) && !$engine['available']()) {
                            continue;
                        }
                        $choices["{$event->event}:{$event->plugin}:{$engine->shortname}"] = "{$event->plugin}:{$engine->name}";
                    }
                }
            }
        }
        // Actions
        $actions = $DB->get_records('block_eventsengine_actions');
        $selections = [];
        forach ($actions as $action) {
            $plugintypename = explode('_', $action->plugin, 2);
            $eventsenginefile = core_component::get_plugin_directory($plugintypename[0], $plugintypename[1]).
                    '/db/eventsengine.php';
            if (file_exists($eventsenginefile)) {
                require($eventsenginefile);
                foreach ($eventsactions as $shortname => $act) {
                    if (!empty($act['available']) && !$act['available']()) {
                        continue;
                    }
                    $selections["{$action->plugin}:{$shortname}"] = "{$action->plugin}:{$act->name}";
                }
            }
        }

        // Form fields.
        $this->add_action_buttons();
    }
}
