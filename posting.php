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
if(count($_POST) > 0)
foreach($_POST as $key => $value)
$$key = $value;

include("inc.php");

if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
 {
  require('captcha/captcha.php');
  $captcha = new captcha();
 }

if(isset($_POST['category'])) $category = intval($_POST['category']);
if(isset($_POST['p_category'])) $p_category = intval($_POST['p_category']);

// Schauen, ob User gesperrt ist:
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
 {
  $lock_result = mysqli_query($connid, "SELECT user_lock FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ." LIMIT 1");
  if (!$lock_result) die($lang['db_error']);
  $lock_result_array = mysqli_fetch_assoc($lock_result);
  mysqli_free_result($lock_result);
  if ($lock_result_array['user_lock'] > 0)
   {
    header("location: user.php");
    die("<a href=\"user.php\">further...</a>");
   }
 }

if (isset($_GET['lock']) && isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_GET['lock']) && isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod")
{
 $lock_result = mysqli_query($connid, "SELECT tid, locked FROM ". $db_settings['forum_table'] ." WHERE id=". intval($id) ." LIMIT 1");
 if (!$lock_result) die($lang['db_error']);
 $field = mysqli_fetch_assoc($lock_result);
 mysqli_free_result($lock_result);
 if ($field['locked']==0) mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, locked='1' WHERE tid = ". intval($field['tid']));
 else mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, locked='0' WHERE tid = ". intval($field['tid']));

 if (empty($page)) $page = 0;
 if (empty($order)) $order = "time";
 if (empty($descasc)) $descasc = "DESC";
 if (isset($_GET['view']))
  {
   if ($view=="board") header("location: board_entry.php?id=".$field['tid']."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
   else header("location: mix_entry.php?id=".$field['tid']."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
  }
 else
  {
   header("location: forum_entry.php?id=".$id."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
  }
}

if ($settings['access_for_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only'] != 1)
{
if ($settings['entries_by_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['entries_by_users_only'] != 1)
{
 $categories = get_categories();
 if ($categories == "not accessible") { header("location: index.php"); die("<a href=\"index.php\">further...</a>"); }

    unset($errors);  // Array fÃ¼r Fehlermeldungen
    unset($Thread);
    if (empty($descasc)) $descasc="DESC";
    $edit_authorization = 0; // erst mal sicherheitshalber keine Berechtigung zum Editieren von Postings
    $delete_authorization = 0; // erst mal sicherheitshalber keine Berechtigung zum LÃ¶schen von Postings

    if (empty($action)) $action = "new";

    // Falls editiert oder gelÃ¶scht werden soll, schauen, ob der User dazu berechtigt ist:
    if ($action=="edit" || $action == "delete" || $action == "delete ok")
     {
      $user_id_result = mysqli_query($connid, "SELECT user_id FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id) ." LIMIT 1");
      if (!$user_id_result) die($lang['db_error']);
      $result_array = mysqli_fetch_assoc($user_id_result);
      mysqli_free_result($user_id_result);

      $user_type_result = mysqli_query($connid, "SELECT user_type FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($result_array["user_id"]) ." LIMIT 1");
      if (!$user_type_result) die($lang['db_error']);
      $user_result_array = mysqli_fetch_assoc($user_type_result);
      mysqli_free_result($user_type_result);

      // ist da jemand bekanntes?
      if (isset($_SESSION[$settings['session_prefix'].'user_id']))
       {
        // Admin darf alles:
        if ($_SESSION[$settings['session_prefix'].'user_type'] == "admin")
         {
          $edit_authorization = 1;
          $delete_authorization = 1;
         }
        // Moderator darf alles auÃer Postings von Admins editieren/lÃ¶schen:
        elseif ($_SESSION[$settings['session_prefix'].'user_type'] == "mod")
         {
          if ($user_result_array["user_type"] != "admin")
          {
           $edit_authorization = 1;
           $delete_authorization = 1;
          }
         }
        // User darf (falls aktiviert) nur seine eigenen Postings editieren/lÃ¶schen:
        elseif ($_SESSION[$settings['session_prefix'].'user_type'] == "user")
         {
          // Schauen, ob es sich um einen eigenen Eintrag handelt:
          if ($result_array["user_id"] == $_SESSION[$settings['session_prefix'].'user_id'])
           {
            if ($settings['user_edit'] == 1) $edit_authorization = 1;
            if ($settings['user_delete'] == 1) $delete_authorization = 1;
           }
         }
       }
      else
       {
        $edit_authorization = 0;
        $delete_authorization = 0;
       }
     } // Ende ÃberprÃ¼fung der Berechtigung

 // wenn das Formular noch nicht abgeschickt wurde:
 if (empty($form))
  {
   switch ($action)
    {
     case "new":
      // Cookies mit Userdaten einlesen, falls es sich um einen nicht angemeldeten User handelt und Cookies vorhanden sind:
      if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
       {
        if (isset($_COOKIE['user_name'])) {$name = $_COOKIE['user_name']; $setcookie = 1; }
        if (isset($_COOKIE['user_email'])) {$email = $_COOKIE['user_email'];}
        if (isset($_COOKIE['user_hp'])) {$hp = $_COOKIE['user_hp'];}
        if (isset($_COOKIE['user_place'])) {$place = $_COOKIE['user_place'];}
       }

      if(!isset($id) || $id < 0) $id = 0;
      else $id = (int)$id;

      if (empty($show_signature)) $show_signature = 1;

      // if message is a reply:
      if ($id != 0)
       {
        $result = mysqli_query($connid, "SELECT tid, pid, name, subject, category, text, locked FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
        if (!$result) die($lang['db_error']);
        $field = mysqli_fetch_assoc($result);
        if (mysqli_num_rows($result) != 1) $id = 0;
        else
         {
          $thema = $field["tid"];
          $subject = $field["subject"];
          $p_category = $field["category"];
          $text = $field["text"];
          $aname = $field["name"];
          $text = wordwrap($text);
          // Zitatzeichen an den Anfang jeder Zeile stellen:
          $text = preg_replace("/^/m", $settings['quote_symbol']." ", $text);
         }
        mysqli_free_result($result);
        if ($field['locked'] > 0 && (empty($_SESSION[$settings['session_prefix'].'user_type']) || (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin' && $_SESSION[$settings['session_prefix'].'user_type'] != 'mod'))) { $show = "no authorization"; $reason = $lang['thread_locked_error']; }
        else $show = "form";
       }
      else $show = "form";
     break;

     case "edit":
      if ($edit_authorization == 1)
       {
        // fetch data of message which should be edited:
        $edit_result = mysqli_query($connid, "SELECT tid, pid, user_id, name, email, hp, place, subject, category, text, email_notify, show_signature, locked, fixed, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(NOW() - INTERVAL ". $settings['edit_period'] ." MINUTE) AS edit_diff FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
        if (!$edit_result) die($lang['db_error']);
        $field = mysqli_fetch_assoc($edit_result);
        mysqli_free_result($edit_result);
        $thema = $field["tid"];
        $tid = $field["tid"];
        $pid = $field["pid"];
        $p_user_id = $field["user_id"];
        $name = $field["name"];
        $aname = $field["name"];
        $email = $field["email"];
        $hp = $field["hp"];
        $place = $field["place"];
        $subject = $field["subject"];
        $p_category = $field["category"];
        $text = $field["text"];
        $email_notify = $field["email_notify"];
        $show_signature = $field["show_signature"];
        $fixed = $field["fixed"];
        if ($field['locked'] > 0 && (empty($_SESSION[$settings['session_prefix'].'user_type']) || (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin' && $_SESSION[$settings['session_prefix'].'user_type'] != 'mod'))) { $show = "no authorization"; $reason = $lang['thread_locked_error']; }
        elseif ($settings['edit_period'] > 0 && $field["edit_diff"] > $field["time"] && (empty($_SESSION[$settings['session_prefix'].'user_type']) || (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin' && $_SESSION[$settings['session_prefix'].'user_type'] != 'mod'))) { $show = "no authorization"; $reason = str_replace('[minutes]',$settings['edit_period'],$lang['edit_period_over']); }
        else $show = "form";
       }
      else $show = "no authorization";
     break;

     case "delete":
      if ($delete_authorization == 1)
       {
        $delete_result = mysqli_query($connid, "SELECT tid, pid, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS tp_time, name, subject, category FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
        if(!$delete_result) die($lang['db_error']);
        $field = mysqli_fetch_assoc($delete_result);
        $aname = $field["name"];
        $thema = $field["tid"];
        $show = "delete form";
       }
      else $show = "no authorization";
     break;

     case "delete ok":
       if ($delete_authorization == 1)
        {
         $pid_result=mysqli_query($connid, "SELECT pid FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
         if (!$pid_result) die($lang['db_error']);
         $feld = mysqli_fetch_assoc($pid_result);
         if ($feld["pid"] == 0)
          {
           $delete_result = mysqli_query($connid, "DELETE FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($id));
          }
         else
          {
           $last_answer_result=mysqli_query($connid, "SELECT tid, time, last_answer FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
           $field = mysqli_fetch_assoc($last_answer_result);
           mysqli_free_result($last_answer_result);
           // if message is newest in topic:
           if ($field['time'] == $field['last_answer'])
            {
             // vorige Antwort heraussuchen und "last_answer" aktualisieren:
             $last_answer_result=mysqli_query($connid, "SELECT time FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($field['tid']) ." AND time < '". mysqli_real_escape_string($connid, $field['time']) ."' ORDER BY time DESC LIMIT 1");
             $field2 = mysqli_fetch_assoc($last_answer_result);
             mysqli_free_result($last_answer_result);
             $update_result=mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer='". mysqli_real_escape_string($connid, $field2['time']) ."' WHERE tid=". intval($field['tid']));
            }
           // delete message:
           $delete_result = mysqli_query($connid, "DELETE FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
          }

         if (isset($page) && isset($order) && isset($category) && isset($descasc)) { $qs="?page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category; }
         else $qs = "";

         if(isset($view))
          {
           if ($view=='board') { header("location: board.php".$qs); die("<a href=\"board.php".$qs."\">further...</a>"); }
           else { header("location: mix.php".$qs); die("<a href=\"mix.php".$qs."\">further...</a>"); }
          }
         else { header("location: forum.php".$qs); die("<a href=\"forum.php".$qs."\">further...</a>"); }
        }
       else $show = "no authorization";
     break;

    }
  }

 // form submitted:
 elseif (isset($form))
  {
   if (empty($_POST['fixed'])) $fixed = 0; else $fixed = $_POST['fixed'];
   switch ($action)
    {
     case "new":
      // is it a registered user?
      if (isset($_SESSION[$settings['session_prefix'].'user_id']))
       {
        $user_id = $_SESSION[$settings['session_prefix'].'user_id'];
        $name = $_SESSION[$settings['session_prefix'].'user_name'];
       }

      // falls eine Antwort verfasst werden soll, die Thread-ID ermitteln:
      if ($id != 0)
       {
        $tid_result = mysqli_query($connid, "SELECT tid, locked FROM ". $db_settings['forum_table'] ." WHERE id=". intval($id));
        if (!$tid_result) die($lang['db_error']);
        if (mysqli_num_rows($tid_result) != 1) die($lang['db_error']);
        else
         {
          $field = mysqli_fetch_assoc($tid_result);
          $Thread = $field['tid'];
          if ($field['locked'] > 0) { unset($action); $show = "no authorization"; $reason = $lang['thread_locked_error']; }
         }
        mysqli_free_result($tid_result);
       }
      elseif ($id == 0) $Thread = 0;

     break;

     case "edit";
      // fetch missing data from database:
      $edit_result = mysqli_query($connid, "SELECT name, locked, UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(NOW() - INTERVAL ". $settings['edit_period'] ." MINUTE) AS edit_diff FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
      if (!$edit_result) die($lang['db_error']);
      $field = mysqli_fetch_assoc($edit_result);
      mysqli_free_result($edit_result);
      if (empty($name)) $name = $field["name"];
     break;
    }

   // trim and complete data:
      if (empty($email)) $email = "";
      if (empty($hp)) $hp = "";
      if (empty($place)) $place = "";
      #if (empty($hide_email)) $hide_email = 0;
      if (empty($show_signature)) $show_signature = 0;
      if (empty($user_id)) $user_id = 0;
      #if (isset($hp) && substr($hp,0,7) == "http://") $hp = substr($hp,7);
      if (empty($email_notify)) $email_notify = 0;
      if (empty($p_category)) $p_category = 0;
      if (isset($name)) $name = trim($name);
      if (isset($subject)) $subject = trim($subject);
      if (isset($text)) $text = trim($text);
      if (isset($email)) $email = trim($email);
      if (isset($hp)) $hp = trim($hp);
      if (isset($place)) $place = trim($place);
   // end trim and complete data

   // check data:
        // double entry?
        $uniqid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE uniqid = '". mysqli_real_escape_string($connid, $uniqid) ."' AND time > NOW()-10000");
        list($uniqid_count) = mysqli_fetch_row($uniqid_result);
        mysqli_free_result($uniqid_result);
        if ($uniqid_count > 0)
          { header("location: index.php"); die("<a href=\"index.php\">further...</a>"); }

        // check for not accepted words:
        $result=mysqli_query($connid, "SELECT list FROM ". $db_settings['banlists_table'] ." WHERE name = 'words' LIMIT 1");
        if(!$result) die($lang['db_error']);
        $data = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if(trim($data['list']) != '')
         {
          $not_accepted_words = explode(',',trim($data['list']));
          foreach($not_accepted_words as $not_accepted_word)
           {
            if($not_accepted_word!='' && (preg_match("/".$not_accepted_word."/i",$name) || preg_match("/".$not_accepted_word."/i",$text) || preg_match("/".$not_accepted_word."/i",$subject) || preg_match("/".$not_accepted_word."/i",$email) || preg_match("/".$not_accepted_word."/i",$hp) || preg_match("/".$not_accepted_word."/i",$place)))
             {
              $errors[] = $lang['error_not_accepted_word'];
              break;
             }
           }
         }

        if (!isset($name) || $name == "") $errors[] = $lang['error_no_name'];
        // name reserved?
        if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
         {
          $result = mysqli_query($connid, "SELECT user_name FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysqli_real_escape_string($connid, $name) ."'");
          if(!$result) die($lang['db_error']);
          $field = mysqli_fetch_assoc($result);
          mysqli_free_result($result);
          if (strtolower($field["user_name"]) == strtolower($name) && $name != "")
           {
            $lang['error_name_reserved'] = str_replace("[name]", htmlsc($name), $lang['error_name_reserved']);
            $errors[] = $lang['error_name_reserved'];
           }
         }
        if (isset($email) && $email != "" and !preg_match("/^[^@]+@.+\.\D{2,5}$/", $email)) // ÃberprÃ¼fung ob die Email-Adresse das Format name@domain.tld hat
         $errors[] = $lang['error_email_wrong'];
        //if(isset($hp) && $hp != "" and !preg_match("[hier fehlt noch die Reg-Ex]", $hp))
        //  $errors[] = $lang['error_hp_wrong'];
        if ($email == "" && isset($email_notify) && $email_notify == 1 && !isset($_SESSION[$settings['session_prefix'].'user_id']) || $email == "" && isset($email_notify) && $email_notify == 1 && isset($p_user_id) && $p_user_id == 0)
         $errors[] = $lang['error_no_email_to_notify'];
        if (!isset($subject) || $subject == "")
         $errors[] = $lang['error_no_subject'];
        if (empty($settings['empty_postings_possible']) || isset($settings['empty_postings_possible']) && $settings['empty_postings_possible'] != 1)
         {
          if (!isset($text) || $text == "")
          $errors[] = $lang['error_no_text'];
         }
        if (strlen($name) > $settings['name_maxlength'])
         $errors[] = $lang['name_marking'] . " " .$lang['error_input_too_long'];
        if (strlen($email) > $settings['email_maxlength'])
         $errors[] = $lang['email_marking'] . " " .$lang['error_input_too_long'];
        if (strlen($hp) > $settings['hp_maxlength'])
         $errors[] = $lang['hp_marking'] . " " .$lang['error_input_too_long'];
        if (strlen($place) > $settings['place_maxlength'])
         $errors[] = $lang['place_marking'] . " " .$lang['error_input_too_long'];
        if (strlen($subject) > $settings['subject_maxlength'])
         $errors[] = $lang['subject_marking'] . " " .$lang['error_input_too_long'];
        if (strlen($text) > $settings['text_maxlength'])
         {
          $lang['error_text_too_long'] = str_replace("[length]", strlen($text), $lang['error_text_too_long']);
          $lang['error_text_too_long'] = str_replace("[maxlength]", $settings['text_maxlength'], $lang['error_text_too_long']);
          $errors[] = $lang['error_text_too_long'];
         }
        $text_arr = explode(" ",$name); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['name_word_maxlength']) {
        $error_nwtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
        $errors[] = $error_nwtl; } }
        $text_arr = explode(" ",$place); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['place_word_maxlength']) {
        $error_pwtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['place_word_maxlength']))."...", $lang['error_place_word_too_long']);
        $errors[] = $error_pwtl; } }
        $text_arr = explode(" ",$subject); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['subject_word_maxlength']) {
        $error_swtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['subject_word_maxlength']))."...", $lang['error_subject_word_too_long']);
        $errors[] = $error_swtl; } }
        $text_arr = str_replace("\n", " ", $text);
        if ($settings['bbcode'] == 1) { $text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "", $text_arr); $text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr); $text_arr = preg_replace("#\[url\](.+?)\[/url\]#is", "", $text_arr); $text_arr = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "\\2", $text_arr); }
        if ($settings['bbcode_img'] == 1 && $settings['bbcode_img'] == 1) { $text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr); $text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr); $text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr); }
        if ($settings['autolink'] == 1) $text_arr = text_check_link($text_arr);
        $text_arr = explode(" ",$text_arr); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['text_word_maxlength']) {
        $error_twtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['error_text_word_too_long']);
        $errors[] = $error_twtl; } }

        // CAPTCHA check:
        if(isset($_POST['save_entry']) && empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
         {
          if($settings['captcha_type']==1)
           {
            if($captcha->check_captcha($_SESSION['captcha_session'],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
           }
          else
           {
            if($captcha->check_math_captcha($_SESSION['captcha_session'][2],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
           }
         }

   // end check data

   if(empty($errors) && empty($preview) && isset($_POST['save_entry']))
    {
     switch ($action)
      {
       case "new":
            $result = mysqli_query($connid, "INSERT INTO ". $db_settings['forum_table'] ." (pid, tid, uniqid, time, last_answer, user_id, name, subject, email, hp, place, ip, text, show_signature, email_notify, category, fixed) VALUES (". intval($id) .",". intval($Thread) .",'". mysqli_real_escape_string($connid, $uniqid) ."',NOW(),NOW(),". intval($user_id) .",'". mysqli_real_escape_string($connid, $name) ."','". mysqli_real_escape_string($connid, $subject) ."','". mysqli_real_escape_string($connid, $email) ."','". mysqli_real_escape_string($connid, $hp) ."','". mysqli_real_escape_string($connid, $place) ."','". mysqli_real_escape_string($connid, $_SERVER["REMOTE_ADDR"]) ."','". mysqli_real_escape_string($connid, $text) ."',". intval($show_signature) .",". intval($email_notify) .",". intval($p_category) .",". intval($fixed) .")");
            if(!$result) die($lang['db_error']);
            if($id == 0) // Jetzt die Thread-id des neuen Threads korrekt setzen
            if(!mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET tid=id, time=time WHERE id = LAST_INSERT_id()"))
            die($lang['db_error']);
            // wann auf Thread als letztes geantwortet wurde aktualisieren (für Board-Ansicht):
            if($id != 0) { if(!mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=NOW() WHERE tid=". intval($Thread)))
            die($lang['db_error']); }
            // letzten Eintrag ermitteln (um darauf umzuleiten):
            $result_neu = mysqli_query($connid, "SELECT tid, pid, id FROM ". $db_settings['forum_table'] ." WHERE id = LAST_INSERT_ID()");
            $neu = mysqli_fetch_assoc($result_neu);

            // Schauen, ob eine E-Mail benachrichtigung versendet werden soll:
             if ($settings['email_notification'] == 1)
              {
               $parent_result = mysqli_query($connid, "SELECT user_id, name, email, subject, text, email_notify FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id) ." LIMIT 1");
               $parent = mysqli_fetch_assoc($parent_result);
               if ($parent["email_notify"] == 1)
                {
                 // wenn das Posting von einem registrierten User stammt, E-Mail-Adresse aus den User-Daten holen:
                 if ($parent["user_id"] > 0)
                  {
                   $email_result = mysqli_query($connid, "SELECT user_name, user_email FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($parent["user_id"]) ." LIMIT 1");
                   if (!$email_result) die($lang['db_error']);
                   $field = mysqli_fetch_assoc($email_result);
                   mysqli_free_result($email_result);
                   $parent["name"] = $field["user_name"];
                   $parent["email"] = $field["user_email"];
                  }
                 $ip = $_SERVER["REMOTE_ADDR"];
                 $mail_text = unbbcode($text);
                 $emailbody = str_replace("[recipient]", $parent["name"], $lang['email_text']);
                 $emailbody = str_replace("[name]", $name, $emailbody);
                 $emailbody = str_replace("[subject]", $subject, $emailbody);
                 $emailbody = str_replace("[text]", $mail_text, $emailbody);
                 if ($settings['standard'] == "board") $emailbody = str_replace("[posting_address]", $settings['forum_address']."board_entry.php?id=".$neu["tid"]."#p".$neu["id"], $emailbody);
                 elseif ($settings['standard'] == "mix") $emailbody = str_replace("[posting_address]", $settings['forum_address']."mix_entry.php?id=".$neu["tid"]."#p".$neu["id"], $emailbody);
                 else $emailbody = str_replace("[posting_address]", $settings['forum_address']."forum_entry.php?id=".$neu["id"], $emailbody);
                 $emailbody = str_replace("[original_subject]", $parent["subject"], $emailbody);
                 $emailbody = str_replace("[original_text]", $parent["text"], $emailbody);
                 $emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
                 $emailbody = str_replace(htmlsc($settings['quote_symbol']), ">", $emailbody);
                 $emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
                 $an = $parent["name"] ." <". $parent["email"] .">";
                 $sent = processEmail($an, $lang['email_subject'], $emailbody);
                 if ($sent === true) {
                   $sent = "ok";
                 }
                 unset($header); unset($emailbody);
                }
              }
             // E-Mail-Benachrichtigung an Admins und Moderatoren:
               #$ip = $_SERVER["REMOTE_ADDR"];
               $mail_text = unbbcode($text);
               if ($id > 0) $emailbody = str_replace("[name]", $name, $lang['admin_email_text_reply']); else $emailbody = str_replace("[name]", $name, $lang['admin_email_text']);
               $emailbody = str_replace("[subject]", $subject, $emailbody);
               $emailbody = str_replace("[text]", $mail_text, $emailbody);
               if ($settings['standard'] == "board") $emailbody = str_replace("[posting_address]", $settings['forum_address']."board_entry.php?id=".$neu["tid"]."#p".$neu["id"], $emailbody);
               elseif ($settings['standard'] == "mix") $emailbody = str_replace("[posting_address]", $settings['forum_address']."mix_entry.php?id=".$neu["tid"]."#p".$neu["id"], $emailbody);
               else $emailbody = str_replace("[posting_address]", $settings['forum_address']."forum_entry.php?id=".$neu["id"], $emailbody);
               $emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
               $emailbody = str_replace(htmlsc($settings['quote_symbol']), ">", $emailbody);
               $emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
               // Schauen, wer eine E-Mail-Benachrichtigung will:
               $en_result=mysqli_query($connid, "SELECT user_name, user_email FROM ". $db_settings['userdata_table'] ." WHERE new_posting_notify=1");
               if(!$en_result) die($lang['db_error']);
               while ($admin_array = mysqli_fetch_assoc($en_result))
               {
                $ind_emailbody = str_replace("[admin]", $admin_array['user_name'], $emailbody);
                $an = $admin_array['user_name'] ." <". $admin_array['user_email'] .">";
                $sent = processEmail($an, str_replace("[subject]", $subject, $lang['admin_email_subject']), $ind_emailbody);
                if ($sent === true) {
                  $sent2 = "ok";
                }
               }
               mysqli_free_result($en_result);

              // Cookies setzen, falls gewÃ¼nscht und Funktion aktiv:
              if ($settings['remember_userdata'] == 1)
               {
                if (isset($setcookie) && $setcookie==1)
                 {
                  setcookie("user_name",$name,time()+(3600*24*30));
                  setcookie("user_email",$email,time()+(3600*24*30));
                  setcookie("user_hp",$hp,time()+(3600*24*30));
                  setcookie("user_place",$place,time()+(3600*24*30));
                 }
               }

             // fÃ¼r Weiterleitung:
             $further_tid = $neu["tid"];
             $further_id = $neu["id"];
             $refer = 1;

       break;

       case "edit":
        if ($edit_authorization == 1 && ($field['locked'] == 0 || (isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type']=='admin' || $_SESSION[$settings['session_prefix'].'user_type']=='mod'))))
         {
          if (!($settings['edit_period'] > 0 && $field["edit_diff"] > $field["time"] && (empty($_SESSION[$settings['session_prefix'].'user_type']) || (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin' && $_SESSION[$settings['session_prefix'].'user_type'] != 'mod'))))
          {
          $tid_result=mysqli_query($connid, "SELECT tid, name, subject, text FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
          if (!$tid_result) die($lang['db_error']);
          $field = mysqli_fetch_assoc($tid_result);
          mysqli_free_result($tid_result);
          // unnoticed editing for admins and mods:
          if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=="admin" && $settings['dont_reg_edit_by_admin']==1 || isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=="mod" && $settings['dont_reg_edit_by_mod']==1 || ($field['text'] == $text && $field['subject'] == $subject && $field['name'] == $name && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type']=="admin" || $_SESSION[$settings['session_prefix'].'user_type']=="mod")))
           {
            $posting_update_result = mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, name='". mysqli_real_escape_string($connid, $name) ."', subject='". mysqli_real_escape_string($connid, $subject) ."', category=". intval($p_category) .", email='". mysqli_real_escape_string($connid, $email) ."', hp='". mysqli_real_escape_string($connid, $hp) ."', place='". mysqli_real_escape_string($connid, $place) ."', text='". mysqli_real_escape_string($connid, $text) ."', email_notify=". intval($email_notify) .", show_signature=". intval($show_signature) .", fixed=". intval($fixed) ." WHERE id=". intval($id));
           }
          else
           {
            $posting_update_result = mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=NOW(), edited_by='". mysqli_real_escape_string($connid, $_SESSION[$settings['session_prefix']."user_name"]) ."', name='". mysqli_real_escape_string($connid, $name) ."', subject='". mysqli_real_escape_string($connid, $subject) ."', category=". intval($p_category) .", email='". mysqli_real_escape_string($connid, $email) ."', hp='". mysqli_real_escape_string($connid, $hp) ."', place='". mysqli_real_escape_string($connid, $place) ."', text='". mysqli_real_escape_string($connid, $text) ."', email_notify=". intval($email_notify) .", show_signature=". intval($show_signature) .", fixed=". intval($fixed) ." WHERE id=". intval($id));
           }
          $category_update_result = mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, category=". intval($p_category) ." WHERE tid = ". intval($field["tid"]));

          if (isset($back)) $further_tid = $back;
          $further_id = $id;
          $refer = 1;
          }
          else { $show = "no authorization"; $reason = str_replace('[minutes]',$settings['edit_period'],$lang['edit_period_over']); }
         }
        else { $show = "no authorization"; $reason = $lang['thread_locked_error']; }

       break;

      }
    } // Ende "if (empty($errors) && empty($preview))"
   else $show="form";

   if (isset($refer))
    {
     if (isset($page) && isset($order) && isset($category) && isset($descasc)) { $qs="&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category; }
     elseif (isset($category)) { $qs="&category=".$category; }
     else $qs = "";
     if (isset($view) && $view=="board") { header("location: board_entry.php?id=".$further_tid.$qs); die("<a href=\"board_entry.php?id=".$further_tid.$qs."\">further...</a>"); }
     elseif (isset($view) && $view=="mix") { header("location: mix_entry.php?id=".$further_id.$qs); die("<a href=\"mix_entry.php?id=".$further_tid.$qs."\">further...</a>"); }
     else { header("location: forum_entry.php?id=".$further_id.$qs); die("<a href=\"forum_entry.php?id=".$further_id.$qs."\">further...</a>"); }
     exit(); // Skript beenden
    }

  } // Ende "if (isset(form))"

switch ($action)
 {
  case "new":
   if ($id == 0) $wo = $lang['new_entry_marking']; else $wo = $lang['answer_marking'];
  break;
  case "edit";
    $wo = $lang['edit_marking'];
  break;
  case "delete";
   $wo = $lang['delete_marking'];
  break;

 }

if (isset($aname))
 {
  $lang['back_to_posting_linkname'] = str_replace("[name]", htmlsc($aname), $lang['back_to_posting_linkname']);
  $lang['answer_on_posting_marking'] = str_replace("[name]", htmlsc($aname), $lang['answer_on_posting_marking']);
 }

$subnav_1 = '';
if ($action == "new" && $id != 0 || $action == "edit" || $action == "delete")
 {
  if (empty($view))
   {
    if (isset($page) && isset($order) && isset($category) && isset($descasc))
     {
      if (isset($aname)) $subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_posting_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_linkname'].'</a>';
     }
    else
     {
      if (isset($aname)) $subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;descasc='.$descasc.'">'.$lang['back_to_posting_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;descasc='.$descasc.'">'.$lang['back_linkname'].'</a>';
     }
   }
  else
   {
    if ($view=="board")
     {
      if (isset($page) && isset($order) && isset($category) && isset($descasc)) $subnav_1 .= '<a class="textlink" href="board_entry.php?id='.$thema.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="board_entry.php?id='.$thema.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
     }
    else
     {
      if (isset($page) && isset($order) && isset($category) && isset($descasc)) $subnav_1 .= '<a class="textlink" href="mix_entry.php?id='.$thema.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="mix_entry.php?id='.$thema.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
     }
   }
 }
elseif ($action == "new" && $id == 0)
 {
  if (empty($view))
   {
    if (isset($category)) $subnav_1 .= '<a class="textlink" href="forum.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
    else $subnav_1 .= '<a class="textlink" href="forum.php">'.$lang['back_to_overview_linkname'].'</a>';
   }
  elseif (isset($view))
   {
    if ($view=="board")
     {
      if (isset($category)) $subnav_1 .= '<a class="textlink" href="board.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="board.php">'.$lang['back_to_overview_linkname'].'</a>';
     }
    else
     {
      if (isset($category)) $subnav_1 .= '<a class="textlink" href="mix.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
      else $subnav_1 .= '<a class="textlink" href="mix.php">'.$lang['back_to_overview_linkname'].'</a>';
     }
   }
 }

parse_template();
echo $header;

switch ($show)
  {
   case "form":
   if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
    {
     if($settings['captcha_type']==1) $_SESSION['captcha_session'] = $captcha->generate_code();
     else $_SESSION['captcha_session'] = $captcha->generate_math_captcha();
    }
    // Ãberschrift:
    switch ($action)
     {
      case "new":
       if ($id == 0) { ?><h2 class="postingform"><?php echo $lang['new_entry_marking']; ?></h2><?php }
       else { ?><h2 class="postingform"><?php echo $lang['answer_marking']; ?></h2><p class="postingforma"><?php echo $lang['answer_on_posting_marking']; ?></p><?php }
      break;

      case "edit":
       ?><h2 class="postingform"><?php echo $lang['edit_marking']; ?></h2><?php
      break;
     }

    // Fehlermeldungen, falls Fehler aufgetreten sind:
    if(isset($errors))
     {
      ?><p class="caution" style="margin-top: 10px;"><?php echo $lang['error_headline']; ?><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><?php
     }

    // Preview:
    if (isset($preview) && empty($errors))
    {
     if(isset($_SESSION[$settings['session_prefix'].'user_id']))
      {
       if ($action=="edit") $pr_id = $p_user_id; else $pr_id = $_SESSION[$settings['session_prefix']."user_id"];
       $preview_result = mysqli_query($connid, "SELECT user_name, user_email, hide_email, user_hp, user_place, signature FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($pr_id) ." LIMIT 1");
       if (!$preview_result) die($lang['db_error']);
       $field = mysqli_fetch_assoc($preview_result);
       mysqli_free_result($preview_result);
       $pr_name = $field["user_name"];
       $pr_email = $field["user_email"];
       $hide_email = $field["hide_email"];
       $pr_hp = $field["user_hp"];
       $pr_place = $field["user_place"];
       $pr_signature = $field["signature"];
      }

     if (empty($pr_name)) $pr_name = $name;
     if (empty($pr_email)) $pr_email = $email;
     if (empty($hide_email)) $hide_email = 0;
     if (empty($pr_hp)) $pr_hp = $hp;
     if (empty($pr_place)) $pr_place = $place;

     // aktuelle Zeit:
     list($pr_time) = mysqli_fetch_row(mysqli_query($connid, "SELECT UNIX_TIMESTAMP(NOW() + INTERVAL ". $time_difference ." HOUR)"));

     ?><p class="caution" style="margin: 10px 0px 3px 0px;"><?php echo $lang['preview_headline']; ?></p><?php
     if (isset($view))
     {
      ?>
      <table class="normaltab" border="0" cellpadding="5" cellspacing="1" width="100%">
       <tr>
        <td class="autorcell" rowspan="2" valign="top">
         <b><?php echo htmlsc($pr_name); ?></b><br />
         <?php if (($pr_email != "" && $hide_email != 1) or $pr_hp != "") { echo "<br />"; }
         if ($pr_hp != "")
          {
           if (substr($pr_hp,0,7) != "http://" && substr($pr_hp,0,8) != "https://" && substr($pr_hp,0,6) != "ftp://" && substr($pr_hp,0,9) != "gopher://" && substr($pr_hp,0,7) != "news://") $pr_hp = "http://".$pr_hp;
           echo "<a href=\"" . $pr_hp . "\"><img src=\"img/homepage.gif\" alt=\"".$lang['homepage_alt']."\" width=\"13\" height=\"13\" /></a>";
          }
         if (($pr_email != ""  && $hide_email != 1) && $pr_hp != "") { echo "&nbsp;"; }
         if ($pr_email != "" && $hide_email != 1) { echo '<a href="contact.php"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlsc($pr_name), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>'; }
         if (($pr_email != "" && $hide_email != 1) or $pr_hp !="") { echo "<br />"; }
         echo "<br />";
         if ($pr_place != "") { echo htmlsc($pr_place); echo ", <br />"; }
         echo strftime($lang['time_format'],$pr_time); ?>
         <div class="autorcellwidth">&nbsp;</div></td>
        <td class="titlecell"><div class="left"><h2><?php echo htmlsc($subject); ?></h2></div></td>
       </tr>
       <tr>
        <td class="postingcell" valign="top">
         <?php
          if ($text == "")
           { echo $lang['no_text']; }
          else
           {
             $pr_text=$text;
             $pr_text = htmlsc($pr_text);
             $pr_text = nl2br($pr_text);
             $pr_text = zitat($pr_text);
             if ($settings['autolink'] == 1) $pr_text = make_link($pr_text);
             if ($settings['bbcode'] == 1) $pr_text = bbcode($pr_text);
             if ($settings['smilies'] == 1) $pr_text = smilies($pr_text);
             echo '<p class="postingboard">'.$pr_text.'</p>';
            }
           if ($show_signature == 1 && $pr_signature != "")
            {
             $pr_signature = htmlsc($pr_signature);
             $pr_signature = nl2br($pr_signature);
             if ($settings['autolink'] == 1) $pr_signature = make_link($pr_signature);
             if ($settings['bbcode'] == 1) $pr_signature = bbcode($pr_signature);
             if ($settings['smilies'] == 1) $pr_signature = smilies($pr_signature);
             echo '<p class="signature">'.$settings['signature_separator'].$pr_signature.'</p>';
            }

            ?><br /></td>
       </tr>
      </table>
      <?php
     }
    else
     {
      ?><div class="preview">
      <h2 class="postingheadline"><?php echo htmlsc($subject); ?></h2>
          <?php
          $email_hp = ""; $place_wc = ""; $place_c = "";
          if (($pr_email != "" && $hide_email != 1) or $pr_hp != "") $email_hp = " ";
          if ($pr_hp != "") { if (substr($pr_hp,0,7) != "http://" && substr($pr_hp,0,8) != "https://" && substr($pr_hp,0,6) != "ftp://" && substr($pr_hp,0,9) != "gopher://" && substr($pr_hp,0,7) != "news://") $pr_hp = "http://".$pr_hp; $email_hp .= "<a href=\"" . $pr_hp . "\" title=\"".htmlsc($pr_hp)."\"><img src=\"img/homepage.gif\" alt=\"".$lang['homepage_alt']."\" width=\"13\" height=\"13\" /></a>"; }
          if ($pr_email != ""  && $hide_email != 1 && $pr_hp != "") { $email_hp .= " "; }
          if ($pr_email != "" && $hide_email != 1) { $email_hp .= '<a href="contact.php"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlsc($pr_name), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>'; }
          if ($pr_place != "") { $place_c = htmlsc($pr_place) . ", "; $place_wc = htmlsc($pr_place); }
          $lang['forum_author_marking'] = str_replace("[name]", htmlsc($pr_name), $lang['forum_author_marking']);
          $lang['forum_author_marking'] = str_replace("[email_hp]", $email_hp, $lang['forum_author_marking']);
          $lang['forum_author_marking'] = str_replace("[place, ]", $place_c, $lang['forum_author_marking']);
          $lang['forum_author_marking'] = str_replace("[place]", $place_wc, $lang['forum_author_marking']);
          $lang['forum_author_marking'] = str_replace("[time]", strftime($lang['time_format'],$pr_time), $lang['forum_author_marking']);
          ?>
          <p class="author"><?php echo $lang['forum_author_marking']; ?></p>
          <?php
           if ($text == "")
            {
             echo $lang['no_text'];
            }
           else
            {
             $pr_text=$text;
             $pr_text = htmlsc($pr_text);
             $pr_text = nl2br($pr_text);
             $pr_text = zitat($pr_text);
             if ($settings['autolink'] == 1) $pr_text = make_link($pr_text);
             if ($settings['bbcode'] == 1) $pr_text = bbcode($pr_text);
             if ($settings['smilies'] == 1) $pr_text = smilies($pr_text);
             echo '<p class="posting">'.$pr_text.'</p>';
            }
           if ($show_signature == 1 && $pr_signature != "")
            {
             $pr_signature = htmlsc($pr_signature);
             $pr_signature = nl2br($pr_signature);
             if ($settings['autolink'] == 1) $pr_signature = make_link($pr_signature);
             if ($settings['bbcode'] == 1) $pr_signature = bbcode($pr_signature);
             if ($settings['smilies'] == 1) $pr_signature = smilies($pr_signature);
             echo '<p class="signature">'.$settings['signature_separator'].$pr_signature.'</p>';
            }
           ?>
        </div>
       <?php
     }
   }
   // Ende Vorschau
  ?><form action="posting.php" method="post" id="entryform" accept-charset="UTF-8"><div style="margin-top: 10px;">
  <?php if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1) { ?><input type="hidden" name="<?php echo session_name(); ?>" value="<?php echo session_id(); ?>" /><?php } ?>
  <input type="hidden" name="form" value="true" />
  <input type="hidden" name="id" value="<?php echo $id; ?>" />
  <?php if ($action == "edit") { ?><input type="hidden" name="pid" value="<?php echo $pid; ?>" /><?php } ?>
  <input type="hidden" name="uniqid" value="<?php echo uniqid(""); ?>" />
  <input type="hidden" name="action" value="<?php echo $action; ?>" />
  <?php if (isset($p_user_id)) { ?><input type="hidden" name="p_user_id" value="<?php echo $p_user_id; ?>" /><?php } ?>
  <?php if (isset($aname)) { ?><input type="hidden" name="aname" value="<?php echo htmlsc($aname); ?>" /><?php } ?>
  <?php if (isset($view)) echo"<input type=\"hidden\" name=\"view\" value=\"".$view."\" />"; ?>
  <?php if (isset($back)) echo"<input type=\"hidden\" name=\"back\" value=\"".$back."\" />"; ?>
  <?php if (isset($thema)) echo"<input type=\"hidden\" name=\"thema\" value=\"".$thema."\" />"; ?>
  <?php if (isset($page)) echo"<input type=\"hidden\" name=\"page\" value=\"".$page."\" />"; ?>
  <?php if (isset($order)) echo"<input type=\"hidden\" name=\"order\" value=\"".$order."\" />"; ?>
  <?php if (isset($descasc)) echo"<input type=\"hidden\" name=\"descasc\" value=\"".$descasc."\" />"; ?>
  <?php if (isset($category)) echo"<input type=\"hidden\" name=\"category\" value=\"".$category."\" />"; ?>
  <?php if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1) { ?><input type="hidden" name="<?php echo session_name(); ?>" value="<?php echo session_id(); ?>" /><?php } ?>
  <table class="normal" border="0" cellpadding="0" cellspacing="5">
   <tr>
    <td style="width:100px;">&nbsp;</td><td>&nbsp;</td>
   </tr>
   <?php
   // Formularfelder fÃ¼r unbekannte User bzw. wenn Posting unbekannter User editiert wird:
   if (!isset($_SESSION[$settings['session_prefix'].'user_id']) or $action == "edit" && $p_user_id == 0)
   {
   ?>
   <tr>
    <td><b><?php echo $lang['name_marking']; ?></b></td><td><input type="text" size="40" name="name" value="<?php if (isset($name)) { echo htmlsc($name); } ?>" maxlength="<?php echo $settings['name_maxlength']; ?>" /></td>
   </tr>
   <tr>
    <td><b><?php echo $lang['email_marking']; ?></b></td><td><input type="text" size="40" name="email" value="<?php if (isset($email)) { echo htmlsc($email); } ?>" maxlength="<?php echo $settings['email_maxlength']; ?>" />&nbsp;<span class="xsmall"><?php echo $lang['optional_marking']; ?></span></td>
   </tr>
   <tr>
    <td><b><?php echo $lang['hp_marking']; ?></b></td><td><input type="text" size="40" name="hp" value="<?php if (isset($hp)) { echo htmlsc($hp); } ?>" maxlength="<?php echo $settings['hp_maxlength']; ?>" />&nbsp;<span class="xsmall"><?php echo $lang['optional_marking']; ?></span></td>
   </tr>
   <tr>
    <td><b><?php echo $lang['place_marking']; ?></b></td><td><input type="text" size="40" name="place" value="<?php if (isset($place)) { echo htmlsc($place); } ?>" maxlength="<?php echo $settings['place_maxlength']; ?>" />&nbsp;<span class="xsmall"><?php echo $lang['optional_marking']; ?></span></td>
   </tr>
   <?php if ($settings['remember_userdata'] == 1 && !isset($_SESSION[$settings['session_prefix'].'user_id'])) { ?>
   <tr>
    <td>&nbsp;</td><td><span class="small"><input type="checkbox" name="setcookie" value="1"<?php if (isset($setcookie) && $setcookie == 1) echo " checked=\"checked\""; ?> />&nbsp;<?php echo $lang['remember_userdata_cbm']; if (isset($_COOKIE['user_name']) || isset($_COOKIE['user_email']) or isset($_COOKIE['user_hp']) or isset($_COOKIE['user_hp'])) { ?>&nbsp;&nbsp;&nbsp;<a onclick="javascript:delete_cookie(); return false;" href="delete_cookie.php" title="<?php echo $lang['delete_cookies_linktitle']; ?>"><img border="0" src="img/dc.gif" name="dc" alt="" width="12" height="9"><?php echo $lang['delete_cookies_linkname']; ?></a><?php } ?></span></td>
   </tr>
   <?php } ?>
   <tr>
    <td>&nbsp;</td><td>&nbsp;</td>
   </tr>
   <?php } ?>
   <?php if ($categories != false) { ?>
   <tr>
    <td><b><?php echo $lang['category_marking']; ?></b></td><td><select size="1" name="p_category">
     <?php
     if (empty($id) || $id == 0 || $action=="edit" && isset($pid) && $pid == 0)
      {
       while(list($key, $val) = each($categories))
        {
         if($key!=0)
          {
           ?><option value="<?php echo $key; ?>"<?php if((isset($category) && $category!=0 && $key==$category && empty($p_category)) || (isset($p_category) && $key==$p_category)) { ?> selected="selected"<?php } ?>><?php echo $val; ?></option><?php
          }
        }
       }
      else
       {
        ?><option value="<?php echo $p_category; ?>"><?php if(isset($categories[$p_category])) echo $categories[$p_category]; ?></option><?php
       } ?></select></td>
   </tr>
   <?php } ?>
   <tr>
    <td><b><?php echo $lang['subject_marking']; ?></b></td><td><input type="text" size="50" name="subject" value="<?php if (isset($subject)) echo htmlsc($subject) ; ?>" maxlength="<?php echo $settings['subject_maxlength']; ?>" /></td>
   </tr>
   <tr>
    <td>&nbsp;</td><td>&nbsp;</td>
   </tr>
   <tr>
    <td colspan="2"><b><?php echo $lang['text_marking']; ?></b><?php if ($action == "new" && $id != 0) { ?>&nbsp;&nbsp;<span class="small"><?php echo str_replace('[delete_link]','<a class="sln" href="javascript:clear();">'.$lang['delete_link'].'</a>',$lang['delete_quoted_text']); ?></span><?php } ?></td>
   </tr>
   <tr>
    <td colspan="2">
     <table class="normal" border="0" cellpadding="0" cellspacing="0">
     <tr><td valign="top">
     <textarea cols="78" rows="20" name="text"><?php if (isset($text)) echo htmlsc($text); ?></textarea></td><td valign="top" style="padding: 0px 0px 0px 5px;"><?php
     if ($settings['bbcode'] == 1)
      {
       ?><input class="bbcode-button" style="font-weight: bold;" type="button" name="bold" value="<?php echo $lang['bbcode_bold']; ?>" title="<?php echo $lang['bbcode_bold_title']; ?>" onclick="bbcode('b');" /><br />
       <input class="bbcode-button" style="font-style: italic;" type="button" name="italic" value="<?php echo $lang['bbcode_italic']; ?>" title="<?php echo $lang['bbcode_italic_title']; ?>" onclick="bbcode('i');" /><br />
       <input class="bbcode-button" style="color: #0000ff; text-decoration: underline;" type="button" name="link2" value="<?php echo $lang['bbcode_link']; ?>" title="<?php echo $lang['bbcode_link_title']; ?>" onclick="insert_link('entryform','text','<?php echo $lang['bbcode_link_linktext']; ?>','<?php echo $lang['bbcode_link_url']; ?>');" /><br />
       <?php if ($settings['bbcode_img']==1)
        {
         ?><input class="bbcode-button" type="button" name="image" value="<?php echo $lang['bbcode_image']; ?>" title="<?php echo $lang['bbcode_image_title']; ?>" onclick="bbcode('img');" /><br /><?php
         if ($settings['upload_images']==1)
          {
           ?><input class="bbcode-button" type="button" name="imgupload" value="<?php echo $lang['upload_image']; ?>" title="<?php echo $lang['upload_image_title']; ?>" onclick="upload();" /><br /><?php
          }
        }
       ?><br /><?php
      }
      if ($settings['smilies'] == 1)
      {
       $smiley_buttons = 6;

       $count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['smilies_table']);
       list($smilies_count) = mysqli_fetch_row($count_result);
       mysqli_free_result($count_result);

       $result = mysqli_query($connid, "SELECT file, code_1, title FROM ". $db_settings['smilies_table'] ." ORDER BY order_id ASC LIMIT ". intval($smiley_buttons));
       $i=1;
       while ($data = mysqli_fetch_assoc($result))
        {
         ?><button class="smiley-button" name="smiley" type="button" value="<?php echo $data['code_1']; ?>" title="<?php echo $lang['smiley_title']; ?>" onclick="insert('<?php echo $data['code_1']; ?> ');"><img src="img/smilies/<?php echo $data['file']; ?>" alt="<?php echo $data['code_1']; ?>" /></button><?php if($i % 2 == 0) { ?><br /><?php }
         $i++;
        }
       mysqli_free_result($result);

       if($smilies_count > $smiley_buttons) { if($i % 2 == 0) { ?><br /><?php } ?><span class="small"><a href="javascript:more_smilies()" title="<?php echo $lang['more_smilies_linktitle']; ?>"><?php echo $lang['more_smilies_linkname']; ?></a></span><?php }
      }
     ?></td></tr></table>
    </td>
   </tr>
   <tr>
    <td>&nbsp;</td><td>&nbsp;</td>
   </tr>
   <?php if ((isset($_SESSION[$settings['session_prefix'].'user_id']) && $action=="new") || (isset($_SESSION[$settings['session_prefix'].'user_id']) && $action=="edit" && $p_user_id > 0)) { ?>
   <tr>
    <td colspan="2"><input type="checkbox" name="show_signature" value="1"<?php if (isset($show_signature) && $show_signature==1) { echo "checked=\"checked\""; } ?> />&nbsp;<?php echo $lang['show_signature_cbm']; ?></td>
   </tr>
   <?php } ?>
   <?php if ($settings['email_notification'] == 1) { ?>
   <tr>
    <td colspan="2"><input type="checkbox" name="email_notify" value="1"<?php if (isset($email_notify) && $email_notify==1) { echo "checked=\"checked\""; } ?> />&nbsp;<?php echo $lang['email_notification_cbm']; ?></td>
   </tr><?php } else { ?><input type="hidden" name="email_b" value="" /><?php } ?>
   <?php if (isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "admin" || $_SESSION[$settings['session_prefix'].'user_type'] == "mod") && (empty($id) || $id == 0 || $action=="edit" && isset($pid) && $pid == 0)) { ?>
   <tr>
    <td colspan="2"><input type="checkbox" name="fixed" value="1"<?php if (isset($fixed) && $fixed==1) { echo "checked=\"checked\""; } ?> />&nbsp;<?php echo $lang['fix_thread']; ?></td>
   </tr><?php } ?>
   <tr>
    <td>&nbsp;</td><td>&nbsp;</td>
   </tr>
   <?php if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
   { ?><tr>
    <td colspan="2"><b><?php echo $lang['captcha_marking']; ?></b></td>
   </tr><?php
   if($settings['captcha_type']==1)
   { ?><tr>
    <td colspan="2"><img class="captcha" src="captcha/captcha_image.php<?php echo '?'.SID; ?>" alt="<?php echo $lang['captcha_image_alt']; ?>" width="180" height="40"/></td>
   </tr>
   <tr>
    <td colspan="2"><?php echo $lang['captcha_expl_image']; ?></td>
   </tr>
   <tr>
    <td colspan="2"><input type="text" name="captcha_code" value="" size="10" /></td>
   </tr><?php }
   else
   { ?>
   <tr>
    <td colspan="2"><?php echo $lang['captcha_expl_math']; ?></td>
   </tr>
   <tr>
    <td colspan="2"><?php echo $_SESSION['captcha_session'][0]; ?> + <?php echo $_SESSION['captcha_session'][1]; ?> = <input type="text" name="captcha_code" value="" size="5" /></td>
   </tr><?php } ?>
   <tr>
    <td>&nbsp;</td><td>&nbsp;</td>
   </tr><?php } ?>
   <tr>
    <td colspan="2"><input type="submit" name="save_entry" value="<?php echo $lang['submit_button']; ?>" title="<?php echo $lang['submit_button_title']; ?>" />&nbsp;<input type="submit" name="preview" value="<?php echo $lang['preview_button']; ?>" title="<?php echo $lang['preview_button_title']; ?>" />&nbsp;<input type="reset" value="<?php echo $lang['reset_button']; ?>" title="<?php echo $lang['reset_button_title']; ?>" /></td>
   </tr>
  </table>
  </div></form>
  <?php if (!isset($_SESSION[$settings['session_prefix'].'user_id']) || isset($_SESSION[$settings['session_prefix'].'user_id']) && $action=="edit" ) { ?><p class="xsmall" style="margin-top: 30px;"><?php echo $lang['email_exp']; ?></p><?php } else echo "<p>&nbsp;</p>";
  break;

  case "no authorization":
   ?><p class="caution"><?php echo $lang['no_authorization']; ?></p><?php
   if (isset($reason)) { ?><p><?php echo $reason; ?></p><?php }
  break;

   case "delete form":
    $lang['thread_info'] = str_replace("[name]", htmlsc($field["name"]), $lang['thread_info']);
    $lang['thread_info'] = str_replace("[time]", strftime($lang['time_format'],$field["tp_time"]), $lang['thread_info']);
    ?>
    <h2><?php echo $lang['delete_marking']; ?></h2>
    <p><?php echo $lang['delete_posting_sure']; if ($field["pid"]==0) echo "<br />".$lang['delete_whole_thread']; ?></p>
    <p><b><?php echo htmlsc($field["subject"]); ?></b>&nbsp;<?php echo $lang['thread_info']; ?></p>
    <form action="posting.php" method="post" accept-charset="UTF-8"><div>
    <input type="hidden" name="action" value="delete ok" />
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <?php if (isset($view)) { ?><input type="hidden" name="view" value="<?php echo $view; ?>" /><?php } ?>
    <?php if (isset($page)) { ?><input type="hidden" name="page" value="<?php echo $page; ?>" /><?php } ?>
    <?php if (isset($order)) { ?><input type="hidden" name="order" value="<?php echo $order; ?>" /><?php } ?>
    <?php if (isset($descasc)) { ?><input type="hidden" name="descasc" value="<?php echo $descasc; ?>" /><?php } ?>
    <?php if (isset($category)) { ?><input type="hidden" name="category" value="<?php echo $category; ?>" /><?php } ?>
    <input type="submit" name="delete" value="<?php echo $lang['delete_posting_ok']; ?>" />
    </div></form><p>&nbsp;</p><?php
   break;
  }

echo $footer;
}
else { header("location: login.php?msg=noentry"); die("<a href=\"login.php?msg=noentry\">further...</a>"); }
}
else { header("location: login.php?msg=noaccess"); die("<a href=\"login.php?msg=noaccess\">further...</a>"); }
?>
