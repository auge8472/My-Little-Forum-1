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

/**
 * disables magic_quotes from given variables of different types
 *
 * remove the slashes if magic_quotes_gpc is activated
 * see also: http://php.net/manual/en/security.magicquotes.disabling.html
 *
 * @param array [$value]
 * @return array [$value]
 * @since 1.7.8
 * @link http://php.net/manual/en/security.magicquotes.disabling.html
 */
function stripslashes_deep($value) {
	$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	return $value;
} # End: stripslashes_deep


function get_settings() {
	global $lang, $connid, $db_settings;
	$r = array();
	$result = mysqli_query($connid, "SELECT name, value FROM ".$db_settings['settings_table']);
	if (!$result) die($lang['db_error']);
	while ($line = mysqli_fetch_assoc($result)) {
		$r[$line['name']] = $line['value'];
	}
	mysqli_free_result($result);
	return $r;
}

function get_categories()
 {
  global $lang, $settings, $connid, $db_settings;

  $count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ".$db_settings['category_table']);
  list($category_count) = mysqli_fetch_row($count_result);
  mysqli_free_result($count_result);

  if ($category_count > 0)
   {
    if (empty($_SESSION[$settings['session_prefix'].'user_id']))
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 ORDER BY category_order ASC");
     }
    elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "user")
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 ORDER BY category_order ASC");
     }
    elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "mod" || $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 OR accession = 2 ORDER BY category_order ASC");
     }
    if(!$result) die($lang['db_error']);
    $categories[0]='';
    while ($line = mysqli_fetch_assoc($result))
     {
      $categories[$line['id']] = $line['category'];
     }
    mysqli_free_result($result);
    return $categories;
   }
  else return false;
 }

function get_category_ids($categories)
 {
  if($categories!=false)
   {
    foreach ($categories as $key => $val)
     {
      $category_ids[] = $key;
     }
    return $category_ids;
   }
  else return false;
 }

function category_accession()
 {
  global $settings, $lang, $connid, $db_settings;
  $result = mysqli_query($connid, "SELECT id, accession FROM ".$db_settings['category_table']);
  while ($line = mysqli_fetch_assoc($result))
   {
    $category_accession[$line['id']] = $line['accession'];
   }
  mysqli_free_result($result);
  if (isset($category_accession)) return $category_accession; else return false;
 }

function nav($page, $entries_per_page, $entry_count, $order, $descasc, $category, $action="") // Seiten-Navigation für forum.php, board.php und mix.php
  {
   global $lang, $select_submit_button;
   $output = "";
   if ($entry_count > $entries_per_page)
    {
     $output .= "&nbsp; ";
     $new_index_before = $page - 1;
     $new_index_after = $page + 1;
     $site_count = ceil($entry_count / $entries_per_page);
     $ic = "";
     if (isset($category)) $ic .= "&amp;category=". intval($category);
     if (isset($action) && $action!="") $ic .= "&amp;action=". urlencode($action);
     if (isset($_GET['letter'])) $ic .= "&amp;letter=". urlencode($_GET['letter']);
     if ($new_index_before >= 0) $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?page='. intval($new_index_before) .'&amp;order='. urlencode($order) .'&amp;descasc='. urlencode($descasc) . $ic .'"><img src="img/prev.gif" alt="&laquo;" title="'. htmlsc($lang['previous_page_linktitle']) .'" width="12" height="9" /></a>&nbsp;';
     //if ($new_index_before >= 0 && $new_index_after < $site_count) $output .= " ";
     $page_count = ceil($entry_count/$entries_per_page);
     $output .= '<form action="'. basename($_SERVER["SCRIPT_NAME"]) .'" method="get" accept-charset="UTF-8"><div style="display: inline;">';
     if (isset($order)) $output .= '<input type="hidden" name="order" value="'. htmlsc($order).'" />';
     if (isset($descasc)) $output .= '<input type="hidden" name="descasc" value="'. htmlsc($descasc) .'" />';
     if (isset($category)) $output .= '<input type="hidden" name="category" value="'. intval($category) .'" />';
     if (isset($action) && $action!="") $output .= '<input type="hidden" name="action" value="'. htmlsc($action) .'" />';
     if (isset($_GET['letter'])) $output .= '<input type="hidden" name="letter" value="'. htmlsc($_GET['letter']) .'" />';
     $output .= '<select class="kat" size="1" name="page" onchange="this.form.submit();">';
     if ($page == 0) $output .= '<option value="0" selected="selected">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
     else $output .= '<option value="0">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
     for($x=$page-9; $x<$page+10; $x++)
      {
       if ($x > 0 && $x < $page_count)
        {
         if ($page == $x) $output .= '<option value="'. intval($x) .'" selected="selected">'.str_replace("[number]", intval($x+1), $lang['page_number']).'</option>';
         else $output .= '<option value="'. intval($x) .'">'.str_replace("[number]", intval($x+1), $lang['page_number']).'</option>';
        }
      }
    $output .= '</select><noscript>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>';
    if ($new_index_after < $site_count) $output .= '&nbsp;<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?page='. intval($new_index_after) .'&amp;order='. urlencode($order) .'&amp;descasc='. intval($descasc) . $ic .'"><img src="img/next.gif" alt="&raquo;" title="'. htmlsc($lang['next_page_linktitle']) .'" width="12" height="9" /></a>';

    }
   return $output;
  }

