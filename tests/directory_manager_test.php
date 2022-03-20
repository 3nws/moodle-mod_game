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
require_once($CFG->dirroot.'/mod/game/classes/dirs.php');
require_once($CFG->dirroot.'/course/modlib.php');

class directory_manager_test extends advanced_testcase {

    public function test_remove_dir_contents(){
        global $CFG;

        $this->resetAfterTest();
        $this->setUser(2);

        $manager = new directory_manager();

        $games_dir = $CFG->dirroot.'/mod/game/games_test/';
        if (!is_dir($games_dir)) {
            mkdir($games_dir);
        }
        if (!file_exists($games_dir.'test_dir/')) {
            mkdir($games_dir.'test_dir/');
        }

        $arrFiles = array();
        $handle = opendir($games_dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== FALSE) {
                    $arrFiles[] = $entry;
                }
            }
        closedir($handle);

        $this->assertNotEmpty($arrFiles);

        $manager->remove_dir_contents($games_dir);

        if (!is_dir($games_dir)) {
            mkdir($games_dir);
        }
        $arrFiles2 = array();
        $handle = opendir($games_dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== FALSE) {
                    $arrFiles2[] = $entry;
                }
            }
        closedir($handle);
        
        $this->assertCount(2, $arrFiles2);
        
    }

    public function test_remove_directories_older_than_x_mins(){
        global $CFG;

        $this->resetAfterTest();
        $this->setUser(2);

        $manager = new directory_manager();

        $games_dir = $CFG->dirroot.'/mod/game/games_test/';
        if (!is_dir($games_dir)) {
            mkdir($games_dir);
        }
        if (!file_exists($games_dir.'test_dir/')) {
            mkdir($games_dir.'test_dir/');
        }

        $arrFiles = array();
        $handle = opendir($games_dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== FALSE) {
                    $arrFiles[] = $entry;
                }
            }
        closedir($handle);

        $this->assertNotEmpty($arrFiles);
        sleep(1);
        $manager->remove_directories_older_than_x_mins($games_dir, 1/60);

        if (!is_dir($games_dir)) {
            mkdir($games_dir);
        }
        $arrFiles2 = array();
        $handle = opendir($games_dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== FALSE) {
                    $arrFiles2[] = $entry;
                }
            }
        closedir($handle);
        
        $this->assertCount(2, $arrFiles2);
        
    }
}