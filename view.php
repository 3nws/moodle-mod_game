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
 * Game module version information
 *
 * @package    mod_game
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/game/lib.php');
require_once($CFG->dirroot.'/mod/game/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course Module ID
$r        = optional_param('r', 0, PARAM_INT);  // Game instance ID
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
require_capability('mod/game:view', $context);

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

if ($redirect) {
    global $DB, $USER;
    // check if a games diretory exists if not create it
    $games_dir = $CFG->dirroot.'/mod/game/games/';
    if (!is_dir($games_dir)) {
        mkdir($games_dir);       
    }
    // to prevent overloading duh
    remove_directories_older_than_x_mins($games_dir, 10);
    $uniq = uniqid();
    $dest = $CFG->dirroot.'/mod/game/games/'.$game->name.$uniq;
    // Gets the newly exported scores on local and inserts them to the database
    if (isset($_POST['dest'])) {
        // $data = new stdClass();
        $old_dest = $_POST['dest'];
        $data = game_get_local_results($game, $old_dest);
        // add to db here
        if ($data){
            $data->course = $course->id;
            $data->name = $game->name." result";
            $data->passornot = ($data->score >= (int)$game->threshold) ? 1 : 0;
            $data->timemodified = time();
            $data->userid = $USER->id;
            $data->gameid = $game->id;
            $data->cmid = $cm->id;
            $sql_query =   "SELECT rs.id, rs.grade, rs.score 
                            FROM {game_results} rs 
                            WHERE rs.userid = :user_id AND rs.score = :score
                            ORDER BY rs.score DESC;";

            $params = [
                'user_id' => $USER->id,
                'score' => $data->score,
            ];
        
            $exact_matches = $DB->get_records_sql($sql_query, $params);
            
            if (!empty(array_values(($exact_matches)))){
                \core\notification::add(get_string('duplicatewarning', 'game'), \core\output\notification::NOTIFY_WARNING);
            }else{
                // delete the results file to reset after inserting the scores to the db
                $DB->insert_record('game_results', $data);
            }
        }else{
            \core\notification::add(get_string('exportjson', 'game'), \core\output\notification::NOTIFY_WARNING);
        }
        remove_directory($old_dest);
    }
    $fp = get_file_packer('application/zip');
    // Extract the stored_file instance into this destination if it doesn't already exist
    $files = !file_exists($dest.'/') ? $fp->extract_to_pathname($file, $dest) : null;

    // Store the path of source file
    $source = './htaccess_config/.htaccess';
    // Create a file to overwrite
    fopen($dest.'/Build/.htaccess', 'w'); 
    // Store the path of destination file
    $destination = $dest.'/Build/.htaccess';
    // Copy the file
    copy($source, $destination);

    $resolution_options = game_get_resolutions();
    
    $width_height = explode("x", $resolution_options[$game->resolution]);

    $results = game_get_results($game);
    $results = array_values($results);
    
    $is_results_empty = !$results ? !empty($results) : true;

    $formaction = $PAGE->url;
    
    $highest_scored_record = new stdClass();

    // highest_scored_record = $results[0];

    $templatecontext = [
        'name' => $game->name,
        'width' => $width_height[0],
        'height' => $width_height[1],
        'build_path' => "games/".$game->name.$uniq."/Build",
        'results' => $is_results_empty ? $results : new stdClass(),
        // 'highest_grade' => $is_results_empty ? $highest_scored_record->grade : '',
        // 'highest_score' => $is_results_empty ? $highest_scored_record->score : '',
        'results_not_empty' => $is_results_empty,
        'formaction' => $formaction,
        'dest' => $dest,
    ];

    $PAGE->set_title($game->name);
    echo $OUTPUT->header();
    // download link for the game
    echo game_get_clicktodownload($file, $game->revision);
    echo $OUTPUT->render_from_template('mod_game/index', $templatecontext);
    echo $OUTPUT->footer();
    
    // for downloading the file
    // $fullurl = moodle_url::make_file_url('/mod/game/games/',$file->get_filename().'_extracted/index.html');
    // redirect($fullurl);
}

// switch ($displaytype) {
//     case RESOURCELIB_DISPLAY_EMBED:
//         game_display_embed($game, $cm, $course, $file);
//         break;
//     case RESOURCELIB_DISPLAY_FRAME:
//         game_display_frame($game, $cm, $course, $file);
//         break;
//     default:
//         game_print_workaround($game, $cm, $course, $file);
//         break;
// }