// makes URLs clickable:
function make_link($string)
 {
  $string = ' ' . $string;
  $string = preg_replace_callback("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "shorten_link", $string);
  $string = preg_replace("#(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:/[^ \"\t\n\r<]*)?)#is", '\\1<a href="http://\\2">\\2</a>', $string);
  $string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", '\\1<a href="mailto:\\2@\\3">\\2@\\3</a>', $string);
  $string = mb_substr($string, 1);

  return $string;
 }

// function to hide the links from the checkings in posting.php
function text_check_link($string)
 {
  $string = ' ' . $string;
  $string = preg_replace("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "", $string);
  $string = mb_substr($string, 1);
  return $string;
 }

function bbcode($string, $rss = false)
 {
  global $settings;
  $string = preg_replace("#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $string);
  $string = preg_replace("#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $string);
  $string = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $string);
  $string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", '<a href="http://www.\\1">www.\\1</a>', $string);
  $string = preg_replace_callback("#\[link\](.+?)\[/link\]#is", "shorten_link", $string);
  $string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", '<a href="\\1">\\2</a>', $string);
  $string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", '<a href="http://www.\\1">www.\\1</a>', $string);
  $string = preg_replace_callback("#\[url\](.+?)\[/url\]#is", "shorten_link", $string);
  $string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", '<a href="\\1">\\2</a>', $string);
  $string = preg_replace_callback("#\[code\](.+?)\[/code\]#is", "parse_code", $string);
  $string = preg_replace("#\[msg\](.+?)\[/msg\]#is", '<a href="'. basename($_SERVER['PHP_SELF']) .'?id=\\1">\\1</a>', $string);
  $string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", '<a href="'. basename($_SERVER['PHP_SELF']) .'?id=\\1">\\2</a>', $string);
  if ($settings['bbcode_img'] == 1)
   {
    if ($rss === true) {
     $string = preg_replace("#\[img\](.+?)\[/img\]#is", '<img src="\\1" alt="[image]" />', $string);
     $string = str_replace('<img src="img/uploaded', '<img src="'. htmlsc($settings['forum_address']) .'img/uploaded', $string);
    } else {
     $string = preg_replace("#\[img\](.+?)\[/img\]#is", '<img src="\\1" alt="[image]" style="margin: 5px 0px 5px 0px" />', $string);
     $string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", '<img src="\\1" alt="[image]" style="float: left; margin: 0px 5px 5px 0px" />', $string);
     $string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", '<img src="\\1" alt="[image]" style="float: right; margin: 0px 0px 5px 5px" />', $string);
    }
   }
  $string=str_replace('javascript','javascr***',$string);
  return $string;
 }

function shorten_link($string)
 {
  global $settings;
  if(count($string) == 2) { $pre = ""; $url = $string[1]; }
  else { $pre = $string[1]; $url = $string[2]; }
  $shortened_url = $url;
  if (mb_strlen($url) > $settings['text_word_maxlength']) $shortened_url = mb_substr($url, 0, ($settings['text_word_maxlength']/2)) . "..." . mb_substr($url, - ($settings['text_word_maxlength']-3-$settings['text_word_maxlength']/2));
  return $pre .'<a href="'. $url .'">'. htmlsc($shortened_url) .'</a>';
 }

function parse_code($string)
 {
  if(basename($_SERVER['SCRIPT_NAME'])=='board_entry.php' || basename($_SERVER['SCRIPT_NAME'])=='mix_entry.php') $p_class='postingboard'; else $p_class='posting';
  $string = $string[1];
  $string = str_replace('<br />','',$string);
  $string = '</p><pre><span class="code">'.$string.'</span></pre><p class="'.$p_class.'">';
  return $string;
 }

// strips bb codes for e-mail texts:
function unbbcode($string)
 {
  global $settings;
  $string = preg_replace("#\[b\](.+?)\[/b\]#is", "*\\1*", $string);
  $string = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $string);
  $string = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $string);
  $string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "http://www.\\1", $string);
  $string = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $string);
  $string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2 --> \\1", $string);
  $string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "http://www.\\1", $string);
  $string = preg_replace("#\[url\](.+?)\[/url\]#is", "\\1", $string);
  $string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "\\2 --> \\1", $string);
  $string = preg_replace("#\[code\](.+?)\[/code\]#is", "\\1", $string);
  $string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $string);
  $string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $string);
  if (isset($bbcode_img) && $settings['bbcode_img'] == 1)
   {
    $string = preg_replace("#\[img\](.+?)\[/img\]#is", "\\1", $string);
    $string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "\\1", $string);
    $string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "\\1", $string);
   }
  return $string;
 }

