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

$string['title'] = 'Moodle Users Enrolment';
$string['description'] = 'Moodle Users Enrolment Plugin';

/** Plugin Configuration Form */

$string['updateoptions'] = 'User Update Options';
$string['institution'] = 'Mahara Institution';
$string['institutiondescription'] = 'The target institution for newly enrolled users (xmlrpc necessary)';
$string['firstname'] = 'First name';
$string['firstnamedescription'] = 'Overwrite first name (default yes)';
$string['lastname'] = 'Last name';
$string['lastnamedescription'] = 'Overwrite last name (default yes)';
$string['email'] = 'Email';
$string['emaildescription'] = 'Overwrite email (default yes)';

$string['throttle'] = 'Frequency';
$string['numbertoupdate'] = 'Maximum number of accounts to create and update at a time';
$string['numbertoupdatedescription'] = "0 - 100,000. The figure will depend on the number of accounts on the Moodle server, server resources, and the frequency of the Mahara cron job (above). Priority is automatically given to creating new users over updating older accounts.";
$string['crondays'] = 'Which days of the week should accounts be created and updated';
$string['crondaysdescription'] = 'Crontab notation:  0-6 (0 = Sun), * (all), - (range), and , (lists)';
$string['cronhours'] = 'What times should accounts be created and updated';
$string['cronhoursdescription'] = 'Crontab notation:  0-23, * (all), - (range), and , (lists)';