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


if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_COOKIE['auto_login'])
&& isset($settings['autologin'])
&& $settings['autologin'] == 1)
	{
	$header  = 'location: '.$settings['forum_address'].'login.php?referer=user.php';
	$header .= (isset($_GET['id'])) ? '&id='.intval($_GET['id']) : '';
	header($header);
	die('<a href="login.php?referer=user.php">further...</a>');
	}

if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	header('location: '.$settings['forum_address'].'login.php');
	die('<a href="login.php">further...</a>');
	}

// import vars:
if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id'];
if (isset($_SESSION[$settings['session_prefix'].'user_type'])) $user_type = $_SESSION[$settings['session_prefix'].'user_type'];
if (isset($_SESSION[$settings['session_prefix'].'user_name'])) $user_name = $_SESSION[$settings['session_prefix'].'user_name'];
if (isset($_GET['id'])) $id = intval($_GET['id']);
if (isset($_GET['action'])) $action = $_GET['action'];
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
$category = empty($category) ? 0 : intval($category);

unset($errors);

// Check if user locked:
$lockedUserQuery = "SELECT
user_lock
FROM ".$db_settings['userdata_table']."
WHERE user_id = ".intval($_SESSION[$settings['session_prefix'].'user_id'])."
LIMIT 1";
$lock_result = mysql_query($lockedUserQuery, $connid);
if (!$lock_result) die($lang['db_error']);
$lock_result_array = mysql_fetch_assoc($lock_result);
mysql_free_result($lock_result);
if ($lock_result_array['user_lock'] > 0) $action = "locked";

