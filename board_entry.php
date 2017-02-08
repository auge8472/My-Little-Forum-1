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

function nav_b($be_page, $entries_per_page, $entry_count, $id, $da, $nr, $page, $category, $order, $descasc)
 {
  global $lang, $page_navigation_pd_menu, $page_navigation_arrows, $select_submit_button;
  $output = '';
  if ($entry_count > $entries_per_page)
   {
    $output .= '&nbsp;&nbsp;';
    $new_index_before = $be_page - 1;
    $new_index_after = $be_page + 1;
    $site_count = ceil($entry_count / $entries_per_page);
    if ($new_index_before >= 0) $output .= '<a href="'.basename($_SERVER["PHP_SELF"]).'?&amp;id='.$id.'&amp;be_page='.$new_index_before.'&amp;da='.$da.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'" title="'.$lang['previous_page_linktitle'].'"><img src="img/prev.gif" alt="&laquo;" width="12" height="9" onmouseover="this.src=\'img/prev_mo.gif\';" onmouseout="this.src=\'img/prev.gif\';" /></a>';
    if ($new_index_before >= 0 && $new_index_after < $site_count)$output .= '&nbsp;';
    if ($new_index_after < $site_count) $output .= '<a href="'.basename($_SERVER["PHP_SELF"]).'?&amp;id='.$id.'&amp;be_page='.$new_index_after.'&amp;da='.$da.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'" title="'.$lang['next_page_linktitle'].'"><img src="img/next.gif" alt="&laquo;" width="12" height="9" onmouseover="this.src=\'img/next_mo.gif\';" onmouseout="this.src=\'img/next.gif\';" /></a>';
    $page_count = ceil($entry_count/$entries_per_page);
    $output .= '&nbsp;&nbsp;<form method="get" action="'.basename($_SERVER["PHP_SELF"]).'" title="'.$lang['choose_page_formtitle'].'"><div style="display: inline;">';
    if (isset($id)) $output .= '<input type="hidden" name="id" value="'.$id.'">';
    if (isset($da)) $output .= '<input type="hidden" name="da" value="'.$da.'">';
    $output .= '<input type="hidden" name="page" value="'.$page.'"><input type="hidden" name="category" value="'.$category.'"><input type="hidden" name="order" value="'.$order.'"><input type="hidden" name="descasc" value ="'.$descasc.'">';
    $output .= '<select class="kat" size="1" name="be_page" onchange="this.form.submit();">';
    if ($be_page == 0) $output .= '<option value="0" selected="selected">1</option>';
    else $output .= '<option value="0">1</option>';
    for($x=$be_page-9; $x<$be_page+9; $x++)
     {
      if ($x > 0 && $x < $page_count-1)
       {
        if ($be_page == $x) $output .= '<option value="'.$x.'" selected="selected">'.($x+1).'</option>';
        else $output .= '<option value="'.$x.'">'.($x+1).'</option>';
       }
     }
    if ($be_page+1 == $page_count) $output .= '<option value="'.($page_count-1).'" selected="selected">'.$page_count.'</option>';
    else $output .= '<option value="'.($page_count-1).'">'.$page_count.'</option>';
    $output .= '</select>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></div></form>';
   }
  return $output;
 }

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  if (isset($_GET['id'])) $id = $_GET['id']; else $id = "";
  header("location: login.php?referer=board_entry.php&id=".$id);
  die("<a href=\"login.php?referer=board_entry.php&id=".$id."\">further...</a>");
 }

 if ($settings['access_for_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only'] != 1)
{

 if (empty($page)) $page = 0;
 if (empty($order)) $order="last_answer";
 if (empty($descasc)) $descasc="DESC";
 if (empty($category)) $category="all";
 if (empty($be_page)) $be_page = 0;
 if (empty($da)) $da="ASC";
 $ul = $be_page * $settings['answers_per_topic'];

 unset($entrydata);
 unset($thread);

 if(isset($id))
  {
   $id = (int) $id;
   if( $id > 0 )
    {
     $result_t=mysql_query("SELECT id, tid, pid, user_id, UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
                        UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_Uhrzeit,
                        UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff, edited_by, user_id, name, email,
                        subject, hp, place, text, show_signature, category, locked, ip FROM ".$db_settings['forum_table']." WHERE id = ".$id." LIMIT 1", $connid);
     $thread = mysql_fetch_assoc($result_t);
     mysql_free_result($result_t);

     // Look if id correct:
     if ($thread['pid'] != 0) header("location: ".basename($_SERVER['PHP_SELF'])."?id=".$thread['tid']."&page=".$page."&category=".$category."&order=".$order."&descasc=".$descasc."#p".$id);

     // category of this posting accessible by user?
     if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
      {
       if(is_array($category_ids) && !in_array($thread['category'], $category_ids))
        {
         header("location: board.php");
         die();
        }
      }

     // count views:
     if (isset($settings['count_views']) && $settings['count_views'] == 1) mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=".$id, $connid);

     $mark_admin = false;
     $mark_mod = false;
     if ($thread["user_id"] > 0)
      {
       $userdata_result_t=mysql_query("SELECT user_name, user_type, user_email, hide_email, user_hp, user_place, signature FROM ".$db_settings['userdata_table']." WHERE user_id = '".$thread["user_id"]."'", $connid);
       if (!$userdata_result_t) die($lang['db_error']);
       $userdata = mysql_fetch_assoc($userdata_result_t);
       mysql_free_result($userdata_result_t);
       $thread["email"] = $userdata["user_email"];
       $thread["hide_email"] = $userdata["hide_email"];
       $thread["place"] = $userdata["user_place"];
       $thread["hp"] = $userdata["user_hp"];
       if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1) $mark_admin = true;
       elseif ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1) $mark_mod = true;
       if ($thread["show_signature"]==1) $signature = $userdata["signature"];
      }
     $result=mysql_query("SELECT id, tid, pid, user_id, UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
                        UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_Uhrzeit,
                        UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff, edited_by, user_id, name, email,
                        subject, hp, place, text, show_signature, category, locked, ip FROM ".$db_settings['forum_table']." WHERE tid = ".$id." AND id != ".$id." ORDER BY time ".$da." LIMIT ".$ul.", ".$settings['answers_per_topic'], $connid);

     $result_c = mysql_query("SELECT tid FROM ".$db_settings['forum_table']." WHERE tid = ".$id." AND id != ".$id, $connid);
     $thread_count = mysql_num_rows($result_c);
     mysql_free_result($result_c);

     if(!$result or !$result_t) die($lang['db_error']);
    }
  }
  else { header("location: board.php"); }

if (empty($thread)) header("location: board.php");

$wo = $thread["subject"];
$subnav_1 = '<a class="textlink" href="board.php?page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'" title="'.$lang['back_to_board_linktitle'].'">'.$lang['back_to_board_linkname'].'</a>';
$subnav_2 = '';
if ($settings['thread_view']==1) $subnav_2 .= '<span class="small"><a href="forum_entry.php?id='.$thread["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category.'" title="'.$lang['thread_view_linktitle'].'"><img src="img/thread.gif" alt="" />'.$lang['thread_view_linkname'].'</a></span>';
if ($settings['mix_view']==1) $subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="mix_entry.php?id='.$thread["tid"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category.'" title="'.$lang['mix_view_linktitle'].'"><img src="img/mix.gif" alt="" />'.$lang['mix_view_linkname'].'</a></span>';
if ($da=="DESC") $subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="board_entry.php?id='.$thread["tid"].'&amp;da=ASC&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category.'" title="'.$lang['order_linktitle_3'].'"><img src="img/order.gif" alt="" width="12" height="9" />'.$lang['order_linkname'].'</a></span>';
else $subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="board_entry.php?id='.$thread["tid"].'&amp;da=DESC&amp;page='.$page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category.'" title="'.$lang['order_linktitle_4'].'"><img src="img/order.gif" alt="" width="12" height="9" />'.$lang['order_linkname'].'</a></span>';
$subnav_2 .= nav_b($be_page, $settings['answers_per_topic'], $thread_count, $thread["tid"], $da, 1, $page, $category, $order, $descasc);

parse_template();
echo $header;

?><table class="board-entry" border="0" cellpadding="0" cellspacing="1" width="100%">
   <?php if ($be_page==0) { ?>
    <tr>
     <td class="autorcell" rowspan="2" valign="top"><?php
      // wenn eingelogged und Posting von einem angemeldeten User stammt, dann Link zu dessen Userdaten:
      if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $thread["user_id"] > 0)
       {
        $show_userdata_linktitle_x = str_replace("[name]", htmlsc(stripslashes($thread["name"])), $lang['show_userdata_linktitle']);
        ?><a class="userlink" name="<?php echo "p".$thread["id"]; ?>" href="user.php?id=<?php echo $thread["user_id"]; ?>" title="<?php echo $show_userdata_linktitle_x; ?>"><?php if ($mark_admin==true) { ?><span class="admin-highlight"><?php } elseif ($mark_mod==true) { ?><span class="mod-highlight"><?php } ?><b><?php echo htmlsc(stripslashes($thread["name"])); ?></b><?php if ($settings['show_registered'] ==1) { ?><img src="img/registered.gif" alt="(R)" width="10" height="10" title="<?php echo $lang['registered_user_title']; ?>" /><?php } if ($mark_admin==true||$mark_mod==true) { ?></span><?php } ?></a><br /><?php
       }
      // ansonsten nur den Namen anzeigen:
      else
       {
        ?>
        <a id="<?php echo "p".$thread["id"]; ?>"><?php if ($mark_admin==true) { ?><span class="admin-highlight"><?php } elseif ($mark_mod==true) { ?><span class="mod-highlight"><?php } ?><b><?php echo htmlsc(stripslashes($thread["name"])); ?></b><?php if ($thread["user_id"] > 0 && $settings['show_registered'] ==1) { ?><img src="img/registered.gif" alt="(R)" width="10" height="10" title="<?php echo $lang['registered_user_title']; ?>" /><?php } ?></a><?php if ($mark_admin==true||$mark_mod==true) { ?></span><?php } ?><br />
        <?php
       }
       if (empty($thread["hide_email"])) $thread["hide_email"] = 0;
       if (($thread["email"]!="" && $thread["hide_email"] != 1) or $thread["hp"]!="") { echo "<br />"; }
       if ($thread["hp"]!="") { if (substr($thread["hp"],0,7) != "http://" && substr($thread["hp"],0,8) != "https://" && substr($thread["hp"],0,6) != "ftp://" && substr($thread["hp"],0,9) != "gopher://" && substr($thread["hp"],0,7) != "news://") $thread["hp"] = "http://".$thread["hp"]; echo "<a href=\"" . $thread["hp"] ."\" title=\"".htmlsc(stripslashes($thread["hp"]))."\"><img src=\"img/homepage.gif\" alt=\"".$lang['homepage_alt']."\" width=\"13\" height=\"13\" /></a>"; }
       if (($thread["email"]!="" && $thread["hide_email"] != 1) && $thread["hp"]!="") { echo "&nbsp;"; }
       #if ($thread["email"]!="" && $thread["hide_email"] != 1) { echo "<a href=\"mailto:" . $thread["email"] ."\" title=\"".htmlsc(stripslashes($thread["email"]))."\"><img src=\"img/email.gif\" alt=\"".$lang['email_alt']."\" width=\"14\" height=\"10\" /></a>"; }
       if ($thread["email"]!="" && $thread["hide_email"] != 1 && isset($page) && isset($order) && isset($category)) { echo '<a href="contact.php?id='.$thread["id"].'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;view=board"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlsc(stripslashes($thread["name"])), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>'; }
       elseif ($thread["email"]!="" && $thread["hide_email"] != 1) { echo '<a href="contact.php?id='.$thread.'&amp;view=board"><img src="img/email.gif" alt="'.$lang['email_alt'].'" width="13" height="10" title="'.str_replace("[name]", htmlsc(stripslashes($thread["name"])), $lang['email_to_user_linktitle']).'" /></a>'; }
       if (($thread["email"]!="" && $thread["hide_email"] != 1) or $thread["hp"]!="") { echo "<br />"; }
       echo "<br />";
       if ($thread["place"]!="") { echo htmlsc(stripslashes($thread["place"])); echo ", <br />"; }
       echo strftime($lang['time_format'],$thread["Uhrzeit"]);
       if ($thread["edited_diff"] > 0 && $thread["edited_diff"] > $thread["time"] && $settings['show_if_edited'] == 1) { $board_em = str_replace("[name]", htmlsc(stripslashes($thread["edited_by"])), $lang['board_edited_marking']); $board_em = str_replace("[time]", strftime($lang['time_format'],$thread["e_Uhrzeit"]), $board_em); ?><br /><span class="xsmall"><?php echo $board_em; ?></span><?php }
       if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><br /><span class="xsmall"><?php echo $thread['ip']; ?></span><?php }
       if ($settings['user_edit'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=<?php echo $thread["id"]; ?>&amp;view=board&amp;back=<?php echo $thread["tid"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;category=<?php echo $category; ?>" title="<?php echo $lang['edit_linktitle']; ?>"><img src="img/edit.gif" alt="" width="15" height="10" /><?php echo $lang['edit_linkname']; ?></a></span><?php } if ($settings['user_delete'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><span class="small"><a href="posting.php?action=delete&amp;id=<?php echo $thread["id"]; ?>&amp;back=<?php echo $thread["tid"]; ?>&amp;view=board&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;category=<?php echo $category; ?>" title="<?php echo $lang['delete_linktitle']; ?>"><img src="img/delete.gif" alt="" width="12" height="9" /><?php echo $lang['delete_linkname']; ?></a></span><?php }
       if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><span class="small"><a href="posting.php?lock=true&amp;view=board&amp;id=<?php echo $thread["id"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;category=<?php echo $category; ?>" title="<?php if ($thread['locked'] == 0) echo $lang['lock_linktitle']; else echo $lang['unlock_linktitle']; ?>"><img src="img/lock.gif" alt="" width="12" height="12" /><?php if ($thread['locked'] == 0) echo $lang['lock_linkname']; else echo $lang['unlock_linkname']; ?></a></span><?php }
       ?><div class="autorcellwidth">&nbsp;</div></td>
     <td class="titlecell" valign="top"><div class="left"><h2><?php echo htmlsc(stripslashes($thread["subject"])); if(isset($categories[$thread["category"]]) && $categories[$thread["category"]]!='') echo "&nbsp;<span class=\"category\">(".$categories[$thread["category"]].")</span>"; ?></h2></div><div class="right"><?php if ($thread['locked'] == 0) { ?><a class="textlink" href="posting.php?id=<?php echo $thread["id"]; if (isset($page) && isset($order) && isset($descasc) && isset($category)) { ?>&amp;page=<?php echo $page; ?>&amp;category=<?php echo $category; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; } ?>&amp;view=board" title="<?php echo $lang['board_answer_linktitle']; ?>"><?php echo $lang['board_answer_linkname']; ?></a><?php } else { ?><span class="xsmall"><img src="img/lock.gif" alt="" width="12" height="12" /><?php echo $lang['thread_locked']; ?></span><?php } ?></div></td>
    </tr>
    <tr>
     <td class="postingcell" valign="top"><?php
                                          if ($thread["text"]=="")
                                           {
                                            echo $lang['no_text'];
                                           }
                                          else
                                           {
                                            $ftext=$thread["text"];
                                            $ftext = htmlsc(stripslashes($ftext));
                                            $ftext = nl2br($ftext);
                                            $ftext = zitat($ftext);
                                            if ($settings['autolink'] == 1) $ftext = make_link($ftext);
                                            if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
                                            if ($settings['smilies'] == 1) $ftext = smilies($ftext);
                                            echo '<p class="postingboard">'.$ftext.'</p>';
                                           }
                                          if (isset($signature) && $signature != "")
                                           {
                                            $signature = htmlsc(stripslashes($signature));
                                            $signature = nl2br($signature);
                                            if ($settings['autolink'] == 1) $signature = make_link($signature);
                                            if ($settings['bbcode'] == 1) $signature = bbcode($signature);
                                            if ($settings['smilies'] == 1) $signature = smilies($signature);
                                            echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>';
                                           }
                                          ?></td>
    </tr><?php } ?>
    <?php
    $i=0;
    while ($entrydata = mysql_fetch_assoc($result)) {
     unset($signature);
     $mark_admin = false;
     $mark_mod = false;
     if ($entrydata["user_id"] > 0)
      {
       $userdata_result=mysql_query("SELECT user_name, user_type, user_email, hide_email, user_hp, user_place, signature FROM ".$db_settings['userdata_table']." WHERE user_id = '".$entrydata["user_id"]."'", $connid);
       if (!$userdata_result) die($lang['db_error']);
       $userdata = mysql_fetch_assoc($userdata_result);
       mysql_free_result($userdata_result);
       $entrydata["email"] = $userdata["user_email"];
       $entrydata["hide_email"] = $userdata["hide_email"];
       $entrydata["place"] = $userdata["user_place"];
       $entrydata["hp"] = $userdata["user_hp"];
       if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1) $mark_admin = true;
       elseif ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1) $mark_mod = true;
       if ($entrydata["show_signature"]==1) $signature = $userdata["signature"];
      }

    //if ($i >= $be_page * $settings['answers_per_topic'] && $i < $be_page * $settings['answers_per_topic'] + $settings['answers_per_topic']) { ?>
       <?php // Posting heraussuchen, auf das geantwortet wurde:
        $result_a = mysql_query("SELECT name FROM ".$db_settings['forum_table']." WHERE id = ".$entrydata["pid"], $connid);
        $posting_a = mysql_fetch_assoc($result_a);
        mysql_free_result($result_a); ?>
    <tr>
     <td class="autorcell" rowspan="2" valign="top"><?php
      // wenn eingelogged und Posting von einem angemeldeten User stammt, dann Link zu dessen Userdaten:
      if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] > 0)
       {
        $show_userdata_linktitle_x = str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['show_userdata_linktitle']);
        ?><a id="<?php echo "p".$entrydata["id"]; ?>" href="user.php?id=<?php echo $entrydata["user_id"]; ?>" title="<?php echo $show_userdata_linktitle_x; ?>"><?php if ($mark_admin==true) { ?><span class="admin-highlight"><?php } elseif ($mark_mod==true) { ?><span class="mod-highlight"><?php } ?><b><?php echo htmlsc(stripslashes($entrydata["name"])); ?></b><?php if ($settings['show_registered'] ==1) { ?><img src="img/registered.gif" alt="(R)" width="10" height="10" title="<?php echo $lang['registered_user_title']; ?>" /><?php } if ($mark_admin==true||$mark_mod==true) { ?></span><?php } ?></a><br /><?php
       }
      // ansonsten nur den Namen anzeigen:
      else
       {
        ?>
        <a id="<?php echo "p".$entrydata["id"]; ?>"><?php if ($mark_admin==true) { ?><span class="admin-highlight"><?php } elseif ($mark_mod==true) { ?><span class="mod-highlight"><?php } ?><b><?php echo htmlsc(stripslashes($entrydata["name"])); ?></b><?php if ($entrydata["user_id"] > 0 && $settings['show_registered'] ==1) { ?><img src="img/registered.gif" alt="(R)" width="10" height="10" title="<?php echo $lang['registered_user_title']; ?>" /><?php } ?></a><?php if ($mark_admin==true||$mark_mod==true) { ?></span><?php } ?><br />
        <?php
       }

        if (empty($entrydata["hide_email"])) $entrydata["hide_email"] = 0;
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) or $entrydata["hp"]!="") { echo "<br />"; }
        if ($entrydata["hp"]!="") { if (substr($entrydata["hp"],0,7) != "http://" && substr($entrydata["hp"],0,8) != "https://" && substr($entrydata["hp"],0,6) != "ftp://" && substr($entrydata["hp"],0,9) != "gopher://" && substr($entrydata["hp"],0,7) != "news://") $entrydata["hp"] = "http://".$entrydata["hp"]; echo "<a href=\"" . $entrydata["hp"] ."\" title=\"".htmlsc(stripslashes($entrydata["hp"]))."\"><img src=\"img/homepage.gif\" alt=\"".$lang['homepage_alt']."\" width=\"13\" height=\"13\" /></a>"; }
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) && $entrydata["hp"]!="") { echo "&nbsp;"; }
        #if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1) { echo "<a href=\"mailto:" . $entrydata["email"] ."\"title=\"".htmlsc(stripslashes($entrydata["email"]))."\"><img src=\"img/email.gif\" alt=\"".$lang['email_alt']."\" width=\"14\" height=\"10\" /></a>"; }
        if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1 && isset($page) && isset($order) && isset($category)) { echo '<a href="contact.php?id='.$entrydata["id"].'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;view=board"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>'; }
        elseif ($entrydata["email"]!="" && $entrydata["hide_email"] != 1) { echo '<a href="contact.php?id='.$entrydata["id"].'&amp;view=board" title="'.str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['email_to_user_linktitle']).'"><img src="img/email.gif" alt="'.$lang['email_alt'].'" width="13" height="10" /></a>'; }
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) or $entrydata["hp"]!="") { echo "<br />"; }
        echo "<br />";
        if ($entrydata["place"]!="") { echo htmlsc(stripslashes($entrydata["place"])); echo ", <br />"; }
        echo strftime($lang['time_format'],$entrydata["Uhrzeit"]); ?>
        <?php if ($entrydata["edited_diff"] > 0 && $entrydata["edited_diff"] > $entrydata["time"] && $settings['show_if_edited'] == 1) { $board_em = str_replace("[name]", htmlsc(stripslashes($entrydata["edited_by"])), $lang['board_edited_marking']); $board_em = str_replace("[time]", strftime($lang['time_format'],$entrydata["e_Uhrzeit"]), $board_em); ?><br /><span class="xsmall"><?php echo $board_em; ?></span><?php } ?>
        <?php if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin") { ?><br /><br /><span class="xsmall"><?php echo $entrydata['ip']; ?></span><?php } ?>
        <span class="xsmall"><br /><br /><!--<a href="<?php echo "#p".$entrydata["pid"]; ?>">-->@ <?php echo htmlsc(stripslashes($posting_a["name"])); ?><!--</a>--></span>
        <?php if ($settings['user_edit'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=<?php echo $entrydata["id"]; ?>&amp;view=board&amp;back=<?php echo $entrydata["tid"]; ?>&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;category=<?php echo $category; ?>" title="<?php echo $lang['edit_linktitle']; ?>"><img src="img/edit.gif" alt="" width="15" height="10" /><?php echo $lang['edit_linkname']; ?></a></span><?php } if ($settings['user_delete'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><span class="small"><a href="posting.php?action=delete&amp;id=<?php echo $entrydata["id"]; ?>&amp;back=<?php echo $entrydata["tid"]; ?>&amp;view=board&amp;page=<?php echo $page; ?>&amp;order=<?php echo $order; ?>&amp;category=<?php echo $category; ?>" title="<?php echo $lang['delete_linktitle']; ?>"><img src="img/delete.gif" alt="" width="12" height="9"><?php echo $lang['delete_linkname']; ?></a></span><?php }
        ?></td>
     <td class="titlecell" valign="top"><div class="left"><h2><?php echo htmlsc(stripslashes($entrydata["subject"])); ?></h2></div><div class="right"><?php if ($entrydata['locked'] == 0) { ?><a class="textlink" href="posting.php?id=<?php echo $entrydata["id"]; if (isset($page) && isset($order) && isset($descasc) && isset($category)) { ?>&amp;page=<?php echo $page; ?>&amp;category=<?php echo $category; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; } ?>&amp;view=board" title="<?php echo $lang['board_answer_linktitle']; ?>"><?php echo $lang['board_answer_linkname']; ?></a><?php } else echo "&nbsp;"; ?></div></td>
    </tr>
    <tr>
     <td class="postingcell" valign="top"><?php
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
                                            echo '<p class="postingboard">'.$ftext.'</p>';
                                           }
                                          if (isset($signature) && $signature != "")
                                           {
                                            $signature = htmlsc(stripslashes($signature));
                                            $signature = nl2br($signature);
                                            if ($settings['autolink'] == 1) $signature = make_link($signature);
                                            if ($settings['bbcode'] == 1) $signature = bbcode($signature);
                                            if ($settings['smilies'] == 1) $signature = smilies($signature);
                                            echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>';
                                           }
                                          ?></td>
     </tr>
     <?php }  mysql_free_result($result); ?>
    </table><?php

echo $footer;
}
else { header("location: login.php?msg=noaccess"); die("<a href=\"login.php?msg=noaccess\">further...</a>"); }
?>
