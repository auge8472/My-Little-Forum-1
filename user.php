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

include("inc.php");

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  if (isset($_GET['id'])) header("location: login.php?referer=user.php&id=".$_GET['id']);
  else header("location: login.php?referer=user.php");
  die("<a href=\"login.php?referer=user.php\">further...</a>");
 }

if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
 {
  header("location: login.php");
  die("<a href=\"login.php\">further...</a>");
 }

// import vars:
if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id'];
if (isset($_SESSION[$settings['session_prefix'].'user_type'])) $user_type = $_SESSION[$settings['session_prefix'].'user_type'];
if (isset($_SESSION[$settings['session_prefix'].'user_name'])) $user_name = $_SESSION[$settings['session_prefix'].'user_name'];
if (isset($_GET['action'])) $action = $_GET['action'];
if (isset($_GET['id'])) $id = intval($_GET['id']);
if (isset($_POST['action'])) $action = $_POST['action'];
if (isset($_POST['userdata_submit'])) $userdata_submit = $_POST['userdata_submit'];
if (isset($_POST['pw_submit'])) $pw_submit = $_POST['pw_submit'];
if (isset($_POST['old_pw'])) $old_pw = $_POST['old_pw'];
if (isset($_POST['new_pw'])) $new_pw = $_POST['new_pw'];
if (isset($_POST['new_pw_conf'])) $new_pw_conf = $_POST['new_pw_conf'];
if (isset($_POST['user_real_name'])) $user_real_name = $_POST['user_real_name'];
if (isset($_POST['hide_email'])) $hide_email = $_POST['hide_email'];
if (isset($_POST['user_hp'])) $user_hp = $_POST['user_hp'];
if (isset($_POST['user_place'])) $user_place = $_POST['user_place'];
if (isset($_POST['profile'])) $profile = $_POST['profile'];
if (isset($_POST['signature'])) $signature = $_POST['signature'];
if (isset($_POST['user_view'])) $user_view = $_POST['user_view'];
if (isset($_POST['user_delete_submit'])) $user_delete_submit = $_POST['user_delete_submit'];
if (isset($_POST['pw_delete'])) $pw_delete = $_POST['pw_delete'];
if (isset($_POST['new_posting_notify'])) $new_posting_notify = $_POST['new_posting_notify'];
if (isset($_POST['new_user_notify'])) $new_user_notify = $_POST['new_user_notify'];
if (isset($_POST['personal_messages'])) $personal_messages = $_POST['personal_messages'];
if (isset($_GET['page'])) $page = $_GET['page'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_GET['descasc'])) $descasc = $_GET['descasc'];
if (isset($_POST['user_time_difference'])) $user_time_difference = $_POST['user_time_difference'];

if (empty($page)) $page = 0;
if (empty($category)) $category = "all";

unset($errors);

