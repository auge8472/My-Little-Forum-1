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

# import vars:
if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

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
	if (empty($page)) $page = 0;
	if (empty($order)) $order="time";
	if (isset($descasc) && $descasc=="ASC")
		{
		$descasc="DESC";
		$page = 0;
		}
	else
		{
		$descasc="DESC";
		}

	if ($order != "time" && $order !="last_answer")
		{
		$page = 0;
		$order="time";
		}
	$ul = $page * $settings['topics_per_page'];
	unset($parent_array);
	unset($child_array);

	# database request
	# no categories defined
	if ($categories === false)
		{
		$result = mysql_query("SELECT id, pid, tid FROM ".$db_settings['forum_table']." WHERE pid = 0 ORDER BY fixed DESC, ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['topics_per_page'], $connid);
		if (!$result) die($lang['db_error']);
		}
	# there are categories and all categories should be shown
	else if (is_array($categories) && $category == 0)
		{
		$result = mysql_query("SELECT id, pid, tid FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category IN (".$category_ids_query.") ORDER BY fixed DESC, ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['topics_per_page'], $connid);
		if (!$result) die($lang['db_error']);
		}
	# there are categories and only one category should be shown
	else if (is_array($categories) && $category != 0 && in_array($category, $category_ids))
		{
		$result = mysql_query("SELECT id, pid, tid FROM ".$db_settings['forum_table']." WHERE category = '".mysql_real_escape_string($category)."' AND pid = 0 ORDER BY fixed DESC, ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['topics_per_page'], $connid);
		if (!$result) die($lang['db_error']);
		# how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category = '".mysql_real_escape_string($category)."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}

	$subnav_1 = outputPostingLink($category);
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		# onmouseover="this.src=\'img/update_mo.gif\';" onmouseout="this.src=\'img/update.gif\';"
		$subnav_2 .= '<a href="index.php?update=1';
		$subnav_2 .= ($category > 0) ? '&amp;category='.intval($category) : '';
		$subnav_2 .= '" class="update-postings" title="'.$lang['update_time_linktitle'].'">';
		$subnav_2 .= $lang['update_time_linkname'].'</a>';
		}
	if ($order=="time")
		{
		$subnav_2 .= '&nbsp;<a href="forum.php?order=last_answer';
		$subnav_2 .= ($category > 0) ? '&amp;category='.intval($category) : '';
		$subnav_2 .= '" class="order-postings" title="'.$lang['order_linktitle_1'];
		$subnav_2 .= '">'.$lang['order_linkname'].'</a>';
		}
	else
		{
		$subnav_2 .= '&nbsp;<a href="forum.php?order=time';
		$subnav_2 .= ($category > 0) ? '&amp;category='.intval($category) : '';
		$subnav_2 .= '" class="order-postings" title="'.$lang['order_linktitle_2'];
		$subnav_2 .= '">'.$lang['order_linkname'].'</a>';
		}
	if ($settings['board_view'] == 1)
		{
		$cat = ($category > 0) ? '?category='.intval($category) : '';
		$cat .= !empty($cat) ? '&amp;view=board' : '?view=board';
		$subnav_2 .= '&nbsp;<a href="board.php'.$cat.'" class="board-view" title="';
		$subnav_2 .= $lang['board_view_linktitle'].'">'.$lang['board_view_linkname'].'</a>';
		}
	if ($settings['mix_view']==1)
		{
		$cat = ($category > 0) ? '?category='.intval($category) : '';
		$cat .= !empty($cat) ? '&amp;view=mix' : '?view=mix';
		$subnav_2 .= '&nbsp;<a href="mix.php'.$cat.'" class="mix-view" title="';
		$subnav_2 .= $lang['mix_view_linktitle'].'">'.$lang['mix_view_linkname'].'</a>';
		}
	$subnav_2 .= nav($page, (int)$settings['topics_per_page'], $thread_count, $order, $descasc, $category);

	$subnav_2 .= outputCategoriesList($categories, $category);

	parse_template();
	echo $header;

	if ($thread_count > 0 && isset($result))
		{
		while ($zeile = mysql_fetch_assoc($result))
			{
			$thread_result = mysql_query("SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS tp_time, UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked, fixed FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"]." ORDER BY time ASC", $connid);

			# put result into arrays:
			while ($tmp = mysql_fetch_assoc($thread_result))
				{
				$parent_array[$tmp["id"]] = $tmp;
				$child_array[$tmp["pid"]][] =  $tmp["id"];
				}
			echo '<ul class="thread">'."\n";
			# display the thread tree
			thread_tree($zeile["id"]);
			echo '</ul>'."\n";
			mysql_free_result($thread_result);
			}
		echo outputManipulateMarked();
		}
	else
		{
		echo '<p>';
		echo ($category!=0) ? $lang['no_messages_in_category'] : $lang['no_messages'];
		echo '</p>'."\n";
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
