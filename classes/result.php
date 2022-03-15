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
 * Game result API
 *
 * @package    mod_game
 * @category   result
 * @author     Enes Kurbetoğlu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */


class results_manager {

    // Create new result in DB
    public function create_result(stdClass $data, stdClass $cm, stdClass $game, stdClass $course) : bool
    {
        global $DB, $USER;
        
        require_capability('mod/game:addresultinstance', context_module::instance($cm->id));

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
            return false;
        }else{
            // delete the results file to reset after inserting the scores to the db
            $DB->insert_record('game_results', $data);
            return true;
        }
    }

    // Return result entries from db
    public function get_results($game){
        global $DB, $USER;

        // Selects results that match the current user and the game
        $sql_query =   "SELECT rs.id, rs.grade, rs.score 
                        FROM {game_results} rs 
                        WHERE rs.userid = :user_id AND rs.gameid = :game_id
                        ORDER BY rs.score DESC;";

        $params = [
            'user_id' => $USER->id,
            'game_id' => $game->id,
        ];

        $results = $DB->get_records_sql($sql_query, $params);
        return $results;
    }

    // Return a string with the results processed
    public function display_results($game){
        global $DB, $USER;
        
        // Selects results that match the current user and the game gets the highest scored entry
        $sql_query =   "SELECT rs.id, rs.grade, rs.score, rs.passornot
                        FROM {game_results} rs 
                        WHERE rs.userid = :user_id AND rs.gameid = :game_id
                        ORDER BY rs.score DESC
                        LIMIT 1;";

        $params = [
            'user_id' => $USER->id,
            'game_id' => $game->id,
        ];
        
        $results = $DB->get_records_sql($sql_query, $params);
        $topic = $game->topic;
        $is_results_empty = !$results ? !empty($results) : true;
        $score = $is_results_empty ? array_values($results)[0]->score : 0;
        $hasPassed = $is_results_empty ? (int)array_values($results)[0]->passornot : 0;
        $message = $hasPassed ? ", <div style='color:green;'> good job!</div>" : ", <div style='color:red;'> please revise the topic ".$topic."!</div>";
        $display_message = $is_results_empty ? "<strong>Your score is ". $score . $message."</strong>" : "<strong>You have no score!</strong>";

        return $display_message;
    }

    // Return a json object consisting of game results
    public function game_get_local_results($game, $dest){
        $dest = $dest.'/results.json';
        if ($flag = file_exists($dest)){
            $data = file_get_contents($dest); 
            $obj = json_decode($data); 
            return $flag ? $obj[0] : false;
        }
    }

    // Clears all the matching records in the results table
    public function clear_records_no_redirect(){
        global $DB;
        
        require_capability('mod/game:clearuserresults', context_system::instance());

        $DB->delete_records_select("game_results", 1);
    }

    // Clears the matching record in the results table
    public function clear_records_by_user($resultid){
        global $DB;

        require_capability('mod/game:clearuserresults', context_system::instance());

        $DB->delete_records_select("game_results", "id = ".$resultid);
    }

    // Clears all records in the results table
    public function clear_records(){
        global $DB;

        require_capability('mod/game:clearuserresults', context_system::instance());

        $DB->delete_records_select("game_results", 1);

        redirect(new moodle_url('../../admin/settings.php', array(
            'section' => 'modsettinggame',
        )));
    }

}