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
include_once("functions/include.prepare.php");


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
	# process the standard parameters
	# and put them into the session
	processStandardParametersGET();
	unset($zeile);

#	if (empty($page)) $page = 0;
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

	$ul = $_SESSION[$settings['session_prefix'].'page'] * $settings['topics_per_page'];

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
	# there are categories and only one category should be shown
	else if (is_array($categories) && $category != 0 && in_array($category, $category_ids))
		{
		$threadsQueryWhere = " AND category = '".mysql_real_escape_string($category)."'";
		// how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category = '".mysql_real_escape_string($category)."'", $connid);
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
		subject,
		category,
		views,
		fixed,
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

	$category = stripslashes($category);

	$subnav_1 = outputPostingLink($category,"board");
	$cat = ($category > 0) ? '&amp;category='.intval($category) : '';
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$url  = 'index.php?update=1&amp;view=board';
		$url .= $cat;
		$class = 'update-postings';
		$title = outputLangDebugInAttributes($lang['update_time_linktitle']);
		$linktext = $lang['update_time_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($settings['thread_view'] == 1)
		{
		$url = 'forum.php?view=thread';
		$url .= $cat;
		$class = 'thread-view';
		$title = outputLangDebugInAttributes($lang['thread_view_linktitle']);
		$linktext = $lang['thread_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($settings['mix_view']==1)
		{
		$url = 'mix.php?view=mix';
		$url .= $cat;
		$class = 'mix-view';
		$title = outputLangDebugInAttributes($lang['mix_view_linktitle']);
		$linktext = $lang['mix_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	$subnav_2 .= nav($_SESSION[$settings['session_prefix'].'page'], $settings['topics_per_page'], $thread_count, $order, $descasc, $category);
	$subnav_2 .= outputCategoriesList($categories, $category);

	parse_template();
	echo $header;
	# start output of SESSION values (testcase)
	echo '<pre>'.print_r($_SESSION, true).'</pre>'."\n";
	# end output of SESSION values (testcase)
	if ($thread_count > 0 && isset($threadsResult))
		{
		$currDescAsc = strtolower($descasc);
		echo '<table class="normaltab">'."\n";
		echo '<tr class="titlerow">'."\n";
		echo '<th><a href="board.php?order=subject&amp;descasc=';
		echo ($descasc=="ASC" && $order=="subject") ? 'DESC' : 'ASC';
		echo $cat;
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_subject_headline'].'</a>';
		if ($order=="subject")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		if ($categories!=false && $category == 0)
			{
			echo '<th><a href="board.php?order=category&amp;descasc=';
			echo ($descasc=="ASC" && $order=="category") ? 'DESC' : 'ASC';
			echo $cat;
			echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_category_headline'].'</a>';
			if ($order=="category")
				{
				echo outputImageDescAsc($currDescAsc);
				}
			echo '</th>'."\n";
			}
		echo '<th><a href="board.php?order=name&amp;descasc=';
		echo ($descasc=="ASC" && $order=="name") ? 'DESC' : 'ASC';
		echo $cat;
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_author_headline'].'</a>'."\n";
		if ($order=="name")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		echo '<th><a href="board.php?order=time&amp;descasc=';
		echo ($descasc=="DESC" && $order=="time") ? "ASC" : "DESC";
		echo $cat;
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_date_headline'].'</a>'."\n";
		if ($order=="time")
			{
			echo outputImageDescAsc($currDescAsc);
			}
		echo '</th>'."\n";
		echo '<th>'.$lang['board_answers_headline'].'</th>'."\n";
		echo '<th><a href="board.php?order=last_answer&amp;descasc=';
		echo ($descasc=="DESC" && $order=="last_answer") ? "ASC" : "DESC";
		echo $cat;
		echo '" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['board_last_answer_headline'].'</a>'."\n";
		if ($order=="last_answer")
			{
			echo outputImageDescAsc($currDescAsc);
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
		while ($zeile = mysql_fetch_assoc($threadsResult))
			{
			# count replies:
			$answers_count = outputGetReplies($zeile["tid"], $connid);

			# data for link to last reply:
			if ($settings['last_reply_link'] == 1 or $settings['last_reply_name'] == 1)
				{
				$last_answer = outputGetLastReply($zeile["tid"], $connid);
				}

			# highlight user, mods and admins:
			if (($settings['admin_mod_highlight'] == 1
			or $settings['user-highlight'] == 1)
			&& $zeile["posters_id"] > 0)
				{
				$mark = outputStatusMark($mark, $zeile['user_type'], $connid);
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
			if ($_SESSION[$settings['session_prefix'].'page'] != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC")
				{
				echo '&amp;page='.$_SESSION[$settings['session_prefix'].'page'].'&amp;category='.$category;
				echo '&amp;order='.$order.'&amp;descasc='.$descasc;
				}
			echo '">'.htmlspecialchars($zeile["subject"]).'</a>'."\n";
			# show sign for fixed threads
			if ($zeile["fixed"] == 1)
				{
				echo ' <img src="img/fixed.png" width="9" height="9" title="';
				echo outputLangDebugInAttributes($lang['fixed']).'" alt="*" />';
				}
			if ($settings['all_views_direct'] == 1)
				{
				echo ' <span class="small">';
				if ($settings['thread_view']==1)
					{
					echo '<a href="forum_entry.php?id='.$zeile["tid"].'&amp;view=thread';
					echo ($category > 0) ? '&amp;category='.$category : '';
					echo '"><img src="img/thread_d.png" alt="[Thread]" title="';
					echo outputLangDebugInAttributes($lang['open_in_thread_linktitle']).'" width="12" height="9" /></a>';
					}
				if ($settings['mix_view'] == 1)
					{
					echo '<a href="mix_entry.php?id='.$zeile["tid"].'&amp;view=mix';
					echo ($category > 0) ? '&amp;category='.$category : '';
					echo '"><img src="img/mix_d.png" alt="[Mix]" title="';
					echo outputLangDebugInAttributes($lang['open_in_mix_linktitle']).'" width="12" height="9" /></a>';
					}
				echo "</span>";
				}
			echo '</td>'."\n"; # end: subject
			if ($categories!=false && $category == 0)
				{
				echo '<td class="info">'."\n"; # start: categories (if in use)
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
			echo '<td class="info">'."\n"; # start: authors names
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["posters_id"] > 0)
				{
				$sult = str_replace("[name]", htmlspecialchars($zeile["name"]), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
				echo '<a href="user.php?id='.$zeile["posters_id"].'" title="'.$sult.'">';
				}
			echo outputAuthorsName($zeile["name"], $mark, $zeile["posters_id"]);
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["posters_id"] > 0)
				{
				echo '</a>';
				}
			echo '</td>'."\n"; # end: authors names
			echo '<td class="info">'.$zeile["Uhrzeit"].'</td>'."\n";
			echo '<td class="number-cell">'.$answers_count.'</td>'."\n";
			echo '<td class="info">'; # start: last reply
			if ($answers_count > 0)
				{
				if ($settings['last_reply_link']==1)
					{
					echo '<a href="board_entry.php?id='.$zeile["tid"].'&amp;be_page=';
					echo (ceil($answers_count / $settings['answers_per_topic'])-1).'&amp;page='.$_SESSION[$settings['session_prefix'].'page'];
					echo ($category > 0) ? '&amp;category='.$category : '';
					echo '&amp;order='.$order.'&amp;descasc='.$descasc.'#p'.$last_answer['id'];
					echo '" title="'.str_replace("[name]", $last_answer['name'], outputLangDebugInAttributes($lang['last_reply_lt'])).'">';
					}
				echo $zeile["la_Uhrzeit"];
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
			echo '</td>'."\n"; # end: last reply
			if ($settings['count_views'] == 1)
				{
				# number of views
				echo '<td class="number-cell">'.$zeile['views'].'</td>'."\n";
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
				{
				echo '<td><a href="admin.php?mark='.$zeile["tid"].'&amp;refer=';
				echo basename($_SERVER["SCRIPT_NAME"]).'&amp;page='.$_SESSION[$settings['session_prefix'].'page'];
				echo ($category > 0) ? '&amp;category='.$category : '';
				echo '&amp;order='.$order.'">';
				if ($zeile['marked']==1)
					{
					echo '<img src="img/marked.png" alt="[x]" width="9" height="9" title="'.outputLangDebugInAttributes($lang['unmark_linktitle']).'" />';
					}
				else
					{
					echo '<img src="img/mark.png" alt="[-]" title="'.outputLangDebugInAttributes($lang['mark_linktitle']).'" width="9" height="9" />';
					}
				echo '</a></td>'."\n";
				}
			echo '</tr>';
			$i++;
			} # End: while ()
		echo "\n".'</table>'."\n";
		mysql_free_result($threadsResult);
		echo outputManipulateMarked('board');
		} # End: if ($thread_count > 0 && isset($result))
	else
		{
		# import posting template
		$output = file_get_contents('data/templates/locked.gen.html');
		$output = str_replace('{locked_hl}', $lang['caution'], $output);
		$langTemp = ($category!=0) ? $lang['no_messages_in_category'] : $lang['no_messages'];
		$output = str_replace('{locked_txt}', $langTemp, $output);
		echo $output;
		}
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
