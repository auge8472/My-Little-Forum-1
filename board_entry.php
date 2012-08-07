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


function nav_b($be_page, $entries_per_page, $entry_count, $id, $da, $page, $category, $order, $descasc) {
global $lang, $select_submit_button;

$output = '';

if ($entry_count > $entries_per_page)
	{
	# entry_count
	# $entries_per_page
	# $be_page
	# $id
	# $da
	# $page
	# $category
	# $order
	# $descasc
	# number of pages for the thread
	$countPages = ceil($entry_count / $entries_per_page);
	# start: output
	$output .= '&nbsp;&nbsp;';
	$new_index_before = $be_page - 1;
	$new_index_after = $be_page + 1;
	if ($new_index_before >= 0)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?&amp;id='.$id.'&amp;be_page=';
		$output .= $new_index_before.'&amp;da='.$da.'&amp;page='.$page;
		$output .= ($category > 0) ? '&amp;category='.$category : '';
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.'" title="';
		$output .= outputLangDebugInAttributes($lang['previous_page_linktitle']).'"><img src="img/prev.png" alt="&laquo;"';
		$output .= 'width="12" height="9" onmouseover="this.src=\'img/prev_mo.png\';"';
		$output .= ' onmouseout="this.src=\'img/prev.png\';" /></a>';
		}
	if ($new_index_before >= 0 && $new_index_after < $countPages)
		{
		$output .= '&nbsp;';
		}
	if ($new_index_after < $countPages)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?&amp;id='.$id.'&amp;be_page=';
		$output .= $new_index_after.'&amp;da='.$da.'&amp;page='.$page;
		$output .= ($category > 0) ? '&amp;category='.$category : '';
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.'" title="';
		$output .= outputLangDebugInAttributes($lang['next_page_linktitle']).'"><img src="img/next.png" alt="&laquo;"';
		$output .= 'width="12" height="9" onmouseover="this.src=\'img/next_mo.png\';"';
		$output .= ' onmouseout="this.src=\'img/next.png\';" /></a>';
		}
	$output .= '&nbsp;<form method="get" action="'.$_SERVER["SCRIPT_NAME"].'"';
	$output .= ' title="'.outputLangDebugInAttributes($lang['choose_page_formtitle']).'">';
	$output .= "\n".'<div class="inline-form">'."\n";
	if (isset($id))
		{
		$output .= '<input type="hidden" name="id" value="'.$id.'">'."\n";
		}
	if (isset($da))
		{
		$output .= '<input type="hidden" name="da" value="'.$da.'">'."\n";
		}
	$output .= '<input type="hidden" name="page" value="'.$page.'">'."\n";
	if ($category > 0)
		{
		$output .= '<input type="hidden" name="category" value="'.$category.'">'."\n";
		}
	$output .= '<input type="hidden" name="order" value="'.$order.'">'."\n";
	$output .= '<input type="hidden" name="descasc" value ="'.$descasc.'">'."\n";
	$output .= '<select class="kat" size="1" name="be_page" onchange="this.form.submit();">'."\n";
	$output .= '<option value="0"';
	$output .= ($be_page == 0) ? ' selected="selected"' : '';
	$output .= '>1</option>'."\n";
	for ($a = 1; $a < $countPages; $a++)
		{
		$output .= '<option value="'.$a.'"';
		$output .= ($be_page == $a) ? ' selected="selected"' : '';
		$output .= '>'.($a + 1).'</option>'."\n";
		}
	$output .= '</select>'."\n".'<noscript><p class="inline-form">&nbsp;<input type="image" name="" value="" src="img/submit.png" alt="&raquo;" /></p></noscript>'."\n".'</div>'."\n".'</form>'."\n";
	}
