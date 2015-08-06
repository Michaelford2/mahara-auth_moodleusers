<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage auth-moodleusers
 * @author     Michael Ford <m.ford@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  (C) 2015 Michael Ford <m.ford@qmul.ac.uk>
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 */


defined('INTERNAL') || die();

require_once(get_config('docroot') . 'init.php');
require_once(get_config('docroot') . 'auth/lib.php');
require_once(get_config('libroot') . 'adodb/adodb.inc.php');


class AuthMoodleusers extends Auth
{
    public function __construct($id = null) {

        $this->type       = 'moodleuserss';
        $this->has_instance_config = true;

        $this->config['debugdb']    = 'on';

        $this->config['firstname']      = get_config_plugin('auth', 'moodleusers', 'firstname');
        $this->config['lastname']       = get_config_plugin('auth', 'moodleusers', 'lastname');
        $this->config['email']          = get_config_plugin('auth', 'moodleusers', 'email');
        $this->config['quota']          = get_config_plugin('auth', 'moodleusers', 'quota');
        $this->config['authinstance']   = get_config_plugin('auth', 'moodleusers', 'authinstance');
        $this->config['numbertoupdate'] = get_config_plugin('auth', 'moodleusers', 'numbertoupdate');

        $this->instanceid = $id;

        if (!empty($id)) {
            return $this->init($id);
        }
        return true;
    }


	/**
	 * Connect to Moodle database
	 * db credentials in config.php
	 *
	 * @return the connection
	 */
	public function db_init() {

	    global $CFG;

        $extdb = ADONewConnection($CFG->mdldbtype);

        if (!empty($extdb->debug)) {
	        $extdb->debug = true;
        }

        if (!$extdb->IsConnected()) {
            $extdb->PConnect($CFG->mdldbhost, $CFG->mdldbuser, $CFG->mdldbpass, $CFG->mdldbname, true);
            $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
	        $extdb->SetCharSet('utf8');
        }

        if (!empty($this->config['setupsql'])) {
            $extdb->Execute($this->config['setupsql']);
        }
        return $extdb;
    }

