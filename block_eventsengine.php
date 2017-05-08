<?php
/**
 * Definition of block_eventsengine
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/lib.php');

/**
 * EventsEngine block.
 */
class block_eventsengine extends block_base {
    /** @var string The component identifier for this block. */
    protected $component = 'block_eventsengine';

    /**
     * Get the block's content.
     *
     * @return \stdClass The block's populated content property.
     */
    public function get_content() {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        // $config = get_config('block_eventsengine');
        $this->content = new stdClass;
        $this->content->text = '';

        $assigns = $DB->get_recordset('block_eventsengine_assign');
        $todisplay = [];
        foreach ($assigns as $assign) {
            $engine = block_eventsengine_get_engine_def($assign->engine, $assign->event)
            if (empty($engine)) {
                // This engine no longer exists so lets just delete it now!
                $DB->delete_records('block_eventsengine_assign', ['id' => $assign->id]);
                // TBD: Todo - log deletion?!
            } else if (!($available = $engine['available']()) || $engine['display'](@unserialize($assign->enginedata))) {
                // Check if action is still defined and flag.
                $actiondef = block_eventsengine_get_action_def($assign->action);
                $assign->actionexists = !empty($actiondef);
                $assign->available = $available;
                $todisplay[] = $assign;
            }
        }

        $this->content->text = html_writer::tag('h5', get_string('listingtitle', 'block_eventsengine'));
        if (!empty($todisplay)) {
            // Tabulate listing.
            $table = new html_table();
            $table->head = [get_string('name', 'block_eventsengine')];
            if (debugging('', DEBUG_DEVELOPER)) {
                $table->head[] = get_string('rawdata', 'block_eventsengine')
            }
            $table->head[] = ''; 
            $table->data = [];
            $i = 0;
            foreach ($todisplay as $item) {
                $owner = '?';
                if (($mdluser = $DB->get_record('user', ['id' => $item->owner]))) {
                    $owner = fullname($mdluser);
                }
                $details = get_string('event', 'block_eventsengine').': '.$item->event."\n".
                        get_string('engine', 'block_eventsengine').': '.$item->engine."\n".
                        get_string('action', 'block_eventsengine').': '.($item->actionexists ? $item->action : '-')."\n".
                        get_string('available', 'block_eventsengine').': '.$item->available."\n".
                        get_string('owner', 'block_eventsengine').': '.$owner];
                $table->data[$i] = [$item->name]; 
                if (debugging('', DEBUG_DEVELOPER)) {
                    ob_start();
                    var_dump(@unserialize($item->enginedata));
                    var_dump(@unserialize($item->actiondata));
                    $rawdata = ob_get_contents();
                    ob_end_clean();
                    $table->data[$i][] = "<pre>{$rawdata}</pre>";
                }
                $table->data[$i][] = html_writer::empty_tag('img', ['src' => "{$CFG->wwwroot}/blocks/recognition/pix/details.png",
                    'title' => $details]).((is_siteadmin() || $USER->id == $item->owner) ?
                        html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php', ['id' => $assign->id, 'returnurl' => $PAGE->url]),
                        new pix_icon('t/edit')).html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php',
                        ['id' => $assign->id, 'delete' => 'delete', 'returnurl' => $PAGE->url]), new pix_icon('t/delete')) : '');
                ++$i;
            }
            $this->content->text .= html_writer::table($table);
        } else {
            $this->content->text .= html_writer::tag('p', get_string('noengines', 'block_eventsengine'));
        }

        // TBD: Add capabilities to restrict access.
        $this->content->text .= html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php',['returnurl' => $PAGE->url]),
                get_string('addengine', 'block_eventsengine'));

        return $this->content;
    }

    /**
     * Whether the block has settings.
     *
     * @return bool Has settings or not.
     */
    public function has_config() {
        return false;
    }
}