if (isset($_GET['user_lock'])
&& isset($_SESSION[$settings['session_prefix'].'user_type'])
&& ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
|| $_SESSION[$settings['session_prefix'].'user_type'] == "mod"))
	{
	$lock_result = mysql_query("SELECT user_lock, user_type FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($_GET['user_lock'])." LIMIT 1", $connid);
	if (!$lock_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($lock_result);
	mysql_free_result($lock_result);
	if ($field['user_type'] == "user")
		{
		if ($field['user_lock'] == 0) $new_lock = 1;
		else $new_lock = 0;
		$update_result = mysql_query("UPDATE ".$db_settings['userdata_table']." SET user_lock='".$new_lock."', last_login=last_login, registered=registered WHERE user_id='".$_GET['user_lock']."' LIMIT 1", $connid);
		}
	$action="show users";
	}

# show form for own forum settings or redirect to user data of a given user-ID
if ($action == "usersettings" or $action == 'submit usersettings')
	{
	if ($settings['user_control_refresh'] == 0 and $settings['user_control_css'] == 0)
		{
		if ((isset($id) and intval($id) > 0) or (isset($user_id) and intval($user_id) > 0))
			{
			$action = "get userdata";
			}
		else $action = "show users";
		}
	}

if (isset($_POST['change_email_submit']))
	{
	$new_email = trim($_POST['new_email']);
	$pw_new_email = $_POST['pw_new_email'];
	# Check data:
	$email_result = mysql_query("SELECT user_id, user_name, user_pw, user_email FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($user_id)." LIMIT 1", $connid);
	if (!$email_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($email_result);
	mysql_free_result($email_result);
	if ($pw_new_email=='' || $new_email=='')
		{
		$errors[] = $lang['error_form_uncompl'];
		}
	if (empty($errors))
		{
		if (mb_strlen($new_email) > $settings['email_maxlength'])
			{
			$errors[] = $lang['email_marking'] . " " .$lang['error_input_too_long'];
			}
		if ($new_email == $field["user_email"])
			{
			$errors[] = $lang['error_email_equal'];
			}
		if (!preg_match("/^[^@]+@.+\.\D{2,5}$/", $new_email))
			{
			$errors[] = $lang['error_email_wrong'];
			}
		if ($field["user_pw"] != md5(trim($pw_new_email)))
			{
			$errors[] = $lang['pw_wrong'];
			}
		}
	if (empty($errors))
		{
		$activate_code = md5(uniqid(rand()));
		# send mail with activation key:
		$lang['change_email_txt'] = strip_tags($lang['change_email_txt']);
		$lang['new_user_email_txt'] = str_replace("[name]", $field['user_name'], $lang['change_email_txt']);
		$lang['new_user_email_txt'] = str_replace("[activate_link]", $settings['forum_address']."register.php?id=".$field['user_id']."&key=".$activate_code, $lang['new_user_email_txt']);
		$header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
		$header .= "X-Mailer: Php/" . phpversion(). "\n";
		$header .= "X-Sender-ip: ".$_SERVER["REMOTE_ADDR"]."\n";
		$header .= "Content-Type: text/plain";
		$new_user_mailto = $field['user_name']." <".$new_email.">";
		if($settings['mail_parameter']!='')
			{
			@mail($new_user_mailto, strip_tags($lang['new_user_email_sj']), $lang['new_user_email_txt'], $header, $settings['mail_parameter']) or $errors[] = $lang['error_meilserv'];
			}
		else
			{
			@mail($new_user_mailto, strip_tags($lang['new_user_email_sj']), $lang['new_user_email_txt'], $header) or $errors[] = $lang['error_meilserv'];
			}
		if(empty($errors))
			{
			$updateUserEmailQuery = "UPDATE ".$db_settings['userdata_table']." SET
			user_email='".mysql_real_escape_string($new_email)."',
			last_login=last_login,
			registered=registered,
			activate_code = '".mysql_real_escape_string($activate_code)."'
			WHERE user_id= ".intval($user_id);
			@mysql_query($updateUserEmailQuery, $connid) or die($lang['db_error']);
			header("location: ".$settings['forum_address']."login.php");
			die("<a href=\"login.php\">further...</a>");
			}
		else $action="email";
		}
	else $action="email";
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$uid = (!empty($id)) ? $id : $_SESSION[$settings['session_prefix'].'user_id'];
	if (!empty($uid) and intval($uid) > 0)
		{
		$singleUserNameQuery = "SELECT
		user_name
		FROM ".$db_settings['userdata_table']."
		WHERE user_id = ".intval($uid)."
		LIMIT 1";
		$userNameResult = @mysql_query($singleUserNameQuery, $connid) or die($lang['db_error']);
		if (!$userNameResult) die($lang['db_error']);
		$userName = mysql_fetch_assoc($userNameResult);
		mysql_free_result($userNameResult);
		}
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && empty($action))
	{
	if (isset($id)) $action = "get userdata";
	else $action = "show users";
	}
else if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($action))
	{
	# Aktionen vor der Ausgabe von HTML
	switch ($action)
		{
		case "get userdata":
		break;
		case "edit submited":
			# Check the posted data:
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
			if (mb_strlen($user_real_name) > $settings['name_maxlength']) $errors[] = $lang['user_real_name'] . " " .$lang['error_input_too_long'];
			if (mb_strlen($user_hp) > $settings['hp_maxlength']) $errors[] = $lang['user_hp'] . " " .$lang['error_input_too_long'];
			if (mb_strlen($user_place) > $settings['place_maxlength']) $errors[] = $lang['user_place'] . " " .$lang['error_input_too_long'];
			if (mb_strlen($profile) > $settings['profile_maxlength'])
				{
				$lang['err_prof_too_long'] = str_replace("[length]", mb_strlen($profile), $lang['err_prof_too_long']);
				$lang['err_prof_too_long'] = str_replace("[maxlength]", $settings['profile_maxlength'], $lang['err_prof_too_long']);
				$errors[] = $lang['err_prof_too_long'];
				}
			if (mb_strlen($signature) > $settings['signature_maxlength'])
				{
				$lang['err_sig_too_long'] = str_replace("[length]", mb_strlen($signature), $lang['err_sig_too_long']);
				$lang['err_sig_too_long'] = str_replace("[maxlength]", $settings['signature_maxlength'], $lang['err_sig_too_long']);
				$errors[] = $lang['err_sig_too_long'];
				}

			$text_arr = explode(" ",$user_real_name);
			for ($i=0; $i<count($text_arr); $i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['name_word_maxlength'])
					{
					$error_nwtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
					$errors[] = $error_nwtl;
					}
				}
			$text_arr = explode(" ",$user_place);
			for ($i=0; $i<count($text_arr); $i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['place_word_maxlength'])
					{
					$error_pwtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['place_word_maxlength']))."...", $lang['error_place_word_too_long']);
					$errors[] = $error_pwtl;
					}
				}
			$text_arr = str_replace("\n", " ", $profile);
			if ($settings['bbcode'] == 1)
				{
				$text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr);
				}
			if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1)
				{
				$text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr);
				$text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr);
				$text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr);
				}
			$text_arr = explode(" ",$text_arr);
			for ($i=0; $i<count($text_arr); $i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['text_word_maxlength'])
					{
					$error_twtl = str_replace("[word]", htmlspecialchars(substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_prof_word_too_long']);
					$errors[] = $error_twtl;
					}
				}
			$text_arr = str_replace("\n", " ", $signature);
			if ($settings['bbcode'] == 1)
				{
				$text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr);
				}
			if ($settings['bbcode'] == 1 && $settings['bbcode_img'] == 1)
				{
				$text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr);
				$text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr);
				$text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr);
				}
			$text_arr = explode(" ",$text_arr);
			for ($i=0; $i<count($text_arr); $i++)
				{
				trim($text_arr[$i]);
				$laenge = strlen($text_arr[$i]);
				if ($laenge > $settings['text_word_maxlength'])
					{
					$error_twtl = str_replace("[word]", htmlspecialchars(substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_sig_word_too_long']);
					$errors[] = $error_twtl;
					}
				}
			# End of checking

			if (empty($hide_email)) $hide_email = 0;
			if (empty($errors))
				{
				$updateUserData = "UPDATE ".$db_settings['userdata_table']." SET
				user_real_name='".mysql_real_escape_string($user_real_name)."',
				hide_email='".$hide_email."',
				user_hp='".mysql_real_escape_string($user_hp)."',
				user_place='".mysql_real_escape_string($user_place)."',
				profile='".mysql_real_escape_string($profile)."',
				signature='".mysql_real_escape_string($signature)."',
				last_login=last_login,
				registered=registered,
				user_view='".$user_view."',
				new_posting_notify='".$new_posting_notify."',
				new_user_notify='".$new_user_notify."',
				personal_messages='".$personal_messages."',
				time_difference='".$user_time_difference."'
				WHERE user_id='".intval($user_id)."'
				LIMIT 1";
				$update_result = mysql_query($updateUserData, $connid);
				$_SESSION[$settings['session_prefix'].'user_view'] = $user_view;
				$_SESSION[$settings['session_prefix'].'user_time_difference'] = $user_time_difference;
				header("location: ".$settings['forum_address']."user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']);
				die("<a href=\"user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']."\">further...</a>");
				}
			else $action="edit";
		break;
		case "pw submited":
			$pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($user_id)." LIMIT 1", $connid);
			if (!$pw_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($pw_result);
			mysql_free_result($pw_result);

			trim($old_pw);
			trim($new_pw);
			trim($new_pw_conf);

			if ($old_pw=="" or $new_pw=="" or $new_pw_conf =="")
				{
				$errors[] = $lang['error_form_uncompl'];
				}
			else
				{
				if ($field["user_pw"] != md5($old_pw))
					{
					$errors[] = $lang['error_old_pw_wrong'];
					}
				if ($new_pw_conf != $new_pw)
					{
					$errors[] = $lang['error_pw_conf_wrong'];
					}
				}
			# Update, if no errors:
			if (empty($errors))
				{
				$pw_update_result = mysql_query("UPDATE ".$db_settings['userdata_table']." SET user_pw='".md5($new_pw)."', last_login=last_login, registered=registered WHERE user_id='".intval($user_id)."'", $connid);
				header("location: ".$settings['forum_address']."user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']);
				die("<a href=\"user.php?id=".$_SESSION[$settings['session_prefix'].'user_id']."\">further...</a>");
				}
			else $action="pw";
		break;
		case "pm_sent":
			$pms_result = mysql_query("SELECT user_name, user_email FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($user_id)." LIMIT 1", $connid);
			if (!$pms_result) die($lang['db_error']);
			$sender = mysql_fetch_assoc($pms_result);
			mysql_free_result($pms_result);

			$pmr_result = mysql_query("SELECT user_name, user_email, personal_messages FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($_POST['recipient_id'])." LIMIT 1", $connid);
			if (!$pmr_result) die($lang['db_error']);
			$recipient = mysql_fetch_assoc($pmr_result);
			mysql_free_result($pmr_result);

			if ($_POST['pm_text'] == "")
				{
				$errors[] = $lang['error_pers_msg_no_text'];
				}
			if ($recipient['personal_messages'] == "")
				{
				$errors[] = $lang['error_pers_msg_deactivated'];
				}

			if (empty($errors))
				{
				$lang['pers_msg_mail_add'] = str_replace("[forum_address]", $settings['forum_address'], $lang['pers_msg_mail_add']);
				$ip = $_SERVER["REMOTE_ADDR"];
				$mail_subject = $_POST['pm_subject'];
				$mail_text  = $_POST['pm_text'];
				$mail_text .= "\n\n".strip_tags($lang['pers_msg_mail_add']);
				$header  = "From: ".$sender['user_name']." <".$sender['user_email'].">\n";
				$header .= "Reply-To: ".$sender['user_name']." <".$sender['user_email'].">\n";
				$header .= "X-Mailer: PHP/" . phpversion(). "\n";
				$header .= "X-Sender-IP: $ip\n";
				$header .= "Content-Type: text/plain";
				if ($settings['mail_parameter']!='')
					{
					if (!@mail($recipient['user_name']." <".$recipient['user_email'].">", $mail_subject, $mail_text, $header, $settings['mail_parameter']))
						{
						$errors[] = $lang['error_meilserv'];
						}
					}
				else
					{
					if (!@mail($recipient['user_name']." <".$recipient['user_email'].">", $mail_subject, $mail_text, $header))
						{
						$errors[] = $lang['error_meilserv'];
						}
					}

				if(empty($errors))
					{
					$lang['conf_email_txt'] = str_replace("[forum_address]", $settings['forum_address'], strip_tags($lang['conf_email_txt']));
					$lang['conf_email_txt'] = str_replace("[sender_name]", $sender['user_name'], $lang['conf_email_txt']);
					$lang['conf_email_txt'] = str_replace("[recipient_name]", $recipient['user_name'], $lang['conf_email_txt']);
					$lang['conf_email_txt'] = str_replace("[subject]", $_POST['pm_subject'], $lang['conf_email_txt']);
					$lang['conf_email_txt'] .= "\n\n".stripslashes($_POST['pm_text']);
					$conf_mailto = $sender['user_name']." <".$sender['user_email'].">";
					$ip = $_SERVER["REMOTE_ADDR"];
					$conf_header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
					$conf_header .= "X-Mailer: PHP/" . phpversion(). "\n";
					$conf_header .= "X-Sender-IP: $ip\n";
					$conf_header .= "Content-Type: text/plain";
					if ($settings['mail_parameter']!='')
						{
						@mail($conf_mailto, strip_tags($lang['conf_sj']), $lang['conf_email_txt'], $conf_header, $settings['mail_parameter']);
						}
					else
						{
						@mail($conf_mailto, strip_tags($lang['conf_sj']), $lang['conf_email_txt'], $conf_header);
						}
					}

				if (empty($errors))
					{
					header("location: ".$settings['forum_address']."user.php?id=".$_POST['recipient_id']);
					die("<a href=\"user.php?id=".$_POST['recipient_id']."\">further...</a>");
					}
				else
					{
					$id = $_POST['recipient_id'];
					$action="personal_message";
					}
				}
			else
				{
				$id = $_POST['recipient_id'];
				$action="personal_message";
				}
		break;
		case "submit usersettings":
			foreach ($_POST['usersetting'] as $key=>$val)
				{
				$putUserForumSetting = "INSERT INTO ".$db_settings['usersettings_table']." SET
				user_id = ".intval($user_id).",
				name = '".mysql_real_escape_string($key)."',
				value = '".mysql_real_escape_string($val)."'
				ON DUPLICATE KEY UPDATE value = '".mysql_real_escape_string($val)."'";
				@mysql_query($putUserForumSetting, $connid);
				}
			$action = "usersettings";
#		break;
		case "usersettings":
			$singleUserQuery = "SELECT
			user_id,
			user_type,
			user_name
			FROM ".$db_settings['userdata_table']."
			WHERE user_id = ".intval($user_id);
			$result = mysql_query($singleUserQuery, $connid);
			if (!$result) die($lang['db_error']);
			$field = mysql_fetch_assoc($result);
			mysql_free_result($result);
			$userSettingsQuery = "SELECT
			name,
			value,
			type
			FROM ".$db_settings['us_templates_table']."
			ORDER BY name ASC";
			$all_settings = mysql_query($userSettingsQuery, $connid);
			if (!$all_settings) die($lang['db_error']);
			$userOwnSettings = "SELECT
			name,
			value
			FROM ".$db_settings['usersettings_table']."
			WHERE user_id = ".intval($user_id)."
			ORDER BY name ASC";
			$own_settings = mysql_query($userOwnSettings, $connid);
			if (!$own_settings) die($lang['db_error']);
			$ownSet = array();
			while ($row = mysql_fetch_assoc($own_settings))
				{
				$ownSet[] = $row;
				}
			mysql_free_result($own_settings);
		break;
		}
	}
else
	{
	header("location: ".$settings['forum_address']."index.php");
	die("<a href=\"index.php\">further...</a>");
	}

$wo = strip_tags($lang['user_area_title']);

$topnav  = '<a class="textlink" href="';
if (!empty($_SESSION[$settings['session_prefix'].'curr_view']))
	{
	if ($_SESSION[$settings['session_prefix'].'curr_view'] == 'thread')
		{
		$topnav .= 'forum.php';
		}
	else
		{
		$topnav .= $_SESSION[$settings['session_prefix'].'curr_view'].'.php';
		}
	}
else if (!empty($_COOKIE['curr_view']) and in_array($_COOKIE['curr_view'], $possViews))
	{
	$topnav .= $_COOKIE['curr_view'].'.php';
	}
else
	{
	$topnav .= 'forum.php';
	}
$topnav .= '">'.$lang['back_to_overview_linkname'].'</a>&nbsp;';
if (!empty($action))
	{
	if ($action == "show users")
		{
		$topnav .= '<span class="current-page">'.$lang['reg_users_hl'];
		if (!empty($_GET['letter']))
			{
			$topnav .= ' ('.htmlspecialchars($_GET['letter']).')';
			}
		$topnav .= '</span>';
		}
	else
		{
		$topnav .= '<a class="textlink" href="user.php">'.$lang['reg_users_hl'].'</a>&nbsp;';
		if ($action == "get userdata")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['user_info_hl']);
			$topnav .= '<span class="current-page">'.$lang['user_info_hl'].'</span>';
			}
		if ($action == "usersettings")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['user_info_hl']);
			$topnav .= '<a class="textlink" href="user.php';
			$topnav .= '?id='.intval($uid);
			$topnav .= '">'.$lang['user_info_hl'].'</a>&nbsp;';
			$topnav .= '<span class="current-page">'.$lang['user_settings_link'].'</span>';
			}
		if ($action == "edit")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['user_info_hl']);
			$topnav .= '<a class="textlink" href="user.php';
			$topnav .= '?id='.intval($uid);
			$topnav .= '">'.$lang['user_info_hl'].'</a>&nbsp;';
			$topnav .= '<span class="current-page">'.$lang['edit_userdata_ln'].'</span>';
			}
		if ($action == "pw")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['user_info_hl']);
			$topnav .= '<a class="textlink" href="user.php';
			$topnav .= '?id='.intval($uid);
			$topnav .= '">'.$lang['user_info_hl'].'</a>&nbsp;';
			$topnav .= '<span class="current-page">'.$lang['edit_pw_ln'].'</span>';
			}
		if ($action == "email")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['user_info_hl']);
			$topnav .= '<a class="textlink" href="user.php';
			$topnav .= '?id='.intval($uid);
			$topnav .= '">'.$lang['user_info_hl'].'</a>&nbsp;';
			$topnav .= '<span class="current-page">'.$lang['change_email_hl'].'</span>';
			}
		if ($action == "personal_message")
			{
			$lang['pers_msg_ln'] = str_replace("[name]", htmlspecialchars($userName["user_name"]), $lang['pers_msg_ln']);
			$topnav .= '<span class="current-page">'.$lang['pers_msg_ln'].'</span>';
			}
		}
	}

