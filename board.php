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
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

if (count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_COOKIE['auto_login'])
&& isset($settings['autologin'])
&& $settings['autologin'] == 1)
	{
	header("location: ".$settings['forum_address']."login.php?referer=board.php");
	die("<a href=\"login.php?referer=board.php\">further...</a>");
	}

if($settings['access_for_users_only']  == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name'])
|| $settings['access_for_users_only']  != 1)
	{
	if ($settings['remember_userstandard']  == 1
	&& !isset($_SESSION[$settings['session_prefix'].'newtime']))
		{
		setcookie("user_view","board",time()+(3600*24*30));
		}

	unset($zeile);

	if (empty($page)) $page = 0;
	if (empty($order)) $order="last_answer";
	if (empty($descasc)) $descasc="DESC";
	if (isset($descasc) && $descasc=="ASC")
		{
		$descasc = "ASC";
		}
	else
		{
		$descasc = "DESC";
		}

	$ul = $page * $settings['topics_per_page'];

	# database request
	# no categories defined
	if ($categories == false)
		{
		$threadsNoCatsQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS xtime,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_time,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE pid = 0
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
   	$result = mysql_query($threadsNoCatsQuery, $connid);
		if(!$result) die($lang['db_error']);
		}
	# there are categories and all categories should be shown
	else if (is_array($categories) && $category == 0)
		{
		$threadsCatsQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS xtime,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_time,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE pid = 0 AND category IN (".$category_ids_query.")
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
		$result = mysql_query($threadsCatsQuery, $connid);
		if(!$result) die($lang['db_error']);
		}
	# there are categories and only one category should be shown
	else if (is_array($categories) && $category != 0 && in_array($category, $category_ids))
		{
		$threadsSingleCatQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS xtime,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_time,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE category = '".mysql_real_escape_string($category)."' AND pid = 0
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
		$result = mysql_query($threadsSingleCatQuery, $connid);
		if(!$result) die($lang['db_error']);
		// how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category = '".mysql_real_escape_string($category)."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}

	$category = stripslashes($category);

	$subnav_1 = outputPostingLink($category,"board");
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$subnav_2 .= '<a href="index.php?update=1&amp;view=board&amp;category='.intval($category);
		$subnav_2 .= '" class="update-postings">'.$lang['update_time_linkname'].'</a>';
		}
	if ($settings['thread_view'] == 1)
		{
		$cat = ($category != 0) ? '?category='.intval($category) : '';
		$subnav_2 .= '&nbsp;<a href="forum.php'.$cat.'" class="thread-view" title="';
		$subnav_2 .= $lang['thread_view_linktitle'].'">'.$lang['thread_view_linkname'].'</a>';
		}
	if ($settings['mix_view']==1)
		{
		$cat = ($category != 0) ? '?category='.intval($category) : '';
		$subnav_2 .= '&nbsp;<a href="mix.php'.$cat.'" class="mix-view" title="';
		$subnav_2 .= $lang['mix_view_linktitle'].'">'.$lang['mix_view_linkname'].'</a>';
		}
	$subnav_2 .= nav($page, $settings['topics_per_page'], $thread_count, $order, $descasc, $category);
	$subnav_2 .= outputCategoriesList($categories, $category);

	parse_template();
	echo $header;
	if ($thread_count > 0 && isset($result))
		{
		echo '<table class="normaltab">'."\n";
		echo '<tr class="titlerow">'."\n";
		echo '<th><a href="board.php?category='.$category.'&amp;order=subject&amp;descasc=';
		echo ($descasc=="ASC" && $order=="subject") ? 'DESC' : 'ASC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_subject_headline'].'</a>';
		if ($order=="subject" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="subject" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		if ($categories!=false && $category == 0)
			{
			echo '<th>'.$lang['board_category_headline'];
			if ($order=="category" && $descasc=="ASC")
				{
				echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
				}
			else if ($order=="category" && $descasc=="DESC")
				{
				echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
				}
			echo '</th>'."\n";
			}
		echo '<th><a href="board.php?category='.$category.'&amp;order=name&amp;descasc=';
		echo ($descasc=="ASC" && $order=="name") ? 'DESC' : 'ASC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_author_headline'].'</a>'."\n";
		if ($order=="name" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="name" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		echo '<th><a href="board.php?category='.$category.'&amp;order=time&amp;descasc=';
		echo ($descasc=="DESC" && $order=="time") ? "ASC" : "DESC";
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_date_headline'].'</a>'."\n";
		if ($order=="time" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="time" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		echo '<th>'.$lang['board_answers_headline'].'</th>'."\n";
		echo '<th><a href="board.php?category='.$category.'&amp;order=last_answer&amp;descasc=';
		echo ($descasc=="DESC" && $order=="last_answer") ? "ASC" : "DESC";
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_last_answer_headline'].'</a>'."\n";
		if ($order=="last_answer" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="last_answer" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		if (isset($settings['count_views']) && $settings['count_views'] == 1)
			{
			echo '<th>'.$lang['views_headline'].'</th>';
			}
		if (isset($_SESSION[$settings['session_prefix'].'user_type'])
		&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
			{
			echo '<th>&nbsp;</th>'."\n";
			}
		echo '</tr>';

		$i=0;
		while ($zeile = mysql_fetch_array($result))
			{
			# count replies:
			$pid_resultc = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"], $connid);
			list($answers_count) = mysql_fetch_row($pid_resultc);
			$answers_count = $answers_count - 1;
			mysql_free_result($pid_resultc);

			# data for link to last reply:
			if ($settings['last_reply_link'] == 1 or $settings['last_reply_name'] == 1)
				{
				$last_answer_result = mysql_query("SELECT name, id FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"]." ORDER BY time DESC LIMIT 1", $connid);
				$last_answer = mysql_fetch_array($last_answer_result);
				mysql_free_result($last_answer_result);
				}

			# highlight user, mods and admins:
			$mark['admin'] = false;
			$mark['mod'] = false;
			$mark['user'] = false;
			if (($settings['admin_mod_highlight'] == 1
			or $settings['user-highlight'] == 1)
			&& $zeile["user_id"] > 0)
				{
				$userdata_result=mysql_query("SELECT user_type FROM ".$db_settings['userdata_table']." WHERE user_id = '".$zeile["user_id"]."'", $connid);
				if (!$userdata_result) die($lang['db_error']);
				$userdata = mysql_fetch_array($userdata_result);
				mysql_free_result($userdata_result);
				if ($userdata['user_type'] == "admin")
					{
					$mark['admin'] = true;
					}
				else if ($userdata['user_type'] == "mod")
					{
					$mark['mod'] = true;
					}
				else if ($userdata['user_type'] == "user")
					{
					$mark['user'] = true;
					}
				}
			$rowClass = ($i % 2 == 0) ? "a" : "b";
			echo '<tr class="'.$rowClass.'">'."\n";
			echo '<td>'."\n"; # start: subject
			echo '<a class="';
			if ((isset($_SESSION[$settings['session_prefix'].'newtime'])
			&& $_SESSION[$settings['session_prefix'].'newtime'] < $zeile["last_answer"])
			|| (($zeile["pid"]==0)
			&& empty($_SESSION[$settings['session_prefix'].'newtime'])
			&& $zeile["last_answer"] > $last_visit))
				{
				echo 'threadnew';
				}
			else
				{
				echo 'thread';
				}
			echo '" href="board_entry.php?id='.$zeile["tid"];
			if ($page != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC")
				{
				echo '&amp;page='.$page.'&amp;category='.$category;
				echo '&amp;order='.$order.'&amp;descasc='.$descasc;
				}
			echo '">'.htmlspecialchars($zeile["subject"]).'</a>'."\n";
			# show sign for fixed threads
			if ($zeile["fixed"] == 1)
				{
				echo ' <img src="img/fixed.gif" width="9" height="9" title="'.$lang['fixed'].'" alt="*" />';
				}
			if ($settings['all_views_direct'] == 1)
				{
				echo " <span class=\"small\">";
				if ($settings['thread_view']==1)
					{
					echo '<a href="forum_entry.php?id='.$zeile["tid"].'"><img src="img/thread_d.gif"';
					echo ' alt="[Thread]" title="'.$lang['open_in_thread_linktitle'].'" width="12" height="9" /></a>';
					}
				if ($settings['mix_view'] == 1)
					{
					echo '<a href="mix_entry.php?id='.$zeile["tid"].'"><img src="img/mix_d.gif"';
					echo ' alt="[Mix]" title="'.$lang['open_in_mix_linktitle'].'" width="12" height="9" /></a>&nbsp;';
					}
				echo "</span>";
				}
			echo '</td>'."\n"; # end: subject
			if ($categories!=false && $category == 0)
				{
				echo '<td>'."\n"; # start: categories (if in use)
				if (isset($categories[$zeile["category"]]) && $categories[$zeile["category"]]!='')
					{
					echo '<a title="'.str_replace("[category]", $categories[$zeile["category"]], $lang['choose_category_linktitle']);
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo " ".$lang['admin_mod_category'];
						}
					else if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						echo " ".$lang['registered_users_category'];
						}
					echo '" href="board.php?category='.$zeile["category"].'"><span class="';
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo "category-adminmod-b";
						}
					elseif (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						echo "category-regusers-b";
						}
					else
						{
						echo "category-b";
						}
					echo '">'.$categories[$zeile["category"]].'</span></a>';
					}
				else
					{
					echo "&nbsp;";
					}
				echo '</td>'."\n"; # end: categories
				}
			echo '<td>'."\n"; # start: authors names
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				$sult = str_replace("[name]", htmlspecialchars($zeile["name"]), $lang['show_userdata_linktitle']);
				echo '<a href="user.php?id='.$zeile["user_id"].'" title="'.$sult.'">';
				}
			echo outputAuthorsName($zeile["name"], $mark, $zeile["user_id"]);
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				echo '</a>';
				}
			echo '</td>'."\n"; # end: authors names
			echo '<td><span class="small">'.strftime($lang['time_format'],$zeile["xtime"]).'</span></td>'."\n"; # number of answers
			echo '<td><span class="small">'.$answers_count.'</span></td>'."\n";
			echo '<td><span class="small">'; # start: last reply
			if ($answers_count > 0)
				{
				if ($settings['last_reply_link']==1)
					{
					echo '<a href="board_entry.php?id='.$zeile["tid"].'&amp;be_page=';
					echo (ceil($answers_count / $settings['answers_per_topic'])-1).'&amp;page='.$page;
					echo '&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc;
					echo '#p'.$last_answer['id'].'" title="'.str_replace("[name]", $last_answer['name'], $lang['last_reply_lt']).'">';
					}
				echo strftime($lang['time_format'],$zeile["la_time"]);
				if ($settings['last_reply_name'] == 1)
					{
					echo (!empty($last_answer['name'])) ? ' ('.$last_answer['name'].')' : '';
					}
				if ($settings['last_reply_link']==1)
					{
					echo '</a>';
					}
				}
			else
				{
				echo "&nbsp;";
				}
			echo '</span></td>'."\n"; # end: last reply
			if ($settings['count_views'] == 1)
				{
				# number of views
				echo '<td><span class="small">'.$zeile['views'].'</span></td>'."\n";
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
				{
				echo '<td><a href="admin.php?mark='.$zeile["tid"].'&amp;refer=';
				echo basename($_SERVER["SCRIPT_NAME"]).'&amp;page='.$page;
				echo '&amp;category='.$category.'&amp;order='.$order.'">';
				if ($zeile['marked']==1)
					{
					echo '<img src="img/marked.gif" alt="[x]" width="9" height="9" title="'.$lang['unmark_linktitle'].'" />';
					}
				else
					{
					echo '<img src="img/mark.gif" alt="[-]" title="'.$lang['mark_linktitle'].'" width="9" height="9" />';
					}
				echo '</a></td>'."\n";
				}
			echo '</tr>';
			$i++;
			} # End: while ()
		echo "\n".'</table>'."\n";
		mysql_free_result($result);
		echo outputManipulateMarked('board');
		} # End: if ($thread_count > 0 && isset($result))
	else
		{
		echo "<p>";
		echo ($category!=0) ? $lang['no_messages_in_category'] : $lang['no_messages'];
		echo "</p>\n";
		}
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
