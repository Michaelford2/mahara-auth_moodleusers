<?php
/**
 *
 * @package    mahara
 * @subpackage auth-moodleusers
 * @author     Michael Ford <m.ford@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 */

defined('INTERNAL') || die();

$config = new StdClass;
$config->version = 2015072001;
$config->release = '0.91';
$config->name = 'moodleusers';
$config->requires_config = 1;
$config->requires_parent = 0;