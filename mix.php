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

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

include("inc.php");

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  header("location: login.php?referer=mix.php");
  die('<a href="login.php?referer=mix.php">further...</a>');
 }

 if ($settings['access_for_users_only']  == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only']  != 1)
{

 function mix_tree($id, $aktuellerEintrag = 0, $tiefe = 0)
 {
  global $settings, $parent_array, $child_array, $page, $order, $category, $descasc, $last_visit, $lang;
  $intendation = 20;
  if ($tiefe==0 or $tiefe >= ($settings['max_thread_indent_mix']/$settings['thread_indent_mix'])) {
    $intendation = 0;
  } else {
    $intendation = intval($settings['thread_indent_mix']);
  }
  ?><div class="threadkl" style="margin-left: <?php echo $intendation; ?>px;"><?php
  //[... Zeile mit den Eintragsdaten oder einem Link ausgeben ...]
  if ($parent_array[$id]["pid"]!=0)
   {
    if (($aktuellerEintrag == 0 && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["time"]) || ($aktuellerEintrag == 0 && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]["time"] > $last_visit)) {
      $treeClass = "replynew";
    } else {
      $treeClass = "reply";
    }
    ?><a class="<?php echo $treeClass; ?>" href="mix_entry.php?id=<?php echo intval($parent_array[$id]["tid"]); if ($page != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC") echo '&amp;page='. intval($page) .'&amp;category='. intval($category) .'&amp;order='. urlencode($order) .'&amp;descasc='. urlencode($descasc); ?>#p<?php echo intval($parent_array[$id]["id"]); ?>" title="<?php echo htmlsc($parent_array[$id]["name"]); echo ", ".strftime($lang['time_format'],$parent_array[$id]["Uhrzeit"]); ?>"><?php echo htmlsc($parent_array[$id]["subject"]); ?></a><?php
   }

  // Anfang der Schleife über alle Kinder ...
  if(isset($child_array[$id]) && is_array($child_array[$id])) {
    foreach($child_array[$id] as $kind) {
      mix_tree($kind, $aktuellerEintrag, $tiefe+1);
    }
  }
 ?></div><?php
 }

 if ($settings['remember_userstandard']  == 1 && !isset($_SESSION[$settings['session_prefix'].'newtime'])) { setcookie("user_view","mix",time()+(3600*24*30)); }

 unset($zeile);

 if (empty($page)) $page = 0;
 if (empty($order)) $order="last_answer";
 if (empty($descasc)) $descasc="DESC";
 if (isset($descasc) && $descasc=="ASC") $descasc = "ASC";
 else $descasc = "DESC";
 $ul = $page * $settings['topics_per_page'];

 unset($parent_array); // Variablen korrekt (de)initialisieren
 unset($child_array);

 // database request
 if ($categories == false) // no categories defined
  {
   $result=mysqli_query($connid, "SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit,
                        UNIX_TIMESTAMP(last_answer + INTERVAL ". $time_difference ." HOUR) AS la_Uhrzeit,
                        UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked, fixed, views FROM ". $db_settings['forum_table'] ." WHERE pid = 0 ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
   if(!$result) die($lang['db_error']);
  }
 elseif (is_array($categories) && $category == 0) // there are categories and all categories should be shown
  {
   $result=mysqli_query($connid, "SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit,
                        UNIX_TIMESTAMP(last_answer + INTERVAL ". $time_difference ." HOUR) AS la_Uhrzeit,
                        UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked, fixed, views FROM ". $db_settings['forum_table'] ." WHERE pid = 0 AND category IN (". $category_ids_query .") ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
   if(!$result) die($lang['db_error']);
 }
 elseif (is_array($categories) && $category != 0 && in_array($category, $category_ids)) // there are categories and only one category should be shown
  {
   $result=mysqli_query($connid, "SELECT id, pid, tid, user_id, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit,
                        UNIX_TIMESTAMP(last_answer + INTERVAL ". $time_difference ." HOUR) AS la_Uhrzeit,
                        UNIX_TIMESTAMP(last_answer) AS last_answer, name, subject, category, marked, fixed, views FROM ". $db_settings['forum_table'] ." WHERE category = ". intval($category) ." AND pid = 0 ORDER BY fixed DESC, ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['topics_per_page']));
   if(!$result) die($lang['db_error']);
   // how many entries?
   $pid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE pid = '0' AND category = '". intval($category));
   list($thread_count) = mysqli_fetch_row($pid_result);
   mysqli_free_result($pid_result);
  }

$subnav_1 = '<a class="textlink" href="posting.php?view=mix&amp;category='. intval($category) .'">'.$lang['new_entry_linkname'].'</a>';
$subnav_2 = '';
if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $subnav_2 .= '<a href="index.php?update=1&amp;view=mix&amp;category='. intval($category) .'"><img src="img/update.gif" alt="'. htmlsc($lang['update_time_linktitle']) .'" width="9" height="9" /></a>';
if ($settings['thread_view'] == 1 && $category == 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php"><img src="img/thread.gif" alt="" width="12" height="9" />'.$lang['thread_view_linkname'].'</a></span>';
elseif ($settings['thread_view'] == 1 && $category != 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php?category='. intval($category) .'"><img src="img/thread.gif" alt="" width="12" height="9" />'.$lang['thread_view_linkname'].'</a></span>';
if ($settings['board_view']==1 && $category == 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="board.php"><img src="img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';
elseif ($settings['board_view']==1 && $category != 0) $subnav_2 .= ' &nbsp;<span class="small"><a href="board.php?category='. intval($category) .'"><img src="img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';
$subnav_2 .= nav($page, $settings['topics_per_page'], $thread_count, $order, $descasc, $category);
$categories = get_categories();
if ($categories!=false && $categories != "not accessible")
 {
  $subnav_2 .= '&nbsp;&nbsp;<form method="get" action="mix.php" accept-charset="UTF-8"><div style="display: inline;"><select class="kat" size="1" name="category" onchange="this.form.submit();">';
  if (isset($category) && $category==0) $subnav_2 .= '<option value="0" selected="selected">'.$lang['show_all_categories'].'</option>';
  else $subnav_2 .= '<option value="0">'.$lang['show_all_categories'].'</option>';
  foreach ($categories as $key => $val)
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

if($thread_count > 0 && isset($result))
 {
  ?><table class="normaltab" border="0" cellpadding="5" cellspacing="1">
  <tr>
  <th><a href="mix.php?category=<?php echo intval($category); ?>&amp;order=subject&amp;descasc=<?php if ($descasc=="ASC" && $order=="subject") echo "DESC"; else echo "ASC"; ?>" title="<?php echo htmlsc($lang['order_linktitle']); ?>"><?php echo $lang['board_subject_headline']; ?></a><?php if ($order=="subject" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" /><?php } elseif ($order=="subject" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" /><?php } ?></th>
  <?php if ($categories!=false && $category == 0) { ?>
  <th><!--<a href="mix.php?category=<?php echo intval($category); ?>&amp;order=category&amp;descasc=<?php if ($descasc=="ASC" && $order=="category") echo "DESC"; else echo "ASC"; ?>" title="<?php echo htmlsc($lang['order_linktitle']); ?>">--><?php echo $lang['board_category_headline']; ?><!--</a>--><?php if ($order=="category" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" /><?php } elseif ($order=="category" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" /><?php } ?></th>
  <?php } ?>
  <th><a href="mix.php?category=<?php echo intval($category); ?>&amp;order=name&amp;descasc=<?php if ($descasc=="ASC" && $order=="name") echo "DESC"; else echo "ASC"; ?>" title="<?php echo htmlsc($lang['order_linktitle']); ?>"><?php echo $lang['board_author_headline']; ?></a><?php if ($order=="name" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" /><?php } elseif ($order=="name" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" /><?php } ?></th>
  <th><a href="mix.php?category=<?php echo intval($category); ?>&amp;order=time&amp;descasc=<?php if ($descasc=="DESC" && $order=="time") echo "ASC"; else echo "DESC"; ?>" title="<?php echo htmlsc($lang['order_linktitle']); ?>"><?php echo $lang['board_date_headline']; ?></a><?php if ($order=="time" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" /><?php } elseif ($order=="time" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" /><?php } ?></th>
  <th><?php echo $lang['board_answers_headline']; ?></th>
  <th><a href="mix.php?category=<?php echo intval($category); ?>&amp;order=last_answer&amp;descasc=<?php if ($descasc=="DESC" && $order=="last_answer") echo "ASC"; else echo "DESC"; ?>" title="<?php echo htmlsc($lang['order_linktitle']); ?>"><?php echo $lang['board_last_answer_headline']; ?></a><?php if ($order=="last_answer" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" /><?php } elseif ($order=="last_answer" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" /><?php } ?></th>
  <?php if (isset($settings['count_views']) && $settings['count_views'] == 1) { ?>
  <th><?php echo $lang['views_headline']; ?></th><?php } ?>
  <?php if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin") { ?><th>&nbsp;</th><?php } ?>
  </tr>
  <?php
  $i=0;
  while ($zeile = mysqli_fetch_assoc($result)) {

  // count replies:
  $pid_resultc = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($zeile["tid"]));
  list($answers_count) = mysqli_fetch_row($pid_resultc);
  $answers_count = $answers_count - 1;
  mysqli_free_result($pid_resultc);

  // data for link to last reply:
  if ($settings['last_reply_link'] == 1)
  {
   $last_answer_result = mysqli_query($connid, "SELECT name, id FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($zeile["tid"]) ." ORDER BY time DESC LIMIT 1");
   $last_answer = mysqli_fetch_assoc($last_answer_result);
   mysqli_free_result($last_answer_result);
  }
  ?><tr>
  <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php
  ?><a class="<?php if ((isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $zeile["last_answer"]) || (($zeile["pid"]==0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $zeile["last_answer"] > $last_visit)) echo "threadnew"; else echo "thread"; ?>" href="mix_entry.php?id=<?php echo intval($zeile["tid"]); if ($page != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC") echo '&amp;page='. intval($page) .'&amp;category='. intval($category) .'&amp;order='. urlencode($order) .'&amp;descasc='. urlencode($descasc); ?>"><?php echo htmlsc($zeile["subject"]); ?></a><?php
  if ($zeile["fixed"] == 1) { ?> <img src="img/fixed.gif" width="9" height="9" title="<?php echo $lang['fixed']; ?>" alt="*" /><?php }
  if ($settings['all_views_direct'] == 1) { echo ' <span class="small">'; if ($settings['board_view'] == 1) { ?><a href="board_entry.php?id=<?php echo intval($zeile["tid"]); ?>"><img src="img/board_d.gif" alt="[Board]" title="<?php echo htmlsc($lang['open_in_board_linktitle']); ?>" width="12" height="9" /></a><?php } if ($settings['thread_view']==1) {?><a href="forum_entry.php?id=<?php echo intval($zeile["tid"]); ?>"><img src="img/thread_d.gif" alt="[Thread]" title="<?php echo htmlsc($lang['open_in_thread_linktitle']); ?>" width="12" height="9" /></a><?php } echo "</span>"; }
      $thread_result=mysqli_query($connid, "SELECT id, pid, tid, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit,
                     name, subject, category FROM ". $db_settings['forum_table'] ."
                     WHERE tid = ". intval($zeile["tid"]) ." ORDER BY time ASC");
         // Ergebnisse einlesen:
         while($tmp = mysqli_fetch_assoc($thread_result))
          {                                           // Ergebnis holen
           $parent_array[ $tmp["id"] ] = $tmp;          // Ergebnis im Array ablegen
           $child_array[ $tmp["pid"] ][] =  $tmp["id"]; // Vorwärtsbezüge konstruieren
          }
      mix_tree($zeile["tid"]);
      mysqli_free_result($thread_result); ?></td>
      <?php if ($categories!=false && $category == 0) { ?>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if(isset($categories[$zeile["category"]]) && $categories[$zeile["category"]]!='') { ?><a title="<?php echo str_replace("[category]", htmlsc($categories[$zeile["category"]]), $lang['choose_category_linktitle']); if (isset($category_accession[$zeile["category"]]) && $category_accession[$zeile["category"]] == 2) echo " ".$lang['admin_mod_category']; elseif (isset($category_accession[$zeile["category"]]) && $category_accession[$zeile["category"]] == 1) echo " ".$lang['registered_users_category']; ?>" href="mix.php?category=<?php echo intval($zeile["category"]); ?>"><span class="<?php if (isset($category_accession[$zeile["category"]]) && $category_accession[$zeile["category"]] == 2) echo "category-adminmod-b"; elseif (isset($category_accession[$zeile["category"]]) && $category_accession[$zeile["category"]] == 1) echo "category-regusers-b"; else echo "category-b"; ?>"><?php echo $categories[$zeile["category"]]; ?></span></a><?php } else echo "&nbsp;"; ?></td>
      <?php } ?>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0) { ?><a href="user.php?id=<?php echo intval($zeile["user_id"]); ?>"><?php } ?><span class="small"><?php echo htmlsc($zeile["name"]); ?></span><?php if ($zeile["user_id"] > 0 && $settings['show_registered'] ==1) { ?><img src="img/registered.gif" alt="<?php echo htmlsc($lang['registered_user_title']); ?>" width="10" height="10" /><?php } if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0) { ?></a><?php } ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php echo strftime($lang['time_format'],($zeile["Uhrzeit"])); ?></span></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php echo $answers_count; ?></span></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php if ($answers_count > 0) { if ($settings['last_reply_link']==1) { ?><a href="mix_entry.php?id=<?php echo intval($zeile["tid"]); ?>&amp;page=<?php echo intval($page); ?>&amp;category=<?php echo intval($category); ?>&amp;order=<?php echo urlencode($order); ?>&amp;descasc=<?php echo urlencode($descasc); ?>#p<?php echo intval($last_answer['id']); ?>" title="<?php echo str_replace("[name]", htmlsc($last_answer['name']), $lang['last_reply_lt']); ?>"><?php } echo strftime($lang['time_format'],$zeile["la_Uhrzeit"]); if ($settings['last_reply_link']==1) { ?></a><?php } } else echo "&nbsp;"; ?></span></td>
     <?php if (isset($settings['count_views']) && $settings['count_views'] == 1) { ?>
     <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php echo $zeile['views']; ?></span></td>
     <?php }
     if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin") { ?><td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?mark=<?php echo intval($zeile["tid"]); ?>&amp;refer=<?php echo urlencode(basename($_SERVER["SCRIPT_NAME"])); ?>&amp;page=<?php echo intval($page); ?>&amp;category=<?php echo intval($category); ?>&amp;order=<?php echo urlencode($order); ?>"><?php
     if ($zeile['marked']==1) { ?><img src="img/marked.gif" alt="<?php echo htmlsc($lang['unmark_linktitle']); ?>" width="9" height="9" /><?php }
     else { echo '<img src="img/mark.gif" alt="'. htmlsc($lang['mark_linktitle']) .'" width="9" height="9" />'; }
     ?></a></td><?php } ?>
     </tr>
     <?php $i++; } ?></table><?php
     mysqli_free_result($result);

     if(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=='admin')
      {
       ?><p class="marked-threads-board"><img src="img/marked.gif" alt="[x]" width="9" height="9" /> <?php echo $lang['marked_threads_actions']; ?> <a href="admin.php?action=delete_marked_threads&amp;refer=mix"><?php echo $lang['delete_marked_threads']; ?></a> - <a href="admin.php?action=lock_marked_threads&amp;refer=mix"><?php echo $lang['lock_marked_threads']; ?></a> - <a href="admin.php?action=unlock_marked_threads&amp;refer=mix"><?php echo $lang['unlock_marked_threads']; ?></a> - <a href="admin.php?action=unmark&amp;refer=mix"><?php echo $lang['unmark_threads']; ?></a> - <a href="admin.php?action=invert_markings&amp;refer=mix"><?php echo $lang['invert_markings']; ?></a> - <a href="admin.php?action=mark_threads&amp;refer=mix"><?php echo $lang['mark_threads']; ?></a></p><?php
      }

     }
     else
     {
      if ($category!=0) echo "<p>".$lang['no_messages_in_category']."</p><p>&nbsp;</p>";
      else echo "<p>".$lang['no_messages']."</p><p>&nbsp;</p>";
     }

echo $footer;

}
else { header("location: login.php?msg=noaccess"); die('<a href="login.php?msg=noaccess">further...</a>'); }
?>
