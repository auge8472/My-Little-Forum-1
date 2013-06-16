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
	and $settings['captcha_posting'] == 1)
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

/**
 * Start: block for special cases
 */
# lock or unlock a thread (forbid or allow answers to a thread)
if (isset($_GET['lock'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$lockQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	locked = IF(locked = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($lockQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['lock']) ...)

# pin or unpin threads to the top of the views
if (isset($_GET['fix'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$fixQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	fixed = IF(fixed = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($fixQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['fix']) ...)

# subscribe or unsubscribe threads
if (isset($_GET['subscribe'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and isset($_GET['back']))
	{
	if ($_GET['subscribe'] == 'true')
		{
		$querySubscribe = "INSERT INTO ". $db_settings['usersubscripts_table'] ." SET
		user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) .",
		tid = ". intval($_GET['back']) ."
		ON DUPLICATE KEY UPDATE
		user_id = user_id,
		tid = tid";
		$queryUnsubscribePost = "UPDATE ". $db_settings['forum_table'] ." SET
		email_notify = 0
		WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
		AND tid = ". intval($_GET['id']);
		}
	else if ($_GET['subscribe'] == 'false')
		{
		$subscriptThread = processSearchThreadSubscriptions($_GET['back'], $_SESSION[$settings['session_prefix'].'user_id']);
		if (($subscriptThread !== false
		and is_array($subscriptThread))
		and ($subscriptThread['user_id'] == $_SESSION[$settings['session_prefix'].'user_id']
		and $subscriptThread['tid'] == $_GET['back']))
			{
			$querySubscribe = "DELETE FROM ". $db_settings['usersubscripts_table'] ."
			WHERE tid = ". intval($_GET['back']) ."
			AND user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
			LIMIT 1";
			}
		}
	if (!empty($querySubscribe)) @mysql_query($querySubscribe, $connid);
	if (!empty($queryUnsubscribePost)) @mysql_query($queryUnsubscribePost, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
	and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='.  intval($_GET['back']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['subscribe'] ...)
/**
 * End: block for special cases
 */
