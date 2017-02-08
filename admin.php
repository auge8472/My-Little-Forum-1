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
include("lang/".$lang['additional_language_file']);

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
{

// remove not activated user accounts:
@mysql_query("DELETE FROM ".$db_settings['userdata_table']." WHERE registered < (NOW() - INTERVAL 24 HOUR) AND activate_code != '' AND logins=0", $connid);

unset($errors);
if (isset($_GET['action'])) $action = $_GET['action'];
if (isset($_POST['action'])) $action = $_POST['action'];

function split_sql($sql)
 {
  // remove comments and empty lines:
  $lines = explode("\n", $sql);
  $cleared_lines = array();
  foreach($lines as $line)
   {
    $line = trim($line);
    if($line != '' && substr($line,0,1)!='#') $cleared_lines[] = $line;
   }
  unset($lines);
  $lines2 = $cleared_lines;
  foreach($lines2 as $line)
   {
    $line = stripslashes($line);
    if(substr($line, -1)==';') $lines[] = substr($line,0,-1);
   }
  return $lines;
 }

// Functions for SQL-Dump:
   function sql_forum()
     {
      global $db_settings, $connid, $lang;
      $sql_result=mysql_query("SELECT id, pid, tid, uniqid, time, last_answer, edited, edited_by, user_id, name, subject, category, email, hp, place, ip, text, show_signature, email_notify, marked, locked, fixed, views FROM ".$db_settings['forum_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['forum_table']." VALUES (".$field['id'].", ".$field['pid'].", ".$field['tid'].", '".$field['uniqid']."', '".$field['time']."', '".$field['last_answer']."', '".$field['edited']."', '".htmlsc(addslashes($field['edited_by']))."', ".$field['user_id'].", '".htmlsc(addslashes($field['name']))."', '".htmlsc(addslashes($field['subject']))."', ".$field['category'].", '".htmlsc(addslashes($field['email']))."', '".htmlsc(addslashes($field['hp']))."', '".htmlsc(addslashes($field['place']))."', '".$field['ip']."', '".str_replace("\r", "\\r", str_replace("\n",  "\\n", htmlsc(addslashes($field['text']))))."', ".$field['show_signature'].", ".$field['email_notify'].", ".$field['marked'].", ".$field['locked'].", ".$field['fixed'].", ".$field['views'].");<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_forum_marked()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT id, pid, tid, uniqid, time, last_answer, edited, edited_by, user_id, name, subject, category, email, hp, place, ip, text, show_signature, email_notify, marked, locked, fixed, views FROM ".$db_settings['forum_table']." WHERE marked='1'", $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Marked forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['forum_table']." VALUES (".$field['id'].", ".$field['pid'].", ".$field['tid'].", '".$field['uniqid']."', '".$field['time']."', '".$field['last_answer']."', '".$field['edited']."', '".htmlsc(addslashes($field['edited_by']))."', ".$field['user_id'].", '".htmlsc(addslashes($field['name']))."', '".htmlsc(addslashes($field['subject']))."', ".$field['category'].", '".htmlsc(addslashes($field['email']))."', '".htmlsc(addslashes($field['hp']))."', '".htmlsc(addslashes($field['place']))."', '".$field['ip']."', '".str_replace("\r", "\\r", str_replace("\n",  "\\n", htmlsc(addslashes($field['text']))))."', ".$field['show_signature'].", ".$field['email_notify'].", ".$field['marked'].", ".$field['locked'].", ".$field['fixed'].", ".$field['views'].");<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_userdata()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT user_id, user_type, user_name, user_real_name, user_pw, user_email, hide_email, user_hp, user_place, signature, profile, logins, last_login, last_logout, user_ip, registered, user_view, new_posting_notify, new_user_notify, personal_messages, time_difference, user_lock, pwf_code, activate_code FROM ".$db_settings['userdata_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Userdata (<?php echo $db_settings['userdata_table']; ?>):<br /><br /><?php
        while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['userdata_table']." VALUES (".$field['user_id'].", '".$field['user_type']."', '".htmlsc(addslashes($field['user_name']))."', '".htmlsc(addslashes($field['user_real_name']))."', '".htmlsc(addslashes($field['user_pw']))."', '".htmlsc(addslashes($field['user_email']))."', '".$field['hide_email']."', '".htmlsc(addslashes($field['user_hp']))."', '".htmlsc(addslashes($field['user_place']))."', '".str_replace("\r", "\\r", str_replace("\n",  "\\n", htmlsc(addslashes($field['signature']))))."', '".str_replace("\r", "\\r", str_replace("\n",  "\\n", htmlsc(addslashes($field['profile']))))."', ".$field['logins'].", '".$field['last_login']."', '".$field['last_logout']."', '".$field['user_ip']."', '".$field['registered']."', '".$field['user_view']."', ".$field['new_posting_notify'].", ".$field['new_user_notify'].", ".$field['personal_messages'].", ".$field['time_difference'].", ".$field['user_lock'].", '".$field['pwf_code']."', '".$field['activate_code']."');<br />";
       }
       mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_categories()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT id, category_order, category, description, accession FROM ".$db_settings['category_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Categories (<?php echo $db_settings['category_table']; ?>):<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['category_table']." VALUES (".$field['id'].", ".$field['category_order'].", '".htmlsc(addslashes($field['category']))."', '".htmlsc(addslashes($field['description']))."', ".$field['accession'].");<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_settings()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT name, value FROM ".$db_settings['settings_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Settings (<?php echo $db_settings['settings_table']; ?>)<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['settings_table']." VALUES ('".$field['name']."', '".htmlsc(addslashes($field['value']))."');<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_smilies()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT id, order_id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Smilies (<?php echo $db_settings['smilies_table']; ?>)<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['smilies_table']." VALUES (".$field['id'].", ".$field['order_id'].", '".htmlsc(addslashes($field['file']))."', '".htmlsc(addslashes($field['code_1']))."', '".htmlsc(addslashes($field['code_2']))."', '".htmlsc(addslashes($field['code_3']))."', '".htmlsc(addslashes($field['code_4']))."', '".htmlsc(addslashes($field['code_5']))."', '".htmlsc(addslashes($field['title']))."');<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
    function sql_banlists()
     {
      global $lang, $db_settings, $connid;
      $sql_result=mysql_query("SELECT name, list FROM ".$db_settings['banlists_table'], $connid);
      if(!$sql_result) die($lang['db_error']);
      ?><pre># Banlists (<?php echo $db_settings['banlists_table']; ?>)<br /><br /><?php
      while ($field = mysql_fetch_assoc($sql_result))
       {
        echo "INSERT INTO ".$db_settings['banlists_table']." VALUES ('".$field['name']."', '".htmlsc(addslashes($field['list']))."');<br />";
       }
      mysql_free_result($sql_result);
      ?><br /></pre><?php
     }
// SQL-Dump:
if (isset($_GET['backup']))
 {
  ?><html>
  <head>
  <title><?php echo $settings['forum_name']; ?> - SQL</title>
  </head>
  <body>
  <?php
   switch ($_GET['backup'])
     {
      case 1:
       sql_forum();
       sql_categories();
       sql_userdata();
       sql_settings();
       sql_smilies();
       sql_banlists();
      break;
      case 2:
       sql_forum();
      break;
      case 3:
       sql_forum_marked();
      break;
      case 4:
       sql_userdata();
      break;
      case 5:
       sql_categories();
      break;
      case 6:
       sql_settings();
      break;
      case 7:
       sql_smilies();
      break;
      case 8:
       sql_banlists();
      break;
     }
  ?></body>
  </html><?php
  exit;
 }

if(isset($_POST['sql_submit']))
 {
  $sql = $_POST['sql'];

  $pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
  if (!$pw_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($pw_result);
  mysql_free_result($pw_result);
  if ($_POST['sql_pw']=='') $errors[] = $lang['error_form_uncompl'];
  else
   {
    if ($field['user_pw'] != md5(trim($_POST['sql_pw']))) $errors[] = $lang['pw_wrong'];
   }

  if(empty($errors))
   {
    $sql_querys = split_sql($sql);
    foreach($sql_querys as $sql_query)
     {
      #echo $sql_query.'<br />';
      mysql_query($sql_query, $connid) or $errors[] = $lang_add['mysql_error'] . mysql_error($connid);
      if(isset($errors)) break;
     }
     #die();
     if(empty($errors)) $action = 'import_sql_ok';
     else $action='import_sql';
   }
  else $action='import_sql';
 }

if (isset($_GET['mark']))
 {
  $mark_result = mysql_query("SELECT marked FROM ".$db_settings['forum_table']." WHERE id='".$_GET['mark']."' LIMIT 1", $connid);
  if (!$mark_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($mark_result);
  mysql_free_result($mark_result);
  if ($field['marked']==0) $marked = 1; else $marked = 0;

  mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='".$marked."' WHERE tid='".$_GET['mark']."'", $connid);

  header("location: ".$_GET['refer']."?id=".$_GET['mark']."&category=".$_GET['category']."&page=".$_GET['page']."&order=".$_GET['order']);
  die("<a href=\"".$_GET['refer']."?id=".$_GET['mark']."&amp;category=".$_GET['category']."&amp;page=".$_GET['page']."&amp;order=".$_GET['order']."\">further...</a>");

 }

if (isset($_POST['new_category']))
 {
  $new_category = trim($_POST['new_category']);
  $new_category = str_replace('"','\'',$new_category);
  $accession = intval($_POST['accession']);
  if($new_category!='')
   {
    #if(preg_match("/\"/i",$new_category) || preg_match("/</i",$new_category) || preg_match("/>/i",$new_category)) $errors[] = $lang_add['category_invalid_chars'];

    // does this category already exist?
    $category_result = mysql_query("SELECT category FROM ".$db_settings['category_table']." WHERE category = '".mysql_escape_string($new_category)."' LIMIT 1", $connid);
    if(!$category_result) die($lang['db_error']);
    $field = mysql_fetch_assoc($category_result);
    mysql_free_result($category_result);

    if(strtolower($field["category"]) == strtolower($new_category)) $errors[] = $lang_add['category_already_exists'];

    if(empty($errors))
     {
      $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
      list($category_count) = mysql_fetch_row($count_result);
      mysql_free_result($count_result);
      mysql_query("INSERT INTO ".$db_settings['category_table']." (category_order, category, accession)
      VALUES (".$category_count."+1,'".mysql_escape_string($new_category)."',".$accession.")", $connid);
      header("location: admin.php?action=categories");
      exit();
     }
   }
  $action='categories';
 }

if(isset($_GET['edit_user']))
 {
  $edit_user_id = intval($_GET['edit_user']);
  $result = mysql_query("SELECT user_type, user_name, user_real_name, user_email, hide_email, user_hp, user_place, signature, profile, user_view, new_posting_notify, new_user_notify, personal_messages, time_difference FROM ".$db_settings['userdata_table']." WHERE user_id = '".$edit_user_id."'", $connid) or die($lang['db_error']);
  $field = mysql_fetch_assoc($result);
  mysql_free_result($result);
  $edit_user_type = $field["user_type"];
  $user_email = $field["user_email"];
  $hide_email = $field["hide_email"];
  $edit_user_name = $field["user_name"];
  $user_real_name = $field["user_real_name"];
  $user_hp = $field["user_hp"];
  $user_place = $field["user_place"];
  $profile = $field["profile"];
  $signature = $field["signature"];
  $user_view = $field["user_view"];
  $user_time_difference = $field["time_difference"];
  $new_posting_notify = $field["new_posting_notify"];
  $new_user_notify = $field["new_user_notify"];
  $personal_messages = $field["personal_messages"];
  $action = 'edit_user';
 }

if(isset($_POST['edit_user_submit']))
 {
  // import posted data:
  $edit_user_id = intval($_POST['edit_user_id']);
  $edit_user_name = trim($_POST['edit_user_name']);
  $edit_user_type = trim($_POST['edit_user_type']);
  $user_email = trim($_POST['user_email']);
  $hide_email = trim($_POST["hide_email"]);
  $user_real_name = trim($_POST['user_real_name']);
  $user_hp = trim($_POST['user_hp']);
  $user_place = trim($_POST['user_place']);
  $profile = trim($_POST['profile']);
  $signature = trim($_POST['signature']);
  $user_view = trim($_POST['user_view']);
  $personal_messages = trim($_POST['personal_messages']);
  $user_time_difference = trim($_POST['user_time_difference']);
  if(isset($_POST['new_posting_notify'])) $new_posting_notify = trim($_POST['new_posting_notify']); else $new_posting_notify = 0;
  if(isset($_POST['new_user_notify'])) $new_user_notify = trim($_POST['new_user_notify']); else $new_user_notify = 0;

  // check data:
  if(empty($user_view) or $user_view == '') $user_view = $standard;
  // does the name already exist?
  $name_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_name = '".mysql_escape_string($edit_user_name)."'", $connid) or die($lang['db_error']);
  $field = mysql_fetch_assoc($name_result);
  mysql_free_result($name_result);
  if($edit_user_id != $field['user_id'] && strtolower($field["user_name"]) == strtolower($edit_user_name)) $errors[] = str_replace("[name]", htmlsc(stripslashes($edit_user_name)), $lang['error_name_reserved']);
  if(strlen($user_real_name) > $settings['name_maxlength']) $errors[] = $lang['user_real_name'] . " " .$lang['error_input_too_long'];
  if(strlen($user_hp) > $settings['hp_maxlength']) $errors[] = $lang['user_hp'] . " " .$lang['error_input_too_long'];
  if(strlen($user_place) > $settings['place_maxlength']) $errors[] = $lang['user_place'] . " " .$lang['error_input_too_long'];
  if(strlen($profile) > $settings['profile_maxlength'])
   {
    $lang['err_prof_too_long'] = str_replace("[length]", strlen($profile), $lang['err_prof_too_long']);
    $lang['err_prof_too_long'] = str_replace("[maxlength]", $settings['profile_maxlength'], $lang['err_prof_too_long']);
    $errors[] = $lang['err_prof_too_long'];
   }
  if (strlen($signature) > $settings['signature_maxlength'])
   {
    $lang['err_sig_too_long'] = str_replace("[length]", strlen($signature), $lang['err_sig_too_long']);
    $lang['err_sig_too_long'] = str_replace("[maxlength]", $settings['signature_maxlength'], $lang['err_sig_too_long']);
    $errors[] = $lang['err_sig_too_long'];
   }

  $text_arr = explode(" ",$user_real_name); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['name_word_maxlength']) {
  $error_nwtl = str_replace("[word]", htmlsc(stripslashes(substr($text_arr[$i],0,$settings['name_word_maxlength'])))."...", $lang['error_name_word_too_long']);
  $errors[] = $error_nwtl; } }
  $text_arr = explode(" ",$user_place); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['place_word_maxlength']) {
  $error_pwtl = str_replace("[word]", htmlsc(stripslashes(substr($text_arr[$i],0,$settings['place_word_maxlength'])))."...", $lang['error_place_word_too_long']);
  $errors[] = $error_pwtl; } }
  $text_arr = str_replace("\n", " ", $profile);
  if ($settings['bbcode'] == 1) { $text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr); }
  if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1) { $text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr); $text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr); $text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr); }
  $text_arr = explode(" ",$text_arr); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['text_word_maxlength']) {
  $error_twtl = str_replace("[word]", htmlsc(stripslashes(substr($text_arr[$i],0,$settings['text_word_maxlength'])))."...", $lang['err_prof_word_too_long']);
  $errors[] = $error_twtl; } }
  $text_arr = str_replace("\n", " ", $signature);
  if ($settings['bbcode'] == 1) { $text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr); }
  if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1) { $text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr); $text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr); $text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr); }
  $text_arr = explode(" ",$text_arr); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['text_word_maxlength']) {
  $error_twtl = str_replace("[word]", htmlsc(stripslashes(substr($text_arr[$i],0,$settings['text_word_maxlength'])))."...", $lang['err_sig_word_too_long']);
  $errors[] = $error_twtl; } }
  // end of checking

  // save if no errors:
  if(empty($errors))
   {
    @mysql_query("UPDATE ".$db_settings['userdata_table']." SET user_name='".mysql_escape_string($edit_user_name)."', user_type='".mysql_escape_string($edit_user_type)."', user_email='".mysql_escape_string($user_email)."', user_real_name='".mysql_escape_string($user_real_name)."', hide_email='".$hide_email."', user_hp='".mysql_escape_string($user_hp)."', user_place='".mysql_escape_string($user_place)."', profile='".mysql_escape_string($profile)."', signature='".mysql_escape_string($signature)."', last_login=last_login, registered=registered, user_view='".$user_view."', new_posting_notify='".$new_posting_notify."', new_user_notify='".$new_user_notify."', personal_messages='".$personal_messages."', time_difference='".$user_time_difference."' WHERE user_id=".$edit_user_id, $connid) or die($lang['db_error']);
    @mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, name='".mysql_escape_string($edit_user_name)."' WHERE user_id=".$edit_user_id, $connid);
    header("location: admin.php?action=user");
    die("<a href=\"admin.php?action=user\">further...</a>");
   }
  $action = 'edit_user';
 }

