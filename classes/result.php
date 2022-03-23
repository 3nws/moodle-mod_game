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

require_once($CFG->dirroot.'/mod/game/classes/dirs.php');

class results_manager {

    // Create new result in DB
    public function create_result(stdClass $data, stdClass $cm, stdClass $game, stdClass $course) : bool
    {
        global $DB, $USER;
        
        require_capability('mod/game:addresultinstance', context_module::instance($cm->id));

        $data->course = $course->id;
        $data->name = $game->name." result";
        $data->passornot = ($data->score >= $game->threshold) ? 1 : 0;
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
            $record = $DB->insert_record('game_results', $data, false);
            $this->update_results();
            return $record;
        }
    }

    public function submit_results($path) : bool
    {
        try{
            global $DB;
            $record = false;
            $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
                        RecursiveIteratorIterator::SELF_FIRST);
            
            foreach ($files as $file) {
                if ($file->isDir() && $file->getFileName()!='Build' && $file->getFileName()!='TemplateData'){
                    $data = $this->game_get_local_results($file->getRealPath());
                    if(!$data){
                        continue;
                    }
                    $arr = explode("_", $file->getRealPath());
                    $cminstance = $arr[1];
                    $userid = $arr[2];
                    $game = $DB->get_record('game', array('id'=>$cminstance));
                    $course = $DB->get_record('course', array('id'=>$game->course));

                    $data->course = $course->id;
                    $data->name = $game->name." result";
                    $data->passornot = ($data->score >= $game->threshold) ? 1 : 0;
                    $data->timemodified = time();
                    $data->userid = $userid;
                    $data->gameid = $game->id;
                    $data->cmid = $cminstance;

                    $sql_query =   "SELECT rs.id, rs.grade, rs.score 
                                    FROM {game_results} rs 
                                    WHERE rs.userid = :user_id AND rs.score = :score
                                    ORDER BY rs.score DESC;";

                    $params = [
                        'user_id' => $userid,
                        'score' => $data->score,
                    ];
                    
                    $exact_matches = $DB->get_records_sql($sql_query, $params);

                    if (!empty(array_values(($exact_matches)))){
                        $record = false;
                    }else{
                        // delete the results file to reset after inserting the scores to the db
                        $record = $DB->insert_record('game_results', $data, false);
                        $this->update_results();
                    }
                    (new directory_manager())->remove_dir_contents($file);
                }
            }
            return $record;
        }catch(Exception $e){
            return $this->submit_results($path);
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

    // Updates all the results' passornot
    public function update_results(){
        global $DB, $USER;
        
        $sql_query =   "UPDATE {game_results} rs 
                        LEFT OUTER JOIN {game} g
                        ON rs.gameid=g.id  
                        SET rs.passornot=0  
                        WHERE g.threshold>rs.score";
        
        $DB->execute($sql_query);

        $sql_query =   "UPDATE {game_results} rs 
                        LEFT OUTER JOIN {game} g
                        ON rs.gameid=g.id  
                        SET rs.passornot=1  
                        WHERE g.threshold<=rs.score";
        
        $DB->execute($sql_query);
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
    public function game_get_local_results($dest){
        $dest = $dest.'/results.json';
        if ($flag = file_exists($dest)){
            $data = file_get_contents($dest); 
            $obj = json_decode($data); 
            return $flag ? $obj[0] : false;
        }
    }

    // Clears the matching record in the results table
    public function clear_records_by_user($resultid) : bool
    {
        global $DB;

        require_capability('mod/game:clearuserresults', context_system::instance());

        return $DB->delete_records_select("game_results", "id = ".$resultid);
    }

    // Clears all records in the results table and redirect to the admin page if set
    public function clear_records($redirect) {
        global $DB;

        require_capability('mod/game:clearuserresults', context_system::instance());

        $is_cleared = $DB->delete_records_select("game_results", 1);

        if ($redirect){
            redirect(new moodle_url('../../admin/settings.php', array(
                'section' => 'modsettinggame',
            )));
        }
        return $is_cleared;
    }

}