<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004 Alex                                                     #
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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& isset($_COOKIE['auto_login'])
	&& (isset($settings['autologin'])
	&& $settings['autologin'] == 1))
	{
	header("location: ". $settings['forum_address'] ."login.php?referer=index.php");
	die('<a href="login.php?referer=index.php">further...</a>');
	}

if (!empty($_SESSION[$settings['session_prefix'].'category'])
	and $_SESSION[$settings['session_prefix'].'category'] > 0)
	{
	$qstrg[] = 'category='. intval($_SESSION[$settings['session_prefix'].'category']);
	}
else if (!empty($_GET['category'])
	and intval($_GET['category']) > 0)
	{
	$qstrg[] = 'category='. intval($_GET['category']);
	}
if (!empty($_SESSION[$settings['session_prefix'].'page'])
	and $_SESSION[$settings['session_prefix'].'page'] > 0)
	{
	$qstrg[] = 'page='. $_SESSION[$settings['session_prefix'].'page'];
	}
else if (!empty($_GET['page'])
	and intval($_GET['page']) > 0)
	{
	$qstrg[] = 'page='. intval($_GET['page']);
	}
$qs = (!empty($qstrg) and is_array($qstrg)) ? '?'. implode('&', $qstrg) : '';
$qsl = (!empty($qstrg) and is_array($qstrg)) ? '?'. implode('&amp;', $qstrg) : '';

if (isset($_GET['update'])
	&& intval($_GET['update']) == 1
	&& isset($_SESSION[$settings['session_prefix'].'newtime']))
	{
	$_SESSION[$settings['session_prefix'].'newtime'] = time();
	$update_result = mysql_query("UPDATE ".$db_settings['userdata_table']." SET last_login=last_login, last_logout=NOW(), registered=registered WHERE user_id='".$_SESSION[$settings['session_prefix'].'user_id']."'", $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		$targetView = processView2Filename($_SESSION[$settings['session_prefix'].'user_view']);
		}
	else if (!empty($_COOKIE['user_view'])
		and in_array($_COOKIE['user_view'], $possViews))
		{
		$targetView = processView2Filename($_COOKIE['user_view']);
		}
	else
		{
		$targetView = processView2Filename($settings['standard']);
		}
	header("location: ".$settings['forum_address'].$targetView.$qs);
	die('<a href="'. $targetView.$qsl .'">further...</a>');
	}

if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
	and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
	{
	$targetView = processView2Filename($_SESSION[$settings['session_prefix'].'user_view']);
	}
else if (isset($_COOKIE['user_view'])
	and in_array($_COOKIE['user_view'], $possViews))
	{
	$targetView = processView2Filename($_COOKIE['user_view']);
	}
else
	{
	$targetView = processView2Filename($settings['standard']);
	}
header("location: ".$settings['forum_address'].$targetView.$qs);
die('<a href="'. $targetView.$qsl .'">further...</a>');

?>
