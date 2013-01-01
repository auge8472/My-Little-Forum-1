<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2005 Alex                                                     #
# http://www.mylittlehomepage.net/                                            #
#                                                                             #
# This program is free software; you can redistribute it and/or               #
# modify it under the terms of the GNU General Public License                 #
# as published by the Free Software Foundation; either version 2              #
# of the License, or (at your option) any later version.                      #
#                                                                             #
# This program is distributed in the hope that it will be useful,             #
# but WITHOUT ANY WARRANTY; without even the implied warranty of              #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                #
# GNU General Public License for more details.                                #
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

/**
 * This file collects code wich is used at start of all requestable scripts
 *
 * @author Heiko August <post@auge8472.de>
 * @since version 1.8
 */


/**
 * if install.php or update.php is present relocate to info.php
 * to show the message "forum in maintanance, try again"
 */
if (file_exists('install.php')
or file_exists('update.php')) {
	header("Location: ".$settings['forum_address']."info.php?info=2");
	die('<a href="info.php?info=2">further...</a>');
	}


$generalLocalPath = dirname($_SERVER['SCRIPT_NAME']);
/**
 * make use of GET-parameters in some scripts
 */
$generalGetParameterUse = array(
$generalLocalPath.'/board.php',
$generalLocalPath.'/board_entry.php',
$generalLocalPath.'/forum.php',
$generalLocalPath.'/forum_entry.php',
$generalLocalPath.'/mix.php',
$generalLocalPath.'/mix_entry.php',
$generalLocalPath.'/posting.php',
$generalLocalPath.'/search.php');

if (in_array($_SERVER['SCRIPT_NAME'], $generalGetParameterUse) and count($_GET) > 0) {
	foreach($_GET as $key => $value) {
		$$key = $value;
		}
	}

/**
 * make use of POST-parameters in some scripts
 */
$generalPostParameterUse = array(
$generalLocalPath.'/posting.php',
$generalLocalPath.'/search.php');

if (in_array($_SERVER['SCRIPT_NAME'], $generalPostParameterUse) and count($_POST) > 0) {
	foreach($_POST as $key => $value) {
		$$key = $value;
		}
	}

/**
 * use $mark in some scripts (handles marking of user roles)
 */
$generalMarkUse = array(
$generalLocalPath.'/board.php',
$generalLocalPath.'/board_entry.php',
$generalLocalPath.'/forum.php',
$generalLocalPath.'/forum_entry.php',
$generalLocalPath.'/mix.php',
$generalLocalPath.'/mix_entry.php');

if (in_array($_SERVER['SCRIPT_NAME'], $generalMarkUse)) {
	$mark['admin'] = 0;
	$mark['mod'] = 0;
	$mark['user'] = 0;
	}

?>