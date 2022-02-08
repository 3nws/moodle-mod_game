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
 * Resource module version information
 *
 * @package    mod_game
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/game/lib.php');
require_once($CFG->dirroot.'/mod/game/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course Module ID
$r        = optional_param('r', 0, PARAM_INT);  // Resource instance ID
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($r) {
    if (!$game = $DB->get_record('game', array('id'=>$r))) {
        game_redirect_if_migrated($r, 0);
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('game', $game->id, $game->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('game', $id)) {
        game_redirect_if_migrated(0, $id);
        print_error('invalidcoursemodule');
    }
    $game = $DB->get_record('game', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
// require_capability('mod/game:view', $context);

// Completion and trigger events.
game_view($game, $course, $cm, $context);

$PAGE->set_url('/mod/game/view.php', array('id' => $cm->id));

if ($game->tobemigrated) {
    game_print_tobemigrated($game, $cm, $course);
    die;
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_game', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
if (count($files) < 1) {
    game_print_filenotfound($game, $cm, $course);
    die;
} else {
    $file = reset($files);
    unset($files);
}

$game->mainfile = $file->get_filename();
$displaytype = game_get_final_display_type($game);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
    $redirect = true;
}

// Don't redirect teachers, otherwise they can not access course or module settings.
if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

if ($redirect && !$forceview) {
    // TODO EXTRACT ZIP REDIRECT INDEX.HTML 
    // die($file->get_filepath().$file->get_filename());

    // Read contents
    // if ($zip) {
    //     $contents = $zip->get_content();
    // } else {
    //     // file doesn't exist - do something
    // }

    // die($a);
    $fp = get_file_packer('application/zip');
    // die($a->get_content());
    $filepath = $file->get_filepath().$file->get_filename();
    $filepath = '/'.$context->id.'/mod_game/content/'.$game->revision.$file->get_filepath().$file->get_filename();
    // $url = moodle_url::make_file_url('/pluginfile.php', $filepath, $displaytype == RESOURCELIB_DISPLAY_OPEN);
    // $files = $fp->extract_to_pathname($filepath, $CFG->dirroot.'/mod/game/games'.$filepath.'_extracted');
    // redirect($url);
    // die($filepath);
    // // die($files);

    // serves the file nothing more
    //$fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    
    
   $fileinfo = array(
    'component' => 'mod_game',     // usually = table name
    'filearea' => 'content',     // usually = table name
    'itemid' => 0,               // usually = ID of row in table
    'contextid' => 469, // ID of context
    'filepath' => '/',           // any path beginning and ending in /
    'filename' => 'game.zip'); // any filename

    // Get file
    $myfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
   
    $files = $fp->extract_to_pathname($filepath, $CFG->dirroot.'/mod/game/games'.$filepath.'_extracted');
   
    die($files);
    // die($files);
    // if ($files = $fs->get_area_files($context->id, 'mod_game', 'content', '0', 'sortorder', false)) {
    //         // Look through each file being managed
    //         foreach ($files as $file) {
    //         // Build the File URL. Long process! But extremely accurate.
    //             $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    //             die($fileurl);
    //         }
    //     } else {
    //         echo '<p>Please upload an image first</p>';
    //     }
    // // die($CFG->dirroot.'/mod/game/games'.$filepath.'_extracted');
    // $zip = new ZipArchive;
    // if ($zip->open($file->get_filepath().$file->get_filename(), ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
    //     die($zip->filename);
    //     $zip->extractTo('./games/'.explode(".", $file->get_filename())[0]);
    //     $zip->close();
    //     echo 'ok';
    // } else {
    //     echo 'failed';
    // }




    // $path = '/'.$context->id.'/mod_game/content/'.$game->revision.$file->get_filepath().$file->get_filename();
    // $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
    // redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        game_display_embed($game, $cm, $course, $file);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        game_display_frame($game, $cm, $course, $file);
        break;
    default:
        game_print_workaround($game, $cm, $course, $file);
        break;
}

