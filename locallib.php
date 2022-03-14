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
 * Private game module utility functions
 *
 * @package    mod_game
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once($CFG->dirroot.'/mod/game/classes/result.php');
require_once("$CFG->dirroot/mod/game/lib.php");



// Removes a directory with the files it contains older than x minutes
function remove_directories_older_than_x_mins($path, $x){
    $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);

    foreach($files as $rs) {
        if (time() - filemtime($rs) > (60*$x)){
            if ($rs->isDir()){
                try{
                    rmdir($rs->getRealPath());
                }catch (Exception $e){
                    ;
                }
            } else {
                unlink($rs->getRealPath());
            }
        }
    }
    remove_empty_sub_folders($path);
}

// Removes emptry sub folders inside the $path directory
function remove_empty_sub_folders($path){
    $empty = true;
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
        $empty &= is_dir($file) && remove_empty_sub_folders($file);
    }
    return $empty && (is_readable($path) && count(scandir($path)) == 2) && rmdir($path);
}


// Removes a directory with the files it contains
function remove_directory($path){
    $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $rs) {
        if ($rs->isDir()){
            rmdir($rs->getRealPath());
        } else {
            unlink($rs->getRealPath());
        }
    }
    rmdir($path);
}

// Return an array of compression method options for mod_game form
function game_get_comp_methods(){
    $comp_options = array();
    $comp_options['0'] = "gzip";
    $comp_options['1'] = "brotli";
    $comp_options['2'] = "None";
    
    return $comp_options;
}

// Return an array of resolution options for mod_game form
function game_get_resolutions(){
    $resolution_options = array();
    $resolution_options['0'] = "1600x900";
    $resolution_options['1'] = "1440x810";
    $resolution_options['2'] = "1280x720";
    $resolution_options['3'] = "854x480";
    $resolution_options['4'] = "640x360";
    
    return $resolution_options;
}

// Return an array of game options for mod_game form
function game_get_games(){
    $game_options = array();
    $game_options['0'] = "Game 1";
    $game_options['1'] = "Game 2";
    $game_options['2'] = "Game 3";
    $game_options['3'] = "Game 4";
    $game_options['4'] = "Game 5";
    
    return $game_options;
}

/**
 * Redirected to migrated game if needed,
 * return if incorrect parameters specified
 * @param int $oldid
 * @param int $cmid
 * @return void
 */
function game_redirect_if_migrated($oldid, $cmid) {
    global $DB, $CFG;

    if ($oldid) {
        $old = $DB->get_record('game_old', array('oldid'=>$oldid));
    } else {
        $old = $DB->get_record('game_old', array('cmid'=>$cmid));
    }

    if (!$old) {
        return;
    }

    redirect("$CFG->wwwroot/mod/$old->newmodule/view.php?id=".$old->cmid);
}

/**
 * Internal function - create click to open text with link.
 */
function game_get_clicktoopen($file, $revision, $extra='') {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_game/content/'.$revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);

    $string = get_string('clicktoopen2', 'game', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}

/**
 * Internal function - create click to open text with link.
 */
function game_get_clicktodownload($file, $revision) {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_game/content/'.$revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, true);

    $string = get_string('clicktodownload', 'game', "<a href=\"$fullurl\">$filename</a>");

    return $string;
}

/**
 * Print game info and workaround link when JS not available.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function game_print_workaround($game, $cm, $course, $file) {
    global $CFG, $OUTPUT, $USER;
    game_print_header($game, $cm, $course);
    game_print_heading($game, $cm, $course, true);

    // Display any activity information (eg completion requirements / dates).
    $cminfo = cm_info::create($cm);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
    echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);

    game_print_intro($game, $cm, $course, true);

    $game->mainfile = $file->get_filename();
    echo '<div class="gameworkaround">';
    switch (game_get_final_display_type($game)) {
        case RESOURCELIB_DISPLAY_POPUP:
            $path = '/'.$file->get_contextid().'/mod_game/content/'.$game->revision.$file->get_filepath().$file->get_filename();
            $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
            $options = empty($game->displayoptions) ? [] : (array) unserialize_array($game->displayoptions);
            $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
            $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
            $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
            $extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";
            echo game_get_clicktoopen($file, $game->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_NEW:
            $extra = 'onclick="this.target=\'_blank\'"';
            echo game_get_clicktoopen($file, $game->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_DOWNLOAD:
            echo game_get_clicktodownload($file, $game->revision);
            break;

        case RESOURCELIB_DISPLAY_OPEN:
        default:
            echo game_get_clicktoopen($file, $game->revision);
            break;
    }
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Print game header.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @return void
 */
function game_print_header($game, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$game->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($game);
    echo $OUTPUT->header();
}

/**
 * Print game heading.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used
 * @return void
 */
function game_print_heading($game, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($game->name), 2);
}