// Check if user locked:
$lock_result = mysqli_query($connid, "SELECT user_lock FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ." LIMIT 1");
if (!$lock_result) die($lang['db_error']);
$lock_result_array = mysqli_fetch_assoc($lock_result);
mysqli_free_result($lock_result);
if ($lock_result_array['user_lock'] > 0) $action = "locked";

if (isset($_GET['user_lock']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "admin" || $_SESSION[$settings['session_prefix'].'user_type'] == "mod"))
 {
  $lock_result = mysqli_query($connid, "SELECT user_lock, user_type FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($_GET['user_lock']) ." LIMIT 1");
  if (!$lock_result) die($lang['db_error']);
  $field = mysqli_fetch_assoc($lock_result);
  mysqli_free_result($lock_result);
  if ($field['user_type'] == "user")
   {
    $new_lock = ($field['user_lock'] == 0) ? 1 : 0;
    $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET user_lock=". intval($new_lock) .", last_login=last_login, registered=registered WHERE user_id=". intval($_GET['user_lock']) ." LIMIT 1");
   }
  $action="show users";
 }

if(isset($_POST['change_email_submit']))
 {
    $new_email = trim($_POST['new_email']);
    $pw_new_email = $_POST['pw_new_email'];
    // Check data:
    $email_result = mysqli_query($connid, "SELECT user_id, user_name, user_pw, user_email FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1");
    if (!$email_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($email_result);
    mysqli_free_result($email_result);
    if ($pw_new_email=='' || $new_email=='') $errors[] = $lang['error_form_uncompl'];
    if (empty($errors))
     {
      if (strlen($new_email) > $settings['email_maxlength']) $errors[] = $lang['email_marking'] . " " .$lang['error_input_too_long'];
      if ($new_email == $field["user_email"]) $errors[] = $lang['error_email_equal'];
      if (!preg_match("/^[^@]+@.+\.\D{2,5}$/", $new_email)) $errors[] = $lang['error_email_wrong'];
      if ($field["user_pw"] != md5(trim($pw_new_email))) $errors[] = $lang['pw_wrong'];
     }
    if (empty($errors))
     {
      $activate_code = md5(uniqid(rand()));
      // send mail with activation key:
      $ip = $_SERVER["REMOTE_ADDR"];
      $lang['new_user_email_txt'] = str_replace("[name]", $field['user_name'], $lang['change_email_txt']);
      #$lang['new_user_email_txt'] = str_replace("[password]", $new_user_pw, $lang['new_user_email_txt']);
      $lang['new_user_email_txt'] = str_replace("[activate_link]", $settings['forum_address']."register.php?id=".$field['user_id']."&key=".$activate_code, $lang['new_user_email_txt']);
      $header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
      $header .= "X-Mailer: Php/" . phpversion(). "\n";
      $header .= "X-Sender-ip: $ip\n";
      $header .= "Content-Type: text/plain";
      $new_user_mailto = $field['user_name']." <".$new_email.">";
      if($settings['mail_parameter']!='')
       {
        @mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt'], $header, $settings['mail_parameter']) or $errors[] = $lang['error_meilserv'];
       }
      else
       {
        @mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt'], $header) or $errors[] = $lang['error_meilserv'];
       }
      if(empty($errors))
       {
        @mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET user_email='". mysqli_real_escape_string($connid, $new_email) ."', last_login=last_login, registered=registered, activate_code = '". mysqli_real_escape_string($connid, $activate_code) ."' WHERE user_id=". intval($user_id)) or die($lang['db_error']);
        header("location: login.php"); die("<a href=\"login.php\">further...</a>");
       }
      else $action="email";
     }
    else $action="email";
 }

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && empty($action))
 {
  if (isset($id)) $action = "get userdata";
  else $action = "show users";
 }
elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($action))
 {
  switch ($action) // Aktionen vor der Ausgabe von HTML
  {
   case "edit submited":
    // Check the posted data:
    $user_real_name = trim($user_real_name);
    $user_hp = trim($user_hp);
    $user_place = trim($user_place);
    $profile = trim($profile);
    $signature = trim($signature);
    #if (isset($user_hp) && substr($user_hp,0,7) == "http://") $user_hp = substr($user_hp,7);
    if (empty($user_view) or $user_view == "") $user_view = $standard;
    if (empty($new_posting_notify)) $new_posting_notify = 0;
    if (empty($new_user_notify)) $new_user_notify = 0;
    #if (isset($user_hp) && $user_hp != "" && !ereg(".",$user_hp)) $errors[] = $lang['error_hp_wrong'];
    if (strlen($user_real_name) > $settings['name_maxlength']) $errors[] = $lang['user_real_name'] . " " .$lang['error_input_too_long'];
    if (strlen($user_hp) > $settings['hp_maxlength']) $errors[] = $lang['user_hp'] . " " .$lang['error_input_too_long'];
    if (strlen($user_place) > $settings['place_maxlength']) $errors[] = $lang['user_place'] . " " .$lang['error_input_too_long'];
    if (strlen($profile) > $settings['profile_maxlength'])
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
     $error_nwtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
     $errors[] = $error_nwtl; } }
     $text_arr = explode(" ",$user_place); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['place_word_maxlength']) {
     $error_pwtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['place_word_maxlength']))."...", $lang['error_place_word_too_long']);
     $errors[] = $error_pwtl; } }
     $text_arr = str_replace("\n", " ", $profile);
     if ($settings['bbcode'] == 1) { $text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr); }
     if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1) { $text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr); $text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr); $text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr); }
     $text_arr = explode(" ",$text_arr); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['text_word_maxlength']) {
     $error_twtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_prof_word_too_long']);
     $errors[] = $error_twtl; } }
     $text_arr = str_replace("\n", " ", $signature);
     if ($settings['bbcode'] == 1) { $text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr); $text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr); }
     if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1) { $text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr); $text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr); $text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr); }
     $text_arr = explode(" ",$text_arr); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['text_word_maxlength']) {
     $error_twtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_sig_word_too_long']);
     $errors[] = $error_twtl; } }
    // End of checking

    #if (isset($hp) && substr($hp,0,7) == "http://") { $hp = substr($hp,7); }
    if (empty($hide_email)) $hide_email = 0;
    if (empty($errors))
     {
      $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET user_real_name='". mysqli_real_escape_string($connid, $user_real_name) ."', hide_email=". intval($hide_email) .", user_hp='". mysqli_real_escape_string($connid, $user_hp) ."', user_place='". mysqli_real_escape_string($connid, $user_place) ."', profile='". mysqli_real_escape_string($connid, $profile) ."', signature='". mysqli_real_escape_string($connid, $signature) ."', last_login=last_login, registered=registered, user_view='". mysqli_real_escape_string($connid, $user_view) ."', new_posting_notify=". intval($new_posting_notify) .", new_user_notify=". intval($new_user_notify) .", personal_messages=". intval($personal_messages) .", time_difference=". intval($user_time_difference) ." WHERE user_id=". intval($user_id) ." LIMIT 1");
      $_SESSION[$settings['session_prefix'].'user_view'] = $user_view;
      $_SESSION[$settings['session_prefix'].'user_time_difference'] = $user_time_difference;

      header("location: user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']); die("<a href=\"user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']."\">further...</a>");
     }
    else $action="edit";
   break;

   case "pw submited":
    $pw_result = mysqli_query($connid, "SELECT user_pw FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1");
    if (!$pw_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($pw_result);
    mysqli_free_result($pw_result);

    trim($old_pw);
    trim($new_pw);
    trim($new_pw_conf);

    if ($old_pw=="" or $new_pw=="" or $new_pw_conf =="") $errors[] = $lang['error_form_uncompl'];
    else
     {
      if ($field["user_pw"] != md5($old_pw)) $errors[] = $lang['error_old_pw_wrong'];
      if ($new_pw_conf != $new_pw) $errors[] = $lang['error_pw_conf_wrong'];
     }
    // Update, if no errors:
    if (empty($errors))
     {
      $pw_update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET user_pw='". mysqli_real_escape_string($connid, md5($new_pw)) ."', last_login=last_login, registered=registered WHERE user_id=". intval($user_id));
      header("location: user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']); die("<a href=\"user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']."\">further...</a>");
     }
    else $action="pw";
   break;
   /* self-delete of user:
   case "delete submited":
    // �berpr�fungen:
    $pw_result = mysqli_query($connid, "SELECT user_pw FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1");
    if (!$pw_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($pw_result);
    mysqli_free_result($pw_result);
    if ($pw_delete=="") $errors[] = $lang['error_form_uncompl'];
    else
     {
      if ($field["user_pw"] != md5(trim($pw_delete))) $errors[] = $lang['pw_wrong'];
     }
    // DB-Update, falls keine Fehler:
    if (empty($errors))
     {
      $delete_result = mysqli_query($connid, "DELETE FROM ". $db_settings['userdata_table'] ." WHERE user_id=". intval($user_id)." LIMIT 1");
      $update_result = mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, user_id=0, email_notify=0 WHERE user_id = ". intval($user_id));
      session_destroy();
      header("location: index.php"); die("<a href=\"index.php\">further...</a>");
     }
    else $action="delete";
   break;
   */

   case "pm_sent":
    $pms_result = mysqli_query($connid, "SELECT user_name, user_email FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1");
    if (!$pms_result) die($lang['db_error']);
    $sender = mysqli_fetch_assoc($pms_result);
    mysqli_free_result($pms_result);

    $pmr_result = mysqli_query($connid, "SELECT user_name, user_email, personal_messages FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($_POST['recipient_id']) ." LIMIT 1");
    if (!$pmr_result) die($lang['db_error']);
    $recipient = mysqli_fetch_assoc($pmr_result);
    mysqli_free_result($pmr_result);

    if ($_POST['pm_text'] == "") $errors[] = $lang['error_pers_msg_no_text'];
    if ($recipient['personal_messages'] == "") $errors[] = $lang['error_pers_msg_deactivated'];

    if (empty($errors))
     {
      $lang['pers_msg_mail_add'] = str_replace("[forum_address]", $settings['forum_address'], $lang['pers_msg_mail_add']);
      $ip = $_SERVER["REMOTE_ADDR"];
      $mail_subject = $_POST['pm_subject'];
      $mail_text = $_POST['pm_text'];
      $mail_text .= "\n\n".$lang['pers_msg_mail_add'];
      $header= "From: ".$sender['user_name']." <".$sender['user_email'].">\n";
      $header .= "Reply-To: ".$sender['user_name']." <".$sender['user_email'].">\n";
      $header .= "X-Mailer: PHP/" . phpversion(). "\n";
      $header .= "X-Sender-IP: $ip\n";
      $header .= "Content-Type: text/plain";
      if($settings['mail_parameter']!='')
       {
        if(!@mail($recipient['user_name']." <".$recipient['user_email'].">", $mail_subject, $mail_text, $header, $settings['mail_parameter'])) { $errors[] = $lang['error_meilserv']; }
       }
      else
       {
        if(!@mail($recipient['user_name']." <".$recipient['user_email'].">", $mail_subject, $mail_text, $header)) { $errors[] = $lang['error_meilserv']; }
       }

      if(empty($errors))
      {
       $lang['conf_email_txt'] = str_replace("[forum_address]", $settings['forum_address'], $lang['conf_email_txt']);
       $lang['conf_email_txt'] = str_replace("[sender_name]", $sender['user_name'], $lang['conf_email_txt']);
       $lang['conf_email_txt'] = str_replace("[recipient_name]", $recipient['user_name'], $lang['conf_email_txt']);
       $lang['conf_email_txt'] = str_replace("[subject]", $_POST['pm_subject'], $lang['conf_email_txt']);
       $lang['conf_email_txt'] .= "\n\n". $_POST['pm_text'];
       $conf_mailto = $sender['user_name']." <".$sender['user_email'].">";
       $ip = $_SERVER["REMOTE_ADDR"];
       $conf_header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
       $conf_header .= "X-Mailer: PHP/" . phpversion(). "\n";
       $conf_header .= "X-Sender-IP: $ip\n";
       $conf_header .= "Content-Type: text/plain";
       if($settings['mail_parameter']!='')
        {
         @mail($conf_mailto, $lang['conf_sj'], $lang['conf_email_txt'], $conf_header, $settings['mail_parameter']);
        }
       else
        {
         @mail($conf_mailto, $lang['conf_sj'], $lang['conf_email_txt'], $conf_header);
        }
      }

      if (empty($errors))
       {
        header("location: user.php?id=".$_POST['recipient_id']);
        die("<a href=\"user.php?id=".$_POST['recipient_id']."\">further...</a>");
       }
      else { $id = $_POST['recipient_id']; $action="personal_message"; }
     }
    else { $id = $_POST['recipient_id']; $action="personal_message"; }
    break;
  }
 }
