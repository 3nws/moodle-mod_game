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
require_once($CFG->dirroot.'/mod/game/classes/result.php');
require_once($CFG->dirroot.'/mod/game/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');

class mod_game_results_manager_test extends advanced_testcase {


    public function test_create_result(){
        $this->resetAfterTest();
        $this->setUser(2);

        $threshold = 51;
        
        $dg = $this->getDataGenerator();
        
        $c1 = $dg->create_course();
        $game1 = $dg->create_module('game', ['course' => $c1->id]);
        $context = context_module::instance($game1->cmid);
        $cm = get_coursemodule_from_instance('game', $game1->id);
    
        $manager = new results_manager();

        $data = new stdClass();
        $data->score = 74;
        $data->grade = "D";

        $game_name_id_threshold = (object) ['name' => "Test game", 'id' => 1, 'threshold' => $threshold];

        $results = $manager->get_results($game_name_id_threshold);

        $this->assertEmpty($results);

        $new_result = $manager->create_result($data, $cm, $game_name_id_threshold, $c1);

        $this->assertTrue($new_result);

        $results = $manager->get_results($game_name_id_threshold);

        $this->assertNotEmpty($results);

        $this->assertCount(1, $results);

        $first_result = array_pop($results);

        $this->assertEquals(74 , $first_result->score);
        $this->assertEquals("D" , $first_result->grade);
    }

}