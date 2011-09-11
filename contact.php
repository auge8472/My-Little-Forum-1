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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include_once("inc.php");
include_once("functions/include.prepare.php");

if (isset($_GET['id'])) $id = $_GET['id'];
if (isset($_POST['id'])) $id = $_POST['id'];
if (isset($_GET['uid'])) $uid = $_GET['uid'];
if (isset($_POST['uid'])) $uid = $_POST['uid'];
if (isset($_GET['view'])) $view = $_GET['view'];
if (isset($_GET['page'])) $page = $_GET['page'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_GET['category'])) $category = $_GET['category'];
if (isset($_GET['descasc'])) $descasc = $_GET['descasc'];
if (isset($_GET['forum_contact'])) $forum_contact = $_GET['forum_contact'];
if (isset($_POST['forum_contact'])) $forum_contact = $_POST['forum_contact'];
if (isset($_POST['view'])) $view = $_POST['view'];
if (isset($_POST['page'])) $page = $_POST['page'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_POST['category'])) $category = $_POST['category'];
if (isset($_POST['descasc'])) $descasc = $_POST['descasc'];
if (empty($page)) $page = 0;
if (empty($order)) $order = "time";
$category = empty($category) ? 0 : intval($category);
if (empty($descasc)) $descasc = "DESC";

# user is not logged in: captcha required (if setted)
if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}
# user is not logged in and tries to contact a specific user: no access
if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($uid))
	{
	header("location: ".$settings['forum_address']."index.php");
	die("<a href=\"index.php\">further...</a>");
	}
# user is not logged in, wants not contact a specific user:
# reload the page to contact an admin
if (empty($id) && empty($uid) && empty($forum_contact))
	{
	header("location: ".$settings['forum_address']."contact.php?forum_contact=true");
	die("<a href=\"contact.php?forum_contact=true\">further...</a>");
	}

