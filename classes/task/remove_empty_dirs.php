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
 * Mod game remove empty dirs task
 *
 * @package    mod_game
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace mod_game\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/game/classes/dirs.php');

class remove_empty_dirs extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('removeemptydirs', 'mod_game');
    }

    /**
     * Execute all game tasks.
     */
    public function execute() {
        global $CFG;
        $games_dir = $CFG->dirroot.'/mod/game/games/';
        $manager = new \directory_manager(); // apparently that backwards slash is pretty important!!
        $manager->remove_empty_sub_folders($games_dir);
    }
}
