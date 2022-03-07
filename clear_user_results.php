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
 * Version details
 *
 * @package    mod_game
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/game/locallib.php');

global $DB;

$PAGE->set_url(new moodle_url('/game/clear_user_results.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(" ");

require_login();

if (!is_siteadmin()) {
    die('Admin only');
}

if (isset($_POST['resultid'])){
    clear_records_by_user($_POST['resultid']);
}

if (isset($_POST['userSearch'])){
    global $DB;

    $username = $_POST['userSearch'];

    $sql_query = "SELECT id
                  FROM {user}
                  WHERE username = :username;";
    
    $params = [
        'username' => $username,
    ];

    $results = $DB->get_records_sql($sql_query, $params);

    $user_id = array_values($results)[0]->id;

    $sql_query = "SELECT g.name, rs.score, rs.grade 
                  FROM {game_results} rs 
                  LEFT JOIN {game} g
                  ON g.id = rs.gameid;";

    $params = [
        'user_id' => $user_id,
    ];

    $results = $DB->get_records_sql($sql_query, $params);

    $results = array_values($results);

    $is_results_empty = !$results ? !empty($results) : true;

    $templatecontext = [
        'username' => $username,
        'results' => $is_results_empty ? $results : new stdClass(),
        'results_not_empty' => $is_results_empty,
        'formaction' => new moodle_url('/mod/game/clear_user_results.php'),
    ];

    $PAGE->set_title($username." Results");
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_game/user_results', $templatecontext);
    echo $OUTPUT->footer();

}
