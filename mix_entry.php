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
global $settings, $connid, $lang, $db_settings, $parent_array, $child_array, $user_delete, $page, $category, $order, $descasc, $time_difference, $categories;

$singlePostingQuery = "SELECT
id,
tid,
pid,
user_id,
UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS p_time,
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
ip
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

echo '<div id="p'.intval($entrydata["id"]).'" class="mixdivl" style="margin-left: ';
echo ($tiefe==0 or $tiefe >= ($settings['max_thread_indent_mix_topic']/$settings['thread_indent_mix_topic'])) ? "0" : $settings['thread_indent_mix_topic'];
echo 'px;">'."\n";
echo '<table class="mix-entry">'."\n".'<tr>'."\n";
echo '<td class="autorcell" rowspan="2" valign="top">'."\n";
echo outputAuthorInfo($mark, $entrydata, $page, $order, 'mix', $category);
# Menu for editing of the posting
echo outputPostingEditMenu($entrydata, 'mix', $opener);
echo '<div class="autorcellwidth">&nbsp;</div></td>'."\n";
echo '<td class="titlecell" valign="top"><div class="left"><h2>';
echo htmlspecialchars($entrydata["subject"]);
if (isset($categories[$entrydata["category"]])
	&& $categories[$entrydata["category"]]!=''
	&& $entrydata["pid"]==0)
	{
	echo "&nbsp;<span class=\"category\">(".$categories[$entrydata["category"]].")</span>";
	}
echo '</h2></div>'."\n".'<div class="right">';
if ($entrydata['locked'] == 0)
	{
	$qs  = '';
	$qs .= !empty($page) ? '&amp;page='.intval($page) : '';
	$qs .= !empty($order) ? '&amp;order='.urlencode($order) : '';
	$qs .= !empty($descasc) ? '&amp;descasc='.urlencode($descasc) : '';
	$qs .= ($category > 0) ? '&amp;category='.intval($category) : '';
	echo '<a class="textlink" href="posting.php?id='.$entrydata["id"].$qs;
	echo '&amp;view=mix" title="'.outputLangDebugInAttributes($lang['board_answer_linktitle']).'">';
	echo $lang['board_answer_linkname'].'</a>';
	}
else
	{
	if ($entrydata['pid']==0)
		{
		echo '<span class="xsmall"><img src="img/lock.gif" alt="" width="12" height="12" />';
		echo $lang['thread_locked'].'</span>';
		}
	else
		{
		echo "&nbsp;";
		}
	}
echo '</div></td>'."\n";
echo '</tr><tr>'."\n";
echo '<td class="postingcell" valign="top">'."\n";
if ($entrydata["text"]=="")
	{
	echo $lang['no_text'];
	}
else
	{
	$ftext = outputPreparePosting($entrydata["text"]);
	echo '<div class="postingboard">'.$ftext.'</div>';
	}
if (isset($signature) && $signature != "")
	{
	$signature = outputPreparePosting($settings['signature_separator'].$signature, 'signature');
	echo '<div class="signature">'.$signature.'</div>';
	}

echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";

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

	if (isset($id))
		{  // Wenn $id übergeben wurde..
		$id = (int) $id;   // ... $id erst mal zu einem Integer machen ..
		if( $id > 0 )      // ... und schauen ob es größer als 0 ist ..
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
	$subnav_2 = "";
	if ($settings['thread_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="forum_entry.php?id='.$entrydata["tid"];
		$subnav_2 .= '&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc;
		$subnav_2 .= ($category > 0) ? '&amp;category='.$category : '';
		$subnav_2 .= '&amp;view=thread" class="thread-view" title="'.outputLangDebugInAttributes($lang['thread_view_linktitle']).'">';
		$subnav_2 .= $lang['thread_view_linkname'].'</a>';
		}
	if ($settings['board_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="board_entry.php?id='.$entrydata["tid"];
		$subnav_2 .= '&amp;page='.$page.'&amp;order='.$order;
		$subnav_2 .= ($category > 0) ? '&amp;category='.$category : '';
		$subnav_2 .= '&amp;view=board" class="board-view"';
		$subnav_2 .= ' title="'.outputLangDebugInAttributes($lang['board_view_linktitle']).'">';
		$subnav_2 .= $lang['board_view_linkname'].'</a></span>';
		}

	parse_template();
	echo $header;
	thread($thread, $id);
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