/**
 * Gets details of the file to cache in course cache to be displayed using {@link game_get_optional_details()}
 *
 * @param object $game Game table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function game_get_file_details($game, $cm) {
    $options = empty($game->displayoptions) ? [] : (array) unserialize_array($game->displayoptions);
    $filedetails = array();
    if (!empty($options['showresults']) || !empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_game', 'content', 0, 'sortorder DESC, id ASC', false);
        // For a typical file game, the sortorder is 1 for the main file
        // and 0 for all other files. This sort approach is used just in case
        // there are situations where the file has a different sort order.
        $mainfile = $files ? reset($files) : null;
        if (!empty($options['showresults'])) {
            $filedetails['size'] = 0;
            foreach ($files as $file) {
                // This will also synchronize the file size for external files if needed.
                $filedetails['size'] += $file->get_filesize();
                if ($file->get_repository_id()) {
                    // If file is a reference the 'size' attribute can not be cached.
                    $filedetails['isref'] = true;
                }
            }
        }
        if (!empty($options['showsize'])) {
            $filedetails['size'] = 0;
            foreach ($files as $file) {
                // This will also synchronize the file size for external files if needed.
                $filedetails['size'] += $file->get_filesize();
                if ($file->get_repository_id()) {
                    // If file is a reference the 'size' attribute can not be cached.
                    $filedetails['isref'] = true;
                }
            }
        }
        if (!empty($options['showtype'])) {
            if ($mainfile) {
                $filedetails['type'] = get_mimetype_description($mainfile);
                $filedetails['mimetype'] = $mainfile->get_mimetype();
                // Only show type if it is not unknown.
                if ($filedetails['type'] === get_mimetype_description('document/unknown')) {
                    $filedetails['type'] = '';
                }
            } else {
                $filedetails['type'] = '';
            }
        }
        if (!empty($options['showdate'])) {
            if ($mainfile) {
                // Modified date may be up to several minutes later than uploaded date just because
                // teacher did not submit the form promptly. Give teacher up to 5 minutes to do it.
                if ($mainfile->get_timemodified() > $mainfile->get_timecreated() + 5 * MINSECS) {
                    $filedetails['modifieddate'] = $mainfile->get_timemodified();
                } else {
                    $filedetails['uploadeddate'] = $mainfile->get_timecreated();
                }
                if ($mainfile->get_repository_id()) {
                    // If main file is a reference the 'date' attribute can not be cached.
                    $filedetails['isref'] = true;
                }
            } else {
                $filedetails['uploadeddate'] = '';
            }
        }
    }
    return $filedetails;
}

/**
 * Gets optional details for a game, depending on game settings.
 *
 * Result may include the file size and type if those settings are chosen,
 * or blank if none.
 *
 * @param object $game Game table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function game_get_optional_details($game, $cm) {
    global $DB;

    $details = '';

    $options = empty($game->displayoptions) ? [] : (array) unserialize_array($game->displayoptions);
    if (!empty($options['showresults']) || !empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        if (!array_key_exists('filedetails', $options)) {
            $filedetails = game_get_file_details($game, $cm);
        } else {
            $filedetails = $options['filedetails'];
        }
        $results = '';
        $size = '';
        $type = '';
        $date = '';
        $langstring = '';
        $infodisplayed = 0;
        $results_manager = new results_manager();
        if (!empty($options['showresults'])) {
            $results = $results_manager->display_results($game->game_obj);
            $langstring .= 'results';
            $infodisplayed += 1;
        }
        if (!empty($options['showsize'])) {
            if (!empty($filedetails['size'])) {
                $size = display_size($filedetails['size']);
                $langstring .= 'size';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showtype'])) {
            if (!empty($filedetails['type'])) {
                $type = $filedetails['type'];
                $langstring .= 'type';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showdate']) && (!empty($filedetails['modifieddate']) || !empty($filedetails['uploadeddate']))) {
            if (!empty($filedetails['modifieddate'])) {
                $date = get_string('modifieddate', 'mod_game', userdate($filedetails['modifieddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            } else if (!empty($filedetails['uploadeddate'])) {
                $date = get_string('uploadeddate', 'mod_game', userdate($filedetails['uploadeddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            }
            $langstring .= 'date';
            $infodisplayed += 1;
        }

        if ($infodisplayed > 1) {
            $details = get_string("gamedetails_{$langstring}", 'game',
                    (object)array('results' => $results, 'size' => $size, 'type' => $type, 'date' => $date));
        } else {
            // Only one of size, type and date is set, so just append.
            $details = $results. $size . $type . $date;
        }
    }

    return $details;
}

/**
 * Print game introduction.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function game_print_intro($game, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($game->displayoptions) ? [] : (array) unserialize_array($game->displayoptions);

    $extraintro = game_get_optional_details($game, $cm);
    if ($extraintro) {
        // Put a paragaph tag around the details
        $extraintro = html_writer::tag('p', $extraintro, array('class' => 'gamedetails'));
    }

    if ($ignoresettings || !empty($options['printintro']) || $extraintro) {
        $gotintro = trim(strip_tags($game->intro));
        if ($gotintro || $extraintro) {
            echo $OUTPUT->box_start('mod_introbox', 'gameintro');
            if ($gotintro) {
                echo format_module_intro('game', $game, $cm->id);
            }
            echo $extraintro;
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Print warning that instance not migrated yet.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @return void, does not return
 */
