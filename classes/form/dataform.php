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
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/blocks/eventsengine/lib.php');

class dataform extends \moodleform {
    function definition() {
        global $DB;
        $mform =& $this->_form;
        $assign = new \stdClass;
        if (!empty($this->_customdata)) {
            $assign = $this->_customdata;
        }

        // Form fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);

        // Frozen base engine params to display & pass/save state.
        $fields = ['event', 'engine', 'action'];
        foreach ($fields as $field) {
            $dummyfield = '_'.$field;
            $element =& $mform->createElement('text', $dummyfield, get_string($field, 'block_eventsengine').': ', ['size' => 60]); // TBD?
            $mform->addElement($element);
            $mform->setDefault($dummyfield, $assign->$field); // TBD?
            $mform->freeze($dummyfield);
            $mform->addElement('hidden', $field);
            $mform->setType($field, PARAM_RAW); // TBD?
        }
        $mform->addElement('hidden', 'context');
        $mform->setType('context', PARAM_TEXT);

        $mform->addElement('text', 'name', get_string('name', 'block_eventsengine').':', ['size' => 40]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', NULL, 'client'); // TBD?
        $mform->addRule('name', null, 'maxlength', 255, 'client');

        $mform->addElement('header', 'title', get_string('editengineform', 'block_eventsengine'));

        // Get engine setting and add sub-form.
        $selectedengine = block_eventsengine_get_engine_def($assign->engine, $assign->event);
        $engineparts = explode(':', $assign->engine, 2);
        try {
            $group = $selectedengine['configform']($mform, $assign);
            if (!empty($group)) {
                $mform->addGroup($group, 'enginedata', get_string('enginedata', 'block_eventsengine'), '<br/>', true);
                $mform->addGroupRule('enginedata', get_string('required'), 'required', null, null /* TBD format: 2? */, 'client');
                if (get_string_manager()->string_exists($engineparts[1].'_help', $engineparts[0])) {
                    $mform->addHelpButton('enginedata', $engineparts[1], $engineparts[0]);
                }
            }
        } catch (Exception $e) {
            block_eventsengine_log("block_eventsengine:form:dataform: Exception in engine {$assign->engine} configform: ".
                    $e->getMessage());
        }

        // Get action setting and add sub-form.
        $selectedaction = block_eventsengine_get_action_def($assign->action);
        $actionparts = explode(':', $assign->action, 2);
        try {
            $group = $selectedaction['configform']($mform, $assign);
            if (!empty($group)) {
                $mform->addGroup($group, 'actiondata', get_string('actiondata', 'block_eventsengine'), '<br/>', true);
                $mform->addGroupRule('actiondata', get_string('required'), 'required', null, null /* TBD format: 2? */, 'client');
                if (get_string_manager()->string_exists($actionparts[1].'_help', $actionparts[0])) {
                    $mform->addHelpButton('actiondata', $actionparts[1], $actionparts[0]);
                }
            }
        } catch (Exception $e) {
            block_eventsengine_log("block_eventsengine:form:dataform: Exception in action {$assign->action} configform: ".
                    $e->getMessage());
        }

        $this->add_action_buttons();
    }

    /**
     * Definition after data method to modify form based on form data
     *
     */
    function definition_after_data() {
        $mform = &$this->_form;

        $mform->addElement('html', '<script type="text/javascript">
        YUI().use("node", "event", function(Y) {
            Y.on("load", function() {
                Y.all("#fgroup_id_enginedata label").each(function($elem) {
                    $elem.removeClass("accesshide");
                });
                Y.all("#fgroup_id_actiondata label").each(function($elem) {
                    $elem.removeClass("accesshide");
                });
            });
        });
        </script>');
    }
}