if ($action == "show users")
	{
	if (empty($descasc)) $descasc="ASC";
	if (empty($order)) $order="user_name";

	if (isset($_GET['letter']) && $_GET['letter']!="")
		{
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table']." WHERE user_name LIKE '".$_GET['letter']."%'", $connid);
		}
	else
		{
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
		}
	list($thread_count) = mysql_fetch_row($pid_result);
	mysql_free_result($pid_result);

	$abs_pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
	list($abs_thread_count) = mysql_fetch_row($abs_pid_result);
	mysql_free_result($abs_pid_result);

	$lang['num_reg_users'] = str_replace("[number]", $abs_thread_count, $lang['num_reg_users']);

	$alphabet = range('A', 'Z');
	$subnav_2 = $lang['num_reg_users'] . '&nbsp;&nbsp;<form action="'.basename($_SERVER["PHP_SELF"]).'" method="get" style="display: inline;"><select class="kat" size="1" name="letter" onchange="this.form.submit();">'."\n";
	$subnav_2 .= '<option value="">A-Z</option>'."\n";
	foreach ($alphabet as $letter)
		{
		$subnav_2 .= '<option value="'.$letter.'"';
		$subnav_2 .= (isset($_GET['letter']) && $_GET['letter'] == $letter) ? ' selected="selected"' : '';
		$subnav_2 .= '>'.$letter.'</option>'."\n";
		}
	$subnav_2 .= '</select>&nbsp;<input type="image" name="" value=""';
	$subnav_2 .= ' src="img/submit.png" alt="&raquo;" /></div></form>'."\n";
	$subnav_2 .= nav($page, $settings['users_per_page'], $thread_count, $order, $descasc, $category);
	}