else
 {
  header("location: index.php"); die("<a href=\"index.php\">further...</a>");
 }

$wo = $lang['user_area_title'];

if ($action == "show users")
 {
  if (empty($descasc)) $descasc="ASC";
  if (empty($order)) $order="user_name";
  $topnav = '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'.$lang['reg_users_hl'].'</b>';
  if (isset($_GET['letter']) && $_GET['letter']!="") $pid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['userdata_table'] ." WHERE user_name LIKE '". mysqli_real_escape_string($connid, $_GET['letter']) ."%'");
  else $pid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['userdata_table']);
  list($thread_count) = mysqli_fetch_row($pid_result);
  mysqli_free_result($pid_result);
  $abs_pid_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['userdata_table']);
  list($abs_thread_count) = mysqli_fetch_row($abs_pid_result);
  mysqli_free_result($abs_pid_result);
  $lang['num_reg_users'] = str_replace("[number]", $abs_thread_count, $lang['num_reg_users']);
  if (isset($_GET['letter']) && $_GET['letter'] == "A") $la = ' selected="selected"'; else $la = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "B") $lb = ' selected="selected"'; else $lb = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "C") $lc = ' selected="selected"'; else $lc = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "D") $ld = ' selected="selected"'; else $ld = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "E") $le = ' selected="selected"'; else $le = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "F") $lf = ' selected="selected"'; else $lf = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "G") $lg = ' selected="selected"'; else $lg = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "H") $lh = ' selected="selected"'; else $lh = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "I") $li = ' selected="selected"'; else $li = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "J") $lj = ' selected="selected"'; else $lj = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "K") $lk = ' selected="selected"'; else $lk = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "L") $ll = ' selected="selected"'; else $ll = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "M") $lm = ' selected="selected"'; else $lm = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "N") $ln = ' selected="selected"'; else $ln = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "O") $lo = ' selected="selected"'; else $lo = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "P") $lp = ' selected="selected"'; else $lp = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "Q") $lq = ' selected="selected"'; else $lq = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "R") $lr = ' selected="selected"'; else $lr = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "S") $ls = ' selected="selected"'; else $ls = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "T") $lt = ' selected="selected"'; else $lt = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "U") $lu = ' selected="selected"'; else $lu = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "V") $lv = ' selected="selected"'; else $lv = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "W") $lw = ' selected="selected"'; else $lw = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "X") $lx = ' selected="selected"'; else $lx = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "Y") $ly = ' selected="selected"'; else $ly = '';
  if (isset($_GET['letter']) && $_GET['letter'] == "Z") $lz = ' selected="selected"'; else $lz = '';
  $subnav_2 = $lang['num_reg_users'] . '&nbsp;&nbsp;<form action="'.basename($_SERVER["PHP_SELF"]).'" method="get" title=""><div style="display: inline;"><select class="kat" size="1" name="letter" onchange="this.form.submit();">
  <option value="">A-Z</option>
  <option value="A"'.$la.'>A</option>
  <option value="B"'.$lb.'>B</option>
  <option value="C"'.$lc.'>C</option>
  <option value="D"'.$ld.'>D</option>
  <option value="E"'.$le.'>E</option>
  <option value="F"'.$lf.'>F</option>
  <option value="G"'.$lg.'>G</option>
  <option value="H"'.$lh.'>H</option>
  <option value="I"'.$li.'>I</option>
  <option value="J"'.$lj.'>J</option>
  <option value="K"'.$lk.'>K</option>
  <option value="L"'.$ll.'>L</option>
  <option value="M"'.$lm.'>M</option>
  <option value="N"'.$ln.'>N</option>
  <option value="O"'.$lo.'>O</option>
  <option value="P"'.$lp.'>P</option>
  <option value="Q"'.$lq.'>Q</option>
  <option value="R"'.$lr.'>R</option>
  <option value="S"'.$ls.'>S</option>
  <option value="T"'.$lt.'>T</option>
  <option value="U"'.$lu.'>U</option>
  <option value="V"'.$lv.'>V</option>
  <option value="W"'.$lw.'>W</option>
  <option value="X"'.$lx.'>X</option>
  <option value="Y"'.$ly.'>Y</option>
  <option value="Z"'.$lz.'>Z</option>
  </select>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></div></form>' . nav($page, $settings['users_per_page'], $thread_count, $order, $descasc, $category);
 }
