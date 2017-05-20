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
        $mform =& $this->_form;
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
            $eventsengine = [];
            $eventsactions = [];
            if (!$event->disabled && block_eventsengine_load_for_plugin($event->plugin, $eventsengine, $eventsactions)) {
                if (!empty($eventsengine[$event->event])) {
                    foreach ($eventsengine[$event->event] as $shortname => $engine) {
                        if (!($available = empty($engine['available']))) {
                            try {
                                $available = $engine['available']();
                            } catch (Exception $e) {
                                $available = false;
                                error_log("block_eventsengine:form:editengine: Exception in {$event->plugin}:eventsengine:{$shortname}:available(): ".
                                        $e->getMessage());
                           }
                        }
                        if ($available) {
                            $choices["{$event->event}:{$event->plugin}:{$engine->shortname}"] = "{$event->plugin}:{$engine->name}";
                        }
                    }
                }
            }
        }
        // Actions
        $actions = $DB->get_records('block_eventsengine_actions');
        $selections = [];
        forach ($actions as $action) {
            $eventsengine = [];
            $eventsactions = [];
            if (!$action->disabled && block_eventsengine_load_for_plugin($action->plugin, $eventsengine, $eventsactions)) {
                foreach ($eventsactions as $shortname => $act) {
                    if (!($available = empty($act['available']))) {
                        try {
                            $available = $act['available']();
                        } catch (Exception $e) {
                            $available = false;
                            error_log("block_eventsengine:form:editengine: Exception in {$action->plugin}:eventsactions:{$shortname}:available(): ".
                                    $e->getMessage());
                        }
                    }
                    if ($available) {
                        $selections["{$action->plugin}:{$shortname}"] = "{$action->plugin}:{$act->name}";
                    }
                }
            }
        }

        // Form fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'block_eventsengine').':');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', NULL, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');

        $mform->addElement('select', 'engine', get_string('engine', 'block_eventsengine'), $choices);
        $mform->addElement('select', 'action', get_string('action', 'block_eventsengine'), $selections);

        $this->add_action_buttons(true, get_string('next'));
    }
}
