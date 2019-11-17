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

// Variablen importieren:
if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id'];
#if (isset($_SESSION[$settings['session_prefix'].'user_type'])) $user_type = $_SESSION[$settings['session_prefix'].'user_type'];
#if (isset($_SESSION[$settings['session_prefix'].'user_name'])) $user_name = $_SESSION[$settings['session_prefix'].'user_name'];
if (isset($_POST['username'])) $username = $_POST['username'];
if (isset($_POST['userpw'])) $userpw = $_POST['userpw'];
if (isset($_GET['username'])) $username = $_GET['username'];
if (isset($_GET['userpw'])) $userpw = $_GET['userpw'];
if (isset($_GET['action'])) $action = $_GET['action'];
if (isset($_POST['action'])) $action = $_POST['action'];
if (isset($_GET['msg'])) $msg = $_GET['msg'];
if (isset($_POST['pwf_username'])) $pwf_username = $_POST['pwf_username'];
if (isset($_POST['pwf_email'])) $pwf_email = $_POST['pwf_email'];

// schauen, ob Session registriert ist - wenn nicht, dann zum Login:
if (isset($_SESSION[$settings['session_prefix'].'user_id']) && empty($action))
 {
  $action = "logout";
 }
elseif (empty($_SESSION[$settings['session_prefix'].'user_id']) && isset($username) && $username != "" && isset($userpw) && $userpw != "")
 {
  $action = "login ok";
 }
elseif (empty($_SESSION[$settings['session_prefix'].'user_id']) && isset($username) && isset($userpw) && ($username == ""  || $userpw == ""))
 {
  header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=login_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=login_failed">further...</a>');
 }
