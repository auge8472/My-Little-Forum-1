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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
 {
  require('captcha/captcha.php');
  $captcha = new captcha();
 }

// remove not activated user accounts:
@mysqli_query($connid, "DELETE FROM ". $db_settings['userdata_table'] ." WHERE registered < (NOW() - INTERVAL 24 HOUR) AND activate_code != '' AND logins=0");

if(isset($_POST['action'])) $action = $_POST['action'];
if(isset($_GET['action'])) $action = $_GET['action'];

unset($errors);

if(isset($_GET['id']) && isset($_GET['key']) && trim($_GET['key'])!='')
 {
  $user_id = intval($_GET['id']);
  $key = trim($_GET['key']);

  if($user_id==0) $errors[] = true;
  if($key=='') $errors[] = true;

  if(empty($errors))
   {
    $result = mysqli_query($connid, "SELECT user_name, user_email, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1");
    if(!$result) die($lang['db_error']);
    if(mysqli_num_rows($result) != 1) $errors[] = true;
    $data = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
   }
  if(empty($errors))
   {
    if(trim($data['activate_code']) == '') $errors[] = true;
   }
  if(empty($errors))
   {
    if($data['activate_code'] == $key)
     {
      @mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET activate_code = '' WHERE user_id=". intval($user_id)) or die('x');

      // E-Mail-Benachrichtigung an Admins und Moderatoren:
      // E-Mail erstellen:
      $ip = $_SERVER["REMOTE_ADDR"];
      $lang['new_user_notif_txt'] = str_replace("[name]", $data['user_name'], $lang['new_user_notif_txt']);
      $lang['new_user_notif_txt'] = str_replace("[email]", $data['user_email'], $lang['new_user_notif_txt']);
      $lang['new_user_notif_txt'] = str_replace("[user_link]", $settings['forum_address']."user.php?id=".$user_id, $lang['new_user_notif_txt']);
      $header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
      $header .= "X-Mailer: Php/" . phpversion(). "\n";
      $header .= "X-Sender-ip: $ip\n";
      $header .= "Content-Type: text/plain";
      // Schauen, wer eine E-Mail-Benachrichtigung will:
      $admin_result=mysqli_query($connid, "SELECT user_name, user_email FROM ". $db_settings['userdata_table'] ." WHERE new_user_notify=1");
      if(!$admin_result) die($lang['db_error']);
      while ($admin_array = mysqli_fetch_assoc($admin_result))
       {
        $ind_reg_emailbody = str_replace("[admin]", $admin_array['user_name'], $lang['new_user_notif_txt']);
        $admin_mailto = $admin_array['user_name']." <".$admin_array['user_email'].">";
        if($settings['mail_parameter']!='')
         {
          if(@mail($admin_mailto, $lang['new_user_notif_sj'], $ind_reg_emailbody, $header, $settings['mail_parameter'])) { $sent = "ok"; }
         }
        else
         {
          if(@mail($admin_mailto, $lang['new_user_notif_sj'], $ind_reg_emailbody, $header)) { $sent = "ok"; }
         }
       }

      header("location: login.php?msg=user_activated");
      exit();
     }
    else $errors[] = true;
   }
  if(isset($errors))
   {
    header("location: register.php?action=activation_failed");
    die();
   }
 }