return $output;
} # End: nav_b

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
	header("location: ".$settings['forum_address']."login.php?referer=board_entry.php".$lid);
	die("<a href=\"login.php?referer=board_entry.php".$did."\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
	&& isset($_SESSION[$settings['session_prefix'].'user_name'])
	|| $settings['access_for_users_only'] != 1)
	{

	if (empty($page)) $page = 0;
	if (empty($order)) $order = "last_answer";
	if (empty($descasc)) $descasc = "DESC";
	$category = empty($category) ? 0 : intval($category);
	$be_page = (empty($_GET['be_page'])) ? 0 : intval($_GET['be_page']);
	if (empty($da)) $da = "ASC";
	$ul = $be_page * $settings['answers_per_topic'];

	unset($entrydata);
	unset($thread);

	if (isset($id))
		{
		$id = (int) $id;
		if ($id > 0)
			{
			$firstPostingQuery = "SELECT
			id,
			tid,
			pid,
			user_id,
			DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS posting_time,
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
			text,
			show_signature,
			category,
			locked,
			INET_NTOA(ip_addr) AS ip_address
			FROM ".$db_settings['forum_table']."
			WHERE id = ".$id." LIMIT 1";
			$result_t = mysql_query($firstPostingQuery, $connid);
			$thread = mysql_fetch_assoc($result_t);
			mysql_free_result($result_t);

			# Look if id correct:
			if ($thread['pid'] != 0)
				{
				header("location: ".$settings['forum_address']."board_entry.php?id=".$thread['tid']."&page=".$page."&category=".$category."&order=".$order."&descasc=".$descasc."#p".$id);
				}

			# category of this posting accessible by user?
			if (!(isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
				{
				if (is_array($category_ids) && !in_array($thread['category'], $category_ids))
					{
					header("location: ".$settings['forum_address']."board.php");
					die('<a href="'. $settings['forum_address'] .'board.php">further...</a>');
					}
				}

			# count views:
			if (isset($settings['count_views']) && $settings['count_views'] == 1)
				{
				mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=".$id, $connid);
				}

			if ($thread["user_id"] > 0)
				{
				$userdataByIdQuery = "SELECT
				user_name,
				user_type,
				user_email,
				hide_email,
				user_hp,
				user_place,
				signature
				FROM ".$db_settings['userdata_table']."
				WHERE user_id = ".intval($thread["user_id"]);
				$userdata_result_t = mysql_query($userdataByIdQuery, $connid);
				if (!$userdata_result_t) die($lang['db_error']);
				$userdata = mysql_fetch_assoc($userdata_result_t);
				mysql_free_result($userdata_result_t);
				$thread["email"] = $userdata["user_email"];
				$thread["hide_email"] = $userdata["hide_email"];
				$thread["place"] = $userdata["user_place"];
				$thread["hp"] = $userdata["user_hp"];
				$mark = outputStatusMark($mark, $userdata["user_type"], $connid);
				if ($thread["show_signature"]==1)
					{
					$signature = $userdata["signature"];
					}
				} # End: if ($thread["user_id"] > 0)
			$allPostingsQuery = "SELECT
			id,
			tid,
			pid,
			user_id,
			DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS posting_time,
			UNIX_TIMESTAMP(time) AS time,
			UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_time,
			UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff,
			edited_by,
			name,
			email,
			subject,
			hp,
			place,
			text,
			show_signature,
			category,
			locked,
			INET_NTOA(ip_addr) AS ip_address
			FROM ".$db_settings['forum_table']."
			WHERE tid = ".intval($id)." AND id != ".intval($id)."
			ORDER BY time ".mysql_real_escape_string($da)."
			LIMIT ".$ul.", ".$settings['answers_per_topic'];
			$result = mysql_query($allPostingsQuery, $connid);
			$result_c = mysql_query("SELECT tid FROM ".$db_settings['forum_table']." WHERE tid = ".$id." AND id != ".$id, $connid);
			if(!$result or !$result_t) die($lang['db_error']);
			$thread_count = mysql_num_rows($result_c);
			mysql_free_result($result_c);
			}
		} # End: if ($thread['pid'] != 0)
	else
		{
		header("location: ".$settings['forum_address']."board.php");
		}

	if (empty($thread))
		{
		header("location: ".$settings['forum_address']."board.php");
		}

	$wo = $thread["subject"];
	$subnav_1  = '<a class="textlink" href="board.php" title="';
	$subnav_1 .= outputLangDebugInAttributes($lang['back_to_board_linktitle']).'">'.$lang['back_to_board_linkname'].'</a>';
	$cat = ($_SESSION[$settings['session_prefix'].'category'] > 0) ? '&amp;category='.intval($_SESSION[$settings['session_prefix'].'category']) : '';
	$subnav_2 = '';
	if ($da=="DESC")
		{
		$order_order = 'ASC';
		$order_title = $lang['order_linktitle_3'];
		$linktext  = $lang['order_linkname'];
		}
	else
		{
		$order_order = 'DESC';
		$order_title = $lang['order_linktitle_4'];
		$linktext  = $lang['order_linkname'];
		}
	$url  = 'board_entry.php?id='.$thread["tid"].'&amp;da='.$order_order.'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc;
	$url .= $cat;
	$class = 'order-postings';
	$title = outputLangDebugInAttributes($order_title);
	$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
	if ($settings['thread_view']==1)
		{
		$url = 'forum_entry.php?id='.$thread["tid"].$cat;
		$class = 'thread-view';
		$title = outputLangDebugInAttributes($lang['thread_view_linktitle']);
		$linktext = $lang['thread_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($settings['mix_view']==1)
		{
		$url = 'mix_entry.php?id='.$thread["tid"].'&amp;order='.$order.'&amp;descasc='.$descasc.$cat;
		$class = 'mix-view';
		$title = outputLangDebugInAttributes($lang['mix_view_linktitle']);
		$linktext = $lang['mix_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	$subnav_2 .= nav_b($be_page, $settings['answers_per_topic'], $thread_count, $thread["tid"], $da, $page, $category, $order, $descasc);

	parse_template();
	echo $header;
	echo outputDebugSession();

	# import posting template
	$sPosting = file_get_contents('data/templates/posting.board.html');
	echo '<table class="board-entry">'."\n";
	if ($be_page==0)
		{
		$posting = $sPosting;
		$pHeadline  = htmlspecialchars($thread["subject"]);
		if(isset($categories[$thread["category"]])
			&& $categories[$thread["category"]]!='')
			{
			$pHeadline .= '&nbsp;<span class="category">('.$categories[$thread["category"]].')</span>';
			}
		if ($thread['locked'] == 0)
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
				$answerlink  = '<a class="textlink" href="posting.php?id='.$thread["id"].$qs;
				$answerlink .= '&amp;view=board" title="'.outputLangDebugInAttributes($lang['board_answer_linktitle']).'">';
				$answerlink .= $lang['board_answer_linkname'].'</a>';
				}
			}
		else
			{
			$answerlink = '<span class="xsmall"><img src="img/lock.png" alt="" width="12" height="12" />'.$lang['thread_locked'].'</span>';
			}
		$ftext = ($thread["text"]=="") ? $lang['no_text'] : outputPreparePosting($thread["text"]);
		$signature = (isset($signature) && $signature != "") ? $signature = '<div class="signature">'.outputPreparePosting($settings['signature_separator']."\n".$signature, 'signature').'</div>'."\n" : '';
		# generate HTML source code of posting
		$posting = str_replace('{postingID}', 'p'.$thread['id'], $posting);
		$posting = str_replace('{postingheadline}', $pHeadline, $posting);
		$posting = str_replace('{authorinfo}', outputAuthorInfo($mark, $thread, $page, $order, 'board', $category), $posting);
		$posting = str_replace('{posting}', $ftext, $posting);
		$posting = str_replace('{signature}', $signature, $posting);
		$posting = str_replace('{answer-locked}', $answerlink, $posting);
		$posting = str_replace('{editmenu}', outputPostingEditMenu($thread, 'board', 'opener'), $posting);
		echo $posting;
		}
	$i=0;
	while ($entrydata = mysql_fetch_assoc($result))
		{
		unset($signature);
		unset($posting);
		$mark['admin'] = 0;
		$mark['mod'] = 0;
		$mark['user'] = 0;
		if ($entrydata["user_id"] > 0)
			{
			$userdataPerPostingQuery = "SELECT
			user_name,
			user_type,
			user_email,
			hide_email,
			user_hp,
			user_place,
			signature
			FROM ".$db_settings['userdata_table']."
			WHERE user_id = ".intval($entrydata["user_id"]);
			$userdata_result = mysql_query($userdataPerPostingQuery, $connid);
			if (!$userdata_result) die($lang['db_error']);
			$userdata = mysql_fetch_assoc($userdata_result);
			mysql_free_result($userdata_result);
			$entrydata["email"] = $userdata["user_email"];
			$entrydata["hide_email"] = $userdata["hide_email"];
			$entrydata["place"] = $userdata["user_place"];
			$entrydata["hp"] = $userdata["user_hp"];
			$mark = outputStatusMark($mark, $userdata["user_type"], $connid);
			if ($entrydata["show_signature"]==1)
				{
				$signature = $userdata["signature"];
				}
			}

		# Posting heraussuchen, auf das geantwortet wurde:
		$result_a = mysql_query("SELECT name FROM ".$db_settings['forum_table']." WHERE id = ".$entrydata["pid"], $connid);
		$posting_a = mysql_fetch_assoc($result_a);
		mysql_free_result($result_a);
		$entrydata['answer'] = $posting_a['name'];

		$posting = $sPosting;
		$pHeadline  = htmlspecialchars($entrydata["subject"]);
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
				$answerlink  = '<a class="textlink" href="posting.php?id='.$entrydata["id"].$qs;
				$answerlink .= '&amp;view=board" title="'.outputLangDebugInAttributes($lang['board_answer_linktitle']).'">';
				$answerlink .= $lang['board_answer_linkname'].'</a>';
				}
			}
		else
			{
			$answerlink = '<span class="xsmall"><img src="img/lock.png" alt="" width="12" height="12" />'.$lang['thread_locked'].'</span>';
			}
		$ftext = ($entrydata["text"]=="") ? $lang['no_text'] : outputPreparePosting($entrydata["text"]);
		$signature = (isset($signature) && $signature != "") ? $signature = '<div class="signature">'.outputPreparePosting($settings['signature_separator']."\n".$signature, 'signature').'</div>'."\n" : '';
		# generate HTML source code of posting
		$posting = str_replace('{postingID}', 'p'.$entrydata['id'], $posting);
		$posting = str_replace('{postingheadline}', $pHeadline, $posting);
		$posting = str_replace('{authorinfo}', outputAuthorInfo($mark, $entrydata, $page, $order, 'board', $category), $posting);
		$posting = str_replace('{posting}', $ftext, $posting);
		$posting = str_replace('{signature}', $signature, $posting);
		$posting = str_replace('{answer-locked}', $answerlink, $posting);
		$posting = str_replace('{editmenu}', outputPostingEditMenu($entrydata, 'board'), $posting);
		echo $posting;
		}
	mysql_free_result($result);
	echo '</table>'."\n";
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