if (isset($id) || isset($uid) || isset($forum_contact))
	{
	if (isset($_COOKIE['user_name']) && empty($_POST["form_submitted"])) $sender_name = $_COOKIE['user_name'];
	if (isset($_COOKIE['user_email']) && empty($_POST["form_submitted"])) $sender_email = $_COOKIE['user_email'];
	if (isset($_SESSION[$settings['session_prefix'].'user_id']) && empty($_POST["form_submitted"]))
		{
		$ue_result = mysql_query("SELECT user_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".intval($_SESSION[$settings['session_prefix'].'user_id'])."' LIMIT 1", $connid);
		if (!$ue_result) die($lang['db_error']);
		$ue_field = mysql_fetch_assoc($ue_result);
		mysql_free_result($ue_result);
		$sender_name = $_SESSION[$settings['session_prefix'].'user_name'];
		$sender_email = $ue_field['user_email'];
		}

	if (isset($id))
		{
		$result = mysql_query("SELECT tid, user_id, name, email, subject FROM ".$db_settings['forum_table']." WHERE id = '".intval($id)."' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$name = $field['name'];
		$email = $field['email'];
		}
	else if (isset($uid))
		{
		$result = mysql_query("SELECT user_id, user_name, user_email, hide_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".intval($uid)."' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$name = $field['user_name'];
		$email = $field['user_email'];
		$hide_email = $field['hide_email'];
		}

	if (isset($field['user_id']) && $field['user_id'] > 0 && empty($uid))
		{
		$user_result = mysql_query("SELECT user_email, hide_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".intval($field['user_id'])."' LIMIT 1", $connid);
		if (!$user_result) die($lang['db_error']);
		$user_field = mysql_fetch_assoc($user_result);
		mysql_free_result($user_result);
		$email = $user_field['user_email'];
		$hide_email = $user_field['hide_email'];
		}

	if (empty($forum_contact) && $field['user_id'] == 0 && $email == "" || empty($forum_contact) && $field['user_id'] > 0 && $hide_email == 1) $no_message = true;

	if (isset($_POST["form_submitted"]))
		{
		# übergebene Variablen ermitteln:
		$sender_name = trim(preg_replace("/\n/", "", preg_replace("/\r/", "", $_POST['sender_name'])));
		$sender_email = trim(preg_replace("/\n/", "", preg_replace("/\r/", "", $_POST['sender_email'])));
		$subject = trim($_POST['subject']);

		# Check the data:
		unset($errors);
		if ($sender_name == "") $errors[] = $lang['error_no_name'];
		if ($sender_email == "") $errors[] = $lang['error_no_email'];
		if ($sender_email != "" and !preg_match($validator['email'], $sender_email)) $errors[] = $lang['error_email_wrong'];
		if (empty($_POST['text'])) $errors[] = $lang['error_no_text'];

		# check for not accepted words:
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
					&& (preg_match("/".$not_accepted_word."/i",$sender_name)
					|| preg_match("/".$not_accepted_word."/i",$sender_email)
					|| preg_match("/".$not_accepted_word."/i",$subject)
					|| preg_match("/".$not_accepted_word."/i",$text)))
					{
					$errors[] = $lang['err_mail_not_accepted_word'];
					break;
					}
				}
			}

		// CAPTCHA check:
		if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
			{
			if (empty($_SESSION['captcha_session'])) $errors[] = $lang['captcha_code_invalid'];
			if (empty($errors))
				{
				if( $settings['captcha_type']==1)
					{
					if ($captcha->check_captcha($_SESSION['captcha_session'],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				else
					{
					if ($captcha->check_math_captcha($_SESSION['captcha_session'][2],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				}
			}
    
		if(empty($errors))
			{
			# process text content of the message
			$emailbody = trim($_POST['text'])."\n\n". str_replace("[forum_address]", $settings['forum_address'], strip_tags($lang['msg_add']));
			# generate and process TO
			if (isset($forum_contact))
				{
				$name = $settings['forum_name'];
				$email = $settings['forum_email'];
				}
			$an = mb_encode_mimeheader($name, "UTF-8")." <".$email.">";
			# process subject
			$mail_subject = ($_POST['subject'] != "") ? trim($_POST['subject']) : $lang['email_no_subject'];
			$emailsubject = mb_encode_mimeheader(strip_tags($mail_subject), "UTF-8");
			# send email
			$sent = processEmail($an, $emailsubject, $emailbody, $sender_email);
			unset($emailsubject);
			unset($emailbody);
			unset($an);
			// Bestätigung:
			if (isset($sent) and $sent === true)
				{
				$emailbody = strip_tags($lang['conf_email_txt']);
				$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
				$emailbody = str_replace("[sender_name]", $sender_name, $emailbody);
				$emailbody = str_replace("[recipient_name]", $name, $emailbody);
				$emailbody = str_replace("[subject]", $mail_subject, $emailbody);
				$emailbody .= "\n\n".$text;
				# generate and process TO
				$an = mb_encode_mimeheader($sender_name, "UTF-8")." <".$sender_email.">";
				# process subject
				$emailsubject = mb_encode_mimeheader(strip_tags($lang['conf_sj']), "UTF-8");
				# send email
				$sent = processEmail($an, $emailsubject, $emailbody);
				unset($emailsubject);
				unset($emailbody);
				unset($an);
				}
			}
		}
	}


$subnav_1 = '';
if (isset($uid))
	{
	$subnav_1 .= '<a class="textlink" href="user.php?id='.intval($uid).'">'.$lang['back_linkname'].'</a>';
	}
else if (isset($forum_contact))
	{
	$subnav_1 .= '<a class="textlink" href="index.php">'.$lang['back_linkname'].'</a>';
	}
else if ($id == 0 || isset($no_message))
	{
	$subnav_1 .= '<a class="textlink" href="javascript:history.back(1)">'.$lang['back_linkname'].'</a>';
	}
else
	{
	if (empty($view))
		{
		$subnav_1 .= '&nbsp;<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.intval($category).'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.str_replace("[name]", htmlspecialchars($field["name"]), $lang['back_to_posting_linkname']).'</a>';
		}
	else
		{
		$backURL = ($view=="board") ? 'board_entry.php' : 'mix_entry.php';
		$subnav_1 .= '&nbsp;<a class="textlink" href="'.$backURL.'?id='.$field['tid'].'&amp;page='.$page.'&amp;category='.intval($category).'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
		}
	}

$wo = $email_headline;
parse_template();
echo $header;
if (isset($id) || isset($uid) || isset($forum_contact))
	{
	if (empty($no_message))
		{
		echo '<h2>';
		echo (isset($forum_contact)) ? $lang['forum_contact_hl'] : str_replace("[name]", htmlspecialchars($name), $lang['message_to']);
		echo '</h2>'."\n";
		}
	if (empty($sent) && empty($no_message))
		{
		if(isset($errors))
			{
			echo errorMessages($errors);
			}
		if(empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_contact']==1)
			{
			if($settings['captcha_type']==1) $_SESSION['captcha_session'] = $captcha->generate_code();
			else $_SESSION['captcha_session'] = $captcha->generate_math_captcha();
			}
		echo '<form method="post" action="'.$_SERVER["SCRIPT_NAME"].'" accept-charset="UTF-8">'."\n";
		if(empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_contact']==1)
			{
			echo '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />'."\n";
			}
		if (isset($id))
			{
			echo '<input type="hidden" name="id" value="'.intval($id).'" />'."\n";
			}
		else if (isset($uid))
			{
			echo '<input type="hidden" name="uid" value="'.intval($uid).'" />'."\n";
			}
		if (isset($view))
			{
			echo '<input type="hidden" name="view" value="'.htmlspecialchars($view).'" />'."\n";
			}
		if (isset($forum_contact))
			{
			echo '<input type="hidden" name="forum_contact" value="'.$forum_contact.'" />'."\n";
			}
		if (isset($page) && isset($order) && isset($category) && isset($descasc))
			{
			echo '<input type="hidden" name="page" value="'.intval($page).'" />'."\n";
			echo '<input type="hidden" name="order" value="'.htmlspecialchars($order).'" />'."\n";
			echo '<input type="hidden" name="category" value="'.intval($category).'" />'."\n";
			echo '<input type="hidden" name="descasc" value="'.htmlspecialchars($descasc).'" />'."\n";
			}
		echo '<table>'."\n";
		echo '<tr>'."\n";
		echo '<td><label for="sender_name">'.$lang['name_marking_msg'].'</label></td>'."\n";
		echo '<td><input type="text" name="sender_name" value="';
		echo isset($sender_name) ? htmlspecialchars($sender_name) : "";
		echo '" size="40" id="sender_name" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td><label for="sender_email">'.$lang['email_marking_msg'].'</label></td>'."\n";
		echo '<td><input type="text" name="sender_email" value="';
		echo isset($sender_email) ? htmlspecialchars($sender_email) : "";
		echo '" size="40" id="sender_email" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td><label for="subject">'.$lang['subject_marking'].'</label></td>'."\n";
		echo '<td><input type="text" name="subject" value="';
		echo isset($subject) ? htmlspecialchars($subject) : "";
		echo '" size="40" id="subject" /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td colspan="2"><textarea name="text" cols="60" rows="15">';
		echo isset($text) ? htmlspecialchars($text) : "";
		echo '</textarea></td>'."\n";
		echo '</tr>';
		if (empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_contact']==1)
			{
			echo '<tr>'."\n";
			echo '<td colspan="2" class="bold">'.$lang['captcha_marking'].'</td>'."\n";
			echo '</tr>';
			if($settings['captcha_type']==1)
				{
				echo '<tr>'."\n";
				echo '<td colspan="2"><img class="captcha" src="captcha/captcha_image.php';
				echo '?'.SID.'" alt="'.outputLangDebugInAttributes($lang['captcha_image_alt']);
				echo '" width="180" height="40"/></td>'."\n";
				echo '</tr><tr>'."\n";
				echo '<td colspan="2">'.$lang['captcha_expl_image'].'</td>'."\n";
				echo '</tr><tr>'."\n";
				echo '<td colspan="2"><input type="text" name="captcha_code" value="" size="10" /></td>'."\n";
				echo '</tr>';
				}
			else
				{
				echo '<tr>'."\n";
				echo '<td colspan="2">'.$lang['captcha_expl_math'].'</td>'."\n";
				echo '</tr><tr>'."\n";
				echo '<td colspan="2">'.$_SESSION['captcha_session'][0];
				echo ' + '.$_SESSION['captcha_session'][1];
				echo ' = <input type="text" name="captcha_code" value="" size="5" /></td>'."\n";
				echo '</tr>';
				}
			}
		echo '</table>'."\n";
		echo '<p><input type="submit" name="form_submitted" value="';
		echo outputLangDebugInAttributes($lang['pers_msg_subm_button']).'" /></p>'."\n";
		echo '</form>'."\n";
		}
	else if (empty($sent) && isset($no_message))
		{
		echo '<p>'.$lang['email_unknown'].'</p>'."\n";
		}
	else
		{
		echo '<p>';
		echo (isset($forum_contact)) ? $lang['forum_contact_sent'] : str_replace("[name]", htmlspecialchars($name), $lang['msg_sent']);
		echo '</p>'."\n";
		}
	}
echo $footer;
?>
