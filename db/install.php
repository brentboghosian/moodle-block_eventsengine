<?php
/**
 * Install
 *
 * @package   block_eventsengine
 * @category  install
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install for block_eventsengine
 * @return boolean
 */
function xmldb_block_eventsengine_install() {
    block_eventsengine_register('block_eventsengine');
    return true;
}
