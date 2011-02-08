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
	$id = isset($_GET['id']) ? 'id='.intval($_GET['id']) : '';
	if (!empty($id))
		{
		$lid = '&'.$id;
		$did = '&amp;'.$id;
		}
	header("location: ".$settings['forum_address']."login.php?referer=forum_entry.php".$lid);
	die("<a href=\"login.php?referer=forum_entry.php".$did."\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
	&& isset($_SESSION[$settings['session_prefix'].'user_name'])
	|| $settings['access_for_users_only'] != 1)
	{
	unset($entrydata);
	unset($parent_array);
	unset($child_array);

	if (empty($page))
		{
		$page = 0;
		}
	if (empty($order))
		{
		$order="time";
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
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS p_time,
		UNIX_TIMESTAMP(time) AS time,
		UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_time,
		UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff,
		edited_by,
		user_id,
		name,
		email,
		subject,
		hp,
		place,
		ip,
		text,
		show_signature,
		category,
		locked,
		fixed,
		ip
		FROM ".$db_settings['forum_table']."
		WHERE id = ".intval($id);
		$result = mysql_query($postingQuery, $connid);
		if (!$result) die($lang['db_error']);
		if (mysql_num_rows($result) == 1)
			{
			$entrydata = mysql_fetch_assoc($result);
			mysql_free_result($result);
			# category of this posting accessible by user?
			if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
				{
				if (is_array($category_ids) && !in_array($entrydata['category'], $category_ids))
					{
					header("location: ".$settings['forum_address']."forum.php");
					# Abbruch mit Fehlermeldung ergänzen!
					die();
					}
				}
			if (isset($settings['count_views']) && $settings['count_views'] == 1)
				{
				mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE id=".intval($id), $connid);
				}

			if ($entrydata["user_id"] > 0)
				{
				$userDataQuery = "SELECT
				user_name,
				user_type,
				user_email,
				hide_email,
				user_hp,
				user_place,
				signature
				FROM ".$db_settings['userdata_table']."
				WHERE user_id = ".intval($entrydata["user_id"]);
				$userdata_result = mysql_query($userDataQuery, $connid);
				if (!$userdata_result) die($lang['db_error']);
				$userdata = mysql_fetch_assoc($userdata_result);
				mysql_free_result($userdata_result);
				$entrydata["email"] = $userdata["user_email"];
				$entrydata["hide_email"] = $userdata["hide_email"];
				$entrydata["place"] = $userdata["user_place"];
				$entrydata["hp"] = $userdata["user_hp"];
				$opener = ($entrydata['pid'] == 0) ? 'opener' : '';
				if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['admin'] = 1;
					}
				else if ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['mod'] = 1;
					}
				else if ($userdata["user_type"] == "user" && $settings['user_highlight'] == 1)
					{
					$mark['user'] = 1;
					}
				if ($entrydata["show_signature"] == 1)
					{
					$signature = $userdata["signature"];
					}
				}
			}
		else
			{
			header("location: ".$settings['forum_address']."forum.php");
			die();
			}
		}
	else
		{
		header("location: ".$settings['forum_address']."forum.php");
		die();
		}

	# thread-data:
	$Thread = $entrydata["tid"];
	$threadQuery = "SELECT
	id,
	pid,
	tid,
	user_id AS posters_id,
	DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS Uhrzeit,
	UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS tp_time,
	UNIX_TIMESTAMP(last_answer) AS last_answer,
	name,
	subject,
	category,
	marked,
	fixed,
	(SELECT
		user_type
		FROM ".$db_settings['userdata_table']."
		WHERE ".$db_settings['userdata_table'].".user_id = posters_id) AS user_type
	FROM ".$db_settings['forum_table']."
	WHERE tid = ".intval($entrydata["tid"])."
	ORDER BY time DESC";
	$result = mysql_query($threadQuery, $connid);
	if (!$result) die($lang['db_error']);
	while ($tmp = mysql_fetch_assoc($result))
		{
		$postArray[$tmp["id"]] = $tmp;           // Ergebnis im Array ablegen
		$childArray[$tmp["pid"]][] = $tmp["id"]; // Vorwärtsbezüge konstruieren
		}
	mysql_free_result($result);

	$category = intval($category);

	$wo = $entrydata["subject"];
	$subnav_1  = '<a class="textlink" href="forum.php?page='.$page;
	$subnav_1 .= ($category > 0) ? '&amp;category='.$category : '';
	$subnav_1 .= '&amp;order='.$order.'" title="';
	$subnav_1 .= outputLangDebugInAttributes($lang['back_to_forum_linktitle']).'">'.$lang['back_to_forum_linkname'].'</a>';
	$subnav_2 = "";
	if ($settings['board_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="board_entry.php?id='.$entrydata["tid"];
		$subnav_2 .= '&amp;page='.$page.'&amp;order='.$order;
		$subnav_2 .= ($category > 0) ? '&amp;category='.$category : '';
		$subnav_2 .= '&amp;view=board" class="board-view"';
		$subnav_2 .= ' title="'.outputLangDebugInAttributes($lang['board_view_linktitle']).'">';
		$subnav_2 .= $lang['board_view_linkname'].'</a>';
		}
	if ($settings['mix_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="mix_entry.php?id='.$entrydata["tid"];
		$subnav_2 .= '&amp;page='.$page.'&amp;order='.$order;
		$subnav_2 .= ($category > 0) ? '&amp;category='.$category : '';
		$subnav_2 .= '&amp;view=mix" class="mix-view"';
		$subnav_2 .= ' title="'.outputLangDebugInAttributes($lang['mix_view_linktitle']).'">';
		$subnav_2 .= $lang['mix_view_linkname'].'</a>';
		}

	parse_template();
	echo $header;
	echo "\n".'<h2 class="postingheadline">'.htmlspecialchars($entrydata["subject"]);
	if (isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='')
		{
		echo ' <span class="category">('.$categories[$entrydata["category"]].')</span>';
		}
	echo '</h2>'."\n";
	echo outputAuthorInfo($mark, $entrydata, $page, $order, 'forum', $category);
	if ($entrydata["text"]=="")
		{
		echo $lang['no_text'];
		}
	else
		{
		$ftext = outputPreparePosting($entrydata["text"]);
		echo '<div class="posting">'.$ftext.'</div>'."\n";
		}
	if (isset($signature) && $signature != "")
		{
		$signature = outputPreparePosting($settings['signature_separator'].$signature, 'signature');
		echo '<div class="signature">'.$signature.'</div>'."\n";
		}
	echo '<div class="postingbottom">'."\n";
	echo '<div class="postinganswer">';

	if ($entrydata['locked'] == 0)
		{
		if ($settings['entries_by_users_only'] == 0
			or ($settings['entries_by_users_only'] == 1
			and isset($_SESSION[$settings['session_prefix'].'user_name'])))
			{
			$qs  = '';
			$qs .= !empty($page) ? '&amp;page='.intval($page) : '';
			$qs .= !empty($order) ? '&amp;order='.urlencode($order) : '';
			$qs .= !empty($descasc) ? '&amp;descasc='.urlencode($descasc) : '';
			$qs .= ($category > 0) ? '&amp;category='.intval($category) : '';
			echo '<a class="textlink" href="posting.php?id='.$id.$qs;
			echo '" title="'.outputLangDebugInAttributes($lang['forum_answer_linktitle']).'">';
			echo $lang['forum_answer_linkname'].'</a>';
			}
		}
	else
		{
		echo '<span class="xsmall"><img src="img/lock.gif" alt="" width="12" height="12" />';
		echo $lang['thread_locked'].'</span>';
		}

	echo '</div>'."\n";
	echo '<div class="postingedit">';
	# Menu for editing of the posting
	echo outputPostingEditMenu($entrydata, '', $opener);
	echo '</div>'."\n".'</div>'."\n";
	echo '<hr class="entryline" />'."\n";
	echo '<h3>'.$lang['whole_thread_marking'].'</h3>'."\n";
	echo outputThreads($postArray, $childArray, 'forum', 0);
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
