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

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

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

$mark['admin'] = false;
$mark['mod'] = false;
$mark['user'] = false;

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
	if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1)
		{
		$mark['admin'] = true;
		}
	else if ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1)
		{
		$mark['mod'] = true;
		}
	else if ($userdata["user_type"] == "user" && $settings['user_highlight'] == 1)
		{
		$mark['user'] = true;
		}
	if ($entrydata["show_signature"]==1)
		{
		$signature = $userdata["signature"];
		}
	}

# Posting heraussuchen, auf das geantwortet wurde:
$result_a = mysql_query("SELECT name FROM ".$db_settings['forum_table']." WHERE id = ".$parent_array[$id]["pid"], $connid);
$posting_a = mysql_fetch_array($result_a);
mysql_free_result($result_a);

$entrydata['answer'] = $posting_a['name'];

echo '<div class="mixdivl" style="margin-left: ';
echo ($tiefe==0 or $tiefe >= ($settings['max_thread_indent_mix_topic']/$settings['thread_indent_mix_topic'])) ? "0" : $settings['thread_indent_mix_topic'];
echo 'px;">'."\n";
echo '<table class="mix-entry">'."\n".'<tr>'."\n";
echo '<td class="autorcell" rowspan="2" valign="top">'."\n";
echo outputAuthorInfo($mark, $entrydata, $page, $order, 'mix', $category);

