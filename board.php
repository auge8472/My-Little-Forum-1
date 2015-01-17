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
	die('<a href="login.php?referer=board.php">further...</a>');
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
	$ul = $_SESSION[$settings['session_prefix'].'page'] * $settings['topics_per_page'];

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
		$threadsQueryWhere = " AND category IN (".$category_ids_query.")";
		}
	# there are categories and only one category should be shown
	else if (is_array($categories)
		&& $_SESSION[$settings['session_prefix'].'category'] != 0
		&& in_array($_SESSION[$settings['session_prefix'].'category'], $category_ids))
		{
		$threadsQueryWhere = " AND category = '". mysql_real_escape_string($_SESSION[$settings['session_prefix'].'category']) ."'";
		// how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category = '". mysql_real_escape_string($_SESSION[$settings['session_prefix'].'category']) ."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}
	# list all threads
	$threadsQuery = "SELECT
		tid,
		pid,
		tid AS viewID,
		user_id AS posters_id,
		DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS Uhrzeit,
		DATE_FORMAT(last_answer + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS la_Uhrzeit,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		(SELECT SUM(views) FROM ".$db_settings['forum_table']." WHERE tid = viewID) AS views,
		fixed,
		marked,
		user_type
		FROM ".$db_settings['posting_view']."
		WHERE pid = 0".$threadsQueryWhere."
		ORDER BY fixed DESC, ".$_SESSION[$settings['session_prefix'].'order']." ".$_SESSION[$settings['session_prefix'].'descasc']."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
	$threadsResult = mysql_query($threadsQuery, $connid);
	if (!$threadsResult) die($lang['db_error']);

	$subnav_1 = outputPostingLink($_SESSION[$settings['session_prefix'].'category'], "board");
	$pagination = ($_SESSION[$settings['session_prefix'].'page'] > 0) ? '&amp;page='.$_SESSION[$settings['session_prefix'].'page'] : '';
	$cat = ($_SESSION[$settings['session_prefix'].'category'] > 0) ? '&amp;category='.intval($_SESSION[$settings['session_prefix'].'category']) : '';
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$url  = 'index.php?update=1'. $pagination.$cat;
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
	$subnav_2 .= nav($_SESSION[$settings['session_prefix'].'page'], (int)$settings['topics_per_page'], $thread_count, $_SESSION[$settings['session_prefix'].'order'], $_SESSION[$settings['session_prefix'].'descasc'], $_SESSION[$settings['session_prefix'].'category']);
	$subnav_2 .= outputCategoriesList($categories, $_SESSION[$settings['session_prefix'].'category']);

	parse_template();
	echo $header;
	echo outputDebugSession();

	if ($thread_count > 0 && isset($threadsResult))
		{
		$xmlFile = dirname($_SERVER["SCRIPT_FILENAME"]) .'/data/templates/view.board.main.xml';
		$xml = simplexml_load_file($xmlFile, null, LIBXML_NOCDATA);
		$templAll = $xml->wholetable;
		$tHeader = $xml->header;
		$tBody = $xml->body;
		$currDescAsc = strtolower($_SESSION[$settings['session_prefix'].'descasc']);
		# generate output for subject
		$ordSubject = ($_SESSION[$settings['session_prefix'].'descasc'] == "ASC"
		&& $_SESSION[$settings['session_prefix'].'order'] == "subject") ? 'DESC' : 'ASC';
		$qsSubject = 'order=subject&amp;descasc='. $ordSubject.$cat;
		$tHeader = str_replace('{QuerySortSubject}', $qsSubject, $tHeader);
		$tHeader = str_replace('{SortTitleSubject}', outputLangDebugInAttributes($lang['order_linktitle']), $tHeader);
		$tHeader = str_replace('{Subject}', htmlspecialchars($lang['board_subject_headline']), $tHeader);
		$sorterSubject = ($_SESSION[$settings['session_prefix'].'order'] == "subject") ? outputImageDescAsc($currDescAsc) : '';
		$tHeader = str_replace('{SorterSubject}', $sorterSubject, $tHeader);
		# generate output for author
		$ordAuthor = ($_SESSION[$settings['session_prefix'].'descasc'] == "ASC"
		&& $_SESSION[$settings['session_prefix'].'order'] == "name") ? 'DESC' : 'ASC';
		$qsAuthor = 'order=name&amp;descasc='. $ordAuthor.$cat;
		$tHeader = str_replace('{QuerySortAuthor}', $qsAuthor, $tHeader);
		$tHeader = str_replace('{SortTitleAuthor}', outputLangDebugInAttributes($lang['order_linktitle']), $tHeader);
		$tHeader = str_replace('{Author}', htmlspecialchars($lang['board_author_headline']), $tHeader);
		$sorterAuthor = ($_SESSION[$settings['session_prefix'].'order'] == "name") ? outputImageDescAsc($currDescAsc) : '';
		$tHeader = str_replace('{SorterAuthor}', $sorterAuthor, $tHeader);
		# generate output for date
		$ordDate = ($_SESSION[$settings['session_prefix'].'descasc'] == "ASC"
		&& $_SESSION[$settings['session_prefix'].'order'] == "time") ? 'DESC' : 'ASC';
		$qsDate = 'order=time&amp;descasc='. $ordDate.$cat;
		$tHeader = str_replace('{QuerySortDate}', $qsDate, $tHeader);
		$tHeader = str_replace('{SortTitleDate}', outputLangDebugInAttributes($lang['order_linktitle']), $tHeader);
		$tHeader = str_replace('{Date}', htmlspecialchars($lang['board_date_headline']), $tHeader);
		$sorterDate = ($_SESSION[$settings['session_prefix'].'order'] == "time") ? outputImageDescAsc($currDescAsc) : '';
		$tHeader = str_replace('{SorterDate}', $sorterDate, $tHeader);
		# generate output for answers quantity
		$tHeader = str_replace('{CountAnswers}', htmlspecialchars($lang['board_answers_headline']), $tHeader);
		# generate output for last answer
		$ordLastAnswer = ($_SESSION[$settings['session_prefix'].'descasc'] == "ASC"
		&& $_SESSION[$settings['session_prefix'].'order'] == "last_answer") ? 'DESC' : 'ASC';
		$qsLastAnswer = 'order=last_answer&amp;descasc='. $ordLastAnswer.$cat;
		$tHeader = str_replace('{QueryLastAnswer}', $qsLastAnswer, $tHeader);
		$tHeader = str_replace('{SortTitleLastAnswer}', outputLangDebugInAttributes($lang['order_linktitle']), $tHeader);
		$tHeader = str_replace('{LastAnswer}', htmlspecialchars($lang['board_last_answer_headline']), $tHeader);
		$sorterLastAnswer = ($_SESSION[$settings['session_prefix'].'order'] == "last_answer") ? outputImageDescAsc($currDescAsc) : '';
		$tHeader = str_replace('{SorterLastAnswer}', $sorterLastAnswer, $tHeader);
		# generate output for category (if categories are defined and if no category is picked)
		if ($categories !== false
		&& $_SESSION[$settings['session_prefix'].'category'] == 0)
			{
			$tCat = $xml->optheadercats;
			$ordCategories = ($_SESSION[$settings['session_prefix'].'descasc'] == "ASC"
			&& $_SESSION[$settings['session_prefix'].'order'] == "category") ? 'DESC' : 'ASC';
			$qsCategories = 'order=category&amp;descasc='. $ordCategories.$cat;
			$tCat = str_replace('{QuerySortCategory}', $qsCategories, $tCat);
			$tCat = str_replace('{SortTitleCategory}', outputLangDebugInAttributes($lang['order_linktitle']), $tCat);
			$tCat = str_replace('{Category}', htmlspecialchars($lang['board_category_headline']), $tCat);
			$sorterCategories = ($_SESSION[$settings['session_prefix'].'order'] == "category") ? outputImageDescAsc($currDescAsc) : '';
			$tCat = str_replace('{SorterCategory}', $sorterCategories, $tCat);
			}
		# generate output for views quantity (if activated)
		if (isset($settings['count_views']) && $settings['count_views'] == 1)
			{
			$tViews = $xml->optheaderviews;
			$tViews = str_replace('{Views}', htmlspecialchars($lang['views_headline']), $tViews);
			}
		if (isset($_SESSION[$settings['session_prefix'].'user_type'])
		&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
			{
			$tMark = $xml->optheadermarker;
			}
		$headerCat = (!empty($tCat)) ? $tCat : '';
		$headerViews = (!empty($tViews)) ? $tViews : '';
		$headerMark = (!empty($tMark)) ? $tMark : '';
		$tHeader = str_replace('{thCategory}', "\n".$headerCat, $tHeader);
		$tHeader = str_replace('{thViews}', "\n".$headerViews, $tHeader);
		$tHeader = str_replace('{thMarker}', "\n".$headerMark, $tHeader);
		$r = '';
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
			$tRow = $tBody;
			# generate output for thread subject
			if ((isset($_SESSION[$settings['session_prefix'].'newtime'])
			&& $_SESSION[$settings['session_prefix'].'newtime'] < $zeile["last_answer"])
			|| (($zeile["pid"]==0)
			&& empty($_SESSION[$settings['session_prefix'].'newtime'])
			&& $zeile["last_answer"] > $last_visit))
				{
				$newPosting = 'threadnew';
				}
			else
				{
				$newPosting = 'thread';
				}
			$tRow = str_replace('{ThreadNewPosting}', $newPosting, $tRow);
			$tRow = str_replace('{ThreadID}', intval($zeile["tid"]), $tRow);
			$tRow = str_replace('{ThreadSubject}', htmlspecialchars($zeile["subject"]), $tRow);
			# show sign for fixed threads
			$tips = '';
			if ($zeile["fixed"] == 1)
				{
				$tips .= ' <img src="img/fixed.png" width="9" height="9" title="'. outputLangDebugInAttributes($lang['fixed']) .'" alt="*" />';
				}
			if ($settings['all_views_direct'] == 1)
				{
				$Targets = array('{Target}', '{Query}', '{Image}', '{Alt}', '{Title}');
				$otherViews = '<a href="{Target}{Query}"><img src="{Image}" alt="{Alt}" title="{Title}" width="12" height="9" /></a>';
				$tips .= ' <span class="small">';
				if ($settings['thread_view']==1)
					{
					$tipp = $otherViews;
					$Strikes = array(
					'forum_entry.php',
					'?id='. intval($zeile["tid"]),
					'img/thread_d.png',
					'[Thread]',
					outputLangDebugInAttributes($lang['open_in_thread_linktitle']));
					$tips .= str_replace($Targets, $Strikes, $tipp);
					}
				if ($settings['mix_view'] == 1)
					{
					$tipp = $otherViews;
					$Strikes = array(
					'mix_entry.php',
					'?id='. intval($zeile["tid"]),
					'img/mix_d.png',
					'[Mix]',
					outputLangDebugInAttributes($lang['open_in_mix_linktitle']));
					$tips .= str_replace($Targets, $Strikes, $tipp);
					}
				$tips .= "</span>";
				}
			$tRow = str_replace('{Tips}', $tips, $tRow);
			# generate output for thread author
			$tAuthor = '';
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["posters_id"] > 0)
				{
				$sult = str_replace("[name]", htmlspecialchars($zeile["name"]), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
				$tAuthor .= '<a href="user.php?id='.$zeile["posters_id"].'" title="'.$sult.'">';
				}
			$tAuthor .= outputAuthorsName($zeile["name"], $mark, $zeile["posters_id"]);
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["posters_id"] > 0)
				{
				$tAuthor .= '</a>';
				}
			$tRow = str_replace('{ThreadAuthor}', $tAuthor, $tRow);
			# generate output for thread date
			$tRow = str_replace('{ThreadDate}', htmlspecialchars($zeile["Uhrzeit"]), $tRow);
			# generate output for threads answers quantity
			$tRow = str_replace('{ThreadAnswers}', htmlspecialchars($answers_count), $tRow);
			# generate output for link to the last reply
			$tLastAnswer = '';
			if ($answers_count > 0)
				{
				if ($settings['last_reply_link']==1)
					{
					$tLastAnswer .= '<a href="board_entry.php?id='.$zeile["tid"].'&amp;be_page=';
					$tLastAnswer .= (ceil($answers_count / $settings['answers_per_topic'])-1);
					$tLastAnswer .= '&amp;order='. $_SESSION[$settings['session_prefix'].'order'] .'#p'.$last_answer['id'];
					$tLastAnswer .= '" title="'. str_replace("[name]", $last_answer['name'], outputLangDebugInAttributes($lang['last_reply_lt'])).'">';
					}
				$tLastAnswer .= $zeile["la_Uhrzeit"];
				if ($settings['last_reply_name'] == 1)
					{
					$tLastAnswer .= (!empty($last_answer['name'])) ? ' ('. htmlspecialchars($last_answer['name']) .')' : '';
					}
				if ($settings['last_reply_link']==1)
					{
					$tLastAnswer .= '</a>';
					}
				}
			else
				{
				$tLastAnswer .= "&nbsp;";
				}
			$tRow = str_replace('{ThreadLastAnswer}', $tLastAnswer, $tRow);
			# generate output for category og the thread
			if ($categories!=false
			&& $_SESSION[$settings['session_prefix'].'category'] == 0)
				{
				# start: categories (if in use)
				$tCat = $xml->optbodycats;
				if (isset($categories[$zeile["category"]]) && $categories[$zeile["category"]]!='')
					{
					$tCategory = '<a title="'. str_replace("[category]", $categories[$zeile["category"]], outputLangDebugInAttributes($lang['choose_category_linktitle']));
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						$tCategory .= " ". outputLangDebugInAttributes($lang['admin_mod_category']);
						}
					else if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						$tCategory .= " ". outputLangDebugInAttributes($lang['registered_users_category']);
						}
					$tCategory .= '" href="board.php?category='.$zeile["category"].'"><span class="';
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						$tCategory .= "category-adminmod-b";
						}
					elseif (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						$tCategory .= "category-regusers-b";
						}
					else
						{
						$tCategory .= "category-b";
						}
					$tCategory .= '">'.$categories[$zeile["category"]].'</span></a>';
					}
				else
					{
					$tCategory = '&nbsp;';
					}
				$tCat = str_replace('{ThreadCategory}', $tCategory, $tCat);
				}
			if ($settings['count_views'] == 1)
				{
				# number of views
				$tViews = $xml->optbodyviews;
				$tViews = str_replace('{ThreadViews}', htmlspecialchars($zeile['views']), $tViews);
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
				{
				$tMark = $xml->optbodymarker;
				$tMark = str_replace('{MarkID}', intval($zeile["tid"]), $tMark);
				$tMark = str_replace('{MarkRefer}', urlencode(basename($_SERVER["SCRIPT_NAME"])), $tMark);
				$tMark = str_replace('{MarkPage}', intval($_SESSION[$settings['session_prefix'].'page']), $tMark);
				$tMark = str_replace('{MarkOrder}', urlencode($_SESSION[$settings['session_prefix'].'order']), $tMark);
				$markCat = ($_SESSION[$settings['session_prefix'].'category'] > 0) ? '&amp;category='. urlencode($_SESSION[$settings['session_prefix'].'category']) : '';
				$tMark = str_replace('{MarkCategory}', $markCat, $tMark);
				if ($zeile['marked']==1)
					{
					$tMark = str_replace('{MarkSrc}', 'img/marked.png', $tMark);
					$tMark = str_replace('{MarkAlt}', '[x]', $tMark);
					$tMark = str_replace('{MarkTitle}', outputLangDebugInAttributes($lang['unmark_linktitle']), $tMark);
					}
				else
					{
					$tMark = str_replace('{MarkSrc}', 'img/mark.png', $tMark);
					$tMark = str_replace('{MarkAlt}', '[-]', $tMark);
					$tMark = str_replace('{MarkTitle}', outputLangDebugInAttributes($lang['mark_linktitle']), $tMark);
					}
				}
			$rowCat = (!empty($tCat)) ? $tCat : '';
			$rowViews = (!empty($tViews)) ? $tViews : '';
			$rowMark = (!empty($tMark)) ? $tMark : '';
			$tRow = str_replace('{trCategory}', "\n".$rowCat, $tRow);
			$tRow = str_replace('{trViews}', "\n".$rowViews, $tRow);
			$tRow = str_replace('{trMarker}', "\n".$rowMark, $tRow);
			$r .= $tRow;
			} # End: while ()
		mysql_free_result($threadsResult);
		$templAll = str_replace('{tp-Headline}', $tHeader, $templAll);
		$templAll = str_replace('{tp-Rows}', $r, $templAll);
		echo $templAll;
		echo outputManipulateMarked('board');
		} # End: if ($thread_count > 0 && isset($result))
	else
		{
		# import posting template
		$output = file_get_contents('data/templates/locked.gen.html');
		$output = str_replace('{locked_hl}', $lang['caution'], $output);
		$langTemp = ($_SESSION[$settings['session_prefix'].'category']!=0) ? $lang['no_messages_in_category'] : $lang['no_messages'];
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
