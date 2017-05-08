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
        // Frozen base engine params to display & pass/save state.

        // Get engine setting and add sub-form.
        $selectedengine = block_eventsengine_get_engine_def($assign->engine, $assign->event);
        $selectedengine['configform']($mform);
        // Get action setting and add sub-form.
        $selectedaction = block_eventsengine_get_action_def($assign->action);
        $selectedaction['configform']($mform);

        $this->add_action_buttons();
    }
}
