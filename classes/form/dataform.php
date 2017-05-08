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

        $mform->addElement('header', 'title', get_string('editengineform', 'block_eventsengine'));

        // Frozen base engine params to display & pass/save state.
        $fields = ['name', 'engine', 'action'];
        foreach ($fields as $field) {
            $element =& $mform->createElement('text', $field, $assign->$field);
            $element->freeze();
            $mform->addElement($element);
            $mform->setType($field, PARAM_TEXT);
        }

        // Get engine setting and add sub-form.
        $mform->addElement('header', 'enginedata', get_string('enginedata', 'block_eventsengine'));
        $selectedengine = block_eventsengine_get_engine_def($assign->engine, $assign->event);
        $selectedengine['configform']($mform);

        // Get action setting and add sub-form.
        $mform->addElement('header', 'actiondata', get_string('actiondata', 'block_eventsengine'));
        $selectedaction = block_eventsengine_get_action_def($assign->action);
        $selectedaction['configform']($mform);

        $this->add_action_buttons();
    }
}
