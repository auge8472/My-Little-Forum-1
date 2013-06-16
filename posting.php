<?php
###############################################################################
# my little forum 1                                                            #
# Copyright (C) 2013 Heiko August                                             #
# http://www.auge8472.de/                                                     #
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

include_once("inc.php");
include_once("functions/include.prepare.php");

# generate captcha if captcha is on
# and a not logged user wants to post
if (empty($_SESSION[$settings['session_prefix'].'user_id'])
&& $settings['captcha_posting'] == 1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

# look for banned user:
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$lockQuery = "SELECT user_lock
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_id = '". intval($_SESSION[$settings['session_prefix'].'user_id']) ."'
	LIMIT 1";
	$lockResult = mysql_query($lockQuery, $connid);
	if ($lockResult === false) die($lang['db_error']);
	$lockResultArray = mysql_fetch_assoc($lockResult);
	mysql_free_result($lockResult);
	if ($lockResultArray['user_lock'] > 0)
		{
		header("location: ". $settings['forum_address'] ."user.php");
		die('<a href="user.php">further...</a>');
		}
	} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))