else $topnav = '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'.$lang['user_area_title'].'</b>';

parse_template();
echo $header;
switch ($action)
 {
  case "get userdata":
   if (empty($id)) $id = $user_id;
   else $result = mysqli_query($connid, "SELECT user_id, user_type, user_name, user_real_name, user_email, hide_email, user_hp, user_place, signature, profile, UNIX_TIMESTAMP(registered + INTERVAL ". $time_difference ." HOUR) AS since_date FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($id));
   if (!$result) die($lang['db_error']);
   $field = mysqli_fetch_assoc($result);
   mysqli_free_result($result);
   // count postings:
   $count_postings_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE user_id = ". intval($id));
   list($postings_count) = mysqli_fetch_row($count_postings_result);
   mysqli_free_result($count_postings_result);

   if ($field["user_name"] != "")
   {
   $lang['user_info_hl'] = str_replace("[name]", htmlsc($field["user_name"]), $lang['user_info_hl']);
   ?>
   <h2><?php echo $lang['user_info_hl']; ?></h2>
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['username_marking']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php echo htmlsc($field["user_name"]); if ($field["user_type"]=="admin") echo "<span class=\"xsmall\">&nbsp;(".$lang['ud_admin'].")</span>"; elseif ($field["user_type"]=="mod") echo "<span class=\"xsmall\">&nbsp;(".$lang['ud_mod'].")</span>";?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_real_name']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php if ($field["user_real_name"]!="") echo htmlsc($field["user_real_name"]); else echo "-" ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_email_marking']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php if ($field["hide_email"]!=1) { ?><a href="contact.php?uid=<?php echo $field['user_id']; ?>"><img src="img/email.gif" alt="'<?php echo $lang['email_alt']; ?>" title="<?php echo str_replace("[name]", htmlsc($field["user_name"]), $lang['email_to_user_linktitle']); ?>" width="13" height="10" /></a><?php } else echo "-"; ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_hp']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php if ($field["user_hp"]!="") { if (substr($field["user_hp"],0,7) != "http://" && substr($field["user_hp"],0,8) != "https://" && substr($field["user_hp"],0,6) != "ftp://" && substr($field["user_hp"],0,9) != "gopher://" && substr($field["user_hp"],0,7) != "news://") $field["user_hp"] = "http://".$field["user_hp"]; ?><a href="<?php echo $field["user_hp"]; ?>"><img src="img/homepage.gif" alt="<?php echo $lang['homepage_alt']; ?>" title="<?php echo htmlsc($field["user_hp"]); ?>" width="13" height="13" /></a><?php } else echo "-" ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_place']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php if ($field["user_place"]!="") echo htmlsc($field["user_place"]); else echo "-" ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_since']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php echo strftime($lang['time_format'],$field["since_date"]); ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_postings']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php echo $postings_count; if ($postings_count > 0) { ?>&nbsp;&nbsp;<span class="small">[ <a href="search.php?show_postings=<?php echo $field["user_id"]; ?>"><?php echo $lang['show_postings_ln']; ?></a> ]</span><?php } ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_profile']; ?></b></p></td>
     <td class="d"><p class="userdata"><?php
          if ($field["profile"]=="")
           {
            echo "-";
           }
          else
           {
            $ftext=$field["profile"];
            $ftext = htmlsc($ftext);
            $ftext = nl2br($ftext);
            $ftext = zitat($ftext);
            if ($settings['autolink'] == 1) $ftext = make_link($ftext);
            if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
            if ($settings['smilies'] == 1) $ftext = smilies($ftext);
            echo $ftext;
      }
     ?></p></td>
    </tr>
    <tr>
     <td class="c"><p class="userdata"><b><?php echo $lang['user_signature']; ?></b></p></td>
     <td class="d"><?php
          if ($field["signature"]=="")
           {
            ?><p class="userdata">-</p><?php
           }
          else
           {
            $ftext=$field["signature"];
            $ftext = htmlsc($ftext);
            $ftext = nl2br($ftext);
            $ftext = zitat($ftext);
            if ($settings['autolink'] == 1) $ftext = make_link($ftext);
            if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
            if ($settings['smilies'] == 1) $ftext = smilies($ftext);
            echo '<p class="signature" style="margin: 0px;">'.$ftext.'</p>';
           }
          ?></td>
    </tr>
   </table>
   <?php
   if ($user_id == $id)
    {
     ?>
     <p><br /><a class="textlink" href="user.php?action=edit"><?php echo $lang['edit_userdata_ln']; ?></a><br />
     <a class="textlink" href="user.php?action=pw"><?php echo $lang['edit_pw_ln']; ?></a></p>
     <!--<a class="textlink" href="user.php?action=delete"><?php echo $lang['delete_account_ln']; ?></a>-->
     <?php
    }
   else
    {
     $lang['pers_msg_ln'] = str_replace("[name]", htmlsc($field["user_name"]), $lang['pers_msg_ln']);
     ?><p><br />
     <a class="textlink" href="user.php?action=personal_message&amp;id=<?php echo $id; ?>"><?php echo $lang['pers_msg_ln']; ?></a></p>
     <?php
    }

   } else echo "<p class=\"caution\">".$lang['user_doesnt_exist']."</p><p>&nbsp;</p>";
  break;

  case "show users":
   if (empty($page)) $page = 0;
   if (empty($order)) $order="user_name";
   if (empty($descasc)) $descasc="ASC";
   $ul = $page * $settings['users_per_page'];
   if (isset($_GET['letter'])) $result = mysqli_query($connid, "SELECT user_id, user_name, user_type, user_email, hide_email, user_hp, user_lock FROM ". $db_settings['userdata_table'] ." WHERE user_name LIKE '". mysqli_real_escape_string($connid, $_GET['letter']) ."%' ORDER BY ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['users_per_page']));
   else $result = mysqli_query($connid, "SELECT user_id, user_name, user_type, user_email, hide_email, user_hp, user_lock FROM ". $db_settings['userdata_table'] ." ORDER BY ". $order ." ". $descasc ." LIMIT ". intval($ul) .", ". intval($settings['users_per_page']));
   if (!$result) die($lang['db_error']);

   // Schauen, wer online ist:
   if ($settings['count_users_online'] == 1)
    {
     $useronline_result = mysqli_query($connid, "SELECT user_id FROM ". $db_settings['useronline_table']);
     if (!$useronline_result) die($lang['db_error']);
     while ($uid_field = mysqli_fetch_assoc($useronline_result))
      {
       $useronline_array[] = $uid_field['user_id'];
      }
     mysqli_free_result($useronline_result);
    }

    if ($thread_count > 0)
     {
      ?><table class="normaltab" border="0" cellpadding="5" cellspacing="1">
      <tr>
      <th><a href="user.php?action=show+users&amp;order=user_name&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_name") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang['userlist_name']; ?></a><?php if ($order=="user_name" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_name" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
      <th><a href="user.php?action=show+users&amp;order=user_type&amp;descasc=<?php if ($descasc=="ASC" && $order=="user_type") echo "DESC"; else echo "ASC"; ?>&amp;ul=<?php echo $ul; ?>" title="<?php echo $lang['order_linktitle']; ?>"><?php echo $lang['userlist_type']; ?></a><?php if ($order=="user_type" && $descasc=="ASC") { ?>&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" border="0"><?php } elseif ($order=="user_type" && $descasc=="DESC") { ?>&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" border="0"><?php } ?></th>
      <th><?php echo $lang['userlist_email']; ?></th>
      <th><?php echo $lang['userlist_hp']; ?></th>
      <?php if ($settings['count_users_online'] == 1) { ?><th><?php echo $lang['userlist_online']; ?></th><?php }
      if (isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "admin" || $_SESSION[$settings['session_prefix'].'user_type'] == "mod")) { ?><th><?php echo $lang['lock']; ?></th> <?php } ?>
      </tr>
      <?php
      $i=0;
      while ($field = mysqli_fetch_assoc($result))
       {
        ?><tr>
        <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><a href="user.php?id=<?php echo $field['user_id']; ?>" title="<?php echo str_replace("[name]", htmlsc($field["user_name"]), $lang['show_userdata_linktitle']); ?>"><b><?php echo htmlsc($field['user_name']); ?></b></a></td>
        <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php if ($field["user_type"] == "admin") echo $lang['ud_admin']; elseif ($field["user_type"] == "mod") echo $lang['ud_mod']; else echo $lang['ud_user']; ?></span></td>
        <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php if ($field["hide_email"]!=1) { ?><a href="contact.php?uid=<?php echo $field['user_id']; ?>"><img src="img/email.gif" alt="'<?php echo $lang['email_alt']; ?>" title="<?php echo str_replace("[name]", htmlsc($field["user_name"]), $lang['email_to_user_linktitle']); ?>" width="13" height="10" /></a><?php } else echo "&nbsp;"; ?></span></td>
        <td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="small"><?php if ($field["user_hp"]!="") { if (substr($field["user_hp"],0,7) != "http://" && substr($field["user_hp"],0,8) != "https://" && substr($field["user_hp"],0,6) != "ftp://" && substr($field["user_hp"],0,9) != "gopher://" && substr($field["user_hp"],0,7) != "news://") $field["user_hp"] = "http://".$field["user_hp"]; ?><a href="<?php echo htmlsc($field["user_hp"]); ?>"><img src="img/homepage.gif" alt="<?php echo $lang['homepage_alt']; ?>" title="<?php echo htmlsc($field["user_hp"]); ?>" width="13" height="13" /></a><?php } else echo "&nbsp;" ?></span></td>
        <?php if ($settings['count_users_online'] == 1) { ?><td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><span class="online"><?php if ($settings['count_users_online'] == 1 && in_array($field['user_id'], $useronline_array)) { echo $lang['online']; } else echo "&nbsp;"; ?></span></td><?php }
        if (isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "admin" || $_SESSION[$settings['session_prefix'].'user_type'] == "mod")) { ?><td class="<?php if($i % 2 == 0) echo "a"; else echo "b"; ?>"><?php if ($field["user_type"]=="user") { if ($field["user_lock"] == 0) { ?><span class="small"><a href="user.php?user_lock=<?php echo $field["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;page=<?php echo $page; ?>" title="<?php echo str_replace("[name]", htmlsc($field["user_name"]), $lang['lock_user_lt']); ?>"><?php echo $lang['unlocked']; ?></a></span><?php } else { ?><span class="small"><a style="color: red;" href="user.php?user_lock=<?php echo $field["user_id"]; ?>&amp;order=<?php echo $order; ?>&amp;descasc=<?php echo $descasc; ?>&amp;page=<?php echo $page; ?>" title="<?php echo str_replace("[name]", htmlsc($field["user_name"]), $lang['unlock_user_lt']); ?>"><?php echo $lang['locked']; ?></a></span><?php } } else echo "&nbsp;"; ?></td><?php } ?>
        </tr>
        <?php $i++;
       }
      ?></table><?php
     }
    else
     {
      ?><p><i><?php echo $lang['no_users']; ?></i></p><?php
     }
  break;

  case "edit":
   $result = mysqli_query($connid, "SELECT user_name, user_real_name, user_email, hide_email, user_hp, user_place, signature, profile, user_view, new_posting_notify, new_user_notify, personal_messages, time_difference FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id));
   if (!$result) die($lang['db_error']);
   $field = mysqli_fetch_assoc($result);
   mysqli_free_result($result);

   if (empty($userdata_submit))
    {
     $hide_email = $field["hide_email"];
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
    }

   $lang['edit_userdata_hl'] = str_replace("[name]", htmlsc($field["user_name"]), $lang['edit_userdata_hl']);
   ?>
   <h2><?php echo $lang['edit_userdata_hl']; ?></h2>
   <?php
   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php }
   ?>
   <form action="user.php" method="post">
   <input type="hidden" name="action" value="edit submited">
   <table class="normaltab" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="c"><b><?php echo $lang['username_marking']; ?></b></td>
     <td class="d"><?php echo htmlsc($field["user_name"]); ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_email_marking']; ?></b></td>
     <td class="d"><?php echo htmlsc($field["user_email"]); ?>&nbsp;&nbsp;<span class="small">[ <a class="sln" href="user.php?action=email"><?php echo $lang['edit_email_ln']; ?></a> ]</span></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_show_email']; ?></b><br /><span class="small"><?php echo $lang['user_show_email_exp']; ?></span></td>
     <td class="d"><input type="radio" name="hide_email" value="0"<?php if ($hide_email=="0") echo " checked"; ?>><?php echo $lang['yes']; ?><br /><input type="radio" name="hide_email" value="1"<?php if ($hide_email=="1") echo " checked"; ?>><?php echo $lang['no']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_real_name']; ?></b><br /><span class="small"><?php echo $lang['optional_marking']; ?></span></td>
     <td class="d"><input type="text" size="40" name="user_real_name" value="<?php echo htmlsc($user_real_name); ?>" maxlength="<?php echo $settings['name_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_hp']; ?></b><br /><span class="small"><?php echo $lang['optional_marking']; ?></span></td>
     <td class="d"><input type="text" size="40" name="user_hp" value="<?php echo htmlsc($user_hp); ?>" maxlength="<?php echo $settings['hp_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_place']; ?></b><br /><span class="small"><?php echo $lang['optional_marking']; ?></span></td>
     <td class="d"><input type="text" size="40" name="user_place" value="<?php echo htmlsc($user_place); ?>" maxlength="<?php echo $settings['place_maxlength'] ?>"></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_profile']; ?></b><br /><span class="small"><?php echo $lang['user_profile_exp'] . "<br />" . $lang['optional_marking']; ?></span></td>
     <td class="d"><textarea cols="65" rows="10" name="profile"><?php echo htmlsc($profile); ?></textarea></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_signature']; ?></b><br /><span class="small"><?php echo $lang['user_sig_exp'] . "<br />" . $lang['optional_marking']; ?></span></td>
     <td class="d"><textarea cols="65" rows="4" name="signature"><?php echo htmlsc($signature); ?></textarea></td>
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
     <td class="c"><b><?php echo $lang['user_pers_msg']; ?></b><br /><span class="small"><?php echo $lang['user_pers_msg_exp']; ?></span></td>
     <td class="d"><input type="radio" name="personal_messages" value="1"<?php if ($personal_messages=="1") echo " checked"; ?>><?php echo $lang['user_pers_msg_act']; ?><br /><input type="radio" name="personal_messages" value="0"<?php if ($personal_messages=="0") echo " checked"; ?>><?php echo $lang['user_pers_msg_deact']; ?></td>
    </tr>
    <tr>
     <td class="c"><b><?php echo $lang['user_time_diff']; ?></b><br /><span class="small"><?php echo $lang['user_time_diff_exp']; ?></span></td>
     <td class="d"><select name="user_time_difference" size="1"><?php for ($h = -24; $h <= 24; $h++) { ?><option value="<?php echo $h; ?>"<?php if ($user_time_difference==$h) echo ' selected="selected"'; ?>><?php echo $h; ?></option><?php } ?></select></td>
    </tr>
    <?php if ($user_type=="admin" || $user_type=="mod")
    { ?>
    <tr>
     <td class="c"><b><?php echo $lang['admin_mod_notif']; ?></b><br /><span class="small"><?php echo $lang['admin_mod_notif_exp']; ?></span></td>
     <td class="d"><input type="checkbox" name="new_posting_notify" value="1"<?php if ($new_posting_notify=="1") echo " checked"; ?>><?php echo $lang['admin_mod_notif_np']; ?><br />
     <input type="checkbox" name="new_user_notify" value="1"<?php if ($new_user_notify=="1") echo " checked"; ?>><?php echo $lang['admin_mod_notif_nu']; ?></td>
    </tr>
    <?php } ?>
    <tr>
     <td class="c">&nbsp;</td>
     <td class="d"><input type="submit" name="userdata_submit" value="<?php echo $lang['userdata_subm_button']; ?>" />&nbsp;<input type="reset" value="<?php echo $lang['reset_button']; ?>" /></td>
    </tr>
   </table>
   </form>
   <?php if($settings['bbcode'] == 1) { ?>
   <br />
   <p class="xsmall"><?php echo $lang['bbcode_marking_user'];
   if ($settings['bbcode_img']==1) { ?><?php echo '<br />'.$lang['bbcode_img_marking_user']; ?><?php } ?></p>
   <?php
   }
  break;

  case "pw":
   ?>
   <h2><?php echo $lang['change_pw_hl']; ?></h2>
   <?php if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php } ?>
   <form action="user.php" method="post">
   <input type="hidden" name="action" value="pw submited">
   <b><?php echo $lang['old_pw']; ?></b><br />
   <input type="password" size="25" name="old_pw" maxlength="50"><br /><br />
   <b><?php echo $lang['new_pw']; ?></b><br />
   <input type="password" size="25" name="new_pw" maxlength="50"><br /><br />
   <b><?php echo $lang['new_pw_conf']; ?></b><br />
   <input type="password" size="25" name="new_pw_conf" maxlength="50"><br /><br />
   <input type="submit" name="pw_submit" value="<?php echo $lang['new_pw_subm_button']; ?>" title="<?php echo $lang['new_pw_subm_button_title']; ?>">
   </form>
   <p>&nbsp;</p>
   <?php
  break;

  case "email":
   ?>
   <h2><?php echo $lang['change_email_hl']; ?></h2>
   <p class="caution"><?php echo $lang['caution']; ?></p>
   <p class="normal"><?php echo $lang['change_email_exp']; ?></p>
   <?php if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php } ?>
   <form action="user.php" method="post"><div>
   <p><b><?php echo $lang['new_email']; ?></b><br />
   <input type="text" size="25" name="new_email" value="<?php if (isset($new_email)) echo htmlsc($new_email); ?>" maxlength="<?php echo $settings['email_maxlength']; ?>"></p>
   <p><b><?php echo $lang['password_marking']; ?></b><br />
   <input type="password" size="25" name="pw_new_email" maxlength="50"></p>
   <p><input type="submit" name="change_email_submit" value="<?php echo $lang['submit_button_ok']; ?>"></p>
   </div></form>
   <?php
  break;

  /*
  case "delete":
   if(isset($errors))
    {
     ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php
    }
   ?><h2 class="caution"><?php echo $lang['caution']; ?></h2>
   <p><?php echo $lang['user_del_conf']; ?></p>
   <form action="user.php" method="post"><div>
   <input type="hidden" name="action" value="delete submited">
   <b><?php echo $lang['password_marking']; ?></b><br />
   <input type="password" size="25" name="pw_delete" maxlength="50"><br /><br />
   <input type="submit" name="user_delete_submit" value="<?php echo $lang['user_del_subm_b']; ?>" />
   </div></form>
   <p>&nbsp;</p>
   <?php
  break;
  */

  case "personal_message":
   $pma_result = mysqli_query($connid, "SELECT user_name, personal_messages FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($id) ." LIMIT 1");
   if (!$pma_result) die($lang['db_error']);
   $field = mysqli_fetch_assoc($pma_result);
   mysqli_free_result($pma_result);

   $lang['pers_msg_hl'] = str_replace("[name]", htmlsc($field["user_name"]), $lang['pers_msg_hl']);
   ?><h2><?php echo $lang['pers_msg_hl']; ?></h2><?php

   if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php }

   if ($field["personal_messages"] == 1)
    {
     ?><form action="<?php echo basename($_SERVER["PHP_SELF"]); ?>" method="post"><div style="margin-top: 20px;">
     <input type="hidden" name="action" value="pm_sent" />
     <input type="hidden" name="recipient_id" value="<?php echo $id; ?>" />
     <b><?php echo $lang['pers_msg_sj']; ?></b><br />
     <input class="fs" type="text" name="pm_subject" value="<?php if (isset($_POST['pm_subject'])) echo htmlsc($_POST['pm_subject']); else echo ""; ?>" size="50" /><br /><br />
     <b><?php echo $lang['pers_msg_txt']; ?></b><br />
     <textarea name="pm_text" cols="60" rows="15"><?php if (isset($_POST['pm_text'])) echo htmlsc($_POST['pm_text']); else echo ""; ?></textarea><br /><br />
     <input type="submit" name="pm_ok" value="<?php echo $lang['pers_msg_subm_button']; ?>" /><br /><br />
     </div></form><?php
    }
   else
    {
     $lang['pers_msg_deactivated'] = str_replace("[name]", htmlsc($field["user_name"]), $lang['pers_msg_deactivated']);
     echo $lang['pers_msg_deactivated'] . "<p>&nbsp;</p>";
    }
   break;

   case "locked":
    ?><h2 class="caution"><?php echo $lang['user_locked_hl']; ?></h2>
    <p><?php echo str_replace("[name]", htmlsc($user_name), $lang['usr_locked_txt']); ?></p>
    <p>&nbsp;</p><?php
   break;
 }

echo $footer;
?>
