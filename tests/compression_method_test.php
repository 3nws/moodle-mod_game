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
 * Resource search unit tests.
 *
 * @package     mod_game
 * @category    test
 * @author      Enes Kurbetoğlu
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/game/classes/compression.php');
require_once($CFG->dirroot.'/course/modlib.php');

class compression_method_test extends advanced_testcase {

    public function test_create_result(){
        $this->resetAfterTest();
        $this->setUser(2);

        $files = ['./htaccess_config/gzip/.htaccess', './htaccess_config/brotli/.htaccess'];

        $game = (object) ['compmethod' => 0];

        $file = (new compression_method($game->compmethod))->access_file;

        $this->assertEquals($file , $files[0]);

        $game = (object) ['compmethod' => 1];

        $file = (new compression_method($game->compmethod))->access_file;

        $this->assertEquals($file , $files[1]);

        $game = (object) ['compmethod' => 2];

        $file = (new compression_method($game->compmethod))->access_file;

        $this->assertEquals($file , $files[1]);
    }

}