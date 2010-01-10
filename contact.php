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


if (isset($_GET['id'])) $id = $_GET['id'];
if (isset($_POST['id'])) $id = $_POST['id'];
if (isset($_GET['uid'])) $uid = $_GET['uid'];
if (isset($_POST['uid'])) $uid = $_POST['uid'];
if (isset($_GET['view'])) $view = $_GET['view'];
if (isset($_GET['page'])) $page = $_GET['page'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_GET['category'])) $category = urldecode($_GET['category']);
if (isset($_GET['descasc'])) $descasc = $_GET['descasc'];
if (isset($_GET['forum_contact'])) $forum_contact = htmlspecialchars($_GET['forum_contact']);
if (isset($_POST['forum_contact'])) $forum_contact = $_POST['forum_contact'];
if (isset($_POST['view'])) $view = $_POST['view'];
if (isset($_POST['page'])) $page = $_POST['page'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_POST['category'])) $category = $_POST['category'];
if (isset($_POST['descasc'])) $descasc = $_POST['descasc'];
if (empty($page)) $page=0;
if (empty($order)) $order="time";
if (empty($category)) $category="all";
if (empty($descasc)) $descasc="DESC";

if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($uid))
	{
	header("location: ".$settings['forum_address']."index.php");
	die("<a href=\"index.php\">further...</a>");
	}
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
		$ue_result = mysql_query("SELECT user_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
		if (!$ue_result) die($lang['db_error']);
		$ue_field = mysql_fetch_assoc($ue_result);
		mysql_free_result($ue_result);
		$sender_name = $_SESSION[$settings['session_prefix'].'user_name'];
		$sender_email = $ue_field['user_email'];
		}

	if (isset($id))
		{
		$result = mysql_query("SELECT tid, user_id, name, email, subject FROM ".$db_settings['forum_table']." WHERE id = '".$id."' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$name = $field['name'];
		$email = $field['email'];
		}
	else if (isset($uid))
		{
		$result = mysql_query("SELECT user_id, user_name, user_email, hide_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".$uid."' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$field = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$name = $field['user_name'];
		$email = $field['user_email'];
		$hide_email = $field['hide_email'];
		}

	if (isset($field['user_id']) && $field['user_id'] > 0 && empty($uid))
		{
		$user_result = mysql_query("SELECT user_email, hide_email FROM ".$db_settings['userdata_table']." WHERE user_id = '".$field['user_id']."' LIMIT 1", $connid);
		if (!$user_result) die($lang['db_error']);
		$user_field = mysql_fetch_assoc($user_result);
		mysql_free_result($user_result);
		$email = $user_field['user_email'];
		$hide_email = $user_field['hide_email'];
		}

	if (empty($forum_contact) && $field['user_id'] == 0 && $email == "" || empty($forum_contact) && $field['user_id'] > 0 && $hide_email == 1) $no_message = true;

	if (isset($_POST["form_submitted"]))
		{
		// übergebene Variablen ermitteln:
		$sender_name = trim(preg_replace("/\n/", "", preg_replace("/\r/", "", $_POST['sender_name'])));
		$sender_email = trim(preg_replace("/\n/", "", preg_replace("/\r/", "", $_POST['sender_email'])));
		$subject = trim($_POST['subject']);
		$text = $_POST['text'];

		// Überprüfungen der Daten:
		unset($errors);
		if ($sender_name == "") $errors[] = $lang['error_no_name'];
		if ($sender_email == "") $errors[] = $lang['error_no_email'];
		if ($sender_email != "" and !preg_match("/^[^@]+@.+\.\D{2,}$/", $sender_email)) $errors[] = $lang['error_email_wrong'];
		if ($text == "") $errors[] = $lang['error_no_text'];

		// check for not accepted words:
		$result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'words' LIMIT 1", $connid);
		if (!$result) die($lang['db_error']);
		$data = mysql_fetch_assoc($result);
		mysql_free_result($result);
		if (trim($data['list']) != '')
			{
			$not_accepted_words = explode(',',trim($data['list']));
			foreach ($not_accepted_words as $not_accepted_word)
				{
				if ($not_accepted_word!='' && (preg_match("/".$not_accepted_word."/i",$sender_name) || preg_match("/".$not_accepted_word."/i",$sender_email) || preg_match("/".$not_accepted_word."/i",$subject) || preg_match("/".$not_accepted_word."/i",$text)))
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
			$headerTemplate  = "X-Mailer: PHP/" . phpversion(). "\n";
			$headerTemplate .= "X-Sender-IP: $ip\n";
			$headerTemplate .= "Content-Type: text/plain; charset=UTF-8\n";
			$headerTemplate .= "Content-Transfer-Encoding: 8bit\n";
			$mail_subject = ($_POST['subject'] != "") ? $subject : $lang['email_no_subject'];
			$copySubject = mb_encode_mimeheader($mail_subject,"UTF-8");
			if (isset($forum_contact))
				{
				$name = $settings['forum_name'];
				$email = $settings['forum_email'];
				}
			$mailto = mb_encode_mimeheader($name,"UTF-8")." <".$email.">";
			$ip = $_SERVER["REMOTE_ADDR"];
			$mail_text = $text;
			$mail_text .= "\n\n".str_replace("[forum_address]", $settings['forum_address'], $lang['msg_add']);
			$header  = "From: ".mb_encode_mimeheader($sender_name,"UTF-8")." <".$sender_email.">\n";
			$header .= "Reply-To: ".mb_encode_mimeheader($sender_name,"UTF-8")." <".$sender_email.">\n";
			$header .= $headerTemplate;
			if ($settings['mail_parameter']!='')
				{
				if (@mail($mailto, $copySubject, $mail_text, $header, $settings['mail_parameter'])) $sent = true; else $errors[] = $lang['error_meilserv'];
				}
			else
				{
				if (@mail($mailto, $copySubject, $mail_text, $header)) $sent = true; else $errors[] = $lang['error_meilserv'];
				}
			// Bestätigung:
			if (isset($sent))
				{
				$lang['conf_email_txt'] = str_replace("[forum_address]", $settings['forum_address'], $lang['conf_email_txt']);
				$lang['conf_email_txt'] = str_replace("[sender_name]", $sender_name, $lang['conf_email_txt']);
				$lang['conf_email_txt'] = str_replace("[recipient_name]", $name, $lang['conf_email_txt']);
				$lang['conf_email_txt'] = str_replace("[subject]", $mail_subject, $lang['conf_email_txt']);
				$lang['conf_email_txt'] .= "\n\n".$text;
				$conf_mailto = mb_encode_mimeheader($sender_name,"UTF-8")." <".$sender_email.">";
				$ip = $_SERVER["REMOTE_ADDR"];
				$conf_header  = "From: ".mb_encode_mimeheader($settings['forum_name'],"UTF-8")." <".$settings['forum_email'].">\n";
				$conf_header .= $headerTemplate;
				$confSubject = mb_encode_mimeheader($lang['conf_sj'],"UTF-8");
				if ($settings['mail_parameter']!='')
					{
					@mail($conf_mailto, $confSubject, $lang['conf_email_txt'], $conf_header, $settings['mail_parameter']);
					}
				else
					{
					@mail($conf_mailto, $confSubject, $lang['conf_email_txt'], $conf_header);
					}
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
		$subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.str_replace("[name]", htmlspecialchars($field["name"]), $lang['back_to_posting_linkname']).'</a>';
		}
	else
		{
		if ($view=="board")
			{
			$subnav_1 .= '<a class="textlink" href="board_entry.php?id='.$field['tid'].'&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
			}
		else
			{
			$subnav_1 .= '<a class="textlink" href="mix_entry.php?id='.$field['tid'].'&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
			}
		}
	}

$wo = $email_headline;
parse_template();
echo $header;
if (isset($id) || isset($uid) || isset($forum_contact))
	{
	if (empty($no_message))
		{
		?><h2><?php
		echo (isset($forum_contact)) ? $lang['forum_contact_hl'] : str_replace("[name]", htmlspecialchars(stripslashes($name)), $lang['message_to']);
		?></h2>
		<?php
		}
	if (empty($sent) && empty($no_message))
		{
		if(isset($errors))
			{
			?><p class="caution"><?php echo $lang['error_headline']; ?></p><ul><?php
			foreach($errors as $f)
				{
				?><li><?php echo $f; ?></li><?php
				}
			?></ul><?php
			}

		if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
			{
			if($settings['captcha_type']==1) $_SESSION['captcha_session'] = $captcha->generate_code();
			else $_SESSION['captcha_session'] = $captcha->generate_math_captcha();
			}

		?>
    <form method="post" action="<?php echo $_SERVER["SCRIPT_NAME"]; ?>" accept-charset="UTF-8">
		<?php
		if(empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
			{
			?><input type="hidden" name="<?php echo session_name(); ?>" value="<?php echo session_id(); ?>" />
			<?php
			}
		if (isset($id))
			{
			?><input type="hidden" name="id" value="<?php echo $id; ?>" />
			<?php
			}
		else if (isset($uid))
			{
			?><input type="hidden" name="uid" value="<?php echo $uid; ?>" />
			<?php
			}
		if (isset($view))
			{
			?><input type="hidden" name="view" value="<?php echo $view; ?>" />
			<?php
			}
		if (isset($forum_contact))
			{
			?><input type="hidden" name="forum_contact" value="<?php echo $forum_contact; ?>" />
			<?php
			}
		if (isset($page) && isset($order) && isset($category) && isset($descasc))
			{
			?>
			<input type="hidden" name="page" value="<?php echo $page; ?>" />
			<input type="hidden" name="order" value="<?php echo $order; ?>" />
			<input type="hidden" name="category" value="<?php echo $category; ?>" />
			<input type="hidden" name="descasc" value="<?php echo $descasc; ?>" />
			<?php
			}
		?>
    <table>
    <tr>
    <td><b><?php echo $lang['name_marking_msg']; ?></b></td>
    <td><input type="text" name="sender_name" value="<?php
		echo (isset($sender_name)) ? htmlspecialchars($sender_name) : "";
		?>" size="40" /></td>
    </tr>
    <tr>
    <td><b><?php echo $lang['email_marking_msg']; ?></b></td>
    <td><input type="text" name="sender_email" value="<?php
		echo (isset($sender_email)) ? htmlspecialchars($sender_email) : "";
		?>" size="40" /></td>
    </tr>
    <tr>
    <td><b><?php echo $lang['subject_marking']; ?></b></td>
    <td><input type="text" name="subject" value="<?php
		echo (isset($subject)) ? htmlspecialchars($subject) : "";
		?>" size="40" /></td>
    </tr>
    <tr>
    <td colspan="2"><textarea name="text" cols="60" rows="15"><?php
		echo (isset($text)) ? htmlspecialchars($text) : "";
		?></textarea></td>
    </tr><?php
		if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_contact']==1)
			{
			?><tr>
     <td colspan="2"><b><?php echo $lang['captcha_marking']; ?></b></td>
     </tr><?php
		if($settings['captcha_type']==1)
			{
			?><tr>
       <td colspan="2"><img class="captcha" src="captcha/captcha_image.php<?php echo '?'.SID; ?>" alt="<?php echo $lang['captcha_image_alt']; ?>" width="180" height="40"/></td>
       </tr>
       <tr>
       <td colspan="2"><?php echo $lang['captcha_expl_image']; ?></td>
       </tr>
       <tr>
       <td colspan="2"><input type="text" name="captcha_code" value="" size="10" /></td>
       </tr><?php
			}
		else
			{
       ?><tr>
       <td colspan="2"><?php echo $lang['captcha_expl_math']; ?></td>
       </tr>
       <tr>
       <td colspan="2"><?php echo $_SESSION['captcha_session'][0]; ?> + <?php echo $_SESSION['captcha_session'][1]; ?> = <input type="text" name="captcha_code" value="" size="5" /></td>
       </tr><?php
			}
		}
		?>
    </table>
    <p><input type="submit" name="form_submitted" value="<?php echo $lang['pers_msg_subm_button']; ?>" /></p>
    </form>
    <?php
		}
	else if (empty($sent) && isset($no_message))
		{
		?><p><?php echo $lang['email_unknown']; ?></p><?php
		}
	else
		{
		?><p><?php
		echo (isset($forum_contact)) ? $lang['forum_contact_sent'] : str_replace("[name]", htmlspecialchars(stripslashes($name)), $lang['msg_sent']);
		?></p><?php
		}
	}

echo $footer;
?>
