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

        // Form fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'event');
        $mform->setType('event', PARAM_TEXT);

        $mform->addElement('header', 'title', get_string('editengineform', 'block_eventsengine'));

        // Frozen base engine params to display & pass/save state.
        $fields = ['name', 'engine', 'action'];
        foreach ($fields as $field) {
            $element =& $mform->createElement('text', $field, get_string($field, 'block_eventsengine'));
            $mform->addElement($element);
            $mform->setType($field, PARAM_TEXT);
            $mform->freeze($field); // TBD: this may prevent submitted value, so may also require hidden?
        }

        // Get engine setting and add sub-form.
        $selectedengine = block_eventsengine_get_engine_def($assign->engine, $assign->event);
        try {
            $group = $selectedengine['configform']($mform);
            $mform->addGroup($group, 'enginedata', get_string('enginedata', 'block_eventsengine'), ' ', false);
        } catch (Exception $e) {
            error_log("block_eventsengine:form:dataform: Exception in engine {$assign->engine} configform: ".$e->getMessage());
        }

        // Get action setting and add sub-form.
        $selectedaction = block_eventsengine_get_action_def($assign->action);
        try {
            $group = $selectedaction['configform']($mform);
            $mform->addGroup($group, 'actiondata', get_string('actiondata', 'block_eventsengine'), ' ', false);
        } catch (Exception $e) {
            error_log("block_eventsengine:form:dataform: Exception in action {$assign->action} configform: ".$e->getMessage());
        }

        $this->add_action_buttons();
    }
}
