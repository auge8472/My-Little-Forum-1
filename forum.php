<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004-2008 Alex                                                #
# http://www.mylittlehomepage.net/                                            #
# Copyright (C) 2009-2019 H. August                                           #
# https://www.projekt-mlf.de/                                                 #
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

// import vars:
if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

include("inc.php");

// log in automatically if cookie is set
if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  header("location: login.php?referer=forum.php");
  die("<a href=\"login.php?referer=forum.php\">further...</a>");
 }

// go on if user has access:
if ($settings['access_for_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only'] != 1)
 {
  if ($settings['remember_userstandard'] == 1 && !isset($_SESSION[$settings['session_prefix'].'newtime'])) { setcookie("user_view","thread",time()+(3600*24*30)); }
  if (empty($page)) $page = 0;
  if (empty($order)) $order="time";
  if (isset($descasc) && $descasc=="ASC") { $descasc="DESC"; $page = 0; }
  else $descasc="DESC";
  if ($order != "time" && $order !="last_answer") { $page = 0; $order="time"; }
  $ul = $page * $settings['topics_per_page'];
  unset($parent_array);
  unset($child_array);

  // database request
  if ($categories == false) // no categories defined
   {
    $result=mysqli_query($connid, "SELECT id, pid, tid FROM ". $db_settings['forum_table'] ." WHERE pid = 0 ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
    if(!$result) die($lang['db_error']);
   }
  elseif (is_array($categories) && $category == 0) // there are categories and all categories should be shown
   {
    $result=mysqli_query($connid, "SELECT id, pid, tid FROM ". $db_settings['forum_table'] ." WHERE pid = 0 AND category IN (". $category_ids_query .") ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
    if (!$result) die($lang['db_error']);
   }
  elseif (is_array($categories) && $category != 0 && in_array($category, $category_ids)) // there are categories and only one category should be shown
   {
    $result=mysqli_query($connid, "SELECT id, pid, tid FROM ". $db_settings['forum_table'] ." WHERE category = ". intval($category) ." AND pid = 0 ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
    if(!$result) die($lang['db_error']);
    // how many entries?
    $pid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE pid = 0 AND category = ". intval($category));
    list($thread_count) = mysqli_fetch_row($pid_result);
    mysqli_free_result($pid_result);
   }

  $subnav_1='<a class="textlink" href="posting.php?category='.$category.'" title="'.$lang['new_entry_linktitle'].'">'.$lang['new_entry_linkname'].'</a>';
  $subnav_2 = '';
  if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $subnav_2 .= '<a href="index.php?update=1&amp;category='.urlencode($category).'"><img src="img/update.gif" alt="" title="'.$lang['update_time_linktitle'].'" width="9" height="9" onmouseover="this.src=\'img/update_mo.gif\';" onmouseout="this.src=\'img/update.gif\';" /></a>';
  if ($order=="time") $subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php?order=last_answer&amp;category='.urlencode($category).'" title="'.$lang['order_linktitle_1'].'"><img src="img/order.gif" alt="" width="12" height="9" />'.$lang['order_linkname'].'</a></span>';
  else $subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php?order=time&amp;category='.urlencode($category).'" title="'.$lang['order_linktitle_2'].'"><img src="img/order.gif" alt="" width="12" height="9" />'.$lang['order_linkname'].'</a></span>';
  if ($settings['board_view'] == 1 && $category == 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="board.php" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';
  elseif ($settings['board_view'] == 1 && $category != 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="board.php?category='.urlencode($category).'" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';
  if ($settings['mix_view']==1 && $category == 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="mix.php" title="'.$lang['mix_view_linktitle'].'"><img src="img/mix.gif" alt="" width="12" height="9" />'.$lang['mix_view_linkname'].'</a></span>';
  elseif ($settings['mix_view']==1 && $category != 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="mix.php?category='.urlencode($category).'" title="'.$lang['mix_view_linktitle'].'"><img src="img/mix.gif" alt="" width="12" height="9" />'.$lang['mix_view_linkname'].'</a></span>';
  $subnav_2 .= nav($page, (int)$settings['topics_per_page'], $thread_count, $order, $descasc, $category);

  if($categories!=false)
   {
    $subnav_2 .= '&nbsp;&nbsp;<form method="get" action="forum.php" title="'.$lang['choose_category_formtitle'].'"><div style="display: inline;"><select class="kat" size="1" name="category" onchange="this.form.submit();">';
    if (isset($category) && $category==0) $subnav_2 .= '<option value="0" selected="selected">'.$lang['show_all_categories'].'</option>';
    else $subnav_2 .= '<option value="0">'.$lang['show_all_categories'].'</option>';
    while(list($key, $val) = each($categories))
     {
      if($key!=0)
       {
        if($key==$category) $subnav_2 .= '<option value="'.$key.'" selected="selected">'.$val.'</option>';
        else $subnav_2 .= '<option value="'.$key.'">'.$val.'</option>';
       }
     }
    $subnav_2 .= '</select><noscript> <input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>';
   }

  parse_template();
  echo $header;

  if ($thread_count > 0 && isset($result))
   {
    while ($zeile = mysqli_fetch_assoc($result))
     {
      $thread_result=mysqli_query($connid, "SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS tp_time, UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked, fixed FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($zeile["tid"]) ." ORDER BY time ASC");

      // put result into arrays:
      while($tmp = mysqli_fetch_assoc($thread_result))
       {
        $parent_array[$tmp["id"]] = $tmp;
        $child_array[$tmp["pid"]][] =  $tmp["id"];
       }

      ?><ul class="thread"><?php

      // display the thread tree
      thread_tree($zeile["id"]);

      ?></ul><?php
      mysqli_free_result($thread_result);
    }

    if(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=='admin')
     {
      ?><p class="marked-threads"><img src="img/marked.gif" alt="[x]" width="9" height="9" /> <?php echo $lang['marked_threads_actions']; ?> <a href="admin.php?action=delete_marked_threads"><?php echo $lang['delete_marked_threads']; ?></a> - <a href="admin.php?action=lock_marked_threads"><?php echo $lang['lock_marked_threads']; ?></a> - <a href="admin.php?action=unlock_marked_threads"><?php echo $lang['unlock_marked_threads']; ?></a> - <a href="admin.php?action=unmark"><?php echo $lang['unmark_threads']; ?></a> - <a href="admin.php?action=invert_markings"><?php echo $lang['invert_markings']; ?></a> - <a href="admin.php?action=mark_threads"><?php echo $lang['mark_threads']; ?></a></p><?php
     }
   }
  else
   {
    if($category!=0)
     {
      ?><p><?php echo $lang['no_messages_in_category']; ?></p><p>&nbsp;</p><?php
     }
    else
     {
      ?><p><?php echo $lang['no_messages']; ?></p><p>&nbsp;</p><?php
     }
   }
  if (isset($result)) mysqli_free_result($result);

  echo $footer;
 }
else // no access
 {
  header("location: login.php?msg=noaccess");
  die("<a href=\"login.php?msg=noaccess\">further...</a>");
 }
?>