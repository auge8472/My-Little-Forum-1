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

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

include("inc.php");

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  if (isset($_GET['id'])) $id = $_GET['id']; else $id = "";
  header("location: login.php?referer=forum_entry.php&id=".$id);
  die("<a href=\"login.php?referer=forum_entry.php&id=".$id."\">further...</a>");
 }

if ($settings['access_for_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only'] != 1)
{

 unset($entrydata);
 unset($parent_array);
 unset($child_array);

 if (empty($page)) $page = 0;
 if (empty($order)) $order="time";

 if (isset($id)) $id = (int)$id;
 if(isset($id) && $id > 0)
  {
   $result=mysql_query("SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS p_time,
                        UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_time,
                        UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff, edited_by, user_id, name, email,
                        subject, hp, place, ip, text, show_signature, category, locked, ip FROM ".$db_settings['forum_table']." WHERE id = ".$id, $connid);
   if (!$result) die($lang['db_error']);
   if (mysql_num_rows($result) == 1)
    {
     $entrydata = mysql_fetch_array($result);
     mysql_free_result($result);

     // category of this posting accessible by user?
     if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
      {
       if(is_array($category_ids) && !in_array($entrydata['category'], $category_ids))
        {
         header("location: forum.php");
         die();
        }
      }

     if (isset($settings['count_views']) && $settings['count_views'] == 1) mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE id=".$id, $connid);

     $mark_admin = false;
     $mark_mod = false;
     if ($entrydata["user_id"] > 0)
     {
      $userdata_result=mysql_query("SELECT user_name, user_type, user_email, hide_email, user_hp, user_place, signature FROM ".$db_settings['userdata_table']." WHERE user_id = '".$entrydata["user_id"]."'", $connid);
      if (!$userdata_result) die($lang['db_error']);
      $userdata = mysql_fetch_array($userdata_result);
      mysql_free_result($userdata_result);
      $entrydata["email"] = $userdata["user_email"];
      $entrydata["hide_email"] = $userdata["hide_email"];
      $entrydata["place"] = $userdata["user_place"];
      $entrydata["hp"] = $userdata["user_hp"];
      if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1) $mark_admin = true;
      elseif ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1) $mark_mod = true;
      if ($entrydata["show_signature"]==1) $signature = $userdata["signature"];
     }
   }
   else { header("location: forum.php"); die(); }
  }
 else { header("location: forum.php"); die(); }

 // thread-data:
 $Thread = $entrydata["tid"];

 $result = mysql_query("SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS tp_time,
                        UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked FROM ".$db_settings['forum_table']."
                        WHERE tid = ".$Thread." ORDER BY time ASC", $connid);
 if(!$result) die($lang['db_error']);


 while($tmp = mysql_fetch_array($result))
  {
   $parent_array[$tmp["id"]] = $tmp;
   $child_array[$tmp["pid"]][] =  $tmp["id"];
  }

 mysql_free_result($result);

$category = stripslashes($category);

$wo = $entrydata["subject"];
$subnav_1 = '<a class="textlink" href="forum.php?page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order.'" title="'.$lang['back_to_forum_linktitle'].'">'.$lang['back_to_forum_linkname'].'</a>';
$subnav_2 = "";
if ($settings['board_view']==1) $subnav_2 .= '<span class="small"><a href="board_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;category='.urlencode($category).'" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';
if ($settings['mix_view']==1) $subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="mix_entry.php?id='.$entrydata["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;category='.urlencode($category).'" title="'.$lang['mix_view_linktitle'].'"><img src="img/mix.gif" alt="" />'.$lang['mix_view_linkname'].'</a></span>';
parse_template();
echo $header;
?><h2 class="postingheadline"><?php echo htmlsc(stripslashes($entrydata["subject"])); ?><?php if (isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='') { ?> <span class="category">(<?php echo $categories[$entrydata["category"]]; ?>)</span><?php } ?></h2>
<?php
$email_hp = ""; $place = ""; $place_c = "";
if (empty($entrydata["hide_email"])) $entrydata["hide_email"]=0;
if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1 or $entrydata["hp"]!="") { $email_hp = " "; }
if ($entrydata["hp"]!="") { if (substr($entrydata["hp"],0,7) != "http://" && substr($entrydata["hp"],0,8) != "https://" && substr($entrydata["hp"],0,6) != "ftp://" && substr($entrydata["hp"],0,9) != "gopher://" && substr($entrydata["hp"],0,7) != "news://") $entrydata["hp"] = "http://".$entrydata["hp"]; $email_hp .= "<a href=\"" . $entrydata["hp"] ."\" title=\"".htmlsc(stripslashes($entrydata["hp"]))."\"><img src=\"img/homepage.gif\" alt=\"".$lang['homepage_alt']."\" width=\"13\" height=\"13\" /></a>"; }
if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) && $entrydata["hp"]!="") { $email_hp .= "&nbsp;"; }