if(isset($_GET['edit_category']))
 {
  $category_result = mysql_query("SELECT id, category_order, category, accession FROM ".$db_settings['category_table']." WHERE id = '".$_GET['edit_category']."' LIMIT 1", $connid);
  if(!$category_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($category_result);
  mysql_free_result($category_result);
  $id = $field['id'];
  $category = $field['category'];
  $accession = $field['accession'];
  $action = "edit_category";
 }

if (isset($_GET['delete_category']))
 {
  $category_result = mysql_query("SELECT id, category FROM ".$db_settings['category_table']." WHERE id = '".intval($_GET['delete_category'])."' LIMIT 1", $connid);
  if(!$category_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($category_result);
  mysql_free_result($category_result);
  $category_id = $field['id'];
  $category_name = $field['category'];
  $action = "delete_category";
 }

if (isset($_POST['edit_category_submit']))
 {
  $id = intval($_POST['id']);
  $category = trim($_POST['category']);
  $category = str_replace('"','\'',$category);
  $accession = intval($_POST['accession']);
  // does this category already exist?
  $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table']." WHERE category LIKE '".mysql_escape_string($category)."' AND id != ".$id, $connid);
  if(!$count_result) die($lang['db_error']);
  list($category_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);

  if($category_count > 0) $errors[] = $lang_add['category_already_exists'];

  if(empty($errors))
   {
    mysql_query("UPDATE ".$db_settings['category_table']." SET category='".mysql_escape_string($category)."', accession=".$accession." WHERE id=".$id, $connid);
    #mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category='".$_POST['edit_category_conf']."' WHERE category='".$_POST['old_category']."'", $connid);
    header("location: admin.php?action=categories");
    die();
   }
  $action='edit_category';
 }

if (isset($_POST['not_displayed_entries_submit']))
 {
  if ($_POST['mode'] == "delete")
   {
    if(isset($category_ids_query))
     {
      mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category NOT IN (".$category_ids_query.")", $connid);
     }
    else
     {
      mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category != 0", $connid);
     }
   }
  else
   {
    if(isset($category_ids_query))
     {
      mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category NOT IN (".$category_ids_query.")", $connid);
     }
    else
     {
      mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category != 0", $connid);
     }
   }
  header("location: admin.php?action=categories");
  die();
 }

if (isset($_GET['move_up_category']))
 {
  $category_result = mysql_query("SELECT category_order FROM ".$db_settings['category_table']." WHERE id = ".$_GET['move_up_category']." LIMIT 1", $connid);
  if(!$category_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($category_result);
  mysql_free_result($category_result);
  if ($field['category_order'] > 1)
   {
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=0 WHERE category_order=".$field['category_order']."-1", $connid);
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=category_order-1 WHERE category_order=".$field['category_order'], $connid);
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$field['category_order']." WHERE category_order=0", $connid);
   }
  header("location: admin.php?action=categories");
  die();
 }

if (isset($_GET['move_down_category']))
 {
  $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
  list($category_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);

  $category_result = mysql_query("SELECT category_order FROM ".$db_settings['category_table']." WHERE id = ".$_GET['move_down_category']." LIMIT 1", $connid);
  if(!$category_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($category_result);
  mysql_free_result($category_result);
  if ($field['category_order'] < $category_count)
   {
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=0 WHERE category_order=".$field['category_order']."+1", $connid);
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=category_order+1 WHERE category_order=".$field['category_order'], $connid);
    mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$field['category_order']." WHERE category_order=0", $connid);
   }
  header("location: admin.php?action=categories");
  die();
 }

if (isset($_POST['delete_category_submit']))
 {
  $category_id = intval($_POST['category_id']);
  if($category_id > 0)
   {
    #$category_result = mysql_query("SELECT category_order, category, accession FROM ".$db_settings['category_table']." ORDER BY category_order ASC", $connid);
    #if(!$category_result) die($lang['db_error']);
    #$i = 1;

    // delete category from category table:
    mysql_query("DELETE FROM ".$db_settings['category_table']." WHERE id=".$category_id, $connid);

    // reset order:
    $result = mysql_query("SELECT id FROM ".$db_settings['category_table']." ORDER BY category_order ASC", $connid);
    $i=1;
    while ($data = mysql_fetch_assoc($result))
     {
      mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$i." WHERE id = ".intval($data['id']), $connid);
      $i++;
     }
    mysql_free_result($result);

    // what to to with the entries of deleted category:
    if ($_POST['delete_mode'] == "complete")
     {
      mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category = ".$category_id, $connid);
     }
    else
     {
      mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category = ".$category_id, $connid);
     }
    header("location: admin.php?action=categories");
    die();
   }
  $action = 'categories';
 }

if (isset($_GET['delete_user']))
 {
  $user_id = intval($_GET['delete_user']);
  $user_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_id='".$user_id."' LIMIT 1", $connid);
  if (!$user_result) die($lang['db_error']);
  $user = mysql_fetch_assoc($user_result);
  mysql_free_result($user_result);
  $selected[] = $user_id;
  $selected_usernames[] = $user["user_name"];
  $action="delete_users_sure";
 }


if (isset($_POST['delete_user']))
 {
  if (isset($_POST['selected']))
   {
    $selected = $_POST['selected'];
    for($x=0; $x<count($selected); $x++)
    {
     $user_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_id='".$selected[$x]."' LIMIT 1", $connid);
     if (!$user_result) die($lang['db_error']);
     $user = mysql_fetch_assoc($user_result);
     mysql_free_result($user_result);
     $selected_usernames[] = $user["user_name"];
    }
   $action="delete_users_sure";
   }
  else $action="user";
 }

if (isset($_POST['clear_userdata']))
 {
  switch ($_POST['clear_userdata'])
   {
    case 1:
     $clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND logins=0 AND registered<(NOW()-INTERVAL 2 DAY) ORDER BY user_name", $connid);
    break;
    case 2:
     $clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (logins<=1 AND last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
    break;
    case 3:
     $clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (logins<=3 AND last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
    break;
    case 4:
     $clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (last_login<(NOW()-INTERVAL 60 DAY))) ORDER BY user_name", $connid);
    break;
    case 5:
     $clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
    break;

   }
  if (!$clear_result) die($lang['db_error']);
  while ($line = mysql_fetch_assoc($clear_result))
   {
    $selected_usernames[] = $line['user_name'];
    $selected[] = $line['user_id'];
   }
   mysql_free_result($clear_result);
   if (isset($selected)) $action="delete_users_sure"; else { $no_users_in_selection = true; $action="user"; }
 }

if (isset($_POST['email_list'])) $action="email_list";

 if (isset($_POST['delete_confirmed']))
 {
  if (isset($_POST['selected_confirmed']))
   {
    $selected_confirmed = $_POST['selected_confirmed'];
    for($x=0; $x<count($selected_confirmed); $x++)
     {
      $delete_result = mysql_query("DELETE FROM ".$db_settings['userdata_table']." WHERE user_id = '".$selected_confirmed[$x]."'",$connid);
      $update_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, user_id='0', email_notify='0' WHERE user_id = '".$selected_confirmed[$x]."'", $connid);
     }
   }
  $action="user";
 }

if (isset($_GET['user_lock']))
 {
  $lock_result = mysql_query("SELECT user_lock FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_GET['user_lock']."' LIMIT 1", $connid);
  if (!$lock_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($lock_result);
  mysql_free_result($lock_result);
  if ($field['user_lock'] == 0) $new_lock = 1; else $new_lock = 0;
  $update_result = mysql_query("UPDATE ".$db_settings['userdata_table']." SET user_lock='".$new_lock."', last_login=last_login, registered=registered WHERE user_id='".$_GET['user_lock']."' LIMIT 1", $connid);
  $action="user";
 }

if (isset($_POST['delete_all_postings_confirmed']))
 {
  $pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
  if (!$pw_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($pw_result);
  mysql_free_result($pw_result);
  if ($_POST['delete_all_postings_confirm_pw']=="") $errors[] = $lang['error_form_uncompl'];
  else
   {
    if ($field['user_pw'] != md5(trim($_POST['delete_all_postings_confirm_pw']))) $errors[] = $lang['pw_wrong'];
   }
  if (empty($errors))
   {
    $empty_forum_result = mysql_query("DELETE FROM ".$db_settings['forum_table'], $connid);
    if (!$empty_forum_result) die($lang['db_error']);
    $action="main";
   }
  else $action="empty";
 }

if (isset($_POST['delete_db_confirmed']))
 {
  $pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
  if (!$pw_result) die($lang['db_error']);
  $field = mysql_fetch_assoc($pw_result);
  mysql_free_result($pw_result);
  if ($_POST['delete_db_confirm_pw']=="" || empty($_POST['delete_modus'])) $errors[] = $lang['error_form_uncompl'];
  else
   {
    if ($field['user_pw'] != md5(trim($_POST['delete_db_confirm_pw']))) $errors[] = $lang['pw_wrong'];
   }
  if (empty($errors))
   {
    echo '<pre>';
    echo 'Deleting table <b>'.$db_settings['forum_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['forum_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['userdata_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['userdata_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['useronline_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['useronline_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['settings_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['settings_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['category_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['category_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['smilies_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['smilies_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    echo 'Deleting table <b>'.$db_settings['banlists_table'].'</b>... ';
    if(mysql_query("DROP TABLE ".$db_settings['banlists_table'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
    if (empty($errors)) echo '<br /><b>'.$lang_add['tables_deleted'].'</b>';
    else echo '<br /><b>'.$lang_add['tables_deleted_error'].'</b>';

    if ($_POST['delete_modus'] == "db")
     {
      unset($errors);
      echo '<br /><br />Deleting database <b>'.$db_settings['db'].'</b>... ';
      $result = mysql_list_tables($db_settings['db'],$connid);
      if(mysql_num_rows($result) == 0)
       {
        if(mysql_query("DROP DATABASE ".$db_settings['db'], $connid)) echo '<b style="color:green;">OK</b><br />'; else { $errors[] = mysql_error($connid); echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_error($connid).')<br />'; }
       }
      else
       {
        $errors[] = 'DB not empty';
        echo '<b style="color:red;">FAILED</b> (there are still tables in the database)<br />';
       }
      if (empty($errors)) echo '<br /><b>'.$lang_add['db_deleted'].'</b>';
      else echo '<br /><b>'.$lang_add['db_deleted_error'].'</b>';
     }
    echo '</pre>';
    die();
   }
  $action="uninstall";
 }

if (isset($_POST['delete_marked_threads_confirmed']))
 {
  $del_marked_result = mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE marked='1'", $connid);
  if (!$del_marked_result) die($lang['db_error']);

  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die();
 }

if (isset($_POST['unmark_confirmed']))
 {
  $remove_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='0'", $connid);
  if (!$remove_markings_result) die($lang['db_error']);

  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die(); }

if (isset($_POST['invert_markings_confirmed']))
 {
  $invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='2' WHERE marked='1'", $connid);
  $invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE marked='0'", $connid);
  $invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='0' WHERE marked='2'", $connid);
  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die();
 }
if (isset($_POST['mark_threads_submitted']))
 {
  if($_POST['mark_threads'] == 1) $limit = intval($_POST['n1'])-1;
  elseif($_POST['mark_threads'] == 2) $limit = intval($_POST['n2'])-1;
  if($limit >= 0)
   {
    // letzten Thread ermitteln, der nicht markiert werden soll:
    $mot_result =  mysql_query("SELECT tid FROM ".$db_settings['forum_table']." WHERE pid = '0' ORDER BY id DESC LIMIT ".$limit.", 1", $connid);
    if (!$mot_result) die($lang['db_error']);
    $field = mysql_fetch_assoc($mot_result);
    $last_thread = $field['tid'];
    mysql_free_result($mot_result);
    // ...und alle älteren markieren:
    if($_POST['mark_threads'] == 1) mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE tid < ".$last_thread, $connid);
    if($_POST['mark_threads'] == 2) mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE tid < ".$last_thread." AND time=last_answer", $connid);
   }
  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die();
 }
if (isset($_POST['lock_marked_threads_submitted']))
 {
  mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, locked='1' WHERE marked='1'", $connid);
  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die();
 }

if (isset($_POST['unlock_marked_threads_submitted']))
 {
  mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, locked='0' WHERE marked='1'", $connid);
  if(isset($_POST['refer']) && $_POST['refer'] == 'board') header("location: board.php");
  elseif(isset($_POST['refer']) && $_POST['refer'] == 'mix') header("location: mix.php");
  else header("location: forum.php");
  die();
 }

 if (isset($_POST['settings_submitted']))
 {
  // not checked checkboxes:
  if(empty($_POST['captcha_posting'])) $_POST['captcha_posting'] = 0;
  if(empty($_POST['captcha_contact'])) $_POST['captcha_contact'] = 0;
  if(empty($_POST['captcha_register'])) $_POST['captcha_register'] = 0;

  while(list($key, $val) = each($_POST))
   {
    if($key != "settings_submitted") mysql_query("UPDATE ".$db_settings['settings_table']." SET value='".$val."' WHERE name='".$key."' LIMIT 1", $connid);
   }

  header("location: admin.php");
  die("<a href=\"admin.php\">further...</a>");
 }

if (isset($_POST['ar_username']))
 {
  // überflüssige Leerzeichen abschneiden:
  $ar_username = $_POST['ar_username'];
  $ar_email = $_POST['ar_email'];
  $ar_pw = $_POST['ar_pw'];
  $ar_pw_conf = $_POST['ar_pw_conf'];
  if(isset($_POST['ar_send_userdata']) && $_POST['ar_send_userdata'] != '') $ar_send_userdata = true;
  $ar_username = trim($ar_username);
  $ar_email = trim($ar_email);
  $ar_pw = trim($ar_pw);
  $ar_pw_conf = trim($ar_pw_conf);
  // Schauen, ob alle Felder ausgefüllt wurden:
  if ($ar_username=="" or $ar_email=="") $errors[] = $lang['error_form_uncompl'];
  if(empty($errors))
   {
    if (($ar_pw=="" or $ar_pw_conf=="") && !isset($ar_send_userdata)) $errors[] = $lang_add['error_send_userdata'];
   }
  // wenn alle Felder ausgefüllt wurden, weitere Überprüfungen durchführen:
  if(empty($errors))
   {
    // Überprüfen, ob der Name zu lang ist:
    if (strlen($ar_username) > $settings['name_maxlength'])
    $errors[] = $lang['name_marking'] . " " .$lang['error_input_too_long'];
    // überprüfen, ob ein Wort im Username zu lang ist:
    $text_arr = explode(" ",$ar_username); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['name_word_maxlength']) {
    $error_nwtl = str_replace("[word]", htmlsc(stripslashes(substr($text_arr[$i],0,$settings['name_word_maxlength'])))."...", $lang['error_name_word_too_long']);
    $errors[] = $error_nwtl; } }
    // schauen, ob der Name schon vergeben ist:
    $name_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_name = '".mysql_escape_string($ar_username)."'");
    if(!$name_result) die($lang['db_error']);
    $field = mysql_fetch_assoc($name_result);
    mysql_free_result($name_result);

    if (strtolower($field["user_name"]) == strtolower($ar_username) && $ar_username != "")
     {
      $lang['error_name_reserved'] = str_replace("[name]", htmlsc(stripslashes($ar_username)), $lang['error_name_reserved']);
      $errors[] = $lang['error_name_reserved'];
     }
    // Überprüfung ob die Email-Adresse das Format name@domain.tld hat:
    if (!preg_match("/^[^@]+@.+\.\D{2,5}$/", $ar_email))
    $errors[] = $lang['error_email_wrong'];

    if ($ar_pw_conf != $ar_pw) $errors[] = $lang_add['error_pw_conf_wrong'];
   }
  // wenn keine Fehler, dann neuen User Aufnehmen:
  if (empty($errors))
   {
    // neuen User in die Datenbank eintragen:

    // Passwort generieren, wenn kein Passwort eingegeben wurde:
    if($ar_pw=='')
     {
      $letters="abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789";
      mt_srand((double)microtime()*1000000);
      $ar_pw="";
      for($i=0;$i<8;$i++) { $ar_pw.=substr($letters,mt_rand(0,strlen($letters)-1),1); }
     }
    $encoded_ar_pw = md5($ar_pw);
    $new_user_result = mysql_query("INSERT INTO ".$db_settings['userdata_table']." (user_type, user_name, user_pw, user_email, hide_email, profile, last_login, last_logout, user_ip, registered, user_view, personal_messages) VALUES ('user','".mysql_escape_string($ar_username)."','".$encoded_ar_pw."','".mysql_escape_string($ar_email)."','1','',NOW(),NOW(),'".$_SERVER["REMOTE_ADDR"]."',NOW(),'".$settings['standard']."','1')", $connid);
    if(!$new_user_result) die($lang['db_error']);

    // E-Mail an neuen User versenden:
    $send_error='';
    if(isset($ar_send_userdata))
     {
      $ip = $_SERVER["REMOTE_ADDR"];
      $lang['new_user_email_txt_a'] = str_replace("[name]", $ar_username, $lang['new_user_email_txt_a']);
      $lang['new_user_email_txt_a'] = str_replace("[password]", $ar_pw, $lang['new_user_email_txt_a']);
      $lang['new_user_email_txt_a'] = str_replace("[login_link]", $settings['forum_address']."login.php?username=".urlencode($ar_username)."&userpw=".$ar_pw, $lang['new_user_email_txt_a']);
      $lang['new_user_email_txt_a'] = stripslashes($lang['new_user_email_txt_a']);
      $header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
      $header .= "X-Mailer: Php/" . phpversion(). "\n";
      $header .= "X-Sender-ip: ".$_SERVER["REMOTE_ADDR"]."\n";
      $header .= "Content-Type: text/plain";
      $new_user_mailto = $ar_username." <".$ar_email.">";
      if($settings['mail_parameter']!='')
       {
        if(!@mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt_a'], $header, $settings['mail_parameter'])) $send_error = '&send_error=true';
       }
      else
       {
        if(!@mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt_a'], $header)) $send_error = '&send_error=true';
       }
     }

    header("location: admin.php?action=user&new_user=".urlencode($ar_username).$send_error);
    die("<a href=\"admin.php?action=user&amp;new_user=".urlencode($ar_username).$send_error."\">further...</a>");
   }
  }
if (isset($_POST['rename_category_submitted']) && trim($_POST['new_category']) != "")
 {
  mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, category='".$_POST['new_category']."' WHERE category='".$_POST['old_category']."'", $connid);

  $data = file("db_settings.php");
  $row_count = count($data);
  $fp = fopen("db_settings.php", "w+");
  flock($fp, 2);
  for ($row = 0; $row < $row_count; $row++)
   {
    if (substr(trim($data[$row]),1,10) == "categories")
     { fwrite($fp, trim(str_replace($_POST['old_category'],$_POST['new_category'],$data[$row]))."\n"); }
    else { fwrite($fp, trim($data[$row])."\n"); }
   }
  flock($fp, 3);
  fclose($fp);

  header("location: admin.php");
  die("<a href=\"admin.php\">further...</a>");
 }

if (isset($_POST['delete_category_confirmed']) && trim($_POST['delete_category']) != "")
 {
  mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category = '".$_POST['delete_category']."'", $connid);
  header("location: admin.php");
  die("<a href=\"admin.php\">further...</a>");
 }

if(isset($_POST['banlists_submit']))
 {
  if(trim($_POST['banned_users']) != '')
   {
    $banned_users_array = explode(',',$_POST['banned_users']);
    foreach($banned_users_array as $banned_user)
     {
      if(trim($banned_user)!='') $banned_users_array_checked[] = trim($banned_user);
     }
    $banned_users = implode(",", $banned_users_array_checked);
   }
  else $banned_users = '';
  mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_escape_string($banned_users)."' WHERE name='users'", $connid);

  if(trim($_POST['banned_ips']) != '')
   {
    $banned_ips_array = explode(',',$_POST['banned_ips']);
    foreach($banned_ips_array as $banned_ip)
     {
      if(trim($banned_ip)!='') $banned_ips_array_checked[] = trim($banned_ip);
     }
    $banned_ips = implode(",", $banned_ips_array_checked);
   }
  else $banned_ips = '';
  mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_escape_string($banned_ips)."' WHERE name='ips'", $connid);

  if(trim($_POST['not_accepted_words']) != '')
   {
    $not_accepted_words_array = explode(',',$_POST['not_accepted_words']);
    foreach($not_accepted_words_array as $not_accepted_word)
     {
      if(trim($not_accepted_word)!='') $not_accepted_words_array_checked[] = trim($not_accepted_word);
     }
    $not_accepted_words = implode(",", $not_accepted_words_array_checked);
   }
  else $not_accepted_words = '';
  mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_escape_string($not_accepted_words)."' WHERE name='words'", $connid);

  header("location: admin.php");
  die("<a href=\"admin.php\">further...</a>");
 }

if(isset($_POST['smiley_file']))
 {
  if(!file_exists('img/smilies/'.$_POST['smiley_file'])) $errors[] = $lang_add['smiley_file_doesnt_exist'];
  if(trim($_POST['smiley_code'])=='') $errors[] = $lang_add['smiley_code_error'];

  if(empty($errors))
   {
    $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
    list($smilies_count) = mysql_fetch_row($count_result);
    mysql_free_result($count_result);
    $order_id = $smilies_count+1;
  
    mysql_query("INSERT INTO ".$db_settings['smilies_table']." (order_id, file, code_1, code_2, code_3, code_4, code_5, title) VALUES (".mysql_escape_string($order_id).",'".mysql_escape_string($_POST['smiley_file'])."','".mysql_escape_string(trim($_POST['smiley_code']))."','','','','','')", $connid) or die(mysql_error($connid));

    header("location: admin.php?action=smilies");
    die();
   }
  else $action='smilies';
 }

if(isset($_GET['delete_smiley']))
 {
  mysql_query("DELETE FROM ".$db_settings['smilies_table']." WHERE id = ".intval($_GET['delete_smiley']), $connid);

  $result = mysql_query("SELECT id FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
  $i=1;
  while ($data = mysql_fetch_assoc($result))
   {
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$i." WHERE id = ".intval($data['id']), $connid);
    $i++;
   }
  mysql_free_result($result);

  header("location: admin.php?action=smilies");
  die();
 }

if(isset($_GET['edit_smiley']))
 {
  $result = mysql_query("SELECT id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']." WHERE id = ".intval($_GET['edit_smiley'])." LIMIT 1", $connid);
  if(!$result) die($lang['db_error']);
  $data = mysql_fetch_assoc($result);
  mysql_free_result($result);

  $id = $data['id'];
  $file = $data['file'];
  $code_1 = $data['code_1'];
  $code_2 = $data['code_2'];
  $code_3 = $data['code_3'];
  $code_4 = $data['code_4'];
  $code_5 = $data['code_5'];
  $title = $data['title'];

  $action='edit_smiley';
 }

if(isset($_POST['edit_smiley_submit']))
 {
  $id = intval($_POST['id']);
  $file = trim($_POST['file']);
  $code_1 = trim($_POST['code_1']);
  $code_2 = trim($_POST['code_2']);
  $code_3 = trim($_POST['code_3']);
  $code_4 = trim($_POST['code_4']);
  $code_5 = trim($_POST['code_5']);
  $title = trim($_POST['title']);

  if(!file_exists('img/smilies/'.$file)) $errors[] = $lang_add['smiley_file_doesnt_exist'];
  if($code_1=='' && $code_2=='' && $code_3=='' && $code_4=='' && $code_5=='') $errors[] = $lang_add['smiley_code_error'];
  if(empty($errors))
   {
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET file='".mysql_escape_string($file)."', code_1='".mysql_escape_string($code_1)."', code_2='".mysql_escape_string($code_2)."', code_3='".mysql_escape_string($code_3)."', code_4='".mysql_escape_string($code_4)."', code_5='".mysql_escape_string($code_5)."', title='".mysql_escape_string($title)."' WHERE id=".$id, $connid);
    header("location: admin.php?action=smilies");
    die();
   }
  else $action='edit_smiley';
 }

if(isset($_GET['enable_smilies']))
 {
  mysql_query("UPDATE ".$db_settings['settings_table']." SET value=1 WHERE name='smilies'", $connid);
  header("location: admin.php?action=smilies");
  die();
 }

if(isset($_GET['disable_smilies']))
 {
  mysql_query("UPDATE ".$db_settings['settings_table']." SET value=0 WHERE name='smilies'", $connid);
  header("location: admin.php?action=smilies");
  die();
 }

if (isset($_GET['move_up_smiley']))
 {
  $result = mysql_query("SELECT order_id FROM ".$db_settings['smilies_table']." WHERE id = ".$_GET['move_up_smiley']." LIMIT 1", $connid);
  if(!$result) die($lang['db_error']);
  $field = mysql_fetch_assoc($result);
  mysql_free_result($result);
  if ($field['order_id'] > 1)
   {
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=0 WHERE order_id=".$field['order_id']."-1", $connid);
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=order_id-1 WHERE order_id=".$field['order_id'], $connid);
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$field['order_id']." WHERE order_id=0", $connid);
   }
  header("location: admin.php?action=smilies");
  die();
 }

if (isset($_GET['move_down_smiley']))
 {
  $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
  list($smilies_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);

  $result = mysql_query("SELECT order_id FROM ".$db_settings['smilies_table']." WHERE id = ".$_GET['move_down_smiley']." LIMIT 1", $connid);
  if(!$result) die($lang['db_error']);
  $field = mysql_fetch_assoc($result);
  mysql_free_result($result);
  if ($field['order_id'] < $smilies_count)
   {
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=0 WHERE order_id=".$field['order_id']."+1", $connid);
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=order_id+1 WHERE order_id=".$field['order_id'], $connid);
    mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$field['order_id']." WHERE order_id=0", $connid);
   }
  header("location: admin.php?action=smilies");
  die();
 }

if (empty($action)) $action="main";

switch ($action)
 {
  case "settings":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['forum_settings'] .'</b>';
  break;
  case "advanced_settings":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=settings"><b>'. $lang_add['forum_settings'] .'</b></a> / <b>'. $lang_add['advanced_settings'] .'</b>';
  break;
  case "categories":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['category_administr'] .'</b>';
  break;
  case "delete_category":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=categories"><b>'. $lang_add['category_administr'] .'</b></a> / <b>'. $lang_add['delete_category'] .'</b>';
  break;
  case "edit_category":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=categories"><b>'. $lang_add['category_administr'] .'</b></a> / <b>'. $lang_add['cat_edit_hl'] .'</b>';
  break;
  case "user":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['user_administr'] .'</b>';
  break;
  case "edit_user":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=user"><b>'. $lang_add['user_administr'] .'</b></a>  / <b>'. $lang_add['edit_user'] .'</b>';
  break;
  case "delete_users_sure":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=user"><b>'. $lang_add['user_administr'] .'</b></a>  / <b>'. $lang_add['delete_user'] .'</b>';
  break;
  case "register":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=user"><b>'. $lang_add['user_administr'] .'</b></a> / <b>'. $lang_add['reg_user'] .'</b>';
  break;
  case "email_list":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=user"><b>'. $lang_add['user_administr'] .'</b></a> / <b>'. $lang_add['email_list'] .'</b>';
  break;
  case "clear_userdata":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=user"><b>'. $lang_add['user_administr'] .'</b></a> / <b>'. $lang_add['clear_userdata'] .'</b>';
  break;
  case "banlists":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['banlists'] .'</b>';
  break;
  case "empty":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['empty_forum'] .'</b>';
  break;
  case "backup":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['backup_restore'] .'</b>';
  break;
  case "import_sql":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=backup"><b>'. $lang_add['backup_restore'] .'</b></a> / <b>'. $lang_add['import_sql'] .'</b>';
  break;
  case "import_sql_ok":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=backup"><b>'. $lang_add['backup_restore'] .'</b></a> / <b>'. $lang_add['import_sql'] .'</b>';
  break;
  case "uninstall":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['uninstall'] .'</b>';
  break;
  case "smilies":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <b>'. $lang_add['smilies'] .'</b>';
  break;
  case "edit_smiley":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><a href="admin.php"><b>'.$lang_add['admin_area'].'</b></a> / <a href="admin.php?action=smilies"><b>'. $lang_add['smilies'] .'</b></a> / <b>'. $lang_add['edit_smiley_hl'] .'</b>';
  break;
  case "delete_marked_threads":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['del_marked'] .'</b>';
  break;
  case "unmark":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['unmark_threads'] .'</b>';
  break;
  case "lock_marked_threads":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['lock_marked'] .'</b>';
  break;
  case "unlock_marked_threads":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['unlock_marked'] .'</b>';
  break;
  case "invert_markings":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['invert_markings'] .'</b>';
  break;
  case "mark_threads":
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'. $lang_add['mark_threads'] .'</b>';
  break;
  default:
   $topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang_add['admin_area'].'</b>';
  break;
 }

parse_template();
echo $header;

switch ($action)
 {
  case "main":
   ?><p class="normal"><a class="textlink" href="admin.php?action=settings"><?php echo $lang_add['forum_settings']; ?></a><br />
   <a class="textlink" href="admin.php?action=categories"><?php echo $lang_add['category_administr']; ?></a><br />
   <a class="textlink" href="admin.php?action=user"><?php echo $lang_add['user_administr']; ?></a><br />
   <a class="textlink" href="admin.php?action=smilies"><?php echo $lang_add['smilies']; ?></a><br />
   <a class="textlink" href="admin.php?action=banlists"><?php echo $lang_add['banlists']; ?></a><br />
   <a class="textlink" href="admin.php?action=empty"><?php echo $lang_add['empty_forum']; ?></a><br />
   <a class="textlink" href="admin.php?action=backup"><?php echo $lang_add['backup_restore']; ?></a><br />
   <a class="textlink" href="admin.php?action=uninstall"><?php echo $lang_add['uninstall']; ?></a><?php
  break;
  case "categories":
  // look if there are entries in not existing categories:
  if(isset($category_ids_query))
   {
    $count_result=mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category NOT IN (".$category_ids_query.")", $connid);
   }
  else
   {
    $count_result=mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category != 0", $connid);
   }
  list($entries_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);
  if($entries_count > 0)
   {
    $cat_select = '<select class="kat" size="1" name="move_category">';
    if ($categories!=false)
     {
      while(list($key, $val) = each($categories))
       {
        if($key!=0) $cat_select .= '<option value="'.$key.'">'.$val.'</option>';
       }
     }
    else
     {
      $cat_select .= '<option value="0">-</option>';
     }
    $cat_select .= '</select>';
    ?><form action="admin.php" method="post"><div style="margin:0px 0px 20px 0px; padding:10px; border:1px dotted red;">
    <p><?php echo $lang_add['entries_in_not_ex_cat']; ?></p>
    <p><input type="radio" name="mode" value="delete" checked="checked" /><?php echo $lang_add['entries_in_not_ex_cat_delete']; ?><br />
    <input type="radio" name="mode" value="move" /><?php echo str_replace("[category]",$cat_select,$lang_add['entries_in_not_ex_cat_move']); ?></p>
    <p><input type="submit" name="not_displayed_entries_submit" value="<?php echo $lang['submit_button_ok']; ?>"></p>
    </div></form><?php
   }

  $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
  list($categories_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);

  if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php }

  if ($categories_count > 0)
   {
    $result = mysql_query("SELECT id, category_order, category, accession FROM ".$db_settings['category_table']." ORDER BY category_order ASC", $connid);
    if(!$result) die($lang['db_error']);
    ?><table class="normaltab" cellspacing="1" cellpadding="5">
     <tr>
      <th><?php echo $lang_add['cat_hl']; ?></th>
      <th><?php echo $lang_add['cat_accessible']; ?></th>
      <th><?php echo $lang_add['cat_topics']; ?></th>
      <th><?php echo $lang_add['cat_entries']; ?></th>
      <th colspan="2"><?php echo $lang_add['cat_actions']; ?></th>
      <th><?php echo $lang_add['cat_move']; ?></th>
     </tr><?php

    $i=0;
    while ($line = mysql_fetch_assoc($result))
     {
      $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category = '".intval($line['id'])."' AND pid = 0", $connid);
      list($threads_in_category) = mysql_fetch_row($count_result);
      mysql_free_result($count_result);
      $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category = '".intval($line['id'])."'", $connid);
      list($postings_in_category) = mysql_fetch_row($count_result);
      mysql_free_result($count_result);
      ?><tr>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><b><?php echo stripslashes($line['category']); ?></b></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if ($line['accession']==2) echo $lang_add['cat_accession_mod_admin']; elseif ($line['accession']==1) echo $lang_add['cat_accession_reg_users']; else echo $lang_add['cat_accession_all']; ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo $threads_in_category; ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo $postings_in_category; ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?edit_category=<?php echo $line['id']; ?>"><?php echo $lang_add['cat_edit']; ?></a></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?delete_category=<?php echo $line['id']; ?>"><?php echo $lang_add['cat_delete']; ?></a></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?move_up_category=<?php echo $line['id']; ?>"><img src="img/up.gif" alt="up" width="11" height="11" onmouseover="this.src='img/up_mo.gif';" onmouseout="this.src='img/up.gif';" /></a>&nbsp;<a href="admin.php?move_down_category=<?php echo $line['id']; ?>"><img src="img/down.gif" alt="down" width="11" height="11" onmouseover="this.src='img/down_mo.gif';" onmouseout="this.src='img/down.gif';" /></a></td>
      </tr><?php
      $i++;
     }
    mysql_free_result($result);
    ?></table>
    <?php
   }
  else
   {
    ?><p><i><?php echo $lang_add['no_categories']; ?></i></p><?php
   }
   ?><br />
   <form action="admin.php" method="post"><div style="display: inline;">
   <b><?php echo $lang_add['new_category']; ?></b><br />
   <input type="text" name="new_category" size="25" value="<?php if(isset($new_category)) echo htmlsc(stripslashes($new_category)); ?>" /><br /><br />
   <b><?php echo $lang_add['accessible_for']; ?></b><br />
   <input type="radio" name="accession" value="0"<?php if(empty($accession) || isset($accession) && $accession == 0) { ?> checked="ckecked"<?php } ?> /><?php echo $lang_add['cat_accession_all']; ?><br />
   <input type="radio" name="accession" value="1"<?php if(isset($accession) && $accession == 1) { ?> checked="ckecked"<?php } ?> /><?php echo $lang_add['cat_accession_reg_users']; ?><br />
   <input type="radio" name="accession" value="2"<?php if(isset($accession) && $accession == 2) { ?> checked="ckecked"<?php } ?> /><?php echo $lang_add['cat_accession_mod_admin']; ?><br /><br />
   <input type="submit" value="<?php echo $lang['submit_button_ok']; ?>" /></div></form><?php
  break;
  case "user":
   if (isset($_GET['order'])) $order = $_GET['order']; else $order="user_id";
   if (isset($_GET['sam'])) $sam = (int)$_GET['sam']; else $sam = 50;
   if (isset($_GET['descasc'])) $descasc = $_GET['descasc']; else $descasc = "ASC";
   if (isset($_GET['page'])) $page = $_GET['page']; else $page = 0;
   if (empty($category)) $category="all";

   if(isset($_GET['search_user'])) $search_user = $_GET['search_user'];
   if(isset($_GET['letter'])) $letter = $_GET['letter'];

   $ul = $page * $settings['users_per_page'];

   if(isset($letter))
    {
     $result = mysql_query("SELECT user_id, user_name, user_type, user_email, logins, UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time, UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time, user_lock FROM ".$db_settings['userdata_table']." WHERE user_name LIKE '".mysql_escape_string($letter)."%' ORDER BY ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['users_per_page'], $connid);
    }
   elseif(isset($search_user))
    {
     $result = mysql_query("SELECT user_id, user_name, user_type, user_email, logins, UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time, UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time, user_lock FROM ".$db_settings['userdata_table']." WHERE user_name LIKE '".mysql_escape_string($search_user)."%' OR user_email LIKE '".mysql_escape_string($search_user)."%' ORDER BY ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['users_per_page'], $connid);
    }
   else
    {
     $result = mysql_query("SELECT user_id, user_name, user_type, user_email, logins, UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time, UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time, user_lock FROM ".$db_settings['userdata_table']." ORDER BY ".$order." ".$descasc." LIMIT ".$ul.", ".$settings['users_per_page'], $connid);
    }
   if(!$result) die($lang['db_error']);
   $result_count = mysql_num_rows($result);

   // schauen, wieviele User vorhanden sind:
   $user_count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
   list($user_count) = mysql_fetch_row($user_count_result);
   mysql_free_result($user_count_result);

   if (isset($_GET['letter']) && $_GET['letter']!="") $su_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table']." WHERE user_name LIKE '".$_GET['letter']."%'", $connid);
   else $su_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
   list($sel_user_count) = mysql_fetch_row($su_result);
   mysql_free_result($su_result);

   if ($user_count < $sam) $sam = $user_count;
   if(isset($letter) && $letter == "A") $la = ' selected="selected"'; else $la = ''; if (isset($letter) && $letter == "B") $lb = ' selected="selected"'; else $lb = ''; if (isset($letter) && $letter == "C") $lc = ' selected="selected"'; else $lc = ''; if (isset($letter) && $letter == "D") $ld = ' selected="selected"'; else $ld = ''; if (isset($letter) && $letter == "E") $le = ' selected="selected"'; else $le = ''; if (isset($letter) && $letter == "F") $lf = ' selected="selected"'; else $lf = ''; if (isset($letter) && $letter == "G") $lg = ' selected="selected"'; else $lg = ''; if (isset($letter) && $letter == "H") $lh = ' selected="selected"'; else $lh = ''; if (isset($letter) && $letter == "I") $li = ' selected="selected"'; else $li = ''; if (isset($letter) && $letter == "J") $lj = ' selected="selected"'; else $lj = '';   if (isset($letter) && $letter == "K") $lk = ' selected="selected"'; else $lk = ''; if (isset($letter) && $letter == "L") $ll = ' selected="selected"'; else $ll = ''; if (isset($letter) && $letter == "M") $lm = ' selected="selected"'; else $lm = ''; if (isset($letter) && $letter == "N") $ln = ' selected="selected"'; else $ln = ''; if (isset($letter) && $letter == "O") $lo = ' selected="selected"'; else $lo = ''; if (isset($letter) && $letter == "P") $lp = ' selected="selected"'; else $lp = ''; if (isset($letter) && $letter == "Q") $lq = ' selected="selected"'; else $lq = ''; if (isset($letter) && $letter == "R") $lr = ' selected="selected"'; else $lr = ''; if (isset($letter) && $letter == "S") $ls = ' selected="selected"'; else $ls = ''; if (isset($letter) && $letter == "T") $lt = ' selected="selected"'; else $lt = ''; if (isset($letter) && $letter == "U") $lu = ' selected="selected"'; else $lu = ''; if (isset($letter) && $letter == "V") $lv = ' selected="selected"'; else $lv = ''; if (isset($letter) && $letter == "W") $lw = ' selected="selected"'; else $lw = ''; if (isset($letter) && $letter == "X") $lx = ' selected="selected"'; else $lx = ''; if (isset($letter) && $letter == "Y") $ly = ' selected="selected"'; else $ly = ''; if (isset($letter) && $letter == "Z") $lz = ' selected="selected"'; else $lz = '';

   ?><table style="margin:0px 0px 10px 0px; padding:0px; width:100%;" cellspacing="0" cellpadding="0" border="0">
   <tr>
   <td><?php echo str_replace("[number]", $user_count, $lang['num_reg_users']); ?></td>
   <td style="text-align:right;"><?php echo $lang_add['search_user']; ?><form action="<?php echo basename($_SERVER['PHP_SELF']); ?>" method="get"><div style="display:inline">
   <input type="hidden" name="action" value="user" />
   <input type="text" name="search_user" value="<?php if(isset($search_user)) echo htmlsc(stripslashes($search_user)); ?>" size="25">&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" />
   </div></form><?php
   if(empty($serach_user) || trim($search_user==''))
   {
   ?>&nbsp;
   <form action="<?php echo basename($_SERVER["PHP_SELF"]); ?>" method="get" title=""><div style="display:inline">
   <input type="hidden" name="action" value="user" />
   <select class="kat" size="1" name="letter" onchange="this.form.submit();">
   <option value="">A-Z</option>
   <option value="A"<?php echo $la; ?>>A</option>
   <option value="B"<?php echo $lb; ?>>B</option>
   <option value="C"<?php echo $lc; ?>>C</option>
   <option value="D"<?php echo $ld; ?>>D</option>
   <option value="E"<?php echo $le; ?>>E</option>
   <option value="F"<?php echo $lf; ?>>F</option>
   <option value="G"<?php echo $lg; ?>>G</option>
   <option value="H"<?php echo $lh; ?>>H</option>
   <option value="I"<?php echo $li; ?>>I</option>
   <option value="J"<?php echo $lj; ?>>J</option>
   <option value="K"<?php echo $lk; ?>>K</option>
   <option value="L"<?php echo $ll; ?>>L</option>
   <option value="M"<?php echo $lm; ?>>M</option>
   <option value="N"<?php echo $ln; ?>>N</option>
   <option value="O"<?php echo $lo; ?>>O</option>
   <option value="P"<?php echo $lp; ?>>P</option>
   <option value="Q"<?php echo $lq; ?>>Q</option>
   <option value="R"<?php echo $lr; ?>>R</option>
   <option value="S"<?php echo $ls; ?>>S</option>
   <option value="T"<?php echo $lt; ?>>T</option>
   <option value="U"<?php echo $lu; ?>>U</option>
   <option value="V"<?php echo $lv; ?>>V</option>
   <option value="W"<?php echo $lw; ?>>W</option>
   <option value="X"<?php echo $lx; ?>>X</option>
   <option value="Y"<?php echo $ly; ?>>Y</option>
   <option value="Z"<?php echo $lz; ?>>Z</option>
   </select>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></div></form>
   <?php echo nav($page, $settings['users_per_page'], $sel_user_count, $order, $descasc, $category, $action);
   } ?></td>
   </tr>
   </table><?php

   if($result_count > 0)
   {
   if (isset($_GET['new_user'])) { ?><p class="caution"><?php echo str_replace("[name]", htmlsc(stripslashes(urldecode($_GET['new_user']))), $lang_add['new_user_registered']); if(isset($_GET['send_error'])) { ?><br /><?php echo $lang_add['userdata_send_error']; } ?></p><p><a class="textlink" href="admin.php?action=register"><?php echo $lang_add['reg_another_user']; ?></a></p><?php }
   if (isset($no_users_in_selection)) { ?><p class="caution"><?php echo $lang_add['no_users_in_sel']; ?></p><?php } ?>
   <form action="admin.php" method="post">
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
   <tr>
   <th>&nbsp;</th>
   <th><a href="admin.php?action=user&amp;order=user_id&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_id") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_id']; ?></a><?php if ($order=="user_id" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_id" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=user_name&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_name") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_name']; ?></a><?php if ($order=="user_name" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_name" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=user_email&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_email") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_email']; ?></a><?php if ($order=="user_email" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_email" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=user_type&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_type") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_type']; ?></a><?php if ($order=="user_type" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_type" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=registered&amp;descasc=<?php if ($descasc=="ASC" && $order=="registered") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_registered']; ?></a><?php if ($order=="registered" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="registered" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=logins&amp;descasc=<?php if ($descasc=="ASC" && $order=="logins") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['user_logins']; ?></a><?php if ($order=="logins" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="logins" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=last_login&amp;descasc=<?php if ($descasc=="ASC" && $order=="last_login") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang_add['last_login']; ?></a><?php if ($order=="last_login" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="last_login" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th><a href="admin.php?action=user&amp;order=user_lock&amp;descasc=<?php if ($descasc=="DESC" && $order=="user_lock") echo "ASC"; else echo "DESC"; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang['lock']; ?></a><?php if ($order=="user_lock" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_lock" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
   <th colspan="2">&nbsp;</th>
   </tr>
   <?php
   $i=0;
   while ($zeile = mysql_fetch_assoc($result)) {
   ?>
   <tr>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>" width="10px"><input type="checkbox" name="selected[]" value="<?php echo $zeile["user_id"]; ?>" /></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>" width="10px"><?php echo $zeile["user_id"]; ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="user.php?id=<?php echo $zeile["user_id"]; ?>" title="<?php echo str_replace("[name]", htmlsc(stripslashes($zeile["user_name"])), $lang['show_userdata_linktitle']); ?>"><b><?php echo htmlsc(stripslashes($zeile["user_name"])); ?></b></a></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="mailto:<?php echo $zeile["user_email"]; ?>" title="<?php echo str_replace("[name]", htmlsc(stripslashes($zeile["user_name"])), $lang_add['mailto_user_lt']); ?>"><?php echo htmlsc(stripslashes($zeile["user_email"])); ?></a></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if ($zeile["user_type"] == "admin") echo $lang['ud_admin']; elseif ($zeile["user_type"] == "mod") echo $lang['ud_mod']; else echo $lang['ud_user']; ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo strftime($lang['time_format'],$zeile["registered_time"]); ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo $zeile["logins"]; ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if ($zeile["logins"] > 0) echo strftime($lang['time_format'],$zeile["last_login_time"]); else echo "&nbsp;"; ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if ($zeile["user_lock"] == 0) { ?><a href="admin.php?user_lock=<?php echo $zeile["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo str_replace("[name]", htmlsc(stripslashes($zeile["user_name"])), $lang['lock_user_lt']); ?>"><?php echo $lang['unlocked']; ?></a><?php } else { ?><a style="color: red;" href="admin.php?user_lock=<?php echo $zeile["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>" title="<?php echo str_replace("[name]", htmlsc(stripslashes($zeile["user_name"])), $lang['unlock_user_lt']); ?>"><?php echo $lang['locked']; ?></a><?php } ?></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?edit_user=<?php echo $zeile["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>"><?php echo $lang_add['edit_link']; ?></a></td>
   <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?delete_user=<?php echo $zeile["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;ul=<?php echo $ul; ?>&amp;sam=<?php echo $sam; ?>"><?php echo $lang_add['delete_link']; ?></a></td>
   </tr>
   <?php $i++; } mysql_free_result($result); ?>
   <!--<tr>
   <td class="b" colspan="9"><img src="img/selected_arrow.gif" alt="" width="35" height="20" border="0"><input type="submit" name="delete_user" value="<?php echo $lang_add['delete_users_sb']; ?>" title="<?php echo $lang_add['delete_users_sb_title']; ?>" /></td>
   </tr>--></table>
   <div style="margin:5px 0px 0px 7px; padding:0px;"><img src="img/selected_arrow.gif" alt="" width="35" height="20" border="0"><input type="submit" name="delete_user" value="<?php echo $lang_add['delete_users_sb']; ?>" title="<?php echo $lang_add['delete_users_sb_title']; ?>" /></div>
   </form><?php
   }
   else
    {
     ?><p><i><?php echo $lang['no_users']; ?></i></p><?php
    }
   ?><p class="normal" style="margin-top:20px;"><a class="textlink" href="admin.php?action=register"><?php echo $lang_add['reg_user']; ?></a><br />
   <a class="textlink" href="admin.php?action=email_list"><?php echo $lang_add['email_list']; ?></a><br />
   <a class="textlink" href="admin.php?action=clear_userdata"><?php echo $lang_add['clear_userdata']; ?></a></p><?php
  break;
  case "register":
   ?><p><?php echo $lang_add['register_exp']; ?></p><?php
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php } ?>
   <form action="admin.php" method="post">
   <input type="hidden" name="action" value="register">
   <b><?php echo $lang['username_marking']; ?></b><br />
   <input type="text" size="25" name="ar_username" value="<?php if (isset($ar_username)) echo htmlsc(stripslashes($ar_username)); ?>" maxlength="<?php echo $name_maxlength; ?>" /><br /><br />
   <b><?php echo $lang['user_email_marking']; ?></b><br />
   <input type="text" size="25" name="ar_email" value="<?php if (isset($ar_email)) echo htmlsc(stripslashes($ar_email)); ?>" maxlength="<?php echo $email_maxlength; ?>" /><br /><br />
   <b><?php echo $lang_add['pw_marking']; ?></b><br />
   <input type="password" size="25" name="ar_pw" maxlength="50"><br /><br />
   <b><?php echo $lang_add['pw_conf_marking']; ?></b><br />
   <input type="password" size="25" name="ar_pw_conf" maxlength="50"><br /><br />
   <input type="checkbox" name="ar_send_userdata" value="true"<?php if(isset($ar_send_userdata)) { ?> checked="checked"<?php } ?> /> <?php echo $lang_add['ar_send_userdata']; ?><br /><br />
   <input type="submit" name="pw_submit" value="<?php echo $lang['new_pw_subm_button']; ?>" title="<?php echo $lang['new_pw_subm_button_title']; ?>">
   </form>
   <p>&nbsp;</p>
   <?php
  break;
  case "settings":
   if (isset($settings['time_difference'])) $std = $settings['time_difference']; else $std = 0;
   ?><form action="admin.php" method="post">
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><b><?php echo $lang_add['forum_name']; ?></b><br /><span class="small"><?php echo $lang_add['forum_name_d']; ?></span></td>
     <td class="d"><input type="text" name="forum_name" value="<?php echo htmlsc(stripslashes($settings['forum_name'])); ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['forum_address']; ?></b><br /><span class="small"><?php echo $lang_add['forum_address_d']; ?></span></td>
     <td class="d"><input type="text" name="forum_address" value="<?php echo $settings['forum_address']; ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['forum_email']; ?></b><br /><span class="small"><?php echo $lang_add['forum_email_d']; ?></span></td>
     <td class="d"><input type="text" name="forum_email" value="<?php echo $settings['forum_email']; ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['home_link']; ?></b><br /><span class="small"><?php echo $lang_add['home_link_d']; ?></span></td>
     <td class="d"><input type="text" name="home_linkaddress" value="<?php echo $settings['home_linkaddress']; ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['home_link_name']; ?></b><br /><span class="small"><?php echo $lang_add['home_link_name_d']; ?></span></td>
     <td class="d"><input type="text" name="home_linkname" value="<?php echo htmlsc(stripslashes($settings['home_linkname'])); ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['template_file']; ?></b><br /><span class="small"><?php echo $lang_add['template_file_d']; ?></span></td>
     <td class="d"><input type="text" name="template" value="<?php echo $settings['template']; ?>" size="40" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['language_file']; ?></b><br /><span class="small"><?php echo $lang_add['language_file_d']; ?></span></td>
     <td class="d"><select name="language_file" size="1"><?php $handle=opendir('./lang/'); while ($file = readdir($handle)) { if (strrchr($file, ".")==".php" && strrchr($file, "_")!="_add.php") { ?><option value="<?php echo $file; ?>"<?php if ($settings['language_file'] ==$file) echo " selected=\"selected\""; ?>><?php echo ucfirst(str_replace(".php","",$file)); ?></option><?php } } closedir($handle); ?></select></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['topics_per_page']; ?></b><br /><span class="small"><?php echo $lang_add['topics_per_page_d']; ?></span></td>
     <td class="d"><input type="text" name="topics_per_page" value="<?php echo $settings['topics_per_page']; ?>" size="5" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['forum_time_difference']; ?></b><br /><span class="small"><?php echo $lang_add['forum_time_difference_d']; ?></span></td>
     <td class="d"><select name="time_difference" size="1"><?php for ($h = -24; $h <= 24; $h++) { ?><option value="<?php echo $h; ?>"<?php if ($std==$h) echo ' selected="selected"'; ?>><?php echo $h; ?></option><?php } ?></select></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['thread_view']; ?></b><br /><span class="small"><?php echo $lang_add['thread_view_d']; ?></span></td>
     <td class="d"><input type="radio" name="thread_view" value="1"<?php if ($settings['thread_view']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="thread_view" value="0"<?php if ($settings['thread_view']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['board_view']; ?></b><br /><span class="small"><?php echo $lang_add['board_view_d']; ?></span></td>
     <td class="d"><input type="radio" name="board_view" value="1"<?php if ($settings['board_view']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="board_view" value="0"<?php if ($settings['board_view']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['mix_view']; ?></b><br /><span class="small"><?php echo $lang_add['mix_view_d']; ?></span></td>
     <td class="d"><input type="radio" name="mix_view" value="1"<?php if ($settings['mix_view']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="mix_view" value="0"<?php if ($settings['mix_view']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['standard']; ?></b><br /><span class="small"><?php echo $lang_add['standard_d']; ?></span></td>
     <td class="d"><input type="radio" name="standard" value="thread"<?php if ($settings['standard']=="thread") echo ' checked="checked"'; ?> /><?php echo $lang_add['standard_thread']; ?> &nbsp;<input type="radio" name="standard" value="board"<?php if ($settings['standard']=="board") echo ' checked="checked"'; ?> /><?php echo $lang_add['standard_board']; ?> &nbsp;<input type="radio" name="standard" value="mix"<?php if ($settings['standard']=="mix") echo ' checked="checked"'; ?> /><?php echo $lang_add['standard_mix']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['accession']; ?></b><br /><span class="small"><?php echo $lang_add['accession_d']; ?></span></td>
     <td class="d"><input type="radio" name="access_for_users_only" value="1"<?php if ($settings['access_for_users_only']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['access_only_reg_users']; ?> &nbsp;<input type="radio" name="access_for_users_only" value="0"<?php if ($settings['access_for_users_only']==0) echo ' checked="checked"'; ?> /><?php echo $lang_add['access_all_users']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['entry_perm']; ?></b><br /><span class="small"><?php echo $lang_add['entry_perm_d']; ?></span></td>
     <td class="d"><input type="radio" name="entries_by_users_only" value="1"<?php if ($settings['entries_by_users_only']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['access_only_reg_users']; ?> &nbsp;<input type="radio" name="entries_by_users_only" value="0"<?php if ($settings['entries_by_users_only']==0) echo ' checked="checked"'; ?> /><?php echo $lang_add['access_all_users']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['register_perm']; ?></b><br /><span class="small"><?php echo $lang_add['register_perm_d']; ?></span></td>
     <td class="d"><input type="radio" name="register_by_admin_only" value="1"<?php if ($settings['register_by_admin_only']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['register_only_admin']; ?> &nbsp;<input type="radio" name="register_by_admin_only" value="0"<?php if ($settings['register_by_admin_only']==0) echo ' checked="checked"'; ?> /><?php echo $lang_add['register_self']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['mark_reg_users']; ?></b><br /><span class="small"><?php echo $lang_add['mark_reg_users_d']; ?></span></td>
     <td class="d"><input type="radio" name="show_registered" value="1"<?php if ($settings['show_registered']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="show_registered" value="0"<?php if ($settings['show_registered']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['email_notification']; ?></b><br /><span class="small"><?php echo $lang_add['email_notification_d']; ?></span></td>
     <td class="d"><input type="radio" name="email_notification" value="1"<?php if ($settings['email_notification']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="email_notification" value="0"<?php if ($settings['email_notification']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_own_entries']; ?></b><br /><span class="small"><?php echo $lang_add['edit_own_entries_d']; ?></span></td>
     <td class="d"><input type="radio" name="user_edit" value="1"<?php if ($settings['user_edit']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="user_edit" value="0"<?php if ($settings['user_edit']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_period']; ?></b><br /><span class="small"><?php echo $lang_add['edit_period_d']; ?></span></td>
     <td class="d"><input type="text" name="edit_period" value="<?php echo $settings['edit_period']; ?>" size="5" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['show_if_edited']; ?></b><br /><span class="small"><?php echo $lang_add['show_if_edited_d']; ?></span></td>
     <td class="d"><input type="radio" name="show_if_edited" value="1"<?php if ($settings['show_if_edited']==1) echo ' checked="checked"'; ?>><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="show_if_edited" value="0"<?php if ($settings['show_if_edited']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_delay']; ?></b><br /><span class="small"><?php echo $lang_add['edit_delay_d']; ?></span></td>
     <td class="d"><input type="text" name="edit_delay" value="<?php echo $settings['edit_delay']; ?>" size="5" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['admin_unnoticeable_edit']; ?></b><br /><span class="small"><?php echo $lang_add['admin_unnoticeable_edit_d']; ?></span></td>
     <td class="d"><input type="radio" name="dont_reg_edit_by_admin" value="1"<?php if ($settings['dont_reg_edit_by_admin']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="dont_reg_edit_by_admin" value="0"<?php if ($settings['dont_reg_edit_by_admin']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['mod_unnoticeable_edit']; ?></b><br /><span class="small"><?php echo $lang_add['mod_unnoticeable_edit_d']; ?></span></td>
     <td class="d"><input type="radio" name="dont_reg_edit_by_mod" value="1"<?php if ($settings['dont_reg_edit_by_mod']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="dont_reg_edit_by_mod" value="0"<?php if ($settings['dont_reg_edit_by_mod']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['bbcode']; ?></b><br /><span class="small"><?php echo $lang_add['bbcode_d']; ?></span></td>
     <td class="d"><input type="radio" name="bbcode" value="1"<?php if ($settings['bbcode']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="bbcode" value="0"<?php if ($settings['bbcode']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['bbcode_img']; ?></b><br /><span class="small"><?php echo $lang_add['bbcode_img_d']; ?></span></td>
     <td class="d"><input type="radio" name="bbcode_img" value="1"<?php if ($settings['bbcode_img']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="bbcode_img" value="0"<?php if ($settings['bbcode_img']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['upload_images']; ?></b><br /><span class="small"><?php echo $lang_add['upload_images_d']; ?></span></td>
     <td class="d"><input type="radio" name="upload_images" value="1"<?php if ($settings['upload_images']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="upload_images" value="0"<?php if ($settings['upload_images']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['smilies']; ?></b><br /><span class="small"><?php echo $lang_add['smilies_d']; ?></span></td>
     <td class="d"><input type="radio" name="smilies" value="1"<?php if ($settings['smilies']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="smilies" value="0"<?php if ($settings['smilies']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['autolink']; ?></b><br /><span class="small"><?php echo $lang_add['autolink_d']; ?></span></td>
     <td class="d"><input type="radio" name="autolink" value="1"<?php if ($settings['autolink']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="autolink" value="0"<?php if ($settings['autolink']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['count_views']; ?></b><br /><span class="small"><?php echo $lang_add['count_views_d']; ?></span></td>
     <td class="d"><input type="radio" name="count_views" value="1"<?php if ($settings['count_views']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="count_views" value="0"<?php if ($settings['count_views']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['count_users_online']; ?></b><br /><span class="small"><?php echo $lang_add['count_users_online_d']; ?></span></td>
     <td class="d"><input type="radio" name="count_users_online" value="1"<?php if ($settings['count_users_online']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="count_users_online" value="0"<?php if ($settings['count_users_online']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['rss_feed']; ?></b><br /><span class="small"><?php echo $lang_add['rss_feed_d']; ?></span></td>
     <td class="d"><input type="radio" name="provide_rssfeed" value="1"<?php if ($settings['provide_rssfeed']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;<input type="radio" name="provide_rssfeed" value="0"<?php if ($settings['provide_rssfeed']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['captcha']; ?></b><br /><span class="small"><?php echo $lang_add['captcha_d']; ?></span></td>
     <td class="d"><input type="checkbox" name="captcha_posting" value="1"<?php if ($settings['captcha_posting']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['captcha_posting']; ?><br />
     <input type="checkbox" name="captcha_contact" value="1"<?php if ($settings['captcha_contact']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['captcha_contact']; ?><br />
     <input type="checkbox" name="captcha_register" value="1"<?php if ($settings['captcha_register']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['captcha_register']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['captcha_type']; ?></b><br /><span class="small"><?php echo $lang_add['captcha_type_d']; ?></span></td>
     <td class="d"><input type="radio" name="captcha_type" value="0"<?php if ($settings['captcha_type']==0) echo ' checked="checked"'; ?> /><?php echo $lang_add['captcha_type_math']; ?><br />
     <input type="radio" name="captcha_type" value="1"<?php if ($settings['captcha_type']==1) echo ' checked="checked"'; ?> /><?php echo $lang_add['captcha_type_image']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['forum_disabled']; ?></b><br /><span class="small"><?php echo $lang_add['forum_disabled_d']; ?></span></td>
     <td class="d"><input type="radio" name="forum_disabled" value="1"<?php if ($settings['forum_disabled']==1) echo ' checked="checked"'; ?> /><?php echo $lang['yes']; ?>&nbsp;&nbsp;<input type="radio" name="forum_disabled" value="0"<?php if ($settings['forum_disabled']==0) echo ' checked="checked"'; ?> /><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c">&nbsp;</td>
     <td class="d"><input type="submit" name="settings_submitted" value="<?php echo $lang_add['settings_sb']; ?>" /></td>
    </tr>
   </table>
   </form>
   <p style="margin-top:10px;"><a class="textlink" href="admin.php?action=advanced_settings"><?php echo $lang_add['advanced_settings']; ?></a></p>
   <?php
  break;
  case "advanced_settings":
   ?><form action="admin.php" method="post">
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1"><?php
   ksort($settings);
   while(list($key, $val) = each($settings))
    {
     ?><tr>
     <td class="c"><b><?php echo $key; ?></b></td>
     <td class="d"><input type="text" name="<?php echo htmlsc(stripslashes($key)); ?>" value="<?php echo htmlsc(stripslashes($val)); ?>" size="30" /></td>
    </tr><?php
   }
   ?></table>
   <p><br /><input type="submit" name="settings_submitted" value="<?php echo $lang_add['settings_sb']; ?>" />&nbsp;<input type="reset" value="<?php echo $lang['reset_button']; ?>" /></p>
   </form><?php
  break;
  case "delete_users_sure":
   ?>
   <h2><?php echo $lang_add['delete_users_hl']; ?></h2>
   <p class="caution"><?php echo $lang['caution']; ?></p>
   <p><?php if(count($selected)==1) echo $lang_add['delete_user_conf']; else echo $lang_add['delete_users_conf']; ?></p>
   <ul>
   <?php
   for($x=0; $x<count($selected_usernames); $x++)
   {
    ?><li><a href="user.php?id=<?php echo $selected[$x]; ?>"><b><?php echo htmlsc(stripslashes($selected_usernames[$x])); ?></b></a><?php
   }
   ?>
   </ul>
   <br />
   <form action="admin.php" method="post"><div>
   <?php
   for($x=0; $x<count($selected); $x++)
   {
    echo "<input type=\"hidden\" name=\"selected_confirmed[]\" value=\"".$selected[$x]."\" />";
   }
   ?>
   <input type="submit" name="delete_confirmed" value="<?php echo $lang['user_del_subm_b']; ?>" />
   </div></form>
   <p>&nbsp;</p>
   <?php
  break;
  case "empty":
  if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php } ?>
  <p class="caution"><?php echo $lang['caution']; ?></p>
  <p><?php echo $lang_add['empty_forum_note']; ?></p>
  <form action="admin.php" method="post"><div>
  <b><?php echo $lang['password_marking']; ?></b><br /><input type="password" size="25" name="delete_all_postings_confirm_pw" /><br /><br />
  <input type="submit" name="delete_all_postings_confirmed" value="<?php echo $lang_add['empty_forum_sb']; ?>" />
  </div></form>
  <p>&nbsp;</p>
  <?php
  break;
  case "uninstall":
  if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php } ?>
  <p class="caution"><?php echo $lang['caution']; ?></p>
  <p><?php echo $lang_add['delete_db_note']; ?></p>
  <form action="admin.php" method="post"><div>
  <input type="radio" name="delete_modus" value="tables" checked="checked" /> <?php echo $lang_add['delete_tables']; ?><br />
  <input type="radio" name="delete_modus" value="db" /> <?php echo str_replace("[database]",$db_settings['db'],$lang_add['delete_db']); ?><br /><br />
  <b><?php echo $lang['password_marking']; ?></b><br /><input type="password" size="25" name="delete_db_confirm_pw" /><br /><br />
  <input type="submit" name="delete_db_confirmed" value="<?php echo $lang_add['delete_db_note_sb']; ?>" />
  </div></form>
  <p>&nbsp;</p>
  <?php
  break;
  case "delete_marked_threads":
  $lang_add['del_marked_note'] = str_replace("[marked_symbol]", "<img src=\"img/marked.gif\" alt=\"[x]\" width=\"9\" height=\"9\" />", $lang_add['del_marked_note']);
  ?><p class="caution"><?php echo $lang['caution']; ?></p>
  <p><?php echo $lang_add['del_marked_note']; ?></p>
  <form action="admin.php" method="post"><div>
  <?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php } ?>
  <input type="submit" name="delete_marked_threads_confirmed" value="<?php echo $lang_add['del_marked_sb']; ?>" />
  </div></form>
  <p>&nbsp;</p>
  <?php
  break;
  case "unmark":
  ?><p><?php echo $lang_add['unmark_threads_note']; ?></p>
  <form action="admin.php" method="post"><div>
  <?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php } ?>
  <input type="submit" name="unmark_confirmed" value="<?php echo $lang['submit_button_ok']; ?>" />
  </div></form>
  <p>&nbsp;</p>
  <?php
  break;
  case "invert_markings":
  ?><p><?php echo $lang_add['invert_markings_note']; ?></p>
  <form action="admin.php" method="post"><div>
  <?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php } ?>
  <input type="submit" name="invert_markings_confirmed" value="<?php echo $lang['submit_button_ok']; ?>" />
  </div></form>
  <p>&nbsp;</p>
  <?php
  break;
  case "mark_threads":
   ?><form action="admin.php" method="post"><div style="display: inline;"><?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php }
   $lang_add['mark_old_threads'] = str_replace("[number]", "<input type=\"text\" name=\"n1\" value=\"\" size=\"4\" />", $lang_add['mark_old_threads']);
   $lang_add['mark_old_threads_no_replies'] = str_replace("[number]", "<input type=\"text\" name=\"n2\" value=\"\" size=\"4\" />", $lang_add['mark_old_threads_no_replies']);
   ?><p><input type="radio" name="mark_threads" value="1" checked="checked" /> <?php echo $lang_add['mark_old_threads']; ?></p>
   <p><input type="radio" name="mark_threads" value="2" /> <?php echo $lang_add['mark_old_threads_no_replies']; ?></p>
   <p><input type="submit" name="mark_threads_submitted" value="<?php echo $lang['submit_button_ok']; ?>" /></p>
   </div></form><p>&nbsp;</p><?php
  break;
  case "lock_marked_threads":
   $lang_add['lock_marked_conf'] = str_replace("[marked_symbol]", "<img src=\"img/marked.gif\" alt=\"[x]\" width=\"9\" height=\"9\" />", $lang_add['lock_marked_conf']);
   ?><p><?php echo $lang_add['lock_marked_conf']; ?></p>
   <form action="admin.php" method="post"><div>
   <?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php } ?>
   <input type="submit" name="lock_marked_threads_submitted" value="<?php echo $lang['submit_button_ok']; ?>" />
   </div></form><p>&nbsp;</p><?php
  break;
  case "unlock_marked_threads":
   $lang_add['unlock_marked_conf'] = str_replace("[marked_symbol]", "<img src=\"img/marked.gif\" alt=\"[x]\" width=\"9\" height=\"9\" />", $lang_add['unlock_marked_conf']);
   ?><p><?php echo $lang_add['unlock_marked_conf']; ?></p>
   <form action="admin.php" method="post"><div>
   <?php if(isset($_GET['refer'])) { ?><input type="hidden" name="refer" value="<?php echo htmlsc(stripslashes($_GET['refer'])); ?>" /><?php } ?>
   <input type="submit" name="unlock_marked_threads_submitted" value="<?php echo $lang['submit_button_ok']; ?>" />
   </div></form><p>&nbsp;</p><?php
  break;
  case "delete_category":
  #$categories = get_categories();
  if (count($categories) > 1)
   {
    $cat_select = '<select class="kat" size="1" name="move_category">';
    while(list($key, $val) = each($categories))
     {
      if ($key!=stripslashes($category_id)) $cat_select .= '<option value="'.$key.'">'.$val.'</option>';
     }
    $cat_select .= '</select>';
   }
  ?><h2><?php echo str_replace("[category]",stripslashes($category_name),$lang_add['del_cat_hl']); ?></h2>
  <p class="caution"><?php echo $lang['caution']; ?></p>
  <form action="admin.php" method="post"><div style="display: inline;">
  <input type="hidden" name="category_id" value="<?php echo $category_id; ?>" />
  <?php if (count($categories) <= 1) { ?><input type="hidden" name="move_category" value="0" /><?php } ?>
  <p><input type="radio" name="delete_mode" value="complete" checked="checked" /> <?php echo $lang_add['del_cat_completely']; ?></p></td>
  <p><input type="radio" name="delete_mode" value="keep_entries" /> <?php if (count($categories) <= 1) echo $lang_add['del_cat_keep_entries']; else echo str_replace("[category]",$cat_select,$lang_add['del_cat_move_entries']); ?>
  <p><input type="submit" name="delete_category_submit" value="<?php echo $lang_add['del_cat_sb']; ?>" /></p>
  </div></form><?php
  break;
  case "edit_category":
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php }
   ?><form action="admin.php" method="post"><div style="display: inline;">
   <input type="hidden" name="id" value="<?php echo $id; ?>" />
   <b><?php echo $lang_add['edit_category']; ?></b><br />
   <input type="text" name="category" value="<?php echo htmlsc(stripslashes($category)); ?>" size="25" /><br /><br />
   <b><?php echo $lang_add['accessible_for']; ?></b><br />
   <input type="radio" name="accession" value="0"<?php if ($accession==0) echo " checked=\"ckecked\""; ?> /><?php echo $lang_add['cat_accession_all']; ?><br />
   <input type="radio" name="accession" value="1"<?php if ($accession==1) echo " checked=\"ckecked\""; ?> /><?php echo $lang_add['cat_accession_reg_users']; ?><br />
   <input type="radio" name="accession" value="2"<?php if ($accession==2) echo " checked=\"ckecked\""; ?> /><?php echo $lang_add['cat_accession_mod_admin']; ?><br /><br />
   <input type="submit" name="edit_category_submit" value="<?php echo $lang['submit_button_ok']; ?>" /></div></form><?php
  break;
  case "backup":
   ?><p><b><?php echo $lang_add['backup']; ?></b></p>
   <ul>
    <li><a href="admin.php?backup=1"><?php echo $lang_add['sql_complete']; ?></a></li>
    <li><a href="admin.php?backup=2"><?php echo $lang_add['sql_forum']; ?></a></li>
    <li><a href="admin.php?backup=3"><?php echo $lang_add['sql_forum_marked']; ?></a></li>
    <li><a href="admin.php?backup=4"><?php echo $lang_add['sql_userdata']; ?></a></li>
    <li><a href="admin.php?backup=5"><?php echo $lang_add['sql_categories']; ?></a></li>
    <li><a href="admin.php?backup=6"><?php echo $lang_add['sql_settings']; ?></a></li>
    <li><a href="admin.php?backup=7"><?php echo $lang_add['sql_smilies']; ?></a></li>
    <li><a href="admin.php?backup=8"><?php echo $lang_add['sql_banlists']; ?></a></li>
   </ul>
   <p><b><?php echo $lang_add['restore']; ?></b></p>
   <ul>
    <li><a href="admin.php?action=import_sql"><?php echo $lang_add['import_sql']; ?></a></li>
   </ul><?php
  break;
  case "import_sql":
   ?><p class="caution"><?php echo $lang['caution']; ?></p>
   <p class="normal"><?php echo $lang_add['import_sql_note']; ?></p><?php
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php }
   ?><form action="admin.php" method="post"><div>
   <b><?php echo $lang_add['sql_dump']; ?></b><br />
   <textarea name="sql" cols="70" rows="15"><?php if(isset($sql)) echo stripslashes($sql); ?></textarea><br /><br />
   <p><b><?php echo $lang['password_marking']; ?></b><br />
   <input type="password" size="25" name="sql_pw" /></p>
   <p><input type="submit" name="sql_submit" value="<?php echo $lang['submit_button_ok']; ?>" /></p>
   </div></form>
   <?php
  break;
  case "import_sql_ok":
   ?><p><?php echo $lang_add['import_sql_ok']; ?></p><?php
  break;
  case "email_list":
   $email_result = mysql_query("SELECT user_email FROM ".$db_settings['userdata_table'], $connid);
   if (!$email_result) die($lang['db_error']);
   while ($line = mysql_fetch_assoc($email_result))
    {
     $email_list[] = $line['user_email'];
    }
   mysql_free_result($email_result);
   ?><textarea onFocus="if (this.value==this.defaultValue) this.select()" readonly="readonly" cols="60" rows="15" /><?php echo implode(", ",$email_list); ?></textarea><?php
  break;
  case "clear_userdata":
   ?><p><?php echo $lang_add['clear_userdata_expl']; ?></p>
   <form action="admin.php" method="post">
   <table border="0" cellpadding="5" cellspacing="0">
    <tr>
     <td style="vertical-align: top;"><input type="radio" name="clear_userdata" value="1" checked="checked" /></td>
     <td style="vertical-align: top;"><?php echo $lang_add['clear_users_1']; ?></td>
    </tr>
    <tr>
     <td style="vertical-align: top;"><input type="radio" name="clear_userdata" value="2" /></td>
     <td style="vertical-align: top;"><?php echo $lang_add['clear_users_2']; ?></td>
    </tr>
    <tr>
     <td style="vertical-align: top;"><input type="radio" name="clear_userdata" value="3" /></td>
     <td style="vertical-align: top;"><?php echo $lang_add['clear_users_3']; ?></td>
    </tr>
    <tr>
     <td style="vertical-align: top;"><input type="radio" name="clear_userdata" value="4" /></td>
     <td style="vertical-align: top;"><?php echo $lang_add['clear_users_4']; ?></td>
    </tr>
    <tr>
     <td style="vertical-align: top;"><input type="radio" name="clear_userdata" value="5" /></td>
     <td style="vertical-align: top;"><?php echo $lang_add['clear_users_5']; ?></td>
    </tr>
    <tr>
     <td colspan="2"><input type="submit" value="<?php echo $lang['submit_button_ok']; ?>" /></td>
    </tr>
   </table>
   </form><?php
  break;
  case "banlists":
   // get banned users:
   $result=mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'users' LIMIT 1", $connid);
   if(!$result) die($lang['db_error']);
   $data = mysql_fetch_assoc($result);
   $banned_users = str_replace(',',', ',$data['list']);
   mysql_free_result($result);
   // get banned ips:
   $result=mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'ips' LIMIT 1", $connid);
   if(!$result) die($lang['db_error']);
   $data = mysql_fetch_assoc($result);
   $banned_ips = str_replace(',',', ',$data['list']);
   mysql_free_result($result);
   // get not accepted words:
   $result=mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'words' LIMIT 1", $connid);
   if(!$result) die($lang['db_error']);
   $data = mysql_fetch_assoc($result);
   $not_accepted_words = str_replace(',',', ',$data['list']);
   mysql_free_result($result);
   ?><form action="admin.php" method="post"><div>
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><b><?php echo $lang_add['banned_users']; ?></b><br /><span class="small"><?php echo $lang_add['banned_users_d']; ?></span></td>
     <td class="d"><textarea name="banned_users" cols="50" rows="5"><?php if(isset($banned_users)) echo stripslashes($banned_users);  ?></textarea></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['banned_ips']; ?></b><br /><span class="small"><?php echo $lang_add['banned_ips_d']; ?></span></td>
     <td class="d"><textarea name="banned_ips" cols="50" rows="5"><?php if(isset($banned_ips)) echo stripslashes($banned_ips);  ?></textarea></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['not_accepted_words']; ?></b><br /><span class="small"><?php echo $lang_add['not_accepted_words_d']; ?></span></td>
     <td class="d"><textarea name="not_accepted_words" cols="50" rows="5"><?php if(isset($not_accepted_words)) echo stripslashes($not_accepted_words);  ?></textarea></td>
    </tr>
    <tr>
     <td class="c">&nbsp;</td>
     <td class="d"><input type="submit" name="banlists_submit" value="<?php echo $lang_add['banlists_submit']; ?>" /></td>
    </tr>
   </table>
   </div></form><?php
  break;
  case "smilies":
  if($settings['smilies']==1)
  {
  $count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
  list($smilies_count) = mysql_fetch_row($count_result);
  mysql_free_result($count_result);

  $fp=opendir('img/smilies/');
  while ($file = readdir($fp))
   {
    if(preg_match('/\.gif$/i', $file) || preg_match('/\.png$/i', $file) || preg_match('/\.jpg$/i', $file))
     {
      $smiley_files[] = $file;
     }
   }
  closedir($fp);

  if ($smilies_count > 0)
   {
    $result = mysql_query("SELECT id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
    if(!$result) die($lang['db_error']);
    ?><table class="normaltab" cellspacing="1" cellpadding="5">
     <tr>
      <th><?php echo $lang_add['edit_smilies_smiley']; ?></th>
      <th><?php echo $lang_add['edit_smilies_codes']; ?></th>
      <th><?php echo $lang_add['edit_smilies_title']; ?></th>
      <th colspan="2"><?php echo $lang_add['edit_smilies_action']; ?></th>
      <th><?php echo $lang_add['edit_smilies_order']; ?></th>
     </tr><?php
    $i=0;
    while($line = mysql_fetch_assoc($result))
     {
      // remove used smilies from smiley array:
      if(isset($smiley_files))
       {
        unset($cleared_smiley_files);
        foreach($smiley_files as $smiley_file)
         {
          if($line['file']!=$smiley_file) $cleared_smiley_files[] = $smiley_file;
         }
        if(isset($cleared_smiley_files)) $smiley_files = $cleared_smiley_files;
        else unset($smiley_files);
       }

      unset($codes);
      if(trim($line['code_1'])!='') $codes[] = stripslashes($line['code_1']);
      if(trim($line['code_2'])!='') $codes[] = stripslashes($line['code_2']);
      if(trim($line['code_3'])!='') $codes[] = stripslashes($line['code_3']);
      if(trim($line['code_4'])!='') $codes[] = stripslashes($line['code_4']);
      if(trim($line['code_5'])!='') $codes[] = stripslashes($line['code_5']);
      $codes_disp = implode(' &nbsp;',$codes);
      ?><tr>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><img src="img/smilies/<?php echo stripslashes($line['file']); ?>" alt="<?php echo stripslashes($line['code_1']); ?>"<?php if($line['title']!='') { ?> title="<?php echo stripslashes($line['title']); ?>"<?php } ?> /></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo $codes_disp; ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php echo stripslashes($line['title']); ?></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?edit_smiley=<?php echo $line['id']; ?>"><?php echo $lang_add['edit_link']; ?></a></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?delete_smiley=<?php echo $line['id']; ?>"><?php echo $lang_add['delete_link']; ?></a></td>
      <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="admin.php?move_up_smiley=<?php echo $line['id']; ?>"><img src="img/up.gif" alt="up" width="11" height="11" onmouseover="this.src='img/up_mo.gif';" onmouseout="this.src='img/up.gif';" /></a>&nbsp;<a href="admin.php?move_down_smiley=<?php echo $line['id']; ?>"><img src="img/down.gif" alt="down" width="11" height="11" onmouseover="this.src='img/down_mo.gif';" onmouseout="this.src='img/down.gif';" /></a></td>
      </tr><?php
      $i++;
     }
    mysql_free_result($result);
    ?></table><br />
    <?php
   }
  else
   {
    ?><p><i><?php echo $lang_add['no_smilies']; ?></i></p><?php
   }

   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php }

   if(isset($smiley_files)) $smiley_count = count($smiley_files);
   else $smiley_count = 0;
   if($smiley_count > 0)
   {
    ?><form action="admin.php" method="post"><div>
    <table >
     <tr>
      <td><?php echo $lang_add['add_smiley_file']; ?></td>
      <td><?php echo $lang_add['add_smiley_code']; ?></td>
      <td>&nbsp;</td>
     </tr>
     <tr>
     <td><select name="smiley_file" size="1"><?php
      foreach($smiley_files as $smiley_file)
       {
        ?><option value="<?php echo $smiley_file; ?>"> <?php echo $smiley_file; ?></option><?php
       }
      ?></select></td>
      <td><input type="text" name="smiley_code" size="10" /></td>
      <td><input type="submit" value="<?php echo $lang['submit_button_ok']; ?>" /></td>
     </tr>
    </table>
    </div></form><?php
   }
   else
   {
    ?><p><i><?php echo $lang_add['no_other_smilies_in_folder']; ?></i></p><?php
   }
   }
   else
   {
    ?><p><i><?php echo $lang_add['smilies_disabled']; ?></i></p><?php
   }
   ?><p style="margin-top:20px;"><?php if($settings['smilies']==1) { ?><a href="admin.php?disable_smilies=true"><?php echo $lang_add['disable_smilies']; ?></a><?php } else { ?><a href="admin.php?enable_smilies=true"><?php echo $lang_add['enable_smilies']; ?></a><?php } ?></p><?php
  break;
  case 'edit_smiley':
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php }
   ?><form action="admin.php" method="post">
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_smilies_smiley']; ?></b></td>
     <td class="d"><select name="file" size="1"><?php
     $fp=opendir('img/smilies/');
     while ($dirfile = readdir($fp))
      {
       if(preg_match('/\.gif$/i', $dirfile) || preg_match('/\.png$/i', $dirfile) || preg_match('/\.jpg$/i', $dirfile))
        {
         ?><option value="<?php echo $dirfile; ?>"<?php if($dirfile==stripslashes($file)) { ?> selected="selected"<?php } ?>> <?php echo $dirfile; ?></option><?php
        }
      }
     closedir($fp);
     ?></select></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_smilies_codes']; ?></b></td>
     <td class="d"><input type="text" name="code_1" size="7" value="<?php if(isset($code_1)) echo stripslashes($code_1); ?>" /> <input type="text" name="code_2" size="7" value="<?php if(isset($code_2)) echo stripslashes($code_2); ?>" /> <input type="text" name="code_3" size="7" value="<?php if(isset($code_3)) echo stripslashes($code_3); ?>" /> <input type="text" name="code_4" size="7" value="<?php if(isset($code_4)) echo stripslashes($code_4); ?>" /> <input type="text" name="code_5" size="7" value="<?php if(isset($code_5)) echo stripslashes($code_5); ?>" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['edit_smilies_title']; ?></b></td>
     <td class="d"><input type="text" name="title" size="25" value="<?php if(isset($title)) echo stripslashes($title); ?>" /></td>
    </tr>
    <tr>
     <td class="c">&nbsp;</td>
     <td class="d"><input type="submit" name="edit_smiley_submit" value="<?php echo $lang['submit_button_ok']; ?>" /><input type="hidden" name="id" value="<?php echo $id; ?>" /></td>
    </tr>
   </table>
   <?php
  break;
  case 'edit_user':
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php }
   ?><form action="admin.php" method="post"><div>
   <input type="hidden" name="edit_user_id" value="<?php echo $edit_user_id; ?>" />
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><b><?php echo $lang['username_marking']; ?></b></td>
     <td class="d"><input type="text" size="40" name="edit_user_name" value="<?php echo htmlsc(stripslashes($edit_user_name)); ?>" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang_add['usertype_marking']; ?></b></td>
     <td class="d"><input type="radio" name="edit_user_type" value="user"<?php if ($edit_user_type=="user") echo " checked"; ?>><?php echo $lang['ud_user']; ?><br /><input type="radio" name="edit_user_type" value="mod"<?php if($edit_user_type=="mod") echo " checked"; ?>><?php echo $lang['ud_mod']; ?><br /><input type="radio" name="edit_user_type" value="admin"<?php if($edit_user_type=="admin") echo " checked"; ?>><?php echo $lang['ud_admin']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_email_marking']; ?></b></td>
     <td class="d"><input type="text" size="40" name="user_email" value="<?php echo htmlsc(stripslashes($user_email)); ?>" /></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_show_email']; ?></b></td>
     <td class="d"><input type="radio" name="hide_email" value="0"<?php if ($hide_email=="0") echo " checked"; ?>><?php echo $lang['yes']; ?><br /><input type="radio" name="hide_email" value="1"<?php if ($hide_email=="1") echo " checked"; ?>><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_real_name']; ?></b></td>
     <td class="d"><input type="text" size="40" name="user_real_name" value="<?php echo htmlsc(stripslashes($user_real_name)); ?>" maxlength="<?php echo $settings['name_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_hp']; ?></b></td>
     <td class="d"><input type="text" size="40" name="user_hp" value="<?php echo htmlsc(stripslashes($user_hp)); ?>" maxlength="<?php echo $settings['hp_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_place']; ?></b></td>
     <td class="d"><input type="text" size="40" name="user_place" value="<?php echo htmlsc(stripslashes($user_place)); ?>" maxlength="<?php echo $settings['place_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_profile']; ?></b></td>
     <td class="d"><textarea cols="65" rows="4" name="profile"><?php echo htmlsc(stripslashes($profile)); ?></textarea></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_signature']; ?></b></td>
     <td class="d"><textarea cols="65" rows="4" name="signature"><?php echo htmlsc(stripslashes($signature)); ?></textarea></td>
    </tr>
    <?php if ($settings['thread_view'] != 0 && $settings['board_view'] != 0 || $settings['board_view'] != 0 && $settings['mix_view'] != 0 || $settings['thread_view'] != 0 && $settings['mix_view'] != 0)
    { ?>
    <tr>
     <td class="c"><b><?php echo $lang['user_standard_view']; ?></b></td>
     <td class="d"><?php
                       if ($settings['thread_view'] == 1) { ?><input type="radio" name="user_view" value="thread"<?php if ($user_view=="thread") echo ' checked="checked"'; ?>><?php echo $lang['thread_view_linkname']; ?><br /><?php }
                       if ($settings['board_view'] == 1) { ?><input type="radio" name="user_view" value="board"<?php if ($user_view=="board") echo ' checked="checked"'; ?>>&nbsp;<?php echo $lang['board_view_linkname']; ?><br /><?php }
                       if ($settings['mix_view'] == 1) { ?><input type="radio" name="user_view" value="mix"<?php if ($user_view=="mix") echo ' checked="checked"'; ?>>&nbsp;<?php echo $lang['mix_view_linkname']; ?><?php } ?></td>
    </tr>
    <?php } ?>
    <tr>
     <td class="c"><b><?php echo $lang['user_pers_msg']; ?></b></td>
     <td class="d"><input type="radio" name="personal_messages" value="1"<?php if ($personal_messages=="1") echo " checked"; ?>><?php echo $lang['user_pers_msg_act']; ?><br /><input type="radio" name="personal_messages" value="0"<?php if ($personal_messages=="0") echo " checked"; ?>><?php echo $lang['user_pers_msg_deact']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_time_diff']; ?></b></td>
     <td class="d"><select name="user_time_difference" size="1"><?php for ($h = -24; $h <= 24; $h++) { ?><option value="<?php echo $h; ?>"<?php if ($user_time_difference==$h) echo ' selected="selected"'; ?>><?php echo $h; ?></option><?php } ?></select></td>
    </tr>
    <?php if ($edit_user_type=="admin" || $edit_user_type=="mod")
    { ?>
    <tr>
     <td class="c"><b><?php echo $lang['admin_mod_notif']; ?></b></td>
     <td class="d"><input type="checkbox" name="new_posting_notify" value="1"<?php if ($new_posting_notify=="1") echo " checked"; ?>><?php echo $lang['admin_mod_notif_np']; ?><br />
     <input type="checkbox" name="new_user_notify" value="1"<?php if ($new_user_notify=="1") echo " checked"; ?>><?php echo $lang['admin_mod_notif_nu']; ?></td>
    </tr>
    <?php } ?>
    <tr>
     <td class="c">&nbsp;</td>
     <td class="d"><input type="submit" name="edit_user_submit" value="<?php echo $lang['userdata_subm_button']; ?>" />&nbsp;<input type="reset" value="<?php echo $lang['reset_button']; ?>" /></td>
    </tr>
   </table>
   </div></form>
   <?php
  break;
 }

echo $footer;

 }
else { header("location: index.php"); die("<a href=\"index.php\">further...</a>"); }
?>
