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

/**
 * Upgrade
 *
 * @package   block_eventsengine
 * @category  event
 * @copyright 2017 onwards Brent Boghosian <brentboghosian@alumni.uwaterloo.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install for block_eventsengine
 * @param int|string $oldversion The already install version, or zero for none.
 * @return boolean True on success, false on error.
 */
function xmldb_block_eventsengine_upgrade($oldversion = 0) {
    global $CFG;
    $result = true;

    // Always make sure up-to-date definitions - db/upgrade.php as well!
    require_once($CFG->dirroot.'/blocks/eventsengine/lib.php');
    block_eventsengine_register('block_eventsengine');

    return $result;
}
