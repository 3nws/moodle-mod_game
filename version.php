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
 * Game module version information
 *
 * @package    mod_game
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2022032301;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2021051100;    // Requires this Moodle version.
$plugin->component = 'mod_game'; // Full name of the plugin (used for diagnostics)
$plugin->cron      = 60;