if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1 && isset($page) && isset($order) && isset($category)) { $email_hp .= '<a href="contact.php?id='.$entrydata["id"].'&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order.'"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>'; }
elseif ($entrydata["email"]!="" && $entrydata["hide_email"] != 1) { $email_hp .= '<a href="contact.php?id='.$entrydata["id"].'" title="'.str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['email_to_user_linktitle']).'"><img src="img/email.gif" alt="'.$lang['email_alt'].'" width="16" height="16" /></a>'; }

if ($entrydata["place"] != "") { $place_c = htmlsc(stripslashes($entrydata["place"])) . ", "; $place = htmlsc(stripslashes($entrydata["place"])); }

if ($mark_admin==true) $name = "<span class=\"admin-highlight\">".htmlsc(stripslashes($entrydata["name"]))."</span>";
elseif ($mark_mod==true) $name = "<span class=\"mod-highlight\">".htmlsc(stripslashes($entrydata["name"]))."</span>";
else $name = htmlsc(stripslashes($entrydata["name"]));

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] > 0 && $settings['show_registered'] ==1)
 {
  $lang['show_userdata_linktitle'] = str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['show_userdata_linktitle']);
  $lang['forum_author_marking'] = str_replace("[name]", "<a href=\"user.php?id=".$entrydata["user_id"]."\" title=\"".$lang['show_userdata_linktitle']."\"><b>".$name."</b><img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" title=\"".$lang['registered_user_title']."\" /></a>", $lang['forum_author_marking']);
 }
elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] > 0 && $settings['show_registered'] !=1)
 {
  $lang['show_userdata_linktitle'] = str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['show_userdata_linktitle']);
  $lang['forum_author_marking'] = str_replace("[name]", "<a href=\"user.php?id=".$entrydata["user_id"]."\" title=\"".$lang['show_userdata_linktitle']."\"><b>".$name."</b></a>", $lang['forum_author_marking']);
 }
elseif (!isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] > 0 && $settings['show_registered'] ==1)
 {
  $lang['forum_author_marking'] = str_replace("[name]", $name."<img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" title=\"".$lang['registered_user_title']."\" />", $lang['forum_author_marking']);
 }
