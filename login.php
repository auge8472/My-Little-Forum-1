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
include_once("functions/include.prepare.php");

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
else if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	&& isset($username)
	&& $username != ""
	&& isset($userpw)
	&& $userpw != "")
	{
	$action = "login ok";
	}
else if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	&& isset($username)
	&& isset($userpw)
	&& ($username == ""  || $userpw == ""))
	{
	header('Location: '.$settings['forum_address'].'login.php?msg=login_failed');
	die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=login_failed">further...</a>');
	}
else if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	&& isset($_COOKIE['auto_login'])
	&& isset($settings['autologin'])
	&& $settings['autologin'] == 1)
	{
	$action = "auto_login";
	}
else if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	&& empty($action)
	&& empty($_GET['activate']))
	{
	$action = "login";
	}
else if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	&& empty($action)
	&& isset($_GET['activate']))
	{
	$action = "activate";
	}

// Aktionen, bevor HTML ausgegeben wird:
switch ($action)
	{
	case "login ok":
		if (isset($username)
			&& trim($username) != ""
			&& isset($userpw)
			&& $userpw != "")
			{
			$result = mysql_query("SELECT user_id, user_name, user_pw, user_type, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(last_logout) AS last_logout, user_view, time_difference, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysql_real_escape_string($username) ."'", $connid);
			if (!$result) die($lang['db_error']);
			if (mysql_num_rows($result) == 1)
				{
				$feld = mysql_fetch_assoc($result);
				if ($feld["user_pw"] == md5($userpw))
					{
					if (trim($feld["activate_code"]) != '')
						{
						header('Location: '.$settings['forum_address'].'login.php?msg=account_not_activated');
						die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=account_not_activated">further...</a>');
						}
					if (isset($_POST['autologin_checked'])
						&& isset($settings['autologin'])
						&& $settings['autologin'] == 1)
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
					$update_result = mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET logins=logins+1, last_login=NOW(), last_logout=NOW(), registered=registered WHERE user_id='".$user_id."'", $connid);
					if ($db_settings['useronline_table'] != "")
						{
						@mysql_query("DELETE FROM ". $db_settings['useronline_table'] ." WHERE ip_addr = INET_ATON('". mysql_real_escape_string($_SERVER['REMOTE_ADDR']) ."')", $connid);
						}
					header('Location: '.$settings['forum_address'].'index.php');
					die('<a href="index.php">further...</a>');
					}
				else
					{
					header('Location: '.$settings['forum_address'].'login.php?msg=login_failed');
					die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=login_failed">further...</a>');
					}
				}
			else
				{
				header('Location: '.$settings['forum_address'].'login.php?msg=login_failed');
				die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=login_failed">further...</a>');
				}
			}
		else
			{
			header('Location: '.$settings['forum_address'].'login.php?msg=login_failed');
			die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=login_failed">further...</a>');
			}
	break;
	case "auto_login":
		if (empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& isset($_COOKIE['auto_login'])
			&& isset($settings['autologin'])
			&& $settings['autologin'] == 1)
			{
			$auto_login_array = explode(".",$_COOKIE['auto_login']);
			$c_uid = $auto_login_array[0];
			$result = mysql_query("SELECT user_id, user_name, user_pw, user_type, UNIX_TIMESTAMP(last_login) AS last_login, UNIX_TIMESTAMP(last_logout) AS last_logout, user_view, time_difference, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = '". intval($c_uid) ."'", $connid);
			if (!$result) die($lang['db_error']);
			if (mysql_num_rows($result) == 1)
				{
				$feld = mysql_fetch_assoc($result);
				if (md5($feld["user_pw"]) == $auto_login_array[1]
					&& trim($feld["activate_code"]==''))
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
					$update_result = mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET logins=logins+1, last_login=NOW(), last_logout=NOW(), registered=registered WHERE user_id='". intval($user_id) ."'", $connid);
					setcookie("auto_login",$_COOKIE['auto_login'],time()+(3600*24*30));
					if ($db_settings['useronline_table'] != "")
						{
						@mysql_query("DELETE FROM ". $db_settings['useronline_table'] ." WHERE ip_addr = INET_ATON('". mysql_real_escape_string($_SERVER['REMOTE_ADDR']) ."')", $connid);
						}
					}
				else setcookie("auto_login","",0);
				}
			else setcookie("auto_login","",0);
			}
		else setcookie("auto_login","",0);
		if (isset($_GET['referer'])
			&& isset($_GET['id']))
			{
			header('Location: '.$_GET['referer'].'?id='.intval($_GET['id']));
			}
		else if (isset($_GET['referer']))
			{
			header("Location: ".$_GET['referer']);
			}
		else
			{
			header('Location: '.$settings['forum_address'].'login.php');
			die('<a href="'.$_SERVER['SCRIPT_NAME'].'">further...</a>');
			}
	break;
	case "logout":
		$update_result = mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, last_logout=NOW(), registered=registered WHERE user_id='". intval($user_id) ."'", $connid);
		session_destroy();
		setcookie("auto_login","",0);
		if ($db_settings['useronline_table'] != "")
			{
			@mysql_query("DELETE FROM ". $db_settings['useronline_table'] ." WHERE user_id = ". intval($user_id) , $connid);
			}
		header('Location: '.$settings['forum_address'].'index.php');
		die('<a href="index.php">further...</a>');
	break;
	case "pw_forgotten_ok":
		if (isset($pwf_username)
			&& trim($pwf_username) != ""
			&& isset($pwf_email)
			&& trim($pwf_email) != "")
			{
			$pwf_result = mysql_query("SELECT user_id, user_name, user_email, user_pw FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysql_real_escape_string($pwf_username) ."'", $connid);
			if (!$pwf_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($pwf_result);
			mysql_free_result($pwf_result);
			if ($field["user_email"] == $pwf_email)
				{
				$pwf_code = md5(uniqid(rand()));
				$update_result = mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, registered=registered, pwf_code='". mysql_real_escape_string($pwf_code) ."' WHERE user_id='". intval($field["user_id"]) ."' LIMIT 1", $connid);

				# send mail with activating link:
				$lang['pwf_activating_email_txt'] = str_replace("[name]", $field["user_name"], strip_tags($lang['pwf_activating_email_txt']));
				$lang['pwf_activating_email_txt'] = str_replace("[forum_address]", $settings['forum_address'], $lang['pwf_activating_email_txt']);
				$lang['pwf_activating_email_txt'] = str_replace("[activating_link]", $settings['forum_address']."login.php?activate=".$field["user_id"]."&code=".$pwf_code, $lang['pwf_activating_email_txt']);
#				$lang['pwf_activating_email_txt'] = stripslashes($lang['pwf_activating_email_txt']);
				$header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
				$header .= "X-Mailer: Php/" . phpversion(). "\n";
				$header .= "X-Sender-ip: ". $_SERVER['REMOTE_ADDR']. "\n";
				$header .= "Content-Type: text/plain";
				$pwf_mailto = $field["user_name"]." <".$field["user_email"].">";
				if ($settings['mail_parameter']!='')
					{
					if (@mail($pwf_mailto, strip_tags($lang['pwf_activating_email_sj']), $lang['pwf_activating_email_txt'], $header,$settings['mail_parameter']))
						{
						header('Location: '.$settings['forum_address'].'login.php?msg=mail_sent');
						die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=mail_sent">further...</a>');
						}
					else die($lang['mail_error']);
					}
				else
					{
					if (@mail($pwf_mailto, strip_tags($lang['pwf_activating_email_sj']), $lang['pwf_activating_email_txt'], $header))
						{
						header('Location: '.$settings['forum_address'].'login.php?msg=mail_sent');
						die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=mail_sent">further...</a>');
						}
					else die($lang['mail_error']);
					}
				}
			else
				{
				header('Location: '.$settings['forum_address'].'login.php?msg=pwf_failed');
				die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=pwf_failed">further...</a>');
				}
			}
		else
			{
			header('Location: '.$settings['forum_address'].'login.php?msg=pwf_failed');
			die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=pwf_failed">further...</a>');
			}
	break;
	case "activate":
		if (isset($_GET['activate'])
			&& trim($_GET['activate']) != ""
			&& isset($_GET['code'])
			&& trim($_GET['code']) != "")
			{
			$pwf_result = mysql_query("SELECT user_id, user_name, user_email, pwf_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = '". intval($_GET["activate"]) ."'", $connid);
			if (!$pwf_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($pwf_result);
			mysql_free_result($pwf_result);
			if ($field['user_id'] == $_GET["activate"]
				&& $field['pwf_code'] == $_GET['code'])
				{
				# generate new password:
				$letters="abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789";
				mt_srand ((double)microtime()*1000000);
				$new_user_pw="";
				for ($i=0; $i<8; $i++)
					{
					$new_user_pw.=substr($letters,mt_rand(0,strlen($letters)-1),1);
					}
				$encoded_new_user_pw = md5($new_user_pw);
				$update_result = mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET last_login=last_login, registered=registered, user_pw='". mysql_real_escape_string($encoded_new_user_pw) ."', pwf_code='' WHERE user_id='". intval($field["user_id"]) ."' LIMIT 1", $connid);
				# send new password:
				$lang['new_pw_email_txt'] = str_replace("[name]", $field['user_name'], strip_tags($lang['new_pw_email_txt']));
				$lang['new_pw_email_txt'] = str_replace("[password]", $new_user_pw, $lang['new_pw_email_txt']);
				$lang['new_pw_email_txt'] = str_replace("[login_link]", $settings['forum_address']."login.php?username=".urlencode($field['user_name'])."&userpw=".$new_user_pw, $lang['new_pw_email_txt']);
#				$lang['new_pw_email_txt'] = stripslashes($lang['new_pw_email_txt']);
				$header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
				$header .= "X-Mailer: Php/" . phpversion(). "\n";
				$header .= "X-Sender-ip: ". $_SERVER['REMOTE_ADDR'] ."\n";
				$header .= "Content-Type: text/plain";
				$new_pw_mailto = $field['user_name']." <".$field['user_email'].">";
				if ($settings['mail_parameter']!='')
					{
					if (@mail($new_pw_mailto, strip_tags($lang['new_pw_email_sj']), $lang['new_pw_email_txt'], $header,$settings['mail_parameter']))
						{
						header('Location: '.$settings['forum_address'].'login.php?msg=pw_sent');
						die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=pw_sent">further...</a>');
						}
					else die($lang['mail_error']);
					}
				else
					{
				if (@mail($new_pw_mailto, strip_tags($lang['new_pw_email_sj']), $lang['new_pw_email_txt'], $header))
					{
					header('Location: '.$settings['forum_address'].'login.php?msg=pw_sent');
					die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=pw_sent">further...</a>');
					}
				else die($lang['mail_error']);
				}
			}
		else
			{
			header('Location: '.$settings['forum_address'].'login.php?msg=code_invalid');
			die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=code_invalid">further...</a>');
			}
		}
	else
		{
		header('Location: '.$settings['forum_address'].'login.php?msg=code_invalid');
		die('<a href="'.$_SERVER['SCRIPT_NAME'].'?msg=code_invalid">further...</a>');
		}
	break;
	}

// HTML:
$wo = strip_tags($lang['login_title']);
$topnav  = '<li><span class="current"><span class="fa fa-sign-in"></span>&nbsp;';
$topnav .= htmlspecialchars($lang['login_title']) .'</span></li>'."\n";
parse_template();
echo $header;

switch ($action)
	{
	case "login":
		if (isset($msg))
			{
			switch ($msg)
				{
				case "noaccess":
					echo '<p class="caution">'.$lang['no_access_marking'].'</p>'."\n";
				break;
				case "noentry":
					echo '<p class="caution">'.$lang['no_entry_marking'].'</p>'."\n";
				break;
				case "mail_sent":
					echo '<p class="caution">'.$lang['pwf_mail_sent_marking'].'</p>'."\n";
				break;
				case "pw_sent":
					echo '<p class="caution">'.$lang['new_pw_ok'].'</p>'."\n";
				break;
				case "code_invalid":
					echo '<p class="caution">'.$lang['new_pw_failed'].'</p>'."\n";
				break;
				case "login_failed":
					echo '<p class="caution">'.$lang['login_failed_marking'].'</p>'."\n";
				break;
				case "account_not_activated":
					echo '<p class="caution">'.$lang['account_not_activated'].'</p>'."\n";
				break;
				case "pwf_failed":
					echo '<p class="caution">'.$lang['pwf_failed_marking'].'</p>'."\n";
				break;
				case "user_banned":
					echo '<p class="caution">'.$lang['user_banned'].'</p>'."\n";
				break;
				case "user_activated":
					echo '<p class="normal">'.$lang['user_activated'].'</p>'."\n";
				break;
				}
			}
		echo '<form action="'.$_SERVER['SCRIPT_NAME'].'" method="post"><div>'."\n";
		echo '<label for="user-name">'.$lang['username_marking'].'</label><br>'."\n";
		echo '<input type="text" name="username" id="user-name"><br><br>'."\n";
		echo '<label for="user-pwd">'.$lang['password_marking'].'</label><br>'."\n";
		echo '<input type="password" name="userpw" id="user-pwd"><br><br>'."\n";
		if (isset($settings['autologin'])
			&& $settings['autologin'] == 1)
			{
			echo '<input type="checkbox" name="autologin_checked" id="autologin" value="true">'."\n";
			echo '<label for="autologin" class="small"> '.$lang['auto_login_marking'];
			echo '</label><br><br>'."\n";
			}
		echo '<input type="submit" value="'.outputLangDebugInAttributes($lang['login_submit_button']).'">'."\n";
		echo '</div></form>'."\n";
		echo '<p>'.$lang['login_advice'].'</p>'."\n";
		echo '<p><span class="small"><a href="'.$_SERVER['SCRIPT_NAME'].'?action=pw_forgotten">'."\n";
		echo $lang['pw_forgotten_linkname'].'</a></span></p>'."\n";
	break;

	case "pw_forgotten":
		echo '<h2>'.$lang['pw_forgotten_hl'].'</h2>'."\n";
		echo '<p class="normal">'.$lang['pw_forgotten_exp'].'</p>'."\n";
		echo '<form action="login.php" method="post">'."\n";
		echo '<div>'."\n";
		echo '<input type="hidden" name="action" value="pw_forgotten_ok">'."\n";
		echo '<label for="user-name">'.$lang['username_marking'].'</label><br>'."\n";
		echo '<input type="text" name="pwf_username" id="user-name"><br><br>'."\n";
		echo '<label for="user-email">'.$lang['user_email_marking'].'</label><br>'."\n";
		echo '<input type="text" name="pwf_email" id="user-email"><br><br>'."\n";
		echo '<input type="submit" value="'.outputLangDebugInAttributes($lang['submit_button_ok']).'"></div>'."\n";
		echo '</form>'."\n";
	break;
	}
echo $footer;
?>