function smilies($string)
 {
  global $connid, $db_settings;
  $result = mysqli_query($connid, "SELECT file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']);
  while($data = mysqli_fetch_assoc($result))
   {
    if($data['code_1']!='') $string = str_replace($data['code_1'], '<img src="img/smilies/'. $data['file'] .'" alt="'. $data['code_1'] .'" />', $string);
    if($data['code_2']!='') $string = str_replace($data['code_2'], '<img src="img/smilies/'. $data['file'] .'" alt="'. $data['code_2'] .'" />', $string);
    if($data['code_3']!='') $string = str_replace($data['code_3'], '<img src="img/smilies/'. $data['file'] .'" alt="'. $data['code_3'] .'" />', $string);
    if($data['code_4']!='') $string = str_replace($data['code_4'], '<img src="img/smilies/'. $data['file'] .'" alt="'. $data['code_4'] .'" />', $string);
    if($data['code_5']!='') $string = str_replace($data['code_5'], '<img src="img/smilies/'. $data['file'] .'" alt="'. $data['code_5'] .'" />', $string);
   }
  mysqli_free_result($result);
  return($string);
 }

function zitat($string)
 {
  global $settings;
  $string = preg_replace("/^".htmlsc($settings['quote_symbol'])."\\s+(.*)/", '<span class="citation">'. htmlsc($settings['quote_symbol']) .' \\1</span>', $string);
  $string = preg_replace("/\\n".htmlsc($settings['quote_symbol'])."\\s+(.*)/", '<span class="citation">'. htmlsc($settings['quote_symbol']) .' \\1</span>', $string);
  $string = preg_replace("/\\n ".htmlsc($settings['quote_symbol'])."\\s+(.*)/", '<span class="citation">'. htmlsc($settings['quote_symbol']) .' \\1</span>', $string);
  return $string;
 }

 function rss_quote($string)
 {
  global $settings;
  $string = preg_replace("/^".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  $string = preg_replace("/\\n".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  $string = preg_replace("/\\n ".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  return $string;
 }

// connects to the database:
function connect_db($host,$user,$pw,$db) {
	global $lang;
	$connid = @mysqli_connect($host, $user, $pw, $db);  // open database connection
	if (!$connid) die($lang['db_error']);
	mysqli_set_charset($connid, 'utf8mb4');
	return $connid;
}

// counts the users which are online:
function user_online()
 {
  $user_online_period = 10;
  global $connid, $db_settings, $settings;
  if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id']; else $user_id = 0;
  $diff = time()-($user_online_period*60);
  if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $ip = "uid_".$_SESSION[$settings['session_prefix'].'user_id'];
  else $ip = $_SERVER['REMOTE_ADDR'];
  @mysqli_query($connid, "DELETE FROM ". $db_settings['useronline_table'] ." WHERE time < ". intval($diff));
  list($is_online) = @mysqli_fetch_row(@mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['useronline_table'] ." WHERE ip= '". mysqli_real_escape_string($connid, $ip) ."'"));
  if ($is_online > 0) @mysqli_query($connid, "UPDATE ". $db_settings['useronline_table'] ." SET time='".time()."', user_id='". intval($user_id) ."' WHERE ip='". mysqli_real_escape_string($connid, $ip) ."'");
  else @mysqli_query($connid, "INSERT INTO ". $db_settings['useronline_table'] ." SET time='". time() ."', ip='". mysqli_real_escape_string($connid, $ip) ."', user_id=". intval($user_id));
  #return $user_online;
 }

 // displays the thread tree:
 function thread_tree($id, $aktuellerEintrag = 0, $tiefe = 0)
 {
  global $settings, $lang, $parent_array, $child_array, $page, $category, $order, $db_settings, $connid, $last_visit, $categories, $category_accession;

  // highlighting of admins and mods:
  $mark_admin = false; $mark_mod = false;
  if ($settings['admin_mod_highlight'] == 1 && $parent_array[$id]["user_id"] > 0)
  {
   $userdata_result=mysqli_query($connid, "SELECT user_type FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($parent_array[$id]["user_id"]));
   if (!$userdata_result) die($lang['db_error']);
   $userdata = mysqli_fetch_assoc($userdata_result);
   mysqli_free_result($userdata_result);
   if ($userdata['user_type'] == "admin") $mark_admin = true;
   elseif ($userdata['user_type'] == "mod") $mark_mod = true;
  }

  if ($mark_admin==true) $name = '<span class="admin-highlight">'. htmlsc($parent_array[$id]["name"]) .'</span>';
  elseif ($mark_mod==true) $name = '<span class="mod-highlight">'. htmlsc($parent_array[$id]["name"]) .'</span>';
  else $name=htmlsc($parent_array[$id]["name"]);
  if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1)
   {
    $thread_info_a = str_replace("[name]", $name .'<a href="user.php?id='. intval($parent_array[$id]["user_id"]) .'"><img src="img/registered.gif" alt="'. $lang['registered_user_title'] .'" width="10" height="10" /></a>', $lang['thread_info']);
   }
  elseif (!isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1) $thread_info_a = str_replace("[name]", $name .'<img src="img/registered.gif" alt="'. $lang['registered_user_title'] .'" width="10" height="10" />', $lang['thread_info']);
  else $thread_info_a = str_replace("[name]", $name, $lang['thread_info']);
  $thread_info_b = str_replace("[time]", strftime($lang['time_format'],$parent_array[$id]["tp_time"]), $thread_info_a);

  ?><li><?php
  if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]==0)
   {
    ?><span class="actthread"><?php echo htmlsc($parent_array[$id]["subject"]); ?></span><?php
   }
  elseif ($id == $aktuellerEintrag && $parent_array[$id]["pid"]!=0)
   {
    ?><span class="actreply"><?php echo htmlsc($parent_array[$id]["subject"]); ?></span><?php
   }
  else
   {
     if ((($parent_array[$id]["pid"]==0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["last_answer"]) || (($parent_array[$id]["pid"]==0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]["last_answer"] > $last_visit)) {
        $treeClass = "threadnew";
     } else if ($parent_array[$id]["pid"]==0) {
        $treeClass = "thread";
     } else if ((($parent_array[$id]["pid"]!=0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["time"]) || (($parent_array[$id]["pid"]!=0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]["time"] > $last_visit)) {
        $treeClass = "replynew";
     } else {
        $treeClass = "reply";
     }
    ?><a class="<?php echo $treeClass; ?>" href="forum_entry.php?id=<?php echo intval($parent_array[$id]["id"]); if ($page != 0 || $category != 0 || $order != "time") echo '&amp;page='. intval($page) .'&amp;category='. intval($category) .'&amp;order='. urlencode($order); ?>"><?php echo htmlsc($parent_array[$id]["subject"]); ?></a><?php
   }
  echo " " . $thread_info_b;
  if ($parent_array[$id]["pid"]==0 && $category==0 && isset($categories[$parent_array[$id]["category"]]) && $categories[$parent_array[$id]["category"]]!='') { ?> <a title="<?php echo str_replace("[category]", htmlsc($categories[$parent_array[$id]["category"]]), $lang['choose_category_linktitle']); if (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 2) echo " ". htmlsc($lang['admin_mod_category']); elseif (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 1) echo " ". htmlsc($lang['registered_users_category']); ?>" href="forum.php?category=<?php echo intval($parent_array[$id]["category"]); ?>"><span class="<?php if (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 2) echo "category-adminmod"; elseif (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 1) echo "category-regusers"; else echo "category"; ?>">(<?php echo $categories[$parent_array[$id]["category"]]; ?>)</span></a><?php }
  if ($aktuellerEintrag == 0 && $parent_array[$id]["pid"]==0 && $parent_array[$id]["fixed"] == 1) { ?> <img src="img/fixed.gif" width="9" height="9" title="<?php echo $lang['fixed']; ?>" alt="*" /><?php }
  if ($parent_array[$id]["pid"]==0 && $settings['all_views_direct'] == 1) { echo ' <span class="small">'; if ($settings['board_view']==1) { ?><a href="board_entry.php?id=<?php echo intval($parent_array[$id]["tid"]); ?>"><img src="img/board_d.gif" alt="[Board]" title="<?php echo htmlsc($lang['open_in_board_linktitle']); ?>" width="12" height="9" /></a><?php } if ($settings['mix_view'] == 1) { ?><a href="mix_entry.php?id=<?php echo intval($parent_array[$id]["tid"]); ?>"><img src="img/mix_d.gif" alt="[Mix]" title="<?php echo htmlsc($lang['open_in_mix_linktitle']); ?>" width="12" height="9" /></a><?php } echo "</span>"; }

      if ($parent_array[$id]["pid"]==0 && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
     {
      ?> <a href="admin.php?mark=<?php echo intval($parent_array[$id]["tid"]); ?>&amp;refer=<?php echo urlencode(basename($_SERVER["SCRIPT_NAME"])); ?>&amp;page=<?php echo intval($page); ?>&amp;category=<?php echo intval($category); ?>&amp;order=<?php echo urlencode($order); ?>"><?php
      if ($parent_array[$id]['marked']==1) { ?><img src="img/marked.gif" alt="<?php echo htmlsc($lang['demark_linktitle']); ?>" width="9" height="9" /><?php }
      else { echo '<img src="img/mark.gif" alt="'. htmlsc($lang['mark_linktitle']) .'" width="9" height="9" />'; }
      ?></a><?php
     }

  // display all branches of the thread tree:
  if(isset($child_array[$id]) && is_array($child_array[$id]))
   {
    ?><ul class="<?php if ($tiefe >= $settings['thread_depth_indent']) echo 'deep-reply'; else echo 'reply'; ?>"><?php
    foreach($child_array[$id] as $kind)
     {
      thread_tree($kind, $aktuellerEintrag, $tiefe+1);
     }
    ?></ul><?php
   }
  ?></li><?php
 }

function parse_template()
 {
  global $settings, $lang, $header, $footer, $wo, $ao, $topnav, $subnav_1, $subnav_2, $footer_info_dump, $search, $show_postings, $counter;
  $template = implode("",file($settings['template']));

  if ($settings['home_linkaddress'] != "" && $settings['home_linkname'] != "") $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "\\1", $template);
  else $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "", $template);

  $template = str_replace("{LANGUAGE}",$lang['language'],$template);
  $template = str_replace("{CHARSET}",$lang['charset'],$template);
  $title = $settings['forum_name']; if (isset($wo)) $title .= " - ". htmlsc($wo);
  $template = str_replace("{TITLE}",$title,$template);
  $template = str_replace("{FORUM-NAME}", $settings['forum_name'],$template);
  $template = str_replace('{HOME-ADDRESS}',$settings['home_linkaddress'],$template);
  $template = str_replace('{HOME-LINK}',$settings['home_linkname'],$template);
  $template = str_replace('{FORUM-INDEX-LINK}',$lang['forum_home_linkname'],$template);
  $template = str_replace('{FORUM-INDEX-LINKTITLE}',$lang['forum_home_linktitle'],$template);
  $template = str_replace('{FORUM-ADDRESS}',$settings['forum_address'],$template);
  $template = str_replace('{CONTACT}',$lang['contact_linkname'],$template);

  // User menu:
  if (isset($_SESSION[$settings['session_prefix']."user_name"])) { if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=="admin") $user_menu_admin = ' | <a href="admin.php">'.$lang['admin_area_linkname'].'</a>'; else $user_menu_admin = ""; $user_menu = '<a href="user.php?id='. intval($_SESSION[$settings['session_prefix'].'user_id']) .'"><b>'. htmlsc($_SESSION[$settings['session_prefix'].'user_name']) .'</b></a> | <a href="user.php">'.$lang['user_area_linkname'].'</a>'.$user_menu_admin.' | <a href="login.php">'.$lang['logout_linkname'].'</a>'; }
  else $user_menu = '<a href="login.php">'.$lang['login_linkname'].'</a> | <a href="register.php">'.$lang['register_linkname'].'</a>';
  $template = str_replace("{USER-MENU}",$user_menu,$template);

  // Search:
  $search_dump = '<form action="search.php" method="get" accept-charset="UTF-8"><div class="search">';
  $search_dump .= $lang['search_marking'];
  $search_dump .= '<span class="normal">&nbsp;</span><input class="searchfield" type="text" name="search" value="" size="20" /><span class="normal">&nbsp;</span><input type="image" name="" src="img/submit.gif" alt="&raquo;" /></div></form>';
  $template = str_replace("{SEARCH}",$search_dump,$template);

  // Sub navigation:
  if (isset($topnav)) $tnd = $topnav; else $tnd = ""; $template = str_replace("{LOCATION}",$tnd,$template);
  if (isset($subnav_1)) $subnav_1_dump = $subnav_1; else $subnav_1_dump = "&nbsp;";
  $template = str_replace("{SUB-NAVIGATION-1}",$subnav_1_dump,$template);
  if (isset($subnav_2)) $subnav_2_dump = $subnav_2; else $subnav_2_dump = "&nbsp;";
  $template = str_replace("{SUB-NAVIGATION-2}",$subnav_2_dump,$template);

  // Footer:
  $template = str_replace("{COUNTER}",$counter,$template);
  if ($settings['provide_rssfeed'] == 1 && $settings['access_for_users_only'] == 0) 
   { 
    $rss_feed_link = '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="rss.php" />';
    $rss_feed_button = '<a href="rss.php"><img src="img/rss.png" width="14" height="14" alt="RSS Feed" /></a><br />'; 
   }
  else 
   {
    $rss_feed_link = '';
    $rss_feed_button = '';
   }
  $template = str_replace("{RSS-FEED-LINK}",$rss_feed_link,$template);
  $template = str_replace("{RSS-FEED-BUTTON}",$rss_feed_button,$template);

  $template_parts = explode("{CONTENT}",$template);
  if (isset($template_parts[0])) $header = $template_parts[0]; else $header = "";
  if (isset($template_parts[1])) $footer = $template_parts[1]; else $footer = "";
 }

/**
 * encapsulated htmlspecialchars with the correct charset
 *
 * @param string @string
 * @return string @string
 */
function htmlsc($string) {
	global $lang;
	return htmlspecialchars($string, ENT_QUOTES, $lang['charset'], false);
}

/**
 * unifies all possible line breaks into unixoid break
 *
 * @param string $string
 * @param string $to
 * @return string $string
 */
function convertLineBreaks($string, $to = "\n") {
	return preg_replace("/\015\012|\015|\012/", $to, $string);
} # End: convertLineBreaks

/**
 * puts a name into a formatted string for mail header
 *
 * @param string $name
 * @param string $linefeed
 * @return string $name
 */
function encodeMailName($name, $lf = "\r\n") {
	$name = str_replace('"', '\\"', $name);
	if (preg_match("/(\.|\;|\")/", $name)) {
		return '"'.mb_encode_mimeheader($name, 'UTF-8', "Q", $lf).'"';
	} else {
		return mb_encode_mimeheader($name, 'UTF-8', "Q", $lf);
	}
} # End: encodeMailName

/**
 * Encode string to quoted-printable.
 * Original written by Andy Prevost http://phpmailer.sourceforge.net
 * and distributed under the Lesser General Public License (LGPL) http://www.gnu.org/copyleft/lesser.html
 *
 * @return string
 */
function myQuotedPrintableEncode($input, $line_max = 76, $space_conv = false ) {
	$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	$lines = preg_split('/(?:\r\n|\r|\n)/', $input);
	$eol = "\n";
	$escape = '=';
	$output = '';
	foreach ($lines as $line) {
		$linlen = mb_strlen($line);
		$newline = '';
		for ($i = 0; $i < $linlen; $i++) {
			$c = mb_substr($line, $i, 1);
			$dec = ord( $c );
			# convert first point in the line into =2E
			if (($i == 0) && ($dec == 46)) {
				$c = '=2E';
			}
			if ($dec == 32) {
				# convert space at eol only
				if ($i == ($linlen-1)) {
					$c = '=20';
				} else if ($space_conv) {
					$c = '=20';
				}
			} else if (($dec == 61) || ($dec < 32) || ($dec > 126)) {
				# always encode "\t", which is *not* required
				$h2 = floor($dec/16);
				$h1 = floor($dec%16);
				$c = $escape . $hex[$h2] . $hex[$h1];
			}
			# CRLF is not counted
			if ((mb_strlen($newline) + mb_strlen($c)) >= $line_max) {
				# soft line break; " =\r\n" is okay
				$output .= $newline . $escape . $eol;
				$newline = '';
				# check if newline first character will be point or not
				if ($dec == 46) {
					$c = '=2E';
				}
			}
			$newline .= $c;
		} # end of for
		$output .= $newline . $eol;
	} # end of while
	return $output;
} # End: myQuotedPrintableEncode

/**
 * formats and sends an email
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $from
 * @return bool
 */
function processEmail($to, $subject, $message, $from = '') {
	global $settings;
	$mhs = "\n";
	$to = convertLineBreaks($to, '');
	$subject = mb_encode_mimeheader(convertLineBreaks($subject, ''), 'UTF-8', "Q", $mhs);
	$message = myQuotedPrintableEncode($message);
	$headers = "From: ". encodeMailName($settings['forum_name'], $mhs) ." <". $settings['forum_email'] .">". $mhs;
	if ($from !== '') {
		$headers = "Reply-To: ". encodeMailName($from['name'], $mhs) ." <". $from['email'] .">". $mhs;
	}
	$headers .= "MIME-Version: 1.0". $mhs;
	$headers .= "X-Mailer: Php/". phpversion() . $mhs;
	$headers .= "X-Sender-IP: ". $_SERVER['REMOTE_ADDR'] . $mhs;
	$headers .= "Content-Type: text/plain; charset=UTF-8; format=flowed". $mhs;
	$headers .= "Content-Transfer-Encoding: quoted-printable";
	if ($settings['mail_parameter']!='') {
		if (@mail($to, $subject, $message, $headers, $settings['mail_parameter'])) {
			return true;
		} else {
			return false;
		}
	} else {
		if (@mail($to, $subject, $message, $headers)) {
			return true;
		} else {
			return false;
		}
	}
} # End: processEmail

?>