	/**
	 * Retrieve user details from the Moodle database,
	 * filter the results, and return an array
	 *
	 * @return array Moodle users
	 */
	public function get_mdlusers() {

	    global $CFG;

        // Priority given to recently updated and newer user accounts

        $mdlusers = array();

        $extdb = $this->db_init();

        $rs = $extdb->Execute("SELECT username, firstname, lastname, idnumber as studentid, email
                                FROM {$CFG->mdldbprefix}user
                                WHERE confirmed = 1
                                AND deleted = 0
                                AND suspended = 0
                                ORDER BY timemodified DESC, id DESC");

        if (!$rs) {

	        log_info('No moodle user results');

        } else {
            $users = $rs->GetRows();
        }

		$extdb->Close();

        return $users;
    }


	/**
	 * Retrieve current Mahara user accounts
	 *
	 * @return array
	 */
	public function get_mahusers() {

	    global $CFG;

        $mahusers = array();

        db_begin();

        $sql = "SELECT username, firstname, lastname, email
                    FROM {$CFG->dbprefix}usr
                    ORDER BY id DESC";
        $values = array();

        $rs = get_records_sql_array($sql,$values);

        if($rs){
            foreach($rs as $v){
                $mahusers[$v->username]['firstname'] = $v->firstname;
	            $mahusers[$v->username]['lastname'] = $v->lastname;
	            $mahusers[$v->username]['email'] = $v->email;
            }
        }else{
            log_info('No Mahara user results');
        };
        return $mahusers;
    }

	/**
	 * The user fields required to create Mahara accounts
	 *
	 * @return array
	 */
	public function get_createfields() {

        $createfields = array();
        $createfields['firstname']  = '';
        $createfields['lastname']   = '';
        $createfields['email']      = '';

        return $createfields;
    }

	/**
	 * The user fields required to update Mahara,
	 * taking into account those configured in the form,
	 * and those available to be coded later ...
	 *
	 * @return stdClass
	 * @throws Exception
	 * @throws SQLException
	 * @throws SystemException
	 */
	public function get_updatefields() {

        safe_require('artefact', 'internal');

        $userfields = array_keys(ArtefactTypeProfile::get_all_fields());

        $updatefields = new stdClass();

        foreach($userfields as $field){

            if((isset($this->config[$field]))&&($this->config[$field]=='1')){
                $updatefields->{$field} = '';
            }
        }

        return $updatefields;
    }

	/**
	 * Creates and/or updates users in Mahara
	 *
	 *
	 * @throws SQLException
	 */
	public function create_users()
    {
        $start = microtime(true);
   
        require_once(get_config('docroot') . 'lib/institution.php');        
      
        // Get Institution defaults

            $authinstance = (int) $this->config['authinstance'];
            $authrecord   = get_record('auth_instance', 'id', $authinstance);
            $authobj      = AuthFactory::create($authinstance);

            $institution = new Institution($authobj->institution);
 
        // Get a list of current Mahara user accounts

            $mahusers = $this->get_mahusers();            

        // Which fields for account creation in Mahara

            $createfields = $this->get_createfields();
            
        // Which fields to use for updating accounts in Mahara

            $updatefields = $this->get_updatefields();

        // Temporarily disable email sent during user creation, e.g. institution membership

            $GLOBALS['CFG']->sendemail = false;

        // Get Moodle user accounts

            $mdlusers = $this->get_mdlusers();

        // counters for throttle and for accounts processed, created, and updated or not

            $i = 0;
	        $p = 0;
		    $c = 0;
		    $u = 0;
	        $n = 0;

        $args = array(
            'username'      => array('filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),
            'firstname'     => array('filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_FLAG_STRIP_LOW),
            'lastname'      => array('filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_FLAG_STRIP_LOW),
            'studentid'     => FILTER_SANITIZE_NUMBER_INT,
            'email'         => FILTER_SANITIZE_EMAIL,
        );

        ini_set('max_execution_time', 0); // php safe mode needs to be off. This setting is required in order to prevent time out error.

        $totalUsers = count($mdlusers); // P.P: used for log message
        $loopCounter = 0; // P.P: used for log message
        foreach ($mdlusers as $values) {

            $loopCounter++;

            $user = filter_var_array($values,$args);
            $user = array_map('trim', $user);

             if(
                !empty($user['username'])
                && !empty($user['firstname'])
                && !empty($user['lastname'])
                && !empty($user['email'])

            ) {

                $user = (object)array(
                    'username' => $values['username'],
                    'password' => '',
                    'firstname' => $values['firstname'],
                    'lastname' => $values['lastname'],
                    'email' => $values['email'],
                    'authinstance' => $authinstance,
                );

                ksort($mahusers);

                // Panos Paralakis: removed trim inside "if" as username are pushed as it is (not trimmed)
                // that was causing problems
                // if (!array_key_exists(trim($values['username']), $mahusers)) {
                if (!array_key_exists($values['username'], $mahusers)) {

                    if(create_user($user, $createfields, $institution, $authrecord, $values['username'], $values, false)) {
	                    $c++;
	                    $i++;
                    }

                } else {

                    // Panos Paralakis: Prevent mahara root user to be overwritten
                    if($values['username'] == 'root') {
                        continue;
                    }

	                $update = 0;

                    foreach($createfields as $k=>$v){

                        if(trim($values[$k]) !== $mahusers[$user->username][$k]){
                            $user->$k = trim($values[$k]);
                            $updatefields->$k = trim($values[$k]);
	                        $update++;
                        }else{
	                        $user->$k = $mahusers[$user->username][$k];
	                        $updatefields->$k = $mahusers[$user->username][$k];
                        }
                    }

	                if($update>0){

		                if ($updated = update_user($user, $updatefields, $values['username'], $values, false, false)) {
			                $u++;
			                $i++;
		                } else {
			                $n++;
		                }
	                }else{
		                $n++;
	                }
                }
	            $p++;
            }
            
            $timetaken = microtime(true) - $start;

            if ( $i >= $this->config['numbertoupdate'] || $totalUsers == $loopCounter ) {
                // Logging
                $message = "\n".count($mdlusers)." Moodle accounts processed\n".$p." Mahara accounts processed\n".$n.
                    " Mahara accounts not updated\n".$c." Mahara accounts created\n".$u." Mahara accounts updated\nin ".$timetaken. " seconds\n";

                log_info($message,true,true);
                break;
            }
        }

        db_commit();
    }

    public function init($id) {
        $this->ready = parent::init($id);
        return true;
    }

    public function can_auto_create_users()
    {
        return false;
    }
}

/**
 * Plugin configuration class
 */

class PluginAuthMoodleusers extends PluginAuth {

    public static function can_be_disabled() {
        return true;
    }

    public static function has_config() {
        return true;
    }

	public static function get_cron() {

	    // TODO NB remove cron minutes for production

        $crondays = get_config_plugin('auth', 'moodleusers', 'crondays');
        $cronhours = get_config_plugin('auth', 'moodleusers', 'cronhours');

        return array(
            (object)array(
                'callfunction' => 'auth_moodleusers_cron',
                'day' => $crondays,
                'hour' => $cronhours,
	            'minute' => '*',
            ),
        );
    }

	public static function auth_moodleusers_cron() {
        $extdb = new AuthMoodleusers();
        $extdb->create_users();
    }

	public static function get_config_options() {

        $elements = array();

        // Prepare Institutions form element

            $authinstances = auth_get_auth_instances();

            $options = array();

            foreach ($authinstances as $authinstance) {
		            $options[$authinstance->id] = $authinstance->displayname . ': ' . $authinstance->instancename;
		            $INSTITUTIONNAME[$authinstance->name] = $authinstance->displayname;
            }

            $authinstanceelement = array(
                'type' => 'select',
                'title' => get_string('institution', 'auth.moodleusers'),
                'description' => get_string('institutiondescription', 'auth.moodleusers'),
                'options' => $options,
                'defaultvalue' => get_config_plugin('auth', 'moodleusers', 'authinstance'),
            );

        // Update options fieldset

        $elements['updateoptions'] = array(
            'type' => 'fieldset',
            'legend' => get_string('updateoptions', 'auth.moodleusers'),
            'collapsible' => true,
            'elements' => array(
                'authinstance' => $authinstanceelement,
                'quota' => array(
                    'type' => 'bytes',
                    'title' => get_string('filequota1', 'admin'),
                    'description' => get_string('filequotadescription', 'admin'),
                    'rules' => array('integer' => true, 'minvalue' => 0),
                    'defaultvalue' => get_config_plugin('artefact', 'file', 'defaultquota'),
                ),
                'firstname' =>  array(
                    'title'        => get_string('firstname', 'auth.moodleusers'),
                    'description'  => get_string('firstnamedescription', 'auth.moodleusers'),
                    'type'         => 'checkbox',
                    'defaultvalue' => 1,
                ),
                'lastname' =>  array(
                    'title'        => get_string('lastname', 'auth.moodleusers'),
                    'description'  => get_string('lastnamedescription', 'auth.moodleusers'),
                    'type'         => 'checkbox',
                    'defaultvalue' => 1,
                ),
                'email' =>  array(
                    'title'        => get_string('email', 'auth.moodleusers'),
                    'description'  => get_string('emaildescription', 'auth.moodleusers'),
                    'type'         => 'checkbox',
                    'defaultvalue' => 1,
                ),

            ),

        );

        // Set throttle

        $elements['throttle'] =  array(
            'type' => 'fieldset',
            'legend' => get_string('throttle', 'auth.moodleusers'),
	        'collapsible' => true,
            'elements' => array(
                'crondays' =>  array(
                    'title'        => get_string('crondays', 'auth.moodleusers'),
                    'description'  => get_string('crondaysdescription', 'auth.moodleusers'),
                    'type'         => 'text',
                    'defaultvalue' => get_config_plugin('auth', 'moodleusers', 'crondays'),
                ),
                'cronhours' =>  array(
                    'title'        => get_string('cronhours', 'auth.moodleusers'),
                    'description'  => get_string('cronhoursdescription', 'auth.moodleusers'),
                    'type'         => 'text',
                    'defaultvalue' => get_config_plugin('auth', 'moodleusers', 'cronhours'),
                ),
                'numbertoupdate' => array(
                    'title'         => get_string('numbertoupdate', 'auth.moodleusers'),
                    'description'   => get_string('numbertoupdatedescription', 'auth.moodleusers'),
                    'type'          => 'text',
                    'rules'         => array('integer' => true, 'minvalue' => 0, 'maxvalue' => 100000),
                    'defaultvalue'  => get_config_plugin('auth', 'moodleusers', 'numbertoupdate'),
                ),
            )
        );

        // Set user account preferences

        $elements['authname'] = array(
                'type'  => 'hidden',
                'value' => 'moodleusers',
        );

        return array(
            'elements' => $elements,
        );
    }

	public static function save_config_options($form, $values) {
        $configs = array('authinstance','firstname','lastname','email','quota','numbertoupdate','crondays','cronhours');
        foreach ($configs as $config) {
            set_config_plugin('auth', 'moodleusers', $config, $values[$config]);
        }
    }

    public static function has_instance_config() {
        return true;
    }

    public static function is_usable() {
        return true;
    }
}