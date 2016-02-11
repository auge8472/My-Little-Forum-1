<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004 Alex                                                     #
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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");
include_once("functions/include.prepare.php");

if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

# remove not activated user accounts:
@mysql_query("DELETE FROM ". $db_settings['userdata_table'] ." WHERE registered < (NOW() - INTERVAL 48 HOUR) AND activate_code != '' AND logins=0", $connid);

if (isset($_POST['action'])) $action = $_POST['action'];
if (isset($_GET['action'])) $action = $_GET['action'];

unset($errors);

if (isset($_GET['id']) && isset($_GET['key']) && trim($_GET['key'])!='')
	{
	$user_id = intval($_GET['id']);
	$key = trim($_GET['key']);
	if($user_id==0) $errors[] = true;
	if($key=='') $errors[] = true;

	if (empty($errors))
		{
		$result = mysql_query("SELECT user_name, user_email, activate_code FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($user_id) ." LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		if (mysql_num_rows($result) != 1) $errors[] = true;
		$data = mysql_fetch_assoc($result);
		mysql_free_result($result);
		}
	if (empty($errors))
		{
		if (trim($data['activate_code']) == '') $errors[] = true;
		}
	if (empty($errors))
		{
		if ($data['activate_code'] == $key)
			{
			@mysql_query("UPDATE ". $db_settings['userdata_table'] ." SET activate_code = '' WHERE user_id=". intval($user_id), $connid) or die('x');

			# E-Mail-Benachrichtigung an Admins und Moderatoren:
			# E-Mail erstellen:
			$emailbody = strip_tags($lang['new_user_notif_txt']);
			$emailbody = str_replace("[name]", $data['user_name'], $emailbody);
			$emailbody = str_replace("[email]", $data['user_email'], $emailbody);
			$emailbody = str_replace("[user_link]", $settings['forum_address']."user.php?id=".$user_id, $emailbody);
			$subject = strip_tags($lang['new_user_notif_sj']);
			# Schauen, wer eine E-Mail-Benachrichtigung will:
			$admin_result = mysql_query("SELECT user_name, user_email FROM ".$db_settings['userdata_table']." WHERE new_user_notify='1'", $connid);
			if (!$admin_result) die($lang['db_error']);
			while ($admin_array = mysql_fetch_assoc($admin_result))
				{
				$ind_emailbody = str_replace("[admin]", $admin_array['user_name'], $emailbody);
				$admin_an = mb_encode_mimeheader($admin_array['user_name'], 'UTF-8')." <".$admin_array['user_email'].">";
				$sent1[] = processEmail($admin_an, $subject, $ind_emailbody);
				unset($ind_emailbody);
				unset($admin_an);
				}
			unset($subject);
			header("location: ".$settings['forum_address']."login.php?msg=user_activated");
			exit();
			}
		else
			{
			$errors[] = true;
			}
		}
	if (isset($errors))
		{
		header("location: ".$settings['forum_address']."register.php?action=activation_failed");
		die();
		}
	}

if (isset($_POST['register_submit']))
	{
	if($settings['register_by_admin_only']  == 0
	|| isset($_SESSION[$settings['session_prefix'].'user_type'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
		{
		$new_user_name = (!empty($_POST['new_user_name'])) ? trim($_POST['new_user_name']) : "";
		$new_user_email = (!empty($_POST['new_user_email'])) ? trim($_POST['new_user_email']) : "";
		$reg_pw = (!empty($_POST['reg_pw'])) ? $_POST['reg_pw'] : "";
		$reg_pw_conf = (!empty($_POST['reg_pw_conf'])) ? $_POST['reg_pw_conf'] : "";

		# form complete?
		if ($new_user_name=='' || $new_user_email=='' || $reg_pw=='' || $reg_pw_conf=='')
			{
			$errors[] = $lang['error_form_uncompl'];
			}

		if (empty($errors))
			{
			# password and repeatet Password equal?
			if ($reg_pw != $reg_pw_conf)
				{
				$errors[] = $lang['reg_pw_conf_wrong'];
				}
			# name too long?
			if (mb_strlen($new_user_name) > $settings['name_maxlength'])
				{
				$errors[] = $lang['name_marking'] . " " .$lang['error_input_too_long'];
				}
			# e-mail address too long?
			if (mb_strlen($new_user_email) > $settings['email_maxlength'])
				{
				$errors[] = $lang['email_marking'] . " " .$lang['error_input_too_long'];
				}
			# word in username too long?
			$text_arr = explode(" ",$new_user_name);
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
			# look if name already exists:
			$name_result = mysql_query("SELECT user_name FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysql_real_escape_string($new_user_name) ."' LIMIT 1", $connid);
			if (!$name_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($name_result);
			mysql_free_result($name_result);
			if (mb_strtolower($field["user_name"]) == mb_strtolower($new_user_name) && $new_user_name != "")
				{
				$lang['error_name_reserved'] = str_replace("[name]", htmlspecialchars($new_user_name), $lang['error_name_reserved']);
				$errors[] = $lang['error_name_reserved'];
				}
			# look, if e-mail already exists:
			$email_result = mysql_query("SELECT user_email FROM ". $db_settings['userdata_table'] ." WHERE user_email = '". mysql_real_escape_string($new_user_email) ."'", $connid);
			if (!$email_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($email_result);
			mysql_free_result($email_result);
			if (mb_strtolower($field["user_email"]) == mb_strtolower($new_user_email) && $new_user_email != "")
				{
				$errors[] = str_replace("[e-mail]", htmlspecialchars($new_user_email), $lang['error_email_reserved']);
				}
			# e-mail correct?
			if (!preg_match($validator['email'], $new_user_email))
				{
				$errors[] = $lang['error_email_wrong'];
				}

			# CAPTCHA check:
			if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
				{
				if (empty($_SESSION['captcha_session']))
					{
					$errors[] = $lang['captcha_code_invalid'];
					}
				if (empty($errors))
					{
					if ($settings['captcha_type']==1)
						{
						if ($captcha->check_captcha($_SESSION['captcha_session'],$_POST['captcha_code'])!=TRUE)
							{
							$errors[] = $lang['captcha_code_invalid'];
							}
						}
					else
						{
						if ($captcha->check_math_captcha($_SESSION['captcha_session'][2],$_POST['captcha_code'])!=TRUE)
							{
							$errors[] = $lang['captcha_code_invalid'];
							}
						}
					}
				}
			}

		# check for not accepted words in name and e-mail:
		$result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'words' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$data = mysql_fetch_assoc($result);
		mysql_free_result($result);
		if (trim($data['list']) != '')
			{
			$not_accepted_words = explode(',',trim($data['list']));
			foreach ($not_accepted_words as $not_accepted_word)
				{
				if ($not_accepted_word!=''
				&& (preg_match("/".$not_accepted_word."/i",$new_user_name)
				|| preg_match("/".$not_accepted_word."/i",$new_user_email)))
					{
					$errors[] = $lang['error_reg_not_accepted_word'];
					break;
					}
				}
			}

		# save user if no errors:
		if (empty($errors))
			{
			$new_user_type = "user";
			$encoded_new_user_pw = md5($reg_pw);
			$activate_code = md5(uniqid(rand()));
			$newUserQuery = "INSERT INTO ". $db_settings['userdata_table'] ." SET
			user_type = '". mysql_real_escape_string($new_user_type) ."',
			user_name = '". mysql_real_escape_string($new_user_name) ."',
			user_pw = '". mysql_real_escape_string($encoded_new_user_pw) ."',
			user_email = '". mysql_real_escape_string($new_user_email) ."',
			hide_email = '1',
			profile = '',
			last_login = NOW(),
			last_logout = NOW(),
			ip_addr = INET_ATON('". $_SERVER["REMOTE_ADDR"] ."'),
			registered = NOW(),
			user_view = '". mysql_real_escape_string($settings['standard']) ."',
			personal_messages = '1',
			activate_code = '". mysql_real_escape_string($activate_code) ."'";
			@mysql_query($newUserQuery, $connid) or die($lang['db_error']);

			# get new user ID:
			$new_user_id_result = mysql_query("SELECT user_id FROM ". $db_settings['userdata_table'] ." WHERE user_name = '". mysql_real_escape_string($new_user_name) ."' LIMIT 1", $connid);
			if (!$new_user_id_result) die($lang['db_error']);
			$field = mysql_fetch_assoc($new_user_id_result);
			$new_user_id = $field['user_id'];
			mysql_free_result($new_user_id_result);

			# send e-mail with activation key to new user:
			$emailbody = strip_tags($lang['new_user_email_txt']);
			$emailbody = str_replace("[name]", $new_user_name, $emailbody);
			$emailbody = str_replace("[activate_link]", $settings['forum_address']."register.php?id=".$new_user_id."&key=".$activate_code, $emailbody);
			$subject = strip_tags($lang['new_user_email_sj']);
			$an = mb_encode_mimeheader($new_user_name,'UTF-8')." <".$new_user_email.">";
			$sent = processEmail($an, $subject, $emailbody);
			unset($emailbody);
			unset($subject);
			unset($an);
			# Bestätigung anzeigen:
			$action = "registered";
			}
		else
			{
			unset($action);
			}
		}
	}

$wo = strip_tags($lang['register_hl']);
$topnav = '<li><span class="current"><span class="fa fa-user-plus icon-user-plus"></span>&nbsp;'.$lang['register_hl'].'</span></li>'."\n";
parse_template();
echo $header;

if (empty($action))
	{
	$action = 'main';
	}

switch($action)
	{
	case 'main':
		if ($settings['register_by_admin_only']  == 0 ||
		isset($_SESSION[$settings['session_prefix'].'user_type'])
		&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
			{
			if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_register']==1)
				{
				if ($settings['captcha_type']==1) $_SESSION['captcha_session'] = $captcha->generate_code();
				else $_SESSION['captcha_session'] = $captcha->generate_math_captcha();
				}
			echo '<p class="normal">'.$lang['register_exp'].'</p>'."\n";
			# Wenn Fehler, dann Fehlermeldungen ausgeben:
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<form action="register.php" method="post"><div>'."\n";
			if (empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_register']==1)
				{
				echo '<input type="hidden" name="'.session_name().'" value="'.session_id().'">';
				}
			echo "\n".'<p><b>'.$lang['username_marking'].'</b><br>';
			echo '<input type="text" size="25" name="new_user_name" value="';
			echo (isset($new_user_name)) ? htmlspecialchars($new_user_name) : '';
			echo '" maxlength="'.$settings['name_maxlength'].'"></p>'."\n";
			echo '<p><b>'.$lang['user_email_marking'].'</b><br>';
			echo '<input type="text" size="25" name="new_user_email" value="';
			echo (isset($new_user_email)) ? htmlspecialchars($new_user_email) : '';
			echo '" maxlength="'.$settings['email_maxlength'].'"></p>'."\n";
			echo '<p><b>'.$lang['reg_pw'].'</b><br>';
			echo '<input type="password" size="25" name="reg_pw"></p>'."\n";
			echo '<p><b>'.$lang['reg_pw_conf'].'</b><br>';
			echo '<input type="password" size="25" name="reg_pw_conf"></p>'."\n";

			# CAPTCHA:
			if (empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_register']==1)
				{
				echo '<p><b>'.$lang['captcha_marking'].'</b></p>'."\n";
				if ($settings['captcha_type']==1)
					{
					echo '<p><img class="captcha" src="captcha/captcha_image.php?'.SID;
					echo '" alt="'.outputLangDebugInAttributes($lang['captcha_image_alt']).'" width="180" height="40"/></p>'."\n";
					echo '<p>'.$lang['captcha_expl_image'].'<br>';
					echo '<input type="text" name="captcha_code" value="" size="10"></p>'."\n";
					}
				else
					{
					echo '<p>'.$lang['captcha_expl_math'].'<br>';
					echo $_SESSION['captcha_session'][0].' + '.$_SESSION['captcha_session'][1];
					echo ' = <input type="text" name="captcha_code" value="" size="5"></p>'."\n";
					}
				}
			echo '<p><input type="submit" name="register_submit" value="';
			echo outputLangDebugInAttributes($lang['reg_subm_button']).'"></p>'."\n".'</div>'."\n".'</form>'."\n";
			}
		else
			{
			$lang['reg_only_via_admin'] = str_replace("[forum-email]", '<a class="textlink" href="contact.php?forum_contact=true">'.$lang['contact_linkname'].'</a>', $lang['reg_only_via_admin']);
			echo '<p>'.$lang['reg_only_via_admin'].'</p>'."\n";
			}
	break;
	case 'registered':
		if ($sent === true)
			{
			$lang['registered_ok'] = str_replace("[name]", htmlspecialchars(stripslashes($new_user_name)), $lang['registered_ok']);
			$lang['registered_ok'] = str_replace("[email]", htmlspecialchars(stripslashes($new_user_email)), $lang['registered_ok']);
			echo '<p class="normal">'.$lang['registered_ok'].'</p>'."\n";
			}
		else
			{
			echo '<p class="normal">'.$lang['reg_ok_but_mail_prob'].'</p>'."\n";
			}
	break;
	case 'activation_failed';
		echo '<p class="normal">'.$lang['activation_failed'].'</p>'."\n";
	break;
	}

echo $footer;
?>