if(isset($_POST['register_submit']))
 {
  if($settings['register_by_admin_only']  == 0 || isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
  {
  $new_user_name = (!empty($_POST['new_user_name'])) ? trim($_POST['new_user_name']) : "";
  $new_user_email = (!empty($_POST['new_user_email'])) ? trim($_POST['new_user_email']) : "";
  $reg_pw = (!empty($_POST['reg_pw'])) ? $_POST['reg_pw'] : "";
  $reg_pw_conf = (!empty($_POST['reg_pw_conf'])) ? $_POST['reg_pw_conf'] : "";

  // form complete?
  if ($new_user_name=='' || $new_user_email=='' || $reg_pw=='' || $reg_pw_conf=='') $errors[] = $lang['error_form_uncompl'];

  if(empty($errors))
   {
    // password and repeatet Password equal?
    if($reg_pw != $reg_pw_conf) $errors[] = $lang['reg_pw_conf_wrong'];
    // name too long?
    if (strlen($new_user_name) > $settings['name_maxlength']) $errors[] = $lang['name_marking'] . " " .$lang['error_input_too_long'];
    // e-mail address too long?
    if (strlen($new_user_email) > $settings['email_maxlength']) $errors[] = $lang['email_marking'] . " " .$lang['error_input_too_long'];
    // word in username too long?
    $text_arr = explode(" ",$new_user_name); for ($i=0;$i<count($text_arr);$i++) { trim($text_arr[$i]); $laenge = strlen($text_arr[$i]); if ($laenge > $settings['name_word_maxlength']) {
    $error_nwtl = str_replace("[word]", htmlsc(substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
    $errors[] = $error_nwtl; } }
    // look if name already exists:
    $name_result = mysqli_query($connid, "SELECT user_name FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysqli_real_escape_string($connid, $new_user_name) ."' LIMIT 1");
    if(!$name_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($name_result);
    mysqli_free_result($name_result);
    if (strtolower($field["user_name"]) == strtolower($new_user_name) && $new_user_name != "")
     {
      $lang['error_name_reserved'] = str_replace("[name]", htmlsc($new_user_name), $lang['error_name_reserved']);
      $errors[] = $lang['error_name_reserved'];
     }
    // look, if e-mail already exists:
    $email_result = mysqli_query($connid, "SELECT user_email FROM ". $db_settings['userdata_table'] ." WHERE user_email = '". mysqli_real_escape_string($connid, $new_user_email) ."'");
    if(!$email_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($email_result);
    mysqli_free_result($email_result);
    if (strtolower($field["user_email"]) == strtolower($new_user_email) && $new_user_email != "")
     {
      $errors[] = str_replace("[e-mail]", htmlsc($new_user_email), $lang['error_email_reserved']);
     }
    // e-mail correct?
    if (!preg_match("/^[^@]+@.+\.\D{2,5}$/", $new_user_email)) $errors[] = $lang['error_email_wrong'];

    // CAPTCHA check:
    if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
     {
      if(empty($_SESSION['captcha_session'])) $errors[] = $lang['captcha_code_invalid'];
      if(empty($errors))
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
     }
   }

   // check for not accepted words in name and e-mail:
   $result=mysqli_query($connid, "SELECT list FROM ". $db_settings['banlists_table'] ." WHERE name = 'words' LIMIT 1");
   if(!$result) die($lang['db_error']);
   $data = mysqli_fetch_assoc($result);
   mysqli_free_result($result);
   if(trim($data['list']) != '')
    {
     $not_accepted_words = explode(',',trim($data['list']));
     foreach($not_accepted_words as $not_accepted_word)
      {
       if($not_accepted_word!='' && (preg_match("/".$not_accepted_word."/i",$new_user_name) || preg_match("/".$not_accepted_word."/i",$new_user_email)))
        {
         $errors[] = $lang['error_reg_not_accepted_word'];
         break;
        }
      }
    }

   // save user if no errors:
   if (empty($errors))
    {
     $encoded_new_user_pw = md5($reg_pw);
     $activate_code = md5(uniqid(rand()));
     @mysqli_query($connid, "INSERT INTO ". $db_settings['userdata_table'] ." (user_type, user_name, user_pw, user_email, hide_email, profile, last_login, last_logout, user_ip, registered, user_view, personal_messages, activate_code) VALUES ('user','". mysqli_real_escape_string($connid, $new_user_name) ."','". mysqli_real_escape_string($connid, $encoded_new_user_pw) ."','". mysqli_real_escape_string($connid, $new_user_email) ."','1','',NOW(),NOW(),'". mysqli_real_escape_string($connid, $_SERVER["REMOTE_ADDR"]) ."',NOW(),'". mysqli_real_escape_string($connid, $settings['standard']) ."','1', '". mysqli_real_escape_string($connid, $activate_code) ."')") or die($lang['db_error']);

     // get new user ID:
     $new_user_id_result = mysqli_query($connid, "SELECT user_id FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysqli_real_escape_string($connid, $new_user_name) ."' LIMIT 1");
     if (!$new_user_id_result) die($lang['db_error']);
     $field = mysqli_fetch_assoc($new_user_id_result);
     $new_user_id = $field['user_id'];
     mysqli_free_result($new_user_id_result);

     // send e-mail with activation key to new user:
     $ip = $_SERVER["REMOTE_ADDR"];
     $lang['new_user_email_txt'] = str_replace("[name]", $new_user_name, $lang['new_user_email_txt']);
     #$lang['new_user_email_txt'] = str_replace("[password]", $new_user_pw, $lang['new_user_email_txt']);
     $lang['new_user_email_txt'] = str_replace("[activate_link]", $settings['forum_address']."register.php?id=".$new_user_id."&key=".$activate_code, $lang['new_user_email_txt']);
     $header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
     $header .= "X-Mailer: Php/" . phpversion(). "\n";
     $header .= "X-Sender-ip: $ip\n";
     $header .= "Content-Type: text/plain";
     $new_user_mailto = $new_user_name." <".$new_user_email.">";

     if($settings['mail_parameter']!='')
      {
       if(@mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt'], $header, $settings['mail_parameter'])) $sent = true;
      }
     else
      {
       if(@mail($new_user_mailto, $lang['new_user_email_sj'], $lang['new_user_email_txt'], $header)) $sent = true;
      }

     // Bestätigung anzeigen:
     $action = "registered";
    }
   else
    {
     unset($action);
    }
  }
 }

$wo = $lang['register_hl'];
$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['register_hl'].'</b>';
parse_template();
echo $header;

if(empty($action)) $action = 'main';

switch($action)
 {
  case 'main':
   if($settings['register_by_admin_only']  == 0 || isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
     {
      if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
       {
        if($settings['captcha_type']==1) $_SESSION['captcha_session'] = $captcha->generate_code();
        else $_SESSION['captcha_session'] = $captcha->generate_math_captcha();
       }
      ?><p class="normal"><?php echo $lang['register_exp']; ?></p>
      <?php
      // Wenn Fehler, dann Fehlermeldungen ausgeben:
      if (isset($errors)) { ?><p><span class="caution"><?php echo $lang['error_headline']; ?></span><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul><br /></p><?php }
      ?>
      <form action="register.php" method="post" accept-charset="UTF-8"><div>
      <?php if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1) { ?><input type="hidden" name="<?php echo session_name(); ?>" value="<?php echo session_id(); ?>" /><?php } ?>
      <p><b><?php echo $lang['username_marking']; ?></b><br />
      <input type="text" size="25" name="new_user_name" value="<?php if (isset($new_user_name)) echo htmlsc($new_user_name); ?>" maxlength="<?php echo $settings['name_maxlength']; ?>" /></p>
      <p><b><?php echo $lang['user_email_marking']; ?></b><br />
      <input type="text" size="25" name="new_user_email" value="<?php if (isset($new_user_email)) echo htmlsc($new_user_email); ?>" maxlength="<?php echo $settings['email_maxlength']; ?>" /></p>
      <p><b><?php echo $lang['reg_pw']; ?></b><br />
      <input type="password" size="25" name="reg_pw" /></p>
      <p><b><?php echo $lang['reg_pw_conf']; ?></b><br />
      <input type="password" size="25" name="reg_pw_conf" /></p><?php

      // CAPTCHA:
      if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
       {
        ?><p><b><?php echo $lang['captcha_marking']; ?></b></p><?php
        if($settings['captcha_type']==1)
         {
          ?><p><img class="captcha" src="captcha/captcha_image.php<?php echo '?'.SID; ?>" alt="<?php echo $lang['captcha_image_alt']; ?>" width="180" height="40"/></p>
          <p><?php echo $lang['captcha_expl_image']; ?><br />
          <input type="text" name="captcha_code" value="" size="10" /></p><?php
         }
        else
         {
          ?><p><?php echo $lang['captcha_expl_math']; ?><br /><?php
          echo $_SESSION['captcha_session'][0]; ?> + <?php echo $_SESSION['captcha_session'][1]; ?> = <input type="text" name="captcha_code" value="" size="5" /></p><?php
         }
       }

      ?><p><input type="submit" name="register_submit" value="<?php echo $lang['reg_subm_button']; ?>" /></p>
      </div></form>
      <?php
     }
    else
     {
      $lang['reg_only_via_admin'] = str_replace("[forum-email]", '<a class="textlink" href="contact.php?forum_contact=true">'.$lang['contact_linkname'].'</a>', $lang['reg_only_via_admin']);
      ?><p><?php echo $lang['reg_only_via_admin']; ?><p>&nbsp;</p></p><?php
     }
  break;
  case 'registered':
   if (isset($sent))
     {
      $lang['registered_ok'] = str_replace("[name]", htmlsc($new_user_name), $lang['registered_ok']);
      $lang['registered_ok'] = str_replace("[email]", htmlsc($new_user_email), $lang['registered_ok']);
      ?>
      <p class="normal"><?php echo $lang['registered_ok']; ?></p><p>&nbsp;</p>
      <?php
     }
   else
     {
      ?><p class="normal"><?php echo $lang['reg_ok_but_mail_prob']; ?></p><?php
     }
  break;
  case 'activation_failed';
   ?><p class="normal"><?php echo $lang['activation_failed']; ?></p><?php
  break;
 }

echo $footer;
?>
