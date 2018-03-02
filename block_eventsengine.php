<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/lib.php');

define('EE_WIDE_DISPLAY', false); // TBD.

/**
 * Definition of block_eventsengine
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_eventsengine extends block_base {
    /** @var string The component identifier for this block. */
    protected $component = 'block_eventsengine';

    /** @var bool The debug flag. */
    protected $debug = false;

    /**
     * Get table menu.
     * @param string $context The context type.
     * @param string $table The DB table.
     * @param string $entity The entity: 'action' or 'engine'.
     * @return array The table's menu.
     */
    private static function get_table_menu($context, $table, $entity) {
        global $DB;
        $ret = [];
        $recs = $DB->get_recordset($table, ['context' => $context]);
        foreach ($recs as $rec) {
            if ($rec->disabled) {
                continue;
            }
            $key = "{$rec->plugin}:{$rec->$entity}";
            $event = null;
            if ($entity == 'engine') {
               $event = $rec->event;
            }
            $libfcn = "block_eventsengine_get_{$entity}_def";
            $def = $libfcn($key, $event);
            if (!($available = empty($def['available']))) {
                try {
                    $available = $def['available']();
                } catch (Exception $e) {
                    $available = false;
                    block_eventsengine_log("block_eventsengine:get_table_menu: Exception in {$key}:available(): ".
                            $e->getMessage());
                }
            }
            if ($available) {
                $name = block_eventsengine_get_name($key);
                $label = empty($name) ? $rec->$entity : $name;
                $ret[$key] = "{$label} ({$rec->plugin})";
            }
        }
        return $ret;
    }

    /**
     * Get engine menu.
     * @param string $context The context type.
     * @return array The engine menu.
     */
    protected static function get_engine_menu($context) {
        return static::get_table_menu($context, 'block_eventsengine_events', 'engine');
    }

    /**
     * Get action menu.
     * @param string $context The context type.
     * @return array The action menu.
     */
    protected static function get_action_menu($context) {
        return static::get_table_menu($context, 'block_eventsengine_actions', 'action');
    }

    /**
     * Block init() method - required.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_eventsengine');
        $this->debug = get_config('block_eventsengine', 'debug');
    }

    /**
     * Get the block's content.
     *
     * @return \stdClass The block's populated content property.
     */
    public function get_content() {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        $this->content = new stdClass;
        $this->content->text = '';

        $assigns = $DB->get_recordset('block_eventsengine_assign');
        $todisplay = [];
        foreach ($assigns as $assign) {
            $engine = block_eventsengine_get_engine_def($assign->engine, $assign->event);
            if (empty($engine)) {
                // TBD: This engine no longer exists so lets just delete it now?
                // Or perhaps add disabled column to assigns and mark disabled?
                $DB->delete_records('block_eventsengine_assign', ['id' => $assign->id]);
                // TBD: Todo - log deletion/disable.
            } else if (!isset($engine['display']) || $engine['display']((object)@unserialize($assign->enginedata))) {
                $available = !isset($engine['available']) || $engine['available']();
                // Check if action is still defined and flag.
                $actiondef = block_eventsengine_get_action_def($assign->action);
                $assign->context = $engine['context'];
                $assign->actionexists = !empty($actiondef);
                $assign->available = $available;
                $assign->actionname = !empty($actiondef) ? block_eventsengine_get_name($assign->action) : '-';
                $assign->enginename = block_eventsengine_get_name($assign->engine);
                $todisplay[] = $assign;
            }
        }

        $this->content->text = html_writer::tag('h5', get_string('listingtitle', 'block_eventsengine'));
        $nl = "\n";
        $brnl = html_writer::empty_tag('br').$nl;
        if (!empty($todisplay)) {
            // Tabulate listing.
            $table = new html_table();
            $table->head = [get_string('name', 'block_eventsengine'), '']; // TBD: Conserve width in block?
            if (EE_WIDE_DISPLAY) { // TBD: Alternative method.
                $table->head[] = get_string('info');
                $table->head[] = get_string('actions');
            }
            $table->data = [];
            $i = 0;
            foreach ($todisplay as $item) {
                $owner = '?';
                if (($mdluser = $DB->get_record('user', ['id' => $item->owner]))) {
                    $owner = fullname($mdluser);
                }
                $table->data[$i] = [$item->name];
                $details = get_string('event', 'block_eventsengine').': '.$item->event.$nl.
                        get_string('context', 'block_eventsengine').': '.$item->context.$nl.
                        get_string('engine', 'block_eventsengine').': '.$item->engine.' '.$item->enginename.$nl.
                        get_string('action', 'block_eventsengine').': '.($item->actionexists ? $item->action : '-').' '.
                        ($item->actionexists ? $item->actionname : '').$nl.get_string('available', 'block_eventsengine').': '.
                        $item->available.$nl.get_string('owner', 'block_eventsengine').': '.$owner;
                if (!empty($this->debug) || debugging('', DEBUG_DEVELOPER)) {
                    ob_start();
                    var_dump(@unserialize($item->enginedata));
                    var_dump(@unserialize($item->actiondata));
                    $rawdata = ob_get_contents();
                    ob_end_clean();
                    $details .= "\nData:\n{$rawdata}";
                }
                if (EE_WIDE_DISPLAY) {
                    $table->data[$i][] = $details;
                    // In block so probably would be too wide.
                    $editbuttons = [];
                }
                $editbuttons = [html_writer::img("{$CFG->wwwroot}/blocks/eventsengine/pix/details.png", '', ['title' => $details])];
                if (is_siteadmin() || $USER->id == $item->owner) {
                    $editbuttons[] = html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php',
                            ['id' => $item->id, 'returnurl' => $PAGE->url]), $OUTPUT->pix_icon('t/edit', get_string('edit')));
                    $ablepix = ($item->disabled) ? 't/show' : 't/hide'; // TBD?
                    $ableparam = ($item->disabled) ? 'enable' : 'disable';
                    $editbuttons[] = html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php',
                            ['id' => $item->id, 'enable' => $ableparam, 'returnurl' => $PAGE->url]), $OUTPUT->pix_icon($ablepix,
                            get_string($ableparam, 'block_eventsengine')));
                    $editbuttons[] = html_writer::link(new moodle_url('/blocks/eventsengine/editengine.php',
                            ['id' => $item->id, 'delete' => 'delete', 'returnurl' => $PAGE->url]),
                            $OUTPUT->pix_icon('t/delete', get_string('delete')));
                }
                $table->data[$i][] = implode('&nbsp;', $editbuttons);
                ++$i;
            }
            $this->content->text .= html_writer::table($table);
        } else {
            $this->content->text .= html_writer::tag('p', get_string('noengines', 'block_eventsengine'));
        }
        $this->content->text .= html_writer::empty_tag('hr').html_writer::tag('h5',
                get_string('createnewengine', 'block_eventsengine')); // TBD?

        // TBD: Add capabilities to restrict access to creation.

        // New eventsengine selector of contexts.
        $contextmenu = array_keys($DB->get_records_menu('block_eventsengine_actions', null, '', 'DISTINCT context'));
        $contextmenu = array_combine($contextmenu, $contextmenu);
        $availableengines = [];
        $availableaction = [];
        foreach ($contextmenu as $eventtype => $eto) {
            if ($eventtype != 'any') {
                $availableengines[$eventtype] = static::get_engine_menu($eventtype);
            }
            $availableactions[$eventtype] = static::get_action_menu($eventtype);
        }
        unset($contextmenu['any']);
        $PAGE->requires->yui_module('moodle-block_eventsengine-engineactionselect', 'M.block_eventsengine.init_engineactionselect',
                [$availableengines, $availableactions], null, true);
        $selectstyle = 'width: -webkit-fill-available;';
        $contextselector = html_writer::tag('label', get_string('selectcontext', 'block_eventsengine'),
                ['for' => 'context']).html_writer::select($contextmenu, 'context', '',
                ['0' => get_string('choose', 'block_eventsengine')], ['id' => 'id_context_selector',
                    'style' => $selectstyle]);

        $engineselector = html_writer::tag('label', get_string('selectengine', 'block_eventsengine'),
                ['for' => 'engine']).html_writer::select([], 'engine', '',
                ['0' => get_string('choose', 'block_eventsengine')], ['id' => 'id_engine_selector',
                    'style' => $selectstyle]);

        $actionselector = html_writer::tag('label', get_string('selectaction', 'block_eventsengine'),
                ['for' => 'action']).html_writer::select([], 'action', '',
                ['0' => get_string('choose', 'block_eventsengine')], ['id' => 'id_action_selector',
                    'style' => $selectstyle]);
        $submitbutton = html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'create', 'value' => get_string('create')]);
        $returnurl = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'returnurl', 'value' => $PAGE->url]);
        $this->content->text .= html_writer::tag('form', $returnurl.$brnl.$contextselector.$brnl.$engineselector.$brnl.
                $actionselector.$brnl.$submitbutton, [
                    'id' => 'block_eventsengine_createform',
                    'method' => 'get',
                    'action' => new moodle_url('/blocks/eventsengine/editengine.php', ['returnurl' => $PAGE->url])]);

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
