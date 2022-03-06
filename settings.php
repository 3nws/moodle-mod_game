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
 * Game module admin settings and defaults
 *
 * @package    mod_game
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                        //    RESOURCELIB_DISPLAY_EMBED,
                                                        //    RESOURCELIB_DISPLAY_FRAME,
                                                        //    RESOURCELIB_DISPLAY_DOWNLOAD,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                        //    RESOURCELIB_DISPLAY_NEW,
                                                        //    RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                //    RESOURCELIB_DISPLAY_EMBED,
                                //    RESOURCELIB_DISPLAY_DOWNLOAD,
                                   RESOURCELIB_DISPLAY_OPEN,
                                //    RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('resourcemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('game/printintro',
        get_string('printintro', 'game'), get_string('printintroexplain', 'game'), 1));
    $settings->add(new admin_setting_configselect('game/display',
        get_string('displayselect', 'game'), get_string('displayselectexplain', 'game'), RESOURCELIB_DISPLAY_AUTO,
        $displayoptions));
    $settings->add(new admin_setting_configcheckbox('game/showresults',
        get_string('showresults', 'game'), get_string('showresults_desc', 'game'), 1));
    $settings->add(new admin_setting_configcheckbox('game/showsize',
        get_string('showsize', 'game'), get_string('showsize_desc', 'game'), 0));
    $settings->add(new admin_setting_configcheckbox('game/showtype',
        get_string('showtype', 'game'), get_string('showtype_desc', 'game'), 0));
    $settings->add(new admin_setting_configcheckbox('game/showdate',
        get_string('showdate', 'game'), get_string('showdate_desc', 'game'), 0));
    //--- Empty results db -----------------------------------------------------------------------------------
    $warning = 'Please make sure you have no unsaved changes.';
    $link = "<a href=".new moodle_url('/mod/game/clear.php')." class='btn btn-danger';>Empty all results</a> <strong style='color: red;'>".$warning."</strong>";
    $link .= "</br>
    <div class='input-group rounded'>
        <input type='search' class='form-control rounded' id='userSearch' placeholder='Search' aria-label='Search' aria-describedby='search-addon' />
        <a class='input-group-text border-0' onClick='submit();' href='javascript:;' id='search-addon'>
            <i class='fa fa-search'></i>
        </a>
    </div>
      <table class='table table-sm table-hover'>
        <caption>{{ username }} scores</caption>
        <thead>
          <tr>
            <th scope='col'>Score</th>
            <th scope='col'>Delete</th>
          </tr>
        </thead>
        <tbody>
          {{#results}}
          <tr>
            <td>{{ score }}</td>
            <td>{{ grade }}</td>
          </tr>
          {{/results}}
        </tbody>
      </table>
      ";
    $link .= "</br>
    <script>
        function submit(){
            var s = document.getElementById('userSearch');
            var username = s.value;
            var domainname = 'http://localhost/moodle';
            var token = '103b1227537662221dd76f27df68ef81';
            var functionname = 'core_user_get_users_by_field'; 
            var serverurl = domainname + '/webservice/rest/server.php' ;
            var data = {
                        wstoken: token,
                        wsfunction: functionname,
                        moodlewsrestformat: 'json',
                        field: 'username',
                        values: [username,],
                    } 
            var response = $.ajax(
                        {   type: 'GET',
                            data: data,
                            url: serverurl
                        }
                );
            console.log(response);
        }
    </script>
    ";


    
    $settings->add(new admin_setting_heading('modemptydb', get_string('modemptydb', 'game'), $link));
}
