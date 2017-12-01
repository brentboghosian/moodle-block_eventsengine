<?php
/**
 * Add/Edit/Delete EventsEngine instance.
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
global $DB, $OUTPUT, $PAGE, $SITE, $USER;

$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/eventsengine/editengine.php');

$returnurl = required_param('returnurl', PARAM_URL); // TBD?
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUMEXT);
$enable = optional_param('enable', '', PARAM_ALPHANUMEXT);
$context = optional_param('context', 'user', PARAM_ALPHANUMEXT);
$engine = optional_param('engine', '', PARAM_RAW);
$eeaction = optional_param('action', '', PARAM_RAW);


if (!empty($id) && !$DB->record_exists('block_eventsengine_assign', ['id' => $id])) {
    redirect($returnurl, get_string('invalidassignid', 'block_eventsengine'), 15);
} else if (!empty($id) && $delete == 'delete') {
    $DB->delete_records('block_eventsengine_assign', ['id' => $id]);
    redirect($returnurl, get_string('assigndeleted', 'block_eventsengine', $id), 15);
} else if (!empty($id) && !empty($enable)) {
    $DB->set_field('block_eventsengine_assign', 'disabled', ($enable == 'disable') ? 1 : 0, ['id' => $id]);
    redirect($returnurl, get_string("assign{$enable}d", 'block_eventsengine', $id), 15);
} else {
    if ($id) {
        $assign = $DB->get_record('block_eventsengine_assign', ['id' => $id]);
        $assign->enginedata = (array)@unserialize($assign->enginedata);
        $assign->actiondata = (array)@unserialize($assign->actiondata);
        if (empty($assign)) {
            redirect($returnurl, get_string('error'), 15); // TBD?
        }
        if (!is_siteadmin() && $USER->id != $assign->owner) {
            redirect($returnurl, get_string('notpermitted', 'block_eventsengine'), 15);
        }
    } else {
        if (empty($context) || empty($engine) || strpos($engine, ':') === false ||
                empty($eeaction) || strpos($eeaction, ':') === false) {
            block_eventsengine_log("editengine.php::missing context|engine|action");
            redirect($returnurl, get_string('error'), 15); // TBD?
        }
        $assign = new stdClass;
        $assign->context = $context;
        $engineparts = explode(':', $engine, 2);
        $assign->event = $DB->get_field('block_eventsengine_events', 'event', [
            'plugin' => $engineparts[0], 'engine' => $engineparts[1]]);
        $assign->engine = $engine;
        $assign->action = $eeaction;
    }
    $dataform = new \block_eventsengine\form\dataform(null, $assign);
    if ($dataform->is_cancelled()) {
        redirect($returnurl, get_string('editcancelled', 'block_eventsengine'), 15);
    } else if (($data = $dataform->get_data())) {
        $assign->name = $data->name;
        $assign->event = $data->event;
        $assign->engine = $data->engine;
        $assign->action = $data->action;
        $assign->timemodified = time();
        $engine = block_eventsengine_get_engine_def($data->engine, $data->event);
        try {
            $assign->enginedata = $engine['getformdata']((object)$data->enginedata);
        } catch (Exception $e) {
            block_eventsengine_log("block_eventsengine::editengine.php: Exception in engine {$data->engine} getformdata: ".
                    $e->getMessage());
        }
        if (!empty($assign->enginedata)) {
            $assign->enginedata = @serialize($assign->enginedata);
        } else {
            redirect($returnurl, "block_eventsengine::editengine.php: Failed getting engine data - aborting!", 30);
        }
        $action = block_eventsengine_get_action_def($data->action);;
        try {
            $assign->actiondata = $action['getformdata']((object)$data->actiondata, $context);
        } catch (Exception $e) {
            block_eventsengine_log("block_eventsengine::editengine.php: Exception in action {$data->action} getformdata: ".
                    $e->getMessage());
        }
        if (!empty($assign->actiondata)) {
            $assign->actiondata = @serialize($assign->actiondata);
        } else {
            redirect($returnurl, "block_eventsengine::editengine.php: Failed getting action data - aborting!", 30);
        }
        if ($id) {
            $DB->update_record('block_eventsengine_assign', $assign);
        } else {
            $assign->owner = $USER->id;
            $assign->timecreated = time();
            $DB->insert_record('block_eventsengine_assign', $assign);
        }
        redirect($returnurl, get_string('enginesaved', 'block_eventsengine'), 15);
    } else {
        echo $OUTPUT->header();
        $assign->returnurl = $returnurl;
        $assign->context = $context;
        $dataform->set_data($assign);
        $dataform->display();
        echo $OUTPUT->footer();
    }
}

