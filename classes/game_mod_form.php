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
 * Game moodle form API
 *
 * @package    mod_game
 * @category   game_mod_form
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

class game_mod_form {

    // Return an array of compression method options for mod_game form
    public function get_comp_methods(){
        $comp_options = array();
        $comp_options['0'] = "gzip";
        $comp_options['1'] = "brotli";
        $comp_options['2'] = "None";
        
        return $comp_options;
    }

    // Return an array of resolution options for mod_game form
    public function get_resolutions(){
        $resolution_options = array();
        $resolution_options['0'] = "1600x900";
        $resolution_options['1'] = "1440x810";
        $resolution_options['2'] = "1280x720";
        $resolution_options['3'] = "854x480";
        $resolution_options['4'] = "640x360";
        
        return $resolution_options;
    }

    // Return an array of game options for mod_game form
    public function get_games(){
        $game_options = array();
        $game_options['0'] = "Game 1";
        $game_options['1'] = "Game 2";
        $game_options['2'] = "Game 3";
        $game_options['3'] = "Game 4";
        $game_options['4'] = "Game 5";
        
        return $game_options;
    }

}