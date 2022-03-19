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
 * Version details
 *
 * @package    mod_game
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/game/locallib.php');

global $DB;

$PAGE->set_url(new moodle_url('/game/clear.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(" ");

require_login();
// require_admin();

$context = context_system::instance();
require_capability('mod/game:clearuserresults', $context);

$results_manager = new results_manager();

$results_manager->clear_records(true);