elseif (empty($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
 {
  $action = "auto_login";
 }
elseif (empty($_SESSION[$settings['session_prefix'].'user_id']) && empty($action) && empty($_GET['activate']))
 {
  $action = "login";
 }
elseif (empty($_SESSION[$settings['session_prefix'].'user_id']) && empty($action) && isset($_GET['activate']))
 {
  $action = "activate";
 }

// Aktionen, bevor HTML ausgegeben wird:
switch ($action)
 {
  case "login ok":
   if (isset($username) && trim($username) != "" && isset($userpw) && $userpw != "")
    {
     $result = mysqli_query($connid, "SELECT user_id, user_name, user_pw, user_type, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(last_logout) AS last_logout, user_view, time_difference, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysqli_real_escape_string($connid, $username) ."'");
     if (!$result) die($lang['db_error']);
     if (mysqli_num_rows($result) == 1)
      {
       $feld = mysqli_fetch_assoc($result);
      if (mb_strlen($feld["user_pw"]) == 32) {
        if ($feld["user_pw"] == md5($userpw)) {
          $positive = true;
        } else {
          $positive = false;
        }
        if ($positive === true) {
          $new_hash = password_hash($userpw, PASSWORD_DEFAULT);
          $qNewPassword = "UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, registered=registered, user_pw = '". mysqli_real_escape_string($connid, $new_hash) ."' WHERE user_id = ". intval($feld["user_id"]);
          $rNewPassword = mysqli_query($connid, $qNewPassword);
          if ($rNewPassword === true) {
            $feld["user_pw"] = $new_hash;
          }
        }
      } else {
        $positive = password_verify($userpw, $feld["user_pw"]);
      }
     if ($positive === true)
      {
       if (trim($feld["activate_code"]) != '')
        {
         header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=account_not_activated");
         die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=account_not_activated">further...</a>');
        }

       if (isset($_POST['autologin_checked']) && isset($settings['autologin']) && $settings['autologin'] == 1)
        {
         $cookie_pw = md5($feld["user_pw"]);
         setcookie("auto_login",$feld["user_id"].".".$cookie_pw,time()+(3600*24*30));
        }
       else
        {
         setcookie("auto_login","",0);
        }
       $user_id = $feld["user_id"];
       $user_name = $feld["user_name"];
       $user_type = $feld["user_type"];
       $user_view = $feld["user_view"];
       $user_time_difference = $feld["time_difference"];
       $newtime = $feld["last_logout"];
       $_SESSION[$settings['session_prefix'].'user_id'] = $user_id;
       $_SESSION[$settings['session_prefix'].'user_name'] = $user_name;
       $_SESSION[$settings['session_prefix'].'user_type'] = $user_type;
       $_SESSION[$settings['session_prefix'].'user_view'] = $user_view;
       $_SESSION[$settings['session_prefix'].'newtime'] = $newtime;
       $_SESSION[$settings['session_prefix'].'user_time_difference'] = $user_time_difference;
       $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET logins=logins+1, last_login=NOW(), last_logout=NOW(), registered=registered WHERE user_id=". intval($user_id));
       if ($db_settings['useronline_table'] != "")
        {
         @mysqli_query($connid, "DELETE FROM ". $db_settings['useronline_table'] ." WHERE ip = '". mysqli_real_escape_string($connid, $_SERVER['REMOTE_ADDR']) ."'");
        }
       header("location: index.php"); die('<a href="index.php">further...</a>');
      }
     else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=login_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=login_failed">further...</a>'); }
    }
   else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=login_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=login_failed">further...</a>'); }
   }
   else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=login_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=login_failed">further...</a>'); }
  break;

  case "auto_login":
   if (empty($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
    {
     $auto_login_array = explode(".",$_COOKIE['auto_login']);
     $result = mysqli_query($connid, "SELECT user_id, user_name, user_pw, user_type, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(last_logout) AS last_logout, user_view, time_difference, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($auto_login_array[0]));
     if(!$result) die($lang['db_error']);
     if(mysqli_num_rows($result) == 1)
      {
       $feld = mysqli_fetch_assoc($result);

       if (md5($feld["user_pw"]) == $auto_login_array[1] && trim($feld["activate_code"]==''))
        {
         $user_id = $feld["user_id"];
         $user_name = $feld["user_name"];
         $user_type = $feld["user_type"];
         $user_view = $feld["user_view"];
         $user_time_difference = $feld["time_difference"];
         $newtime = $feld["last_logout"];
         $_SESSION[$settings['session_prefix'].'user_id'] = $user_id;
         $_SESSION[$settings['session_prefix'].'user_name'] = $user_name;
         $_SESSION[$settings['session_prefix'].'user_type'] = $user_type;
         $_SESSION[$settings['session_prefix'].'user_view'] = $user_view;
         $_SESSION[$settings['session_prefix'].'newtime'] = $newtime;
         $_SESSION[$settings['session_prefix'].'user_time_difference'] = $user_time_difference;
         $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET logins=logins+1, last_login=NOW(), last_logout=NOW(), registered=registered WHERE user_id=". intval($user_id));
         setcookie("auto_login",$_COOKIE['auto_login'],time()+(3600*24*30));
         if ($db_settings['useronline_table'] != "")
          {
           @mysqli_query($connid, "DELETE FROM ". $db_settings['useronline_table'] ." WHERE ip = '". mysqli_real_escape_string($connid, $_SERVER['REMOTE_ADDR']) ."'");
          }
        }
       else setcookie("auto_login","",0);
      }
     else setcookie("auto_login","",0);
     }
     else setcookie("auto_login","",0);
   if (isset($_GET['referer']) && isset($_GET['id'])) header("location: ".$_GET['referer']."?id=".$_GET['id']);
   elseif (isset($_GET['referer'])) header("location: ".$_GET['referer']);
   else header("location: ". basename($_SERVER['SCRIPT_NAME']));
   die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'">further...</a>');
  break;

  case "logout":
   $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, last_logout=NOW(), registered=registered WHERE user_id=". intval($user_id));
   session_destroy();
   setcookie("auto_login","",0);
   if ($db_settings['useronline_table'] != "")
    {
     @mysqli_query($connid, "DELETE FROM ". $db_settings['useronline_table'] ." WHERE ip = 'uid_". intval($user_id) ."'");
    }
   header("location: index.php"); die('<a href="index.php">further...</a>');
  break;

  case "pw_forgotten_ok":
   if (isset($pwf_username) && trim($pwf_username) != "" && isset($pwf_email) && trim($pwf_email) != "")
   {
    $pwf_result = mysqli_query($connid, "SELECT user_id, user_name, user_email, user_pw FROM ".$db_settings['userdata_table']." WHERE user_name = '". mysqli_real_escape_string($connid, $pwf_username) ."'");
    if (!$pwf_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($pwf_result);
    mysqli_free_result($pwf_result);
    if($field["user_email"] == $pwf_email)
     {
      $pwf_code = md5(uniqid(rand()));
      $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, registered=registered, pwf_code='". mysqli_real_escape_string($connid, $pwf_code) ."' WHERE user_id=". intval($field["user_id"]) ."' LIMIT 1");

      // send mail with activating link:
      $lang['pwf_activating_email_txt'] = str_replace("[name]", $field["user_name"], $lang['pwf_activating_email_txt']);
      $lang['pwf_activating_email_txt'] = str_replace("[forum_address]", $settings['forum_address'], $lang['pwf_activating_email_txt']);
      $lang['pwf_activating_email_txt'] = str_replace("[activating_link]", $settings['forum_address']. basename($_SERVER['SCRIPT_NAME']) ."?activate=".$field["user_id"]."&code=".$pwf_code, $lang['pwf_activating_email_txt']);
      $pwf_mailto = encodeMailName($field["user_name"], "\n") ." <". $field["user_email"] .">";
      $sent = processEmail($pwf_mailto, $lang['pwf_activating_email_sj'], $lang['pwf_activating_email_txt']);
      if ($sent === true) {
        header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=mail_sent"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=mail_sent">further...</a>');
      } else {
        die($lang['mail_error']);
      }
     }
    else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=pwf_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=pwf_failed">further...</a>'); }
   }
   else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=pwf_failed"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=pwf_failed">further...</a>'); }

  break;

  case "activate":
  if (isset($_GET['activate']) && trim($_GET['activate']) != "" && isset($_GET['code']) && trim($_GET['code']) != "")
   {
    $pwf_result = mysqli_query($connid, "SELECT user_id, user_name, user_email, pwf_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($_GET["activate"]));
    if (!$pwf_result) die($lang['db_error']);
    $field = mysqli_fetch_assoc($pwf_result);
    mysqli_free_result($pwf_result);
    if ($field['user_id'] == $_GET["activate"] && $field['pwf_code'] == $_GET['code'])
     {
      // generate new password:
      $letters="abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789";
      mt_srand ((double)microtime()*1000000);
      $new_user_pw="";
      for($i=0;$i<8;$i++) { $new_user_pw .= mb_substr($letters, mt_rand(0, mb_strlen($letters) - 1), 1); }
      $encoded_new_user_pw = password_hash($new_user_pw, PASSWORD_DEFAULT);
      $update_result = mysqli_query($connid, "UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, registered=registered, user_pw='". mysqli_real_escape_string($connid, $encoded_new_user_pw) ."', pwf_code='' WHERE user_id=". intval($field["user_id"]) ." LIMIT 1");

      // send new password:
      $lang['new_pw_email_txt'] = str_replace("[name]", $field['user_name'], $lang['new_pw_email_txt']);
      $lang['new_pw_email_txt'] = str_replace("[password]", $new_user_pw, $lang['new_pw_email_txt']);
      $lang['new_pw_email_txt'] = str_replace("[login_link]", $settings['forum_address'] . basename($_SERVER['SCRIPT_NAME']) ."?username=". urlencode($field['user_name']) ."&userpw=". $new_user_pw, $lang['new_pw_email_txt']);
      $new_pw_mailto = encodeMailName($field['user_name'], "\n") ." <". $field['user_email'] .">";
      $sent = processEmail($new_pw_mailto, $lang['new_pw_email_sj'], $lang['new_pw_email_txt']);
      if ($sent === true) {
        header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=pw_sent"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=pw_sent">further...</a>');
      } else {
        die($lang['mail_error']);
      }
     }
    else
     {
      header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=code_invalid");
      die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=code_invalid">further...</a>');
     }
   }
   else { header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?msg=code_invalid"); die('<a href="'. basename($_SERVER['SCRIPT_NAME']) .'?msg=code_invalid">further...</a>'); }

  break;
 }

// HTML:
$wo = $lang['login_title'];
$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['login_title'].'</b>';
parse_template();
echo $header;

switch ($action)
 {
  case "login":
   if (isset($msg))
    {
     $templMessage = '<p class"{$classname}">{$message}</p>';
     switch ($msg)
      {
       case "noaccess":
        $messClass = 'caution';
        $messMess = $lang['no_access_marking'];
       break;
       case "noentry":
        $messClass = 'caution';
        $messMess = $lang['no_entry_marking'];
       break;
       case "mail_sent":
        $messClass = 'caution';
        $messMess = $lang['pwf_mail_sent_marking'];
       break;
       case "pw_sent":
        $messClass = 'caution';
        $messMess = $lang['new_pw_ok'];
       break;
       case "code_invalid":
        $messClass = 'caution';
        $messMess = $lang['new_pw_failed'];
       break;
       case "login_failed":
        $messClass = 'caution';
        $messMess = $lang['login_failed_marking'];
       break;
       case "account_not_activated":
        $messClass = 'caution';
        $messMess = $lang['account_not_activated'];
       break;
       case "pwf_failed":
        $messClass = 'caution';
        $messMess = $lang['pwf_failed_marking'];
       break;
       case "user_banned":
        $messClass = 'caution';
        $messMess = $lang['user_banned'];
       break;
       case "user_activated":
        $messClass = 'normal';
        $messMess = $lang['user_activated'];
       break;
      }
     $templMessage = str_replace('{$classname}', $messClass, $templMessage);
     $templMessage = str_replace('{$message}', $messMess, $templMessage);
     echo $templMessage;
    }
    $templateLogin = file_get_contents($settings['themepath'] .'/templates/form-login.html');
    $templateLogin = str_replace('{$shd-login}', htmlsc($lang['login_title']), $templateLogin);
    $templateLogin = str_replace('{$label-username}', htmlsc($lang['username_marking']), $templateLogin);
    $templateLogin = str_replace('{$label-password}', htmlsc($lang['password_marking']), $templateLogin);
    if (isset($settings['autologin']) && $settings['autologin'] == 1) {
      $templateLoginAuto = file_get_contents($settings['themepath'] .'/templates/form-login-auto.html');
      $templateLoginAuto = str_replace('{$checkbox-autologin}', htmlsc($lang['auto_login_marking']), $templateLoginAuto);
      $templateLogin = str_replace('{$autologin-block}', $templateLoginAuto, $templateLogin);
    } else {
      $templateLogin = str_replace('{$autologin-block}', '', $templateLogin);
    }
    $templateLogin = str_replace('{$btn-submit-login}', htmlsc($lang['login_submit_button']), $templateLogin);
    $templateLogin = str_replace('{$login-advice}', htmlsc($lang['login_advice']), $templateLogin);
    $templateLogin = str_replace('{$url-password-forgotten}', basename($_SERVER['SCRIPT_NAME']) .'?action=pw_forgotten', $templateLogin);
    $templateLogin = str_replace('{$link-password-forgotten}', htmlsc($lang['pw_forgotten_linkname']), $templateLogin);
    echo $templateLogin;
  break;
  case "pw_forgotten":
    $templatePWF = file_get_contents($settings['themepath'] .'/templates/form-password-forgotten.html');
    $templatePWF = str_replace('{$shd-password-forgotten}', htmlsc($lang['pw_forgotten_hl']), $templatePWF);
    $templatePWF = str_replace('{$password-forgotten-explanation}', htmlsc($lang['pw_forgotten_exp']), $templatePWF);
    $templatePWF = str_replace('{$label-username}', htmlsc($lang['username_marking']), $templatePWF);
    $templatePWF = str_replace('{$label-e-mail}', htmlsc($lang['user_email_marking']), $templatePWF);
    $templatePWF = str_replace('{$btn-submit-pw-forgotten}', htmlsc($lang['submit_button_ok']), $templatePWF);
    echo $templatePWF;
  break;
 }
echo $footer;
?>