parse_template();
echo $header;

#echo "<h2>SESSION</h2>\n";
#echo "<pre>".print_r($_SESSION, true)."</pre>\n";
#echo "<h2>COOKIE</h2>\n";
#echo "<pre>".print_r($_COOKIE, true)."</pre>\n";
#echo "<h2>GET</h2>\n";
#echo "<pre>".print_r($_GET, true)."</pre>\n";
#echo "<h2>POST</h2>\n";
#echo "<pre>".print_r($_POST, true)."</pre>\n";
#echo "<h2>Infos</h2>\n";
#echo "<pre>".print_r($curr_view, true)."</pre>\n";
#echo "<pre>".print_r($userName, true)."</pre>\n";

switch ($action)
	{
	case "get userdata":
		$id = (empty($id)) ? $user_id : $id;
		
		$singleUserQuery = "SELECT
		user_id,
		user_type,
		user_name,
		user_real_name,
		user_email,
		hide_email,
		user_hp,
		user_place,
		logins,
		signature,
		profile,
		UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS since_date,
		UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS login_date
		FROM ".$db_settings['userdata_table']."
		WHERE user_id = ".intval($id);
		$result = mysql_query($singleUserQuery, $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);

		# count postings:
		$count_postings_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE user_id = ".intval($id), $connid);
		list($postings_count) = mysql_fetch_row($count_postings_result);
		mysql_free_result($count_postings_result);

		if ($field["user_name"] != "")
			{
			$lang['user_info_hl'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['user_info_hl']);
			echo '<h2>'.$lang['user_info_hl'].'</h2>'."\n";
			echo '<table class="normaltab">'."\n";
			echo '<tr>'."\n";
			echo '<td class="c">'.$lang['username_marking'].'</td>'."\n";
			echo '<td class="d">'.htmlspecialchars($field["user_name"]);
			if ($field["user_type"]=="admin") echo "<span class=\"xsmall\">&nbsp;(".$lang['ud_admin'].")</span>";
			else if ($field["user_type"]=="mod") echo "<span class=\"xsmall\">&nbsp;(".$lang['ud_mod'].")</span>";
			echo '</td>'."\n";
			echo '</tr>';
			if ($field["user_real_name"]!="")
				{
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_real_name'].'</td>'."\n";
				echo '<td class="d">'.htmlspecialchars($field['user_real_name']).'</td>'."\n";
				echo '</tr>';
				}
			if ($field["hide_email"]!=1)
				{
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_email_marking'].'</td>'."\n";
				echo '<td class="d"><a href="contact.php?uid='.$field['user_id'].'">';
				echo '<img src="img/email.png" alt="'.outputLangDebugInAttributes($lang['email_alt']).'" title="';
				echo str_replace('[name]', htmlspecialchars($field['user_name']), outputLangDebugInAttributes($lang['email_to_user_linktitle']));
				echo '" width="13" height="10" /></a></td>'."\n";
				echo '</tr>';
				}
			if ($field["user_hp"]!="")
				{
				$field['user_hp'] = amendProtocol($field['user_hp']);
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_hp'].'</td>'."\n";
				echo '<td class="d">';
				echo '<a href="'.$field['user_hp'].'"><img src="img/homepage.png" alt="';
				echo outputLangDebugInAttributes($lang['homepage_alt']).'" title="'.htmlspecialchars($field['user_hp']);
				echo '" width="13" height="13" /></a></td>'."\n";
				echo '</tr>';
				}
			if ($field["user_place"]!=="")
				{
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_place'].'</td>'."\n";
				echo '<td class="d">'.htmlspecialchars($field['user_place']).'</td>'."\n";
				echo '</tr>';
				}
			$days_reg = floor((time() - $field["since_date"])/86400);
			if ($days_reg < 1) $days_reg = 1;
			$lang['user_since_text'] = str_replace('[reg-days]', $days_reg, $lang['user_since_text']);
			$lang['user_last_login_text'] = str_replace('[logins]',$field['logins'],$lang['user_last_login_text']);
			$lang['user_last_login_text'] = str_replace('[log-per-day]',round($field['logins']/$days_reg,2),$lang['user_last_login_text']);
			echo '<tr>'."\n";
			echo '<td class="c">'.$lang['user_since'].'</td>'."\n";
			echo '<td class="d">'.strftime($lang['time_format'],$field['since_date']);
			echo $lang['user_since_text'].'</td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang['user_last_login'].'</td>'."\n";
			echo '<td class="d">'.strftime($lang['time_format'],$field["login_date"]);
			echo $lang['user_last_login_text'].'</td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang['user_postings'].'</td>'."\n";
			echo '<td class="d">'.$postings_count;
			if ($postings_count > 0)
				{
				$lang['user_posting_text'] = str_replace('[post-percent]', round($postings_count*100/$posting_count,1), $lang['user_posting_text']);
				$lang['user_posting_text'] = str_replace('[post-per-day]', round($postings_count/$days_reg,2), $lang['user_posting_text']);
				echo $lang['user_posting_text'].'&nbsp;&nbsp;<span class="small">';
				echo '[ <a href="search.php?show_postings='.$field["user_id"];
				echo '">'.$lang['show_postings_ln'].'</a> ]</span>';
				}
			echo '</td>'."\n";
			echo '</tr>';
			if ($field["profile"]!=="")
				{
				$ftext = $field['profile'];
#				$ftext = htmlspecialchars($ftext);
#				$ftext = nl2br($ftext);
				$ftext = zitat($ftext);
				if ($settings['autolink'] == 1) $ftext = make_link($ftext);
				if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
				if ($settings['smilies'] == 1) $ftext = smilies($ftext);
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_profile'].'</td>'."\n";
				echo '<td class="d">'.$ftext.'</td>'."\n";
				echo '</tr>';
				}
			if ($field["signature"]!=="")
				{
				$ftext = $field['signature'];
#				$ftext = htmlspecialchars($ftext);
#				$ftext = nl2br($ftext);
				if ($settings['autolink'] == 1) $ftext = make_link($ftext);
				if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
				if ($settings['smilies'] == 1) $ftext = smilies($ftext);
				echo '<tr>'."\n";
				echo '<td class="c">'.$lang['user_signature'].'</td>'."\n";
				echo '<td class="d"><p class="signature">'.$ftext.'</p></td>'."\n";
				echo '</tr>';
				}
			echo '</table>'."\n";
			if ($user_id == $id)
				{
				echo '<ul class="linklist">'."\n";
				echo '<li><a class="textlink" href="user.php?action=subscriptions">';
				echo $lang['edit_subscription_ln'].'</a></li>'."\n";
				if ($settings['user_control_refresh'] == 1 or $settings['user_control_css'] == 1)
					{
					echo '<li><a class="textlink" href="user.php?action=usersettings">';
					echo $lang['edit_users_settings'].'</a></li>'."\n";
					}
				echo '<li><a class="textlink" href="user.php?action=edit">';
				echo $lang['edit_userdata_ln'].'</a></li>'."\n";
				echo '<li><a class="textlink" href="user.php?action=pw">';
				echo $lang['edit_pw_ln'].'</a></li>'."\n";
				echo '</ul>'."\n";
				}
			else
				{
				$lang['pers_msg_ln'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['pers_msg_ln']);
				echo '<ul class="linklist">'."\n";
				echo '<li><a class="textlink" href="user.php?action=personal_message&amp;id=';
				echo $id.'">'.$lang['pers_msg_ln'].'</a></li>'."\n";
				echo '</ul>'."\n";
				}
			}
		else
			{
			echo '<p class="caution">'.$lang['user_doesnt_exist'].'</p>'."\n";
			}
	break;
	case "usersettings":
		if ($field["user_name"] != "")
			{
			$lang['user_settings_hl'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['user_settings_hl']);
			echo '<h2>'.$lang['user_settings_hl'].'</h2>'."\n";
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<form action="user.php" method="post">'."\n";
			echo '<table class="normaltab">'."\n";
			while ($allSet = mysql_fetch_assoc($all_settings))
				{
				if (($settings['user_control_refresh'] == 1
					and $allSet['name']=='control_refresh')
					or ($settings['user_control_css'] == 1
					and mb_substr($allSet['name'], 0, 5) == 'mark_'))
					{
					if (!empty($ownSet))
						{
						foreach ($ownSet as $mySetting)
							{
							if ($mySetting['name'] == $allSet['name'])
								{
								$set = $mySetting['value'];
								break;
								}
							}
						}
					echo '<tr>'."\n";
					echo '<td class="c">';
					echo ($allSet['type'] == 'string') ? '<label for="'.$allSet['name'].'">' : '';
					echo $allSet['name'];
					echo ($allSet['type'] == 'string') ? '</label>' : '';
					echo '</td>'."\n";
					echo '<td class="d">';
					if ($allSet['type']=="string")
						{
						echo '<input type="text" name="usersetting['.$allSet['name'].']" value="';
						echo (!empty($set)) ? htmlspecialchars($set) : htmlspecialchars($allSet['value']);
						echo '" id="'.$allSet['name'].'" />'."\n";
						}
					else
						{
						echo '<input type="radio" name="usersetting['.$allSet['name'].']" value="false"';
						echo (empty($set) or $set == 'false') ? ' checked="checked"' : '';
						echo ' id="'.$allSet['name'].'-no" /><label for="'.$allSet['name'].'-no">';
						echo $lang['no'].'</label>'."\n";
						echo '<input type="radio" name="usersetting['.$allSet['name'].']" value="true"';
						echo (!empty($set) and $set == 'true') ? ' checked="checked"' : '';
						echo ' id="'.$allSet['name'].'-yes" /><label for="'.$allSet['name'].'-yes">';
						echo $lang['yes'].'</label>'."\n";
						}
					echo '</td>'."\n";
					echo '</tr>';
					}
				}
			echo "\n".'</table>'."\n";
			echo '<p><input type="hidden" name="action" value="submit usersettings" />';
			echo '<input type="submit" name="us-submit" value="';
			echo outputLangDebugInAttributes($lang['submit_usersettings_button']).'" /></p>';
			echo '</form>'."\n";
			mysql_free_result($all_settings);
			}
	break;
	case "show users":
		if (empty($page)) $page = 0;
		if (empty($order)) $order="user_name";
		if (empty($descasc)) $descasc="ASC";
		$ul = $page * $settings['users_per_page'];
		$allUserQuery  = "SELECT
		user_id,
		user_name,
		user_type,
		user_email,
		hide_email,
		user_hp,
		user_lock
		FROM ".$db_settings['userdata_table'];
		if (isset($_GET['letter']))
			{
			$allUserQuery .= "
			WHERE user_name LIKE '".$_GET['letter']."%'";
			}
		$allUserQuery .= "
		ORDER BY ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['users_per_page'];
		$result = mysql_query($allUserQuery, $connid);
		if (!$result) die($lang['db_error']);

		# Schauen, wer online ist:
		if ($settings['count_users_online'] == 1)
			{
			$useronline_result = mysql_query("SELECT user_id FROM ".$db_settings['useronline_table'], $connid);
			if (!$useronline_result) die($lang['db_error']);
			while ($uid_field = mysql_fetch_assoc($useronline_result))
				{
				$useronline_array[] = $uid_field['user_id'];
				}
			mysql_free_result($useronline_result);
			}
		if ($thread_count > 0)
			{
			$currDescAsc = strtolower($descasc);
			echo '<table class="normaltab">'."\n";
			echo '<tr class="titlerow">'."\n";
			echo '<th><a href="user.php?action=show+users&amp;order=user_name&amp;descasc=';
			echo ($descasc=="ASC" && $order=="user_name") ? 'DESC' : 'ASC';
			echo '&amp;ul='.$ul.'" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['userlist_name'].'</a>';
			if ($order=="user_name")
				{
				echo outputImageDescAsc($currDescAsc);
				}
			echo '</th>'."\n";
			echo '<th><a href="user.php?action=show+users&amp;order=user_type&amp;descasc=';
			echo ($descasc=="ASC" && $order=="user_type") ? 'DESC' : 'ASC';
			echo '&amp;ul='.$ul.'" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['userlist_type'].'</a>';
			if ($order=="user_type")
				{
				echo outputImageDescAsc($currDescAsc);
				}
			echo '</th>'."\n";
			echo '<th>'.$lang['userlist_email'].'</th>'."\n";
			echo '<th>'.$lang['userlist_hp'].'</th>'."\n";
			if ($settings['count_users_online'] == 1)
				{
				echo '<th>'.$lang['userlist_online'].'</th>'."\n";
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
			|| $_SESSION[$settings['session_prefix'].'user_type'] == "mod"))
				{
				echo '<th><a href="user.php?action=show+users&amp;order=user_lock&amp;descasc=';
				echo ($descasc=="ASC" && $order=="user_lock") ? 'DESC' : 'ASC';
				echo '&amp;ul='.$ul.'" title="'.outputLangDebugInAttributes($lang['order_linktitle']).'">'.$lang['lock'].'</a>';
				if ($order=="user_lock")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				}
			echo '</tr>';
			$i=0;
			while ($field = mysql_fetch_assoc($result))
				{
				$rowClass = ($i % 2 == 0) ? "a" : "b";
				echo '<tr class="'.$rowClass.'">'."\n";
				echo '<td><a href="user.php?id='.$field['user_id'].'" title="';
				echo str_replace("[name]", htmlspecialchars($field["user_name"]), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
				echo '"><b>'.htmlspecialchars($field['user_name']).'</b></a></td>'."\n";
				echo '<td class="info">';
				if ($field["user_type"] == "admin") echo $lang['ud_admin'];
				elseif ($field["user_type"] == "mod") echo $lang['ud_mod'];
				else echo $lang['ud_user'];
				echo '</td>'."\n";
				echo '<td class="info">';
				if ($field["hide_email"]!=1)
					{
					echo '<a href="contact.php?uid='.$field['user_id'].'"><img src="img/email.png"';
					echo ' alt="'.outputLangDebugInAttributes($lang['email_alt']).'" title="';
					echo str_replace("[name]", htmlspecialchars($field["user_name"]), outputLangDebugInAttributes($lang['email_to_user_linktitle']));
					echo '" width="13" height="10" /></a>';
					}
				else echo "&nbsp;";
				echo '</td>'."\n";
				echo '<td class="info">';
				if ($field["user_hp"] != '')
					{
					$field["user_hp"] = amendProtocol($field["user_hp"]);
					echo '<a href="'.$field["user_hp"].'"><img src="img/homepage.png" alt="';
					echo outputLangDebugInAttributes($lang['homepage_alt']).'" title="';
					echo htmlspecialchars($field["user_hp"]).'" width="13" height="13" /></a>'."\n";
					}
				else echo "&nbsp;";
				echo '</td>'."\n";
				if ($settings['count_users_online'] == 1)
					{
					echo '<td class="info">';
					if ($settings['count_users_online'] == 1
					&& in_array($field['user_id'], $useronline_array))
						{
						echo '<span class="online">'.$lang['online'].'</span>';
						}
					else echo "&nbsp;";
					echo '</td>'."\n";
					}
				if (isset($_SESSION[$settings['session_prefix'].'user_type'])
				&& ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
				|| $_SESSION[$settings['session_prefix'].'user_type'] == "mod"))
					{
					echo '<td class="info">';
					if ($field["user_type"]=="user")
						{
						if ($field["user_lock"] == 0)
							{
							echo '<a href="user.php?user_lock='.$field["user_id"];
							echo '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;page='.$page;
							echo '" title="'.str_replace("[name]", htmlspecialchars($field["user_name"]), outputLangDebugInAttributes($lang['lock_user_lt']));
							echo '">'.$lang['unlocked'].'</a>';
							}
						else
							{
							echo '<a style="color: red;" href="user.php?user_lock=';
							echo $field["user_id"].'&amp;order='.$order.'&amp;descasc='.$descasc;
							echo '&amp;page='.$page.'" title="'.str_replace("[name]", htmlspecialchars($field["user_name"]), outputLangDebugInAttributes($lang['unlock_user_lt']));
							echo '">'.$lang['locked'].'</a>';
							}
						}
					else echo "&nbsp;";
					echo '</td>'."\n";
					}
				echo '</tr>';
				$i++;
				}
			echo "\n".'</table>'."\n";
			}
		else
			{
			echo '<p><i>'.$lang['no_users'].'</i></p>'."\n";
			}
	break;
	case "edit":
		$singleUserDataQuery = "SELECT
		user_name,
		user_real_name,
		user_email,
		hide_email,
		user_hp,
		user_place,
		signature,
		profile,
		user_view,
		new_posting_notify,
		new_user_notify,
		personal_messages,
		time_difference
		FROM ".$db_settings['userdata_table']."
		WHERE user_id = ".intval($user_id);
		$result = mysql_query($singleUserDataQuery, $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);

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
		$lang['edit_userdata_hl'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['edit_userdata_hl']);
		echo '<h2>'.$lang['edit_userdata_hl'].'</h2>'."\n";
		if (isset($errors))
			{
			echo errorMessages($errors);
			}
		echo '<form action="user.php" method="post">'."\n";
		echo '<input type="hidden" name="action" value="edit submited">'."\n";
		echo '<table class="normaltab">'."\n".'<tr>'."\n";
		echo '<td class="c">'.$lang['username_marking'].'</td>'."\n";
		echo '<td class="d">'.htmlspecialchars($field["user_name"]).'</td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c">'.$lang['user_email_marking'].'</td>'."\n";
		echo '<td class="d">'.htmlspecialchars($field["user_email"]);
		echo '&nbsp;&nbsp;<span class="small">[ <a class="sln" href="user.php?';
		echo 'action=email">'.$lang['edit_email_ln'].'</a> ]</span></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><b>'.$lang['user_show_email'].'</b><br />';
		echo '<span class="info">'.$lang['user_show_email_exp'].'</span></td>'."\n";
		echo '<td class="d"><input type="radio" name="hide_email" id="hidemail-0" value="0"';
		echo ($hide_email=="0") ? ' checked="checked"' : '';
		echo '><label for="hidemail-0">'.$lang['yes'].'</label><br />';
		echo '<input type="radio" name="hide_email" id="hidemail-1" value="1"';
		echo ($hide_email=="1") ? ' checked="checked"' : '';
		echo '><label for="hidemail-1">'.$lang['no'].'</label></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="real-name">'.$lang['user_real_name'].'</label><br />';
		echo '<span class="info">'.$lang['optional_marking'].'</span></td>'."\n";
		echo '<td class="d"><input type="text" size="40" name="user_real_name" value="';
		echo htmlspecialchars($user_real_name).'" maxlength="';
		echo $settings['name_maxlength'].'" id="real-name" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="homepage">'.$lang['user_hp'].'</label><br />';
		echo '<span class="info">'.$lang['optional_marking'].'</span></td>'."\n";
		echo '<td class="d"><input type="text" size="40" name="user_hp" value="';
		echo htmlspecialchars($user_hp).'" maxlength="';
		echo $settings['hp_maxlength'].'" id="homepage" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="userplace">'.$lang['user_place'].'</label><br />';
		echo '<span class="info">'.$lang['optional_marking'].'</span></td>'."\n";
		echo '<td class="d"><input type="text" size="40" name="user_place" value="';
		echo htmlspecialchars($user_place).'" maxlength="';
		echo $settings['place_maxlength'].'" id="userplace" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="userprofile">'.$lang['user_profile'].'</label><br />';
		echo '<span class="info">'.$lang['user_profile_exp'].'<br />';
		echo $lang['optional_marking'].'</span></td>'."\n";
		echo '<td class="d"><textarea cols="65" rows="10" name="profile" id="userprofile">';
		echo htmlspecialchars($profile).'</textarea></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="usersignature">'.$lang['user_signature'].'</label><br />';
		echo '<span class="info">'.$lang['user_sig_exp'].'<br />';
		echo $lang['optional_marking'].'</span></td>'."\n";
		echo '<td class="d"><textarea cols="65" rows="4" name="signature" id="usersignature">';
		echo htmlspecialchars($signature).'</textarea></td>'."\n";
		echo '</tr>';
		if ($settings['thread_view'] != 0
		&& $settings['board_view'] != 0
		|| $settings['board_view'] != 0
		&& $settings['mix_view'] != 0
		|| $settings['thread_view'] != 0
		&& $settings['mix_view'] != 0)
			{
			echo '<tr>'."\n";
			echo '<td class="c">'.$lang['user_standard_view'].'</td>'."\n";
			echo '<td class="d">'."\n";
			if ($settings['thread_view'] == 1)
				{
				echo '<input type="radio" name="user_view" value="thread" id="view-thread"';
				echo ($user_view=="thread") ? ' checked="checked"' : '';
				echo ' /><label for="view-thread">'.$lang['thread_view_linkname'].'</label><br />'."\n";
				}
			if ($settings['board_view'] == 1)
				{
				echo '<input type="radio" name="user_view" value="board" id="view-board"';
				echo ($user_view=="board") ? ' checked="checked"' : '';
				echo ' /><label for="view-board">'.$lang['board_view_linkname'].'</label><br />'."\n";
				}
			if ($settings['mix_view'] == 1)
				{
				echo '<input type="radio" name="user_view" value="mix" id="view-mix"';
				echo ($user_view=="mix") ? ' checked="checked"' : '';
				echo ' /><label for="view-mix">'.$lang['mix_view_linkname']."</label>\n";
				}
			echo '</td>'."\n";
			echo '</tr>'."\n";
			}
		echo '<tr>'."\n";
		echo '<td class="c">'.$lang['user_pers_msg'].'<br />';
		echo '<span class="info">'.$lang['user_pers_msg_exp'].'</span></td>'."\n";
		echo '<td class="d"><input type="radio" name="personal_messages" value="1" id="persmess-1"';
		echo ($personal_messages=="1") ? ' checked="checked"' : '';
		echo ' /><label for="persmess-1">'.$lang['user_pers_msg_act'].'</label><br />'."\n";
		echo '<input type="radio" name="personal_messages" value="0" id="persmess-0"';
		echo ($personal_messages=="0") ? ' checked="checked"' : '';
		echo '><label for="persmess-0">'.$lang['user_pers_msg_deact'].'</label></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="timediff">'.$lang['user_time_diff'].'</label><br />';
		echo '<span class="info">'.$lang['user_time_diff_exp'].'</span></td>'."\n";
		echo '<td class="d"><select name="user_time_difference" size="1" id="timediff">'."\n";
		for ($h = -24; $h <= 24; $h++)
			{
			echo '<option value="'.$h.'"';
			echo ($user_time_difference==$h) ? ' selected="selected"' : '';
			echo '>'.$h.'</option>'."\n";
			}
		echo '</select>';