if ($settings['user_edit'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
|| isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
|| isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
	{
	echo '<br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=';
	echo $entrydata["id"].'&amp;view=mix&amp;back='.$entrydata["tid"].'&amp;page=';
	echo $page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category=';
	echo $category.'" title="'.$lang['edit_linktitle'].'"><img src="img/edit.gif" alt=""';
	echo ' width="15" height="10" title="'.$lang['edit_linktitle'].'" />';
	echo $lang['edit_linkname'].'</a></span>';
	}
if ($settings['user_delete'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
|| isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
|| isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
	{
	echo '<br /><span class="small"><a href="posting.php?action=delete&amp;id=';
	echo $entrydata["id"].'&amp;back='.$entrydata["tid"].'&amp;view=mix&amp;page='.$page;
	echo '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
	echo '" title="'.$lang['delete_linktitle'].'"><img src="img/delete.gif" alt="" width="12"';
	echo ' height="9" title="'.$lang['delete_linktitle'].'" />'.$lang['delete_linkname'].'</a></span>';
	}
if (isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
&& $entrydata['pid'] == 0
|| isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "mod"
&& $entrydata['pid'] == 0)
	{
	echo '<br /><span class="small"><a href="posting.php?lock=true&amp;view=mix&amp;id=';
	echo $entrydata["id"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc;
	echo '&amp;category='.$category.'" title="';
	echo ($entrydata['locked'] == 0) ? $lang['lock_linktitle'] : $lang['unlock_linktitle'];
	echo '"><img src="img/lock.gif" alt="" width="12" height="12" title="';
	echo ($entrydata['locked'] == 0) ? $lang['lock_linktitle'] : $lang['unlock_linktitle'];
	echo '" />';
	echo ($entrydata['locked'] == 0) ? $lang['lock_linkname'] : $lang['unlock_linkname'];
	echo '</a></span>';
	}
echo '<div class="autorcellwidth">&nbsp;</div></td>'."\n";
echo '<td class="titlecell" valign="top"><div class="left"><h2>';
echo htmlspecialchars($entrydata["subject"]);
if(isset($categories[$entrydata["category"]])
&& $categories[$entrydata["category"]]!=''
&& $entrydata["pid"]==0)
	{
	echo "&nbsp;<span class=\"category\">(".$categories[$entrydata["category"]].")</span>";
	}
echo '</h2></div>'."\n".'<div class="right">';
if ($entrydata['locked'] == 0)
	{
	echo '<a class="textlink" href="posting.php?id='.$entrydata["id"];
	if (isset($page) && isset($order) && isset($descasc) && isset($category))
		{
		echo '&amp;page='.$page.'&amp;category='.$category;
		echo '&amp;order='.$order.'&amp;descasc='.$descasc;
		}
	echo '&amp;view=mix" title="'.$lang['board_answer_linktitle'].'">';
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
	$ftext = $entrydata["text"];
	$ftext = htmlspecialchars($ftext);
	$ftext = nl2br($ftext);
	$ftext = zitat($ftext);
	if ($settings['autolink'] == 1) $ftext = make_link($ftext);
	if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
	if ($settings['smilies'] == 1) $ftext = smilies($ftext);
	echo '<p class="postingboard">'.$ftext.'</p>';
	}
if (isset($signature) && $signature != "")
	{
	$signature = htmlspecialchars($signature);
	$signature = nl2br($signature);
	if ($settings['autolink'] == 1) $signature = make_link($signature);
	if ($settings['bbcode'] == 1) $signature = bbcode($signature);
	if ($settings['smilies'] == 1) $signature = smilies($signature);
	echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>';
	}

echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";

if(isset($child_array[$id]) && is_array($child_array[$id]))
	{
	foreach($child_array[$id] as $kind)
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
	if (isset($_GET['id'])) $id = $_GET['id']; else $id = "";
	header("location: ".$settings['forum_address']."login.php?referer=mix_entry.php&id=".$id);
	die("<a href=\"login.php?referer=mix_entry.php&id=".$id."\">further...</a>");
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
	if (empty($order)) $order="last_answer";
	if (empty($category)) $category="all";
	if (empty($descasc)) $descasc="DESC";

	if (isset($id))
		{  // Wenn $id übergeben wurde..
		$id = (int) $id;   // ... $id erst mal zu einem Integer machen ..
		if( $id > 0 )      // ... und schauen ob es größer als 0 ist ..
			{
			$result=mysql_query("SELECT tid, pid, subject, category FROM ".$db_settings['forum_table']." WHERE id = ".$id, $connid);
			if (!$result) die($lang['db_error']);
			if (mysql_num_rows($result) > 0)
				{  // überprüfen ob ein Eintrag mit dieser id in der Datenbank ist
				$entrydata = mysql_fetch_array($result); // Und ggbf. aus der Datenbank holen

				// Look if id correct:
				if ($entrydata['pid'] != 0)
					{
					header("location: ".$settings['forum_address'].basename($_SERVER['SCRIPT_NAME'])."?id=".$entrydata['tid']."&page=".$page."&category=".$category."&order=".$order."&descasc=".$descasc."#p".$id);
					}

				// category of this posting accessible by user?
				if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
					{
					if(is_array($category_ids) && !in_array($entrydata['category'], $category_ids))
						{
						header("location: ".$settings['forum_address']."mix.php");
						die();
						}
					}

				// count views:
				if (isset($settings['count_views']) && $settings['count_views'] == 1)
					{
					mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=".$id, $connid);
					}
				}
			}
		}
	if(!isset($entrydata))
		{
		header("Location: ".$settings['forum_address']."mix.php");
		exit();
		}
	$thread = $entrydata["tid"];
	$result = mysql_query("SELECT id, pid FROM ".$db_settings['forum_table']." WHERE tid = ".$thread." ORDER BY time ASC", $connid);
	if(!$result) die($lang['db_error']);

	// Ergebnisse einlesen
	while($tmp = mysql_fetch_array($result))
		{  // Ergebnis holen
		$parent_array[$tmp["id"]] = $tmp;          // Ergebnis im Array ablegen
		$child_array[$tmp["pid"]][] =  $tmp["id"]; // Vorwärtsbezüge konstruieren
		}
	mysql_free_result($result); // Aufräumen
	$wo = $entrydata["subject"];
	$subnav_1 = '<a class="textlink" href="mix.php?page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_overview_linkname'].'</a>';
	$subnav_2 = "";
	if ($settings['thread_view']==1)
		{
		$subnav_2 .= '<span class="small"><a href="forum_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category.'" title="'.$lang['thread_view_linktitle'].'"><img src="img/thread.gif" alt="" width="12" height="9" title="'.$lang['thread_view_linktitle'].'" />'.$lang['thread_view_linkname'].'</a></span>';
		}
	if ($settings['board_view']==1)
		{
		$subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="board_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;category='.$category.'" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="" width="12" height="9" title="'.$lang['board_view_linktitle'].'" />'.$lang['board_view_linkname'].'</a></span>';
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
