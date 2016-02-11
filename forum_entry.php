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


if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& isset($_COOKIE['auto_login'])
	&& isset($settings['autologin'])
	&& $settings['autologin'] == 1)
	{
	$id = isset($_GET['id']) ? 'id='. intval($_GET['id']) : '';
	if (!empty($id))
		{
		$lid = '&'.$id;
		$did = '&amp;'.$id;
		}
	header('location: '. $settings['forum_address'] .'login.php?referer=forum_entry.php'. $lid);
	die('<a href="login.php?referer=forum_entry.php'. intval($did) .'">further...</a>');
	}

if ($settings['access_for_users_only'] == 1
	&& isset($_SESSION[$settings['session_prefix'].'user_name'])
	|| $settings['access_for_users_only'] != 1)
	{
	unset($entrydata);
	unset($parent_array);
	unset($child_array);

	if ($_SESSION[$settings['session_prefix'].'order'] != "time"
	&& $_SESSION[$settings['session_prefix'].'order'] !="last_answer")
		{
		$threadOrder = "time";
		}
	else
		{
		$threadOrder = $_SESSION[$settings['session_prefix'].'order'];
		}
	if (isset($id))
		{
		$id = (int)$id;
		}

	if (isset($id) && $id > 0)
		{
		$postingQuery = "SELECT
		id,
		pid,
		tid,
		DATE_FORMAT(time + INTERVAL ". $time_difference ." HOUR, '". $lang['time_format_sql'] ."') AS posting_time,
		UNIX_TIMESTAMP(time) AS time,
		UNIX_TIMESTAMP(edited + INTERVAL ". $time_difference ." HOUR) AS e_time,
		UNIX_TIMESTAMP(edited - INTERVAL ". $settings['edit_delay'] ." MINUTE) AS edited_diff,
		edited_by,
		user_id,
		user_id AS posters_id,
		name,
		email,
		subject,
		hp,
		place,
		INET_NTOA(ip_addr) AS ip_address,
		text,
		show_signature,
		category,
		locked,
		fixed,
		user_type,
		hide_email,
		signature
		FROM ". $db_settings['posting_view'] ."
		WHERE id = ". intval($id);
		$result = mysql_query($postingQuery, $connid);
		if (!$result) die($lang['db_error']);
		if (mysql_num_rows($result) == 1)
			{
			$entrydata = mysql_fetch_assoc($result);
			mysql_free_result($result);
			# category of this posting accessible by user?
			if (!(isset($_SESSION[$settings['session_prefix'].'user_type'])
				&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
				{
				if (is_array($category_ids) && !in_array($entrydata['category'], $category_ids))
					{
					header('location: '. $settings['forum_address'] .'forum.php');
					die('<a href="forum.php">further...</a>');
					}
				}
			if (isset($settings['count_views'])
				&& $settings['count_views'] == 1)
				{
				mysql_query("UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE id=". intval($id), $connid);
				}

			if ($entrydata["user_id"] > 0)
				{
				if ($entrydata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['admin'] = 1;
					}
				else if ($entrydata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['mod'] = 1;
					}
				else if ($entrydata["user_type"] == "user" && $settings['user_highlight'] == 1)
					{
					$mark['user'] = 1;
					}
				if ($entrydata["show_signature"] == 1)
					{
					$signature = $entrydata["signature"];
					}
				}
			$opener = ($entrydata['pid'] == 0) ? 'opener' : '';
			}
		else
			{
			header('location: '. $settings['forum_address'] .'forum.php');
			die('<a href="forum.php">further...</a>');
			}
		}
	else
		{
		header('location: '. $settings['forum_address'] .'forum.php');
		die('<a href="forum.php">further...</a>');
		}

	# thread-data:
	$Thread = $entrydata["tid"];
	$threadQuery = "SELECT
	id,
	pid,
	tid,
	user_id,
	user_id AS posters_id,
	DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS Uhrzeit,
	UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS time,
	UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS last_answer,
	name,
	subject,
	category,
	marked,
	fixed,
	user_type
	FROM ". $db_settings['posting_view'] ."
	WHERE tid = ". intval($entrydata["tid"])."
	ORDER BY time ". $settings['thread_view_sorter'];
	$result = mysql_query($threadQuery, $connid);
	if (!$result) die($lang['db_error']);
	while ($tmp = mysql_fetch_assoc($result))
		{
		$postArray[$tmp["id"]] = $tmp;           // Ergebnis im Array ablegen
		$childArray[$tmp["pid"]][] = $tmp["id"]; // Vorwärtsbezüge konstruieren
		}
	mysql_free_result($result);

	$category = !empty($_SESSION[$settings['session_prefix'].'category']) ? intval($_SESSION[$settings['session_prefix'].'category']) : 0;

	$wo = $entrydata["subject"];
	$topnav  = '<li><a href="forum.php" title="'. outputLangDebugInAttributes($lang['back_to_forum_linktitle']) .'">';
	$topnav .= '<span class="fa fa-chevron-right icon-chevron-right"></span>&nbsp;'. htmlspecialchars($lang['back_to_forum_linkname']) .'</a></li>'."\n";
	$cat = ($category > 0) ? '&amp;category='. intval($category) : '';
	$subnav_2tm = "\n <ul>\n{NavPoints} </ul>\n";
	$subnav_2ts = '  <li>{NavPoint}</li>'."\n";
	$subnav_2 = '';
	$subnav2p = array();
	if ($settings['board_view']==1)
		{
		$url = 'board_entry.php?view=board&amp;id='.$entrydata["tid"];
		$class = 'board-view';
		$title = outputLangDebugInAttributes($lang['board_view_linktitle']);
		$linktext = $lang['board_view_linkname'];
		$subnav2p[] = str_replace('{NavPoint}', outputSingleLink($url, $linktext, $title, $class), $subnav_2ts);
		}
	if ($settings['mix_view']==1)
		{
		$url = 'mix_entry.php?view=mix&amp;id='.$entrydata["tid"];
		$class = 'mix-view';
		$title = outputLangDebugInAttributes($lang['mix_view_linktitle']);
		$linktext = $lang['mix_view_linkname'];
		$subnav2p[] = str_replace('{NavPoint}', outputSingleLink($url, $linktext, $title, $class), $subnav_2ts);
		}
	$subnav_2 = str_replace('{NavPoints}', join("", $subnav2p), $subnav_2tm);

	parse_template();
	# import posting template
	$posting = file_get_contents('data/templates/posting.thread.html');
	# generate posting snippets
	$pHeadline  = htmlspecialchars($entrydata["subject"]);
	if (isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='')
		{
		$pHeadline .= ' <span class="category">('. $categories[$entrydata["category"]] .')</span>';
		}
	$ftext = ($entrydata["text"]=="") ? $lang['no_text'] : outputPreparePosting($entrydata["text"]);
	$signature = (isset($signature) && $signature != "") ? $signature = '<div class="signature">'. outputPreparePosting($settings['signature_separator']."\n".$signature, 'signature') .'</div>'."\n" : '';
	if ($entrydata['locked'] == 0)
		{
		if ($settings['entries_by_users_only'] == 0
			or ($settings['entries_by_users_only'] == 1
			and isset($_SESSION[$settings['session_prefix'].'user_name'])))
			{
			$answerlink  = '<a class="buttonize" href="posting.php?id='. intval($id);
			$answerlink .= '" title="'. outputLangDebugInAttributes($lang['forum_answer_linktitle']) .'">';
			$answerlink .= '<span class="fa fa-comment-o icon-comment-o"></span>&nbsp;'. $lang['forum_answer_linkname'] .'</a>';
			}
		}
	else
		{
		$answerlink = '<span class="buttonize"><span class="fa fa-lock icon-lock"></span>&nbsp;'. $lang['thread_locked'] .'</span>';
		}
	# generate HTML source code of posting
	$posting = str_replace('{postingheadline}', $pHeadline, $posting);
	$posting = str_replace('{authorinfo}', outputAuthorInfo($mark, $entrydata, 'forum'), $posting);
	$posting = str_replace('{posting}', $ftext, $posting);
	$posting = str_replace('{signature}', $signature, $posting);
	$posting = str_replace('{answer-locked}', $answerlink, $posting);
	$posting = str_replace('{editmenu}', outputPostingEditMenu($entrydata, '', $opener), $posting);
	$posting = str_replace('{threadheadline}', $lang['whole_thread_marking'], $posting);
	$posting = str_replace('{thread}', outputThreads($postArray, $childArray, 'forum', 0), $posting);
	echo $header;
	echo outputDebugSession();
	echo $posting;
	echo $footer;
	}
else
	{
	header('location: '. $settings['forum_address'] .'login.php?msg=noaccess');
	die('<a href="login.php?msg=noaccess">further...</a>');
	}
?>
