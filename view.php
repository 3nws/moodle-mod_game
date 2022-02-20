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
    global $DB, $USER;
    $dest = $CFG->dirroot.'/mod/game/games/'.$game->name.'_extracted';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // $data = new stdClass();
        $data = game_get_results($game, $dest);
        // add to db here
        if ($data){
            $data->course = $course->id;
            $data->name = $game->name." result";
            $data->passornot = 1;
            $data->timemodified = time();
            $data->userid = $USER->id;
            $data->gameid = $game->id;
            $data->cmid = $cm->id;
            $DB->insert_record('game_results', $data);
        }else{
            die("No results exported");
        }
    }
    $fp = get_file_packer('application/zip');
    // Make a temporary folder that will be automatically deleted at the end of the request.
    // $dest = make_request_directory();
    // Extract the stored_file instance into this destination if it doesn't already exist
    $files = !file_exists($dest.'/') ? $fp->extract_to_pathname($file, $dest) : null;

    $resolution_options = game_get_resolutions();
    
    $width_height = explode("x", $resolution_options[$game->resolution]);

    // TODO read results from DB instead
    $results = game_get_results($game, $dest);
    $is_results_empty = !$results ? !empty($results) : true;

    $formaction = $PAGE->url;

    $templatecontext = [
        'name' => $game->name,
        'width' => $width_height[0],
        'height' => $width_height[1],
        'build_path' => "games/".$game->name."_extracted/Build",
        'grade' => $is_results_empty ? $results->grade : '',
        'score' => $is_results_empty ? $results->score : '',
        'results_not_empty' => $is_results_empty,
        'formaction' => $formaction,
    ];

    $PAGE->set_title($game->name);
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_game/index', $templatecontext);
    echo $OUTPUT->footer();
    
    // $fullurl = moodle_url::make_file_url('/mod/game/games/',$file->get_filename().'_extracted/index.html');
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
        // game_print_workaround($game, $cm, $course, $file);
        break;
}

