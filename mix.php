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
	header("location: ".$settings['forum_address']."login.php?referer=mix.php");
	die("<a href=\"login.php?referer=mix.php\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name'])
|| $settings['access_for_users_only']  != 1)
	{
	if ($settings['remember_userstandard'] == 1
	&& !isset($_SESSION[$settings['session_prefix'].'newtime']))
		{
		setcookie("user_view","mix",time()+(3600*24*30));
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

	# Variablen korrekt (de)initialisieren
	unset($parent_array);
	unset($child_array);

	# use next results somewhere around line 280
	# database request
	# no categories defined
	if ($categories === false)
		{
		$threadsQueryWhere = '';
		}
	# there are categories and all categories should be shown
	else if (is_array($categories) && $category == 0)
		{
		$threadsQueryWhere = " AND category IN (".$category_ids_query.")";
		}
	# there are categories and one category should be shown
	else if (is_array($categories) && $category != 0 && in_array($category, $category_ids))
		{
		$threadsQueryWhere = " AND category = '".mysql_real_escape_string($category)."'";
		# how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = '0' AND category = '".mysql_real_escape_string($category)."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}
	# list all threads
	$threadsQuery = "SELECT
		tid,
		t1.user_id AS posters_id,
		DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS Uhrzeit,
		DATE_FORMAT(last_answer + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS la_Uhrzeit,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		category,
		views,
		(SELECT
			user_type
			FROM ".$db_settings['userdata_table']."
			WHERE ".$db_settings['userdata_table'].".user_id = posters_id) AS user_type
		FROM ".$db_settings['forum_table']." AS t1
		WHERE pid = 0".$threadsQueryWhere."
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
	$threadsResult = mysql_query($threadsQuery, $connid);
	if (!$threadsResult) die($lang['db_error']);

	$subnav_1 = outputPostingLink($category,"mix");
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$subnav_2 .= '<a href="index.php?update=1&amp;view=mix';
		$subnav_2 .= ($category > 0) ? '&amp;category='.intval($category) : '';
		$subnav_2 .= '" class="update-postings" title="'.outputLangDebugInAttributes($lang['update_time_linktitle']).'">';
		$subnav_2 .= $lang['update_time_linkname'].'</a>';
		}
	if ($settings['thread_view'] == 1)
		{
		$cat  = ($category > 0) ? '?category='.intval($category) : '';
		$cat .= !empty($cat) ? '&amp;view=thread' : '?view=thread';
		$subnav_2 .= '&nbsp;<a href="forum.php'.$cat.'" class="thread-view" title="';
		$subnav_2 .= outputLangDebugInAttributes($lang['thread_view_linktitle']).'">'.$lang['thread_view_linkname'].'</a>';
		}
	if ($settings['board_view']==1)
		{
		$cat  = ($category > 0) ? '?category='.intval($category) : '';
		$cat .= !empty($cat) ? '&amp;view=board' : '?view=board';
		$subnav_2 .= '&nbsp;<a href="board.php'.$cat.'" class="board-view" title="';
		$subnav_2 .= outputLangDebugInAttributes($lang['board_view_linktitle']).'">'.$lang['board_view_linkname'].'</a>';
		}
	$subnav_2 .= nav($page, $settings['topics_per_page'], $thread_count, $order, $descasc, $category);
	$categories = get_categories();
	$subnav_2 .= outputCategoriesList($categories, $category);

	parse_template();
	echo $header;

	if ($thread_count > 0 && isset($threadsResult))
		{
		$currDescAsc = strtolower($descasc);
		echo '<table class="normaltab">'."\n";
		echo '<tr class="titlerow">'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=subject&amp;descasc=';
		echo ($descasc=="ASC" && $order=="subject") ? 'DESC' : 'ASC';
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_subject_headline'].'</a>';
		if ($order=="subject")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		if ($categories != false && $category == 0)
			{
			echo '<th><a href="mix.php?category='.$category.'&amp;order=category&amp;descasc=';
			echo ($descasc=="ASC" && $order=="category") ? 'DESC' : 'ASC';
			echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_category_headline'].'</a>';
			if ($order=="category")
				{
				echo outputImageDescAsc($currDescAsc);
				}
			echo '</th>'."\n";
			}
		echo '<th><a href="mix.php?category='.$category.'&amp;order=name&amp;descasc=';
		echo ($descasc=="ASC" && $order=="name") ? 'DESC' : 'ASC';
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_author_headline'].'</a>';
		if ($order=="name")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=time&amp;descasc=';
		echo ($descasc=="DESC" && $order=="time") ? 'ASC' : 'DESC';
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_date_headline'].'</a>';
		if ($order=="time")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		echo '<th>'.$lang['board_answers_headline'].'</th>'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=last_answer&amp;descasc=';
		echo ($descasc=="DESC" && $order=="last_answer") ? 'ASC' : 'DESC';
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_last_answer_headline'].'</a>';
		if ($order=="last_answer")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		if (isset($settings['count_views']) && $settings['count_views'] == 1)
			{
			echo '<th>'.$lang['views_headline'].'</th>'."\n";
			}
		if (isset($_SESSION[$settings['session_prefix'].'user_type'])
		&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
			{
			echo '<th>&nbsp;</th>'."\n";
			}
		echo '</tr>';

		$i = 0;
		while ($zeile = mysql_fetch_assoc($threadsResult))
			{
			# read entries of thread
			$threadCompleteQuery = "SELECT
			id,
			pid,
			tid,
			user_id,
			DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS Uhrzeit,
			name,
			subject,
			category,
			marked,
			fixed,
			views
			FROM ".$db_settings['forum_table']."
			WHERE tid = ".$zeile["tid"]."
			ORDER BY time DESC";
			$rawresult = dbaseAskDatabase($threadCompleteQuery, $connid);
			# Ergebnisse einlesen:
			foreach ($rawresult as $tmp)
				{
				$postArray[$tmp["id"]] = $tmp;           // Ergebnis im Array ablegen
				$childArray[$tmp["pid"]][] = $tmp["id"]; // Vorwärtsbezüge konstruieren
				}
			# count replies:
			$answers_count = outputGetReplies($zeile["tid"], $connid);

			# data for link to last reply:
			if ($settings['last_reply_link'] == 1)
				{
				$last_answer_result = mysql_query("SELECT name, id FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"]." ORDER BY time DESC LIMIT 1", $connid);
				$last_answer = mysql_fetch_assoc($last_answer_result);
				mysql_free_result($last_answer_result);
				}
			# generate output of thread lists
			# highlight user, mods and admins:
			if (!empty($zeile['user_type'])
			and ($settings['admin_mod_highlight'] == 1
			or $settings['user-highlight'] == 1))
				{
				$markA = outputStatusMark($mark, $zeile['user_type'], $connid);
				}
			$rowClass = ($i % 2 == 0) ? "a" : "b";
			echo '<tr class="'.$rowClass.'">'."\n";
			echo ' <td>'.outputThreads($postArray, $childArray, 'mix', 2).'</td>'."\n";
			if ($categories!=false && $category == 0)
				{
				echo '<td class="info">'."\n"; #categories
				if (isset($categories[$zeile["category"]]) && $categories[$zeile["category"]]!='')
					{
					echo '<a title="'.str_replace("[category]", $categories[$zeile["category"]], outputLangDebugInAttributes($lang['choose_category_linktitle']));
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo " ".outputLangDebugInAttributes($lang['admin_mod_category']);
						}
					else if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						echo " ".outputLangDebugInAttributes($lang['registered_users_category']);
						}
					echo '" href="mix.php?category='.$zeile["category"].'"><span class="';
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo "category-adminmod-b";
						}
					else if (isset($category_accession[$zeile["category"]])
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
				echo '</td>'."\n";
				}
			# author op
			echo '<td class="info">'."\n";
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				$sult = str_replace("[name]", htmlspecialchars($zeile["name"]), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
				echo '<a href="user.php?id='.$zeile["user_id"].'" title="'.$sult.'">';
				}
			echo outputAuthorsName($zeile["name"], $markA, $zeile["user_id"]);
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				echo '</a>';
				}
			echo '</td>'."\n";
			# time op
			echo '<td class="info">'.$zeile["Uhrzeit"].'</td>'."\n";
			# number of answers
			echo '<td class="number-cell">'.$answers_count.'</td>'."\n";
			# date last answer
			echo '<td class="info">';
			if ($answers_count > 0)
				{
				if ($settings['last_reply_link']==1)
					{
					echo '<a href="mix_entry.php?id='.$zeile["tid"].'&amp;page='.$page;
					echo ($category > 0) ? '&amp;category='.$category : '';
					echo '&amp;order='.$order.'&amp;descasc=';
					echo $descasc.'#p'.$last_answer['id'].'" title="';
					echo str_replace("[name]", $last_answer['name'], outputLangDebugInAttributes($lang['last_reply_lt'])).'">';
					}
				echo $zeile["la_Uhrzeit"];
				if ($settings['last_reply_link']==1)
					{
					echo '</a>';
					}
				}
			else
				{
				echo "&nbsp;";
				}
			echo '</td>'."\n";
			if (isset($settings['count_views']) && $settings['count_views'] == 1)
				{
				# number of views
				echo '<td class="number-cell">'.$zeile['views'].'</td>'."\n";
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
				{
				# marker for admin
				echo '<td><a href="admin.php?mark='.$zeile["tid"].'&amp;refer=';
				echo basename($_SERVER["SCRIPT_NAME"]).'&amp;page='.$page;
				echo ($category > 0) ? '&amp;category='.$category : '';
				echo '&amp;order='.$order.'">';
				if ($zeile['marked']==1)
					{
					echo '<img src="img/marked.png" alt="[x]" width="9" height="9"';
					echo ' title="'.outputLangDebugInAttributes($lang['unmark_linktitle']).'" />';
					}
				else
					{
					echo '<img src="img/mark.png" alt="[-]" title="';
					echo outputLangDebugInAttributes($lang['mark_linktitle']).'" width="9" height="9" />';
					}
				echo '</a></td>'."\n";
				}
			echo '</tr>'."\n";
			unset($rawresult, $childArray, $postArray);
			$i++;
			}
		echo "\n".'</table>'."\n";
		mysql_free_result($threadsResult);
		echo outputManipulateMarked('mix');
		}
	else
		{
		if ($category!=0) echo "<p>".$lang['no_messages_in_category']."</p>";
		else echo "<p>".$lang['no_messages']."</p>";
		}
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