#		echo '&nbsp;&nbsp;Test: <select size="1">'.outputTimeZonesOptions().'</select>';
		echo '</td>'."\n";
		if ($user_type=="admin" || $user_type=="mod")
			{
			echo '<tr>'."\n";
			echo '<td class="c">'.$lang['admin_mod_notif'].'<br />';
			echo '<span class="info">'.$lang['admin_mod_notif_exp'].'</span></td>'."\n";
			echo '<td class="d"><input type="checkbox" name="new_posting_notify" value="1"';
			echo ($new_posting_notify=="1") ? ' checked="checked"' : '';
			echo ' id="notice-post" /><label for="notice-post">'.$lang['admin_mod_notif_np'].'</label><br />';
			echo '<input type="checkbox" name="new_user_notify" value="1"';
			echo ($new_user_notify=="1") ? ' checked="checked"' : '';
			echo ' id="notice-user" /><label for="notice-user">'.$lang['admin_mod_notif_nu'].'</label></td>'."\n";
			}
		echo '</tr>'."\n".'</table>'."\n";
		echo '<p><input type="submit" name="userdata_submit" value="';
		echo outputLangDebugInAttributes($lang['userdata_subm_button']).'" />&nbsp;';
		echo '<input type="reset" value="'.outputLangDebugInAttributes($lang['reset_button']).'" /></p></form>'."\n";
		if ($settings['bbcode'] == 1)
			{
			echo '<p class="xsmall">'.$lang['bbcode_marking_user'];
			if ($settings['bbcode_img']==1)
				{
				echo '<br />'.$lang['bbcode_img_marking_user'];
				}
			echo '</p>'."\n";
			}
	break;
	case "pw":
		echo '<h2>'.$lang['change_pw_hl'].'</h2>'."\n";
		if (isset($errors))
			{
			echo errorMessages($errors);
			}
		echo '<form action="user.php" method="post"><div>'."\n";
		echo '<input type="hidden" name="action" value="pw submited">'."\n";
		echo '<p><label for="old-pw">'.$lang['old_pw'].'</label><br />'."\n";
		echo '<input type="password" size="25" name="old_pw" id="old-pw" maxlength="50"></p>'."\n";
		echo '<p><label for ="new-pw">'.$lang['new_pw'].'</label><br />'."\n";
		echo '<input type="password" size="25" name="new_pw" id="new-pw" maxlength="50"></p>'."\n";
		echo '<p><label for="pw-conf">'.$lang['new_pw_conf'].'</label><br />'."\n";
		echo '<input type="password" size="25" name="new_pw_conf" id="pw-conf" maxlength="50"></p>'."\n";
		echo '<p><input type="submit" name="pw_submit" value="'.outputLangDebugInAttributes($lang['new_pw_subm_button']);
		echo '" title="'.outputLangDebugInAttributes($lang['new_pw_subm_button_title']).'"></p>'."\n";
		echo '</div></form>'."\n";
	break;
	case "email":
		echo '<h2>'.$lang['change_email_hl'].'</h2>'."\n";
		echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
		echo '<p>'.$lang['change_email_exp'].'</p>'."\n";
		if (isset($errors))
			{
			echo errorMessages($errors);
			}
		echo '<form action="user.php" method="post">'."\n";
		echo '<p><label for="new-email">'.$lang['new_email'].'</label><br />'."\n";
		echo '<input type="text" size="25" name="new_email" id="new-email" value="';
		echo (isset($new_email)) ? htmlspecialchars($new_email) : '';
		echo '" maxlength="'.$settings['email_maxlength'].'"></p>'."\n";
		echo '<p><label for="pw-email">'.$lang['password_marking'].'</label><br />'."\n";
		echo '<input type="password" size="25" name="pw_new_email" id="pw-email" maxlength="50"></p>'."\n";
		echo '<p><input type="submit" name="change_email_submit" value="';
		echo outputLangDebugInAttributes($lang['submit_button_ok']).'"></p>'."\n";
		echo '</form>'."\n";
	break;
	case "personal_message":
		$pma_result = mysql_query("SELECT user_name, personal_messages FROM ".$db_settings['userdata_table']." WHERE user_id = ".intval($id)." LIMIT 1", $connid);
		if (!$pma_result) die($lang['db_error']);
		$field = mysql_fetch_assoc($pma_result);
		mysql_free_result($pma_result);

		$lang['pers_msg_hl'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['pers_msg_hl']);
		echo '<h2>'.$lang['pers_msg_hl'].'</h2>'."\n";
		if (isset($errors))
			{
			echo errorMessages($errors);
			}
		if ($field["personal_messages"] == 1)
			{
			echo '<form action="'.$_SERVER["SCRIPT_NAME"].'" method="post"><div>'."\n";
			echo '<input type="hidden" name="action" value="pm_sent" />'."\n";
			echo '<input type="hidden" name="recipient_id" value="'.intval($id).'" />'."\n";
			echo '<p><label for="mess-subject">'.$lang['pers_msg_sj'].'</label><br />'."\n";
			echo '<input class="fs" type="text" name="pm_subject" value="';
			echo (isset($_POST['pm_subject'])) ? htmlspecialchars($_POST['pm_subject']) : '';
			echo '" size="50" id="mess-subject" /></p>'."\n";
			echo '<p><label for="mess-text">'.$lang['pers_msg_txt'].'</label><br />'."\n";
			echo '<textarea name="pm_text" id="mess-text" cols="60" rows="15">';
			echo (isset($_POST['pm_text'])) ? htmlspecialchars($_POST['pm_text']) : '';
			echo '</textarea></p>'."\n";
			echo '<p><input type="submit" name="pm_ok" value="';
			echo outputLangDebugInAttributes($lang['pers_msg_subm_button']).'" /></p>';
			echo '</div></form>'."\n";
			}
		else
			{
			$lang['pers_msg_deactivated'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['pers_msg_deactivated']);
			echo $lang['pers_msg_deactivated'];
			}
	break;
	case "locked":
		echo '<h2 class="caution">'.$lang['user_locked_hl'].'</h2>'."\n";
		echo '<p>'.str_replace("[name]", htmlspecialchars($user_name), $lang['usr_locked_txt']).'</p>'."\n";
	break;
	}

echo $footer;
?>