else
 {
  $lang['forum_author_marking'] = str_replace("[name]", $name, $lang['forum_author_marking']);
 }
   $lang['forum_author_marking'] = str_replace("[email_hp]", $email_hp, $lang['forum_author_marking']);
   $lang['forum_author_marking'] = str_replace("[place, ]", $place_c, $lang['forum_author_marking']);
   $lang['forum_author_marking'] = str_replace("[place]", $place, $lang['forum_author_marking']);
   $lang['forum_author_marking'] = str_replace("[time]", strftime($lang['time_format'],$entrydata["p_time"]), $lang['forum_author_marking']);
   $lang['forum_edited_marking'] = str_replace("[name]", htmlsc(stripslashes($entrydata["edited_by"])), $lang['forum_edited_marking']);
   $lang['forum_edited_marking'] = str_replace("[time]", strftime($lang['time_format'],$entrydata["e_time"]), $lang['forum_edited_marking']);
   ?><p class="author"><?php
   echo $lang['forum_author_marking'];
   if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "mod") { ?> &nbsp;<span class="xsmall"><?php echo $entrydata['ip']; ?></span><?php }
   if ($entrydata["edited_diff"] > 0 && $entrydata["edited_diff"] > $entrydata["time"] && $settings['show_if_edited'] == 1) { ?><br /><span class="xsmall"><?php echo $lang['forum_edited_marking']; ?></span><?php }
   ?></p><?php
   if ($entrydata["text"]=="")
                        {
                         echo $lang['no_text'];
                        }
                       else
                        {
                         $ftext=$entrydata["text"];
                         $ftext = htmlsc(stripslashes($ftext));
                         $ftext = nl2br($ftext);
                         $ftext = zitat($ftext);
                         if ($settings['autolink'] == 1) $ftext = make_link($ftext);
                         if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
                         if ($settings['smilies'] == 1) $ftext = smilies($ftext);
                         ?><p class="posting"><?php echo $ftext; ?></p><?php
                        }
                       if (isset($signature) && $signature != "")
                        {
                         $signature = htmlsc(stripslashes($signature));
                         $signature = nl2br($signature);
                         #$signature = zitat($signature);
                         if ($settings['autolink'] == 1) $signature = make_link($signature);
                         if ($settings['bbcode'] == 1) $signature = bbcode($signature);
                         if ($settings['smilies'] == 1) $signature = smilies($signature);
                         ?><p class="signature"><?php echo $settings['signature_separator'].$signature; ?></p><?php
                        }
                      ?>
<div class="postingbottom"><div class="postinganswer"><?php if ($entrydata['locked'] == 0) { ?><a class="textlink" href="posting.php?id=<?php echo $id; if (isset($page) && isset($order) && isset($category)) { ?>&amp;page=<?php echo $page; ?>&amp;category=<?php echo urlencode($category); ?>&amp;order=<?php echo $order; } ?>" title="<?php echo $lang['forum_answer_linktitle']; ?>"><?php echo $lang['forum_answer_linkname']; ?></a><?php } else { ?><span class="xsmall"><img src="img/lock.gif" alt="" width="12" height="12" /><?php echo $lang['thread_locked']; ?></span><?php } ?></div>
<div class="postingedit">&nbsp;<?php if ($settings['user_edit'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix'].'user_id'] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "mod") { ?><span class="small"><a href="posting.php?action=edit&amp;id=<?php echo $entrydata["id"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;category=<?php echo urlencode($category); ?>" title="<?php echo $lang['edit_linktitle']; ?>"><img src="img/edit.gif" alt="" width="15" height="10" /><?php echo $lang['edit_linkname']; ?></a></span><?php } if ($settings['user_delete'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix'].'user_id'] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "mod") { ?>&nbsp;&nbsp;<span class="small"><a href="posting.php?action=delete&amp;id=<?php echo $entrydata["id"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;category=<?php echo urlencode($category); ?>" title="<?php echo $lang['delete_linktitle']; ?>"><img src="img/delete.gif" alt="" width="12" height="9" /><?php echo $lang['delete_linkname']; ?></a></span><?php } if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin" && $entrydata['pid'] == 0 || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix'].'user_type'] == "mod" && $entrydata['pid'] == 0) { ?>&nbsp;&nbsp;<span class="small"><a href="posting.php?lock=true&amp;id=<?php echo $entrydata["id"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;category=<?php echo urlencode($category); ?>" title="<?php if ($entrydata['locked'] == 0) echo $lang['lock_linktitle']; else echo $lang['unlock_linktitle']; ?>"><img src="img/lock.gif" alt="" width="12" height="12" /><?php if ($entrydata['locked'] == 0) echo $lang['lock_linkname']; else echo $lang['unlock_linkname']; ?></a></span><?php } ?></div></div>
<hr class="entryline" />
<p><b><?php echo $lang['whole_thread_marking']; ?></b></p>
<ul class="thread"><?php thread_tree($Thread, $id); ?></ul>
<?php
echo $footer;
}
else { header("location: login.php?msg=noaccess"); die("<a href=\"login.php?msg=noaccess\">further...</a>"); }
?>
