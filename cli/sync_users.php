<?php
/**
 *
 * @package    mahara
 * @subpackage auth-ldap
 * @author     Patrick Pollet <pp@patrickpollet.net>
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 * @copyright  (C) 2011 INSA de Lyon France
 *
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *    Moodle - Modular Object-Oriented Dynamic Learning Environment
 *             http://moodle.com
 *
 *    Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details:
 *
 *             http://www.gnu.org/copyleft/gpl.html
 */

/**
 * This is an OPTIONAL command-line script, to be used if you want more detailed control over the
 * ldap sync process than what you get from the standard ldap sync cron task. If you use this script,
 * you will probably also want to disable the standard ldap sync cron task by deleting its entry from
 * the auth_cron table in the database.
 *
 * The purpose of this CLI script is to be run as a cron job to synchronize Mahara' users
 * with users defined on a LDAP server
 *
 * This script requires at least a parameter the name of the target institution
 * in which users will be created/updated.
 * An instance of LDAP or CAS auth plugin MUST have been added to this institution
 * for this script to retrieve LDAP parameters
 * It is possible to run this script for several institutions
 *
 * For the synchronisation of group membership , this script MUST be run before
 * the mahara_sync_groups script
 *
 * This script is strongly inspired of synching Moodle's users with LDAP
 */

define('CLI', 1);
define('INTERNAL', 1);
define('ADMIN', 1);

require_once(get_config('docroot') . 'init.php');
require(get_config('libroot').'cli.php');
require(get_config('docroot') . 'auth/moodleusers/lib.php');

// output all errors
error_reporting('E_ALL');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$cli = get_cli();

$options = array();
$options['verbose'] = new stdClass();
$options['verbose']->description = 'Verbose output - it takes no value and has an alias of v';
$options['verbose']->shortoptions = array('v');
$options['verbose']->required = false;

$settings = new stdClass();
$settings->options = $options;
$settings->allowunmatched = true;
$settings->info = 'Synchronise Mahoodle User Accounts';

$cli->setup($settings);

try {

    $cliusers = new AuthMoodleusers();
	$cliusers->create_users();

}
// we catch missing parameter and unknown institution
catch (Exception $e) {
    // $USER->logout(); // important
    $cli->cli_exit($e->getMessage(), true);

}

$cli->cli_exit($message.'---------- ended at ' . date('r', time()) . ' ----------', true);