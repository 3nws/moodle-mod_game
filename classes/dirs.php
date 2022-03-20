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
 * Game directories API
 *
 * @package    mod_game
 * @category   directory
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

class directory_manager {
    

    // Removes a directory with the files it contains older than x minutes
    public function remove_directories_older_than_x_mins($path, $x){
        $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
                    RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $rs) {
            if (time() - filemtime($rs) > (60*$x)){
                if ($rs->isDir()){
                    $this->remove_dir_contents($rs->getRealPath());
                } else {
                    unlink($rs->getRealPath());
                }
            }
        }
        $this->remove_empty_sub_folders($path);
    }

    

    // Removes a directory's contents
    public function remove_dir_contents($path){
        if (!is_dir($path)) {
            throw new InvalidArgumentException("$path must be a directory");
        }
        if (substr($path, strlen($path) - 1, 1) != '/') {
            $path .= '/';
        }
        $files = scandir($path); 
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (is_dir($path.$file)) {
                $this->remove_dir_contents($path.$file);
            } else {
                if ($path.$file !== __FILE__) {
                    unlink($path.$file);
                }
            }
        }
        rmdir($path);
    }

    
    // Removes empty sub folders inside the $path directory
    public function remove_empty_sub_folders($path){
        $empty = true;
        foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
            $empty &= is_dir($file) && $this->remove_empty_sub_folders($file);
        }
        return $empty && (is_readable($path) && count(scandir($path)) == 2) && rmdir($path);
    }

}