function game_print_tobemigrated($game, $cm, $course) {
    global $DB, $OUTPUT;

    $game_old = $DB->get_record('game_old', array('oldid'=>$game->id));
    game_print_header($game, $cm, $course);
    game_print_heading($game, $cm, $course);
    game_print_intro($game, $cm, $course);
    echo $OUTPUT->notification(get_string('notmigrated', 'game', $game_old->type));
    echo $OUTPUT->footer();
    die;
}

/**
 * Print warning that file can not be found.
 * @param object $game
 * @param object $cm
 * @param object $course
 * @return void, does not return
 */
function game_print_filenotfound($game, $cm, $course) {
    global $DB, $OUTPUT;

    $game_old = $DB->get_record('game_old', array('oldid'=>$game->id));
    game_print_header($game, $cm, $course);
    game_print_heading($game, $cm, $course);
    game_print_intro($game, $cm, $course);
    if ($game_old) {
        echo $OUTPUT->notification(get_string('notmigrated', 'game', $game_old->type));
    } else {
        echo $OUTPUT->notification(get_string('filenotfound', 'game'));
    }
    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $game
 * @return int display type constant
 */
function game_get_final_display_type($game) {
    global $CFG, $PAGE;

    if ($game->display != RESOURCELIB_DISPLAY_AUTO) {
        return $game->display;
    }

    if (empty($game->mainfile)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    } else {
        $mimetype = mimeinfo('type', $game->mainfile);
    }

    if (file_mimetype_in_typegroup($mimetype, 'archive')) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (file_mimetype_in_typegroup($mimetype, array('web_image', '.htm', 'web_video', 'web_audio'))) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * File browsing support class
 */
class game_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

// add to file table
function game_set_mainfile($data) {
    global $DB;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files;

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options = array('subdirs' => true, 'embed' => false);
        if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
            $options['embed'] = true;
        }
        file_save_draft_area_files($draftitemid, $context->id, 'mod_game', 'content', 0, $options);
    }
    $files = $fs->get_area_files($context->id, 'mod_game', 'content', 0, 'sortorder', false);
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_game', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
    }
}
