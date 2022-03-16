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
 * Game compression API
 *
 * @package    mod_game
 * @category   compression
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

class compression_method {

    public $access_file;

    public function __construct($game_comp_method){
        $this->access_file = ($game_comp_method==0) ? './htaccess_config/gzip/.htaccess' : './htaccess_config/brotli/.htaccess';
    }

}