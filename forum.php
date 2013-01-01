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

include("inc.php");
include_once("functions/include.prepare.php");


# log in automatically if cookie is set
if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_COOKIE['auto_login'])
&& isset($settings['autologin'])
&& $settings['autologin'] == 1)
	{
	header("location: ".$settings['forum_address']."login.php?referer=forum.php");
	die("<a href=\"login.php?referer=forum.php\">further...</a>");
	}

// go on if user has access:
if ($settings['access_for_users_only'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name'])
|| $settings['access_for_users_only'] != 1)
	{
	if ($settings['remember_userstandard'] == 1
	&& !isset($_SESSION[$settings['session_prefix'].'newtime']))
		{
		setcookie("user_view","thread",time()+(3600*24*30));
		}

	if ($_SESSION[$settings['session_prefix'].'order'] != "time"
	&& $_SESSION[$settings['session_prefix'].'order'] !="last_answer")
		{
		$threadOrder = "time";
		}
	else
		{
		$threadOrder = $_SESSION[$settings['session_prefix'].'order'];
		}
	$ul = $_SESSION[$settings['session_prefix'].'page'] * $settings['topics_per_page'];
	unset($parent_array);
	unset($child_array);

	# database request
	# no categories defined
	if ($categories === false)
		{
		$threadsQueryWhere = '';
		}
	# there are categories and all categories should be shown
	else if (is_array($categories)
		&& $_SESSION[$settings['session_prefix'].'category'] == 0)
		{
		$threadsQueryWhere = " AND category IN (". $category_ids_query .")";
		}
	# there are categories and only one category should be shown
	else if (is_array($categories)
		&& $_SESSION[$settings['session_prefix'].'category'] != 0
		&& in_array($_SESSION[$settings['session_prefix'].'category'], $category_ids))
		{
		$threadsQueryWhere = " AND category = '". intval($_SESSION[$settings['session_prefix'].'category']) ."'";
		# how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE pid = 0 AND category = '". mysql_real_escape_string($_SESSION[$settings['session_prefix'].'category']) ."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}
	$getAllThreadsQuery = "SELECT
		id,
		pid,
		tid
		FROM ". $db_settings['forum_table'] ."
		WHERE pid = 0 ". $threadsQueryWhere ."
		ORDER BY fixed DESC, ". $threadOrder ." DESC
		LIMIT ". $ul .", ". $settings['topics_per_page'];
	$result = mysql_query($getAllThreadsQuery, $connid);
	if (!$result) die($lang['db_error']);

	$subnav_1 = outputPostingLink($_SESSION[$settings['session_prefix'].'category']);
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$url  = 'index.php?update=1';
		$class = 'update-postings';
		$title = outputLangDebugInAttributes($lang['update_time_linktitle']);
		$linktext = $lang['update_time_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($threadOrder == "time")
		{
		$url = 'forum.php?order=last_answer';
		$title = outputLangDebugInAttributes($lang['order_linktitle_1']);
		}
	else
		{
		$url = 'forum.php?order=time';
		$title = outputLangDebugInAttributes($lang['order_linktitle_2']);
		}
	$class = 'order-postings';
	$linktext = $lang['order_linkname'];
	$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
	if ($settings['board_view'] == 1)
		{
		$url = 'board.php?view=board';
		$class = 'board-view';
		$title = outputLangDebugInAttributes($lang['board_view_linktitle']);
		$linktext = $lang['board_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($settings['mix_view']==1)
		{
		$url = 'mix.php?view=mix';
		$class = 'mix-view';
		$title = outputLangDebugInAttributes($lang['mix_view_linktitle']);
		$linktext = $lang['mix_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	$subnav_2 .= nav($_SESSION[$settings['session_prefix'].'page'], (int)$settings['topics_per_page'], $thread_count, $_SESSION[$settings['session_prefix'].'order'], $_SESSION[$settings['session_prefix'].'descasc'], $_SESSION[$settings['session_prefix'].'category']);
	$subnav_2 .= outputCategoriesList($categories, $_SESSION[$settings['session_prefix'].'category']);

	parse_template();
	echo $header;
	echo outputDebugSession();

	if ($thread_count > 0 && isset($result))
		{
		while ($zeile = mysql_fetch_assoc($result))
			{
			$threadQuery = "SELECT
			id,
			pid,
			tid,
			t1.user_id AS posters_id,
			DATE_FORMAT(time + INTERVAL ". $time_difference ." HOUR, '". $lang['time_format_sql'] ."') AS Uhrzeit,
			UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS time,
			UNIX_TIMESTAMP(last_answer + INTERVAL ". $time_difference ." HOUR) AS last_answer,
			name,
			subject,
			category,
			marked,
			fixed,
			(SELECT
				user_type
				FROM ". $db_settings['userdata_table'] ."
				WHERE ". $db_settings['userdata_table'] .".user_id = posters_id) AS user_type
			FROM ". $db_settings['forum_table'] ." AS t1
			WHERE tid = ". intval($zeile["tid"]) ."
			ORDER BY time ". $settings['thread_view_sorter'];
			$thread_result = @mysql_query($threadQuery, $connid);

			# put result into arrays:
			while ($tmp = mysql_fetch_assoc($thread_result))
				{
				$postArray[$tmp["id"]] = $tmp;           // Ergebnis im Array ablegen
				$childArray[$tmp["pid"]][] = $tmp["id"]; // Vorwärtsbezüge konstruieren
				}
			# generate output of thread lists
			echo outputThreads($postArray, $childArray, 'forum', 1);
			unset($postArray, $childArray);
			mysql_free_result($thread_result);
			}
		echo outputManipulateMarked();
		}
	else
		{
		# import posting template
		$output = file_get_contents('data/templates/locked.gen.html');
		$output = str_replace('{locked_hl}', $lang['caution'], $output);
		$langTemp = ($_SESSION[$settings['session_prefix'].'category']!=0) ? $lang['no_messages_in_category'] : $lang['no_messages'];
		$output = str_replace('{locked_txt}', $langTemp, $output);
		echo $output;
		}
	if (isset($result)) mysql_free_result($result);

	echo $footer;
	}
else // no access
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
