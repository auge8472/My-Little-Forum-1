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


function thread($id, $aktuellerEintrag = 0, $tiefe = 0) {
global $settings, $connid, $lang, $db_settings, $parent_array, $child_array, $user_delete, $page, $category, $order, $descasc, $time_difference, $categories, $mark, $sPosting;

$singlePostingQuery = "SELECT
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
category,
show_signature,
locked,
INET_NTOA(ip_addr) AS ip_address
FROM ".$db_settings['forum_table']."
WHERE id = '".$parent_array[$id]["id"]."'
ORDER BY time ASC";
$posting_result = mysql_query($singlePostingQuery, $connid);
if(!$posting_result) die($lang['db_error']);
$entrydata = mysql_fetch_assoc($posting_result);
mysql_free_result($posting_result);

if ($entrydata["user_id"] > 0)
	{
	$userdata_result = mysql_query("SELECT user_name, user_type, user_email, hide_email, user_hp, user_place, signature FROM ".$db_settings['userdata_table']." WHERE user_id = '".$entrydata["user_id"]."'", $connid);
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
$result_a = mysql_query("SELECT name FROM ".$db_settings['forum_table']." WHERE id = ".$parent_array[$id]["pid"], $connid);
$posting_a = mysql_fetch_assoc($result_a);
mysql_free_result($result_a);

$opener = ($entrydata['pid'] == 0) ? 'opener' : '';
$entrydata['answer'] = $posting_a['name'];

$pHeadline  = htmlspecialchars($entrydata["subject"]);
if (isset($categories[$entrydata["category"]])
	&& $categories[$entrydata["category"]]!=''
	&& $entrydata["pid"]==0)
	{
	$pHeadline .= "&nbsp;<span class=\"category\">(".$categories[$entrydata["category"]].")</span>";
	}
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
	if ($entrydata['pid']==0)
		{
		$answerlink = '<span class="xsmall"><img src="img/lock.png" alt="" width="12" height="12" />';
		$answerlink = $lang['thread_locked'].'</span>';
		}
	else
		{
		$answerlink = "&nbsp;";
		}
	}
$ftext = ($entrydata["text"]=="") ? $lang['no_text'] : outputPreparePosting($entrydata["text"]);
$signature = (isset($signature) && $signature != "") ? $signature = '<div class="signature">'.outputPreparePosting($settings['signature_separator'].$signature, 'signature').'</div>'."\n" : '';
# generate HTML source code of posting
$posting = $sPosting;
$posting = str_replace('{postingID}', 'p'.$entrydata['id'], $posting);
$posting = str_replace('{postingheadline}', $pHeadline, $posting);
$posting = str_replace('{authorinfo}', outputAuthorInfo($mark, $entrydata, $page, $order, 'mix', $category), $posting);
$posting = str_replace('{posting}', $ftext, $posting);
$posting = str_replace('{signature}', $signature, $posting);
$posting = str_replace('{answer-locked}', $answerlink, $posting);
$posting = str_replace('{editmenu}', outputPostingEditMenu($entrydata, 'mix', $opener), $posting);

echo '<div id="p'.intval($entrydata["id"]).'" class="mixdivl" style="margin-left: ';
echo ($tiefe==0 or $tiefe >= ($settings['max_thread_indent_mix_topic']/$settings['thread_indent_mix_topic'])) ? "0" : $settings['thread_indent_mix_topic'];
echo 'px;">'."\n";
echo $posting;
unset($posting);

if (isset($child_array[$id]) && is_array($child_array[$id]))
	{
	foreach ($child_array[$id] as $kind)
		{
		thread($kind, $aktuellerEintrag, $tiefe+1);
		}
	}
echo '</div>'."\n";
} # End: thread

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
	header("location: ".$settings['forum_address']."login.php?referer=mix_entry.php".$lid);
	die("<a href=\"login.php?referer=mix_entry.php".$did."\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
	&& isset($_SESSION[$settings['session_prefix'].'user_name'])
	|| $settings['access_for_users_only'] != 1)
	{
	# deinitialise unused variables
	unset($entrydata);
	unset($parent_array);
	unset($child_array);

	if (empty($page)) $page = 0;
	if (empty($order)) $order = "last_answer";
	$category = empty($category) ? 0 : intval($category);
	if (empty($descasc)) $descasc = "DESC";

	if (isset($_GET['id']))
		{
		# Wenn $id übergeben wurde ...
		$id = intval($_GET['id']);	# ... $id erst mal zu einem Integer machen ...
		if ($id > 0)	# ... und schauen ob es größer als 0 ist ...
			{
			$result = mysql_query("SELECT tid, pid, subject, category FROM ".$db_settings['forum_table']." WHERE id = ".$id, $connid);
			if (!$result) die($lang['db_error']);
			# is an entry with this id present?
			if (mysql_num_rows($result) > 0)
				{
				# Und ggf. aus der Datenbank holen
				$entrydata = mysql_fetch_assoc($result);

				# Look if id correct:
				if ($entrydata['pid'] != 0)
					{
					# if not:
					header("location: ".$settings['forum_address']."mix_entry.php?id=".intval($entrydata['tid'])."&page=".$page."&category=".$category."&order=".$order."&descasc=".$descasc."#p".intval($id));
					}

				# category of this posting accessible by user?
				if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
					{
					if (is_array($category_ids) && !in_array($entrydata['category'], $category_ids))
						{
						header("location: ".$settings['forum_address']."mix.php");
						die();
						}
					}

				# count views:
				if (isset($settings['count_views']) && $settings['count_views'] == 1)
					{
					mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=".$id, $connid);
					}
				}
			}
		}

	if (!isset($entrydata))
		{
		header("Location: ".$settings['forum_address']."mix.php");
		exit();
		}

	$thread = $entrydata["tid"];
	$result = mysql_query("SELECT id, pid FROM ".$db_settings['forum_table']." WHERE tid = ".$thread." ORDER BY time ASC", $connid);
	if (!$result) die($lang['db_error']);

	// Ergebnisse einlesen
	while ($tmp = mysql_fetch_assoc($result))
		{  // Ergebnis holen
		$parent_array[$tmp["id"]] = $tmp;          // Ergebnis im Array ablegen
		$child_array[$tmp["pid"]][] =  $tmp["id"]; // Vorwärtsbezüge konstruieren
		}
	mysql_free_result($result); // Aufräumen

	$wo = $entrydata["subject"];
	$subnav_1  = '<a class="textlink" href="mix.php?page='.$page;
	$subnav_1 .= ($category > 0) ? '&amp;category='.$category : '';
	$subnav_1 .= '&amp;order='.$order.'&amp;descasc='.$descasc.'">';
	$subnav_1 .= $lang['back_to_overview_linkname'].'</a>';
	$cat = ($category > 0) ? '&amp;category='.intval($category) : '';
	$subnav_2 = "";
	if ($settings['thread_view']==1)
		{
		$url = 'forum_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;view=thread';
		$url .= $cat;
		$class = 'thread-view';
		$title = outputLangDebugInAttributes($lang['thread_view_linktitle']);
		$linktext = $lang['thread_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}
	if ($settings['board_view']==1)
		{
		$url = 'board_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;view=board';
		$url .= $cat;
		$class = 'board-view';
		$title = outputLangDebugInAttributes($lang['board_view_linktitle']);
		$linktext = $lang['board_view_linkname'];
		$subnav_2 .= outputSingleLink($url, $linktext, $title, $class);
		}

	parse_template();
	# import posting template
	$sPosting = file_get_contents('data/templates/posting.mix.html');
	echo $header;
	echo outputDebugSession();
	thread($thread, $id);
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
