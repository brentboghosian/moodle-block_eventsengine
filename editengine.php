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

$returnurl = required_param('returnurl', PARAM_URL);
$id = optional_param('id', PARAM_INT);
$delete = optional_param('delete', PARAM_ALPHANUMEXT);

echo $OUTPUT->header();

if (!empty($id) && !$DB->record_exists('block_eventsengine_assign', ['id' => $id])) {
    redirect($returnurl, get_string('invalidassignid', 'block_eventsengine'), 15);
} else if (!empty($id) && $delete == 'delete') {
    $DB->delete_records('block_eventsengine_assign', ['id' => $id]);
    redirect($returnurl, get_string('assigndeleted', 'block_eventsengine'), 15);
} else {
    $assign = $id ? $DB->get_record('block_eventsengine_assign', ['id' => $id]) : new stdClass;
    if ($id && !is_siteadmin() && USER->id != $assign->owner) {
        redirect($returnurl, get_string('notpermitted', 'block_eventsengine'), 15);
    }
    $mainform = new \block_eventsengine\form\editengine($PAGE->url, $assign);
    $dataform = new \block_eventsengine\form\dataform($PAGE->url);
    if ($mainform->is_cancelled()) {
        redirect($returnurl, get_string('editcancelled', 'block_eventsengine'), 15);
    }
    if (($data = $mainform->get_data())) {
        $assign->name = $data->name;
        $extengine = explode(':', $data->engine, 2); // Format = event:plugin:enginename
        $assign->engine = $extengine[1];
        $assign->event = $extengine[0];
        $assign->action = $data->action;
        $dataform->set_data($assign); // TBD?
        $dataform->display();
    } else if (($data = $dataform->get_data())) {
        $assign->name = $data->name;
        $assign->event = $data->event;
        $assign->engine = $data->engine;
        $assign->action = $data->action;
        assign->timemodified = time();
        $engine = block_eventsengine_get_engine_def($data->engine, $data->event);
        $assign->enginedata = $engine['getformdata']($data);
        if (!empty($assign->enginedata)) {
            $assign->enginedata = @serialize($assign->enginedata);
        }
        $action = block_eventsengine_get_action_def($data->action);;
        $assign->actiondata = $action['getformdata']($data);
        if (!empty($assign->actiondata)) {
            $assign->actiondata = @serialize($assign->actiondata);
        }
        if ($id) {
            $DB->update_record('block_eventsengine_assign', $assign);
        } else {
            $assign->owner = $USER->id;
            assign->timecreated = time();
            $DB->insert_record('block_eventsengine_assign', $assign);
        }
        redirect($returnurl, get_string('enginesaved', 'block_eventsengine'), 15);
    } else {
        $mainform->display();
    }
}

echo $OUTPUT->footer();
