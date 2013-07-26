<?php
###############################################################################
# my little forum 1                                                            #
# Copyright (C) 2013 Heiko August                                             #
# http://www.auge8472.de/                                                     #
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

include_once("inc.php");
include_once("functions/include.prepare.php");

# generate captcha if captcha is on
# and a not logged user wants to post
if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	and $settings['captcha_posting'] == 1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

# look for banned user:
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$lockQuery = "SELECT user_lock
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_id = '". intval($_SESSION[$settings['session_prefix'].'user_id']) ."'
	LIMIT 1";
	$lockResult = mysql_query($lockQuery, $connid);
	if ($lockResult === false) die($lang['db_error']);
	$lockResultArray = mysql_fetch_assoc($lockResult);
	mysql_free_result($lockResult);
	if ($lockResultArray['user_lock'] > 0)
		{
		header("location: ". $settings['forum_address'] ."user.php");
		die('<a href="user.php">further...</a>');
		}
	} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))

/**
 * Start: block for special cases
 */
# lock or unlock a thread (forbid or allow answers to a thread)
if (isset($_GET['lock'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$lockQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	locked = IF(locked = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($lockQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['lock']) ...)

# pin or unpin threads to the top of the views
if (isset($_GET['fix'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$fixQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	fixed = IF(fixed = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($fixQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['fix']) ...)

# subscribe or unsubscribe threads
if (isset($_GET['subscribe'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and isset($_GET['back']))
	{
	if ($_GET['subscribe'] == 'true')
		{
		$querySubscribe = "INSERT INTO ". $db_settings['usersubscripts_table'] ." SET
		user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) .",
		tid = ". intval($_GET['back']) ."
		ON DUPLICATE KEY UPDATE
		user_id = user_id,
		tid = tid";
		$queryUnsubscribePost = "UPDATE ". $db_settings['forum_table'] ." SET
		email_notify = 0
		WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
		AND tid = ". intval($_GET['id']);
		}
	else if ($_GET['subscribe'] == 'false')
		{
		$subscriptThread = processSearchThreadSubscriptions($_GET['back'], $_SESSION[$settings['session_prefix'].'user_id']);
		if (($subscriptThread !== false
		and is_array($subscriptThread))
		and ($subscriptThread['user_id'] == $_SESSION[$settings['session_prefix'].'user_id']
		and $subscriptThread['tid'] == $_GET['back']))
			{
			$querySubscribe = "DELETE FROM ". $db_settings['usersubscripts_table'] ."
			WHERE tid = ". intval($_GET['back']) ."
			AND user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
			LIMIT 1";
			}
		}
	if (!empty($querySubscribe)) @mysql_query($querySubscribe, $connid);
	if (!empty($queryUnsubscribePost)) @mysql_query($queryUnsubscribePost, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
	and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='.  intval($_GET['back']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['subscribe'] ...)
/**
 * End: block for special cases
 */

/**
 * processing of normal script requests
 */
if (($settings['access_for_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_name']))
	or $settings['access_for_users_only'] != 1)
	{
	if (($settings['entries_by_users_only'] == 1
		and isset($_SESSION[$settings['session_prefix'].'user_name']))
		or $settings['entries_by_users_only'] != 1)
		{
		if (is_array($categories)
			and !in_array($_SESSION[$settings['session_prefix'].'mlf_category'], $categories))
			{
			header('location: '.$settings['forum_address'].'index.php');
			die('<a href="index.php">further...</a>');
			}

		# delete arrays if present
		if (isset($errors)) unset($errors);
		if (isset($Thread)) unset($Thread);
		# safety: forbid editing and deletion of postings
		$authorisation['edit'] = 0;
		$authorisation['delete'] = 0;
		# $action can only be submitted via POST, is set
		# to standard value or it will be changed during
		# the script run (i.e. by checking GET parameters)
		$action = (!empty($_POST['action']) and in_array($_POST['action'], $allowSubmittedActions)) ? $_POST['action'] : "new";
		$action = (!empty($_GET['edit']) and $_GET['edit'] == "true") ? "edit" : $action;
		$action = (!empty($_GET['delete']) and $_GET['delete'] == "true") ? "delete" : $action;
		$action = (!empty($_GET['delete_ok']) and $_GET['delete_ok'] == "true") ? "delete ok" : $action;
		# if a posting should be edited or deleted, check for authorisation
		# check call via GET or POST parameter
		if ((isset($_GET['id']) and is_numeric($_GET['id']))
			or (isset($_POST['id']) and is_numeric($_POST['id']))
			and ($action == "edit"
				or $action == "delete"
				or $action == "delete ok"))
			{
			$authorisation =  processCheckAuthorisation(isset($_GET['id']) ? $_GET['id'] : $_POST['id'], $authorisation, $connid);
			} # End: check for authorisation if called via GET or POST parameter
		# if form was submitted (old file: line 618)
		if (isset($_POST['form']))
			{
			$_POST['id'] = empty($_POST['id']) ? 0 : intval($_POST['id']);
			switch ($action)
				{
				case "new":
					# is it a registered user?
					if (isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						$user_id = $_SESSION[$settings['session_prefix'].'user_id'];
						$name = $_SESSION[$settings['session_prefix'].'user_name'];
						}
					# if the posting is an answer, search the thread-ID:
					if ($_POST['id'] > 0)
						{
						$threadIdQuery = "SELECT
						tid,
						locked
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". intval($_POST['id']);
						$threadIdResult = mysql_query($threadIdQuery, $connid);
						if (!$threadIdResult) die($lang['db_error']);

						if (mysql_num_rows($threadIdResult) != 1)
							{
							die($lang['db_error']);
							}
						else
							{
							$field = mysql_fetch_assoc($threadIdResult);
							$Thread = $field['tid'];
							if ($field['locked'] > 0)
								{
								unset($action);
								$show = "no authorization";
								$reason = $lang['thread_locked_error'];
								}
							}
						mysql_free_result($threadIdResult);
						}
					else if ($_POST['id'] == 0)
						{
						$Thread = 0;
						}
				break;
				case "edit";
					# fetch missing data from database:
					$postingQuery = "SELECT
					name,
					locked,
					UNIX_TIMESTAMP(time) AS time,
					UNIX_TIMESTAMP(NOW() - INTERVAL ". $settings['edit_period'] ." MINUTE) AS edit_diff
					FROM ". $db_settings['forum_table'] ."
					WHERE id = ". intval($_POST['id']);
					$edit_result = mysql_query($postingQuery, $connid);
					if (!$edit_result) die($lang['db_error']);
					$field = mysql_fetch_assoc($edit_result);
					mysql_free_result($edit_result);
					if (empty($name))
						{
						$name = $field["name"];
						}
				break;
				} # End: switch ($action)
			# check for new or edited posting is complete
			# now check submitted data:
			# double entry?
			$uniqueIdQuery = "SELECT COUNT(*)
			FROM ". $db_settings['forum_table'] ."
			WHERE uniqid = '". mysql_real_escape_string($_POST['uniqid']) ."'
			AND time > NOW()-10000";
			$uniqueIdResult = mysql_query($uniqueIdQuery, $connid);
			list($uniqidCount) = mysql_fetch_row($uniqueIdResult);
			mysql_free_result($uniqueIdResult);
			if ($uniqidCount > 0)
				{
				header("location: ".$settings['forum_address']."index.php");
				die('<a href="index.php">further...</a>');
				}
			if (empty($_POST['name']))
				{
				$errors[] = $lang['error_no_name'];
				}
			# name reserved?
			if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
				{
				$reservedUsernameQuery = "SELECT user_name
				FROM ". $db_settings['userdata_table'] ."
				WHERE user_name = '". mysql_real_escape_string($_POST['name']) ."'";
				$reservedUsernameResult = mysql_query($reservedUsernameQuery,$connid);
				if (!$reservedUsernameResult) die($lang['db_error']);
				$field = mysql_fetch_assoc($reservedUsernameResult);
				mysql_free_result($reservedUsernameResult);
				if (!empty($_POST['name'])
					and mb_strtolower($field["user_name"]) == mb_strtolower($_POST['name']))
					{
					$lang['error_name_reserved'] = str_replace("[name]", htmlspecialchars($_POST['name']), $lang['error_name_reserved']);
					$errors[] = $lang['error_name_reserved'];
					}
				}
			# check the given email address for format name@example.com
			# regular expression: see functions/funcs.processing.php
			if (!empty($_POST['email']))
				and !preg_match($validator['email'], $_POST['email']))
				{
				$errors[] = $lang['error_email_wrong'];
				}
			# check for presence of email address in case of
			# notification about answers to the current posting
			if ((empty($_POST['email'])
				and isset($_POST['email_notify'])
				and $_POST['email_notify'] == 1
				and !isset($_SESSION[$settings['session_prefix'].'user_id']))
				or (empty($_POST['email'])
				and isset($_POST['email_notify'])
				and $_POST['email_notify'] == 1
				and isset($p_user_id) // <== check for source of the variable!
				and $p_user_id == 0))
				{
				$errors[] = $lang['error_no_email_to_notify'];
				}
			# check for empty subject
			if (empty($_POST['subject']))
				{
				$errors[] = $lang['error_no_subject'];
				}
			# check for empty posting text (if it is not allowed)
			if (empty($settings['empty_postings_possible'])
			|| (isset($settings['empty_postings_possible'])
			&& $settings['empty_postings_possible'] != 1))
				{
				if (empty($_POST['text']))
					{
					$errors[] = $lang['error_no_text'];
					}
				}
			# check submitted strings for string length exceeding
			if (!empty($_POST['name'])
				and mb_strlen($_POST['name']) > $settings['name_maxlength'])
				{
				$errors[] = $lang['name_marking']." ".$lang['error_input_too_long'];
				}
			if (!empty($_POST['email'])
				and mb_strlen($_POST['email']) > $settings['email_maxlength'])
				{
				$errors[] = $lang['email_marking']." ".$lang['error_input_too_long'];
				}
			if (!empty($_POST['hp'])
				and mb_strlen($_POST['hp']) > $settings['hp_maxlength'])
				{
				$errors[] = $lang['hp_marking'] . " " .$lang['error_input_too_long'];
				}
			if (!empty($_POST['place'])
				and mb_strlen($_POST['place']) > $settings['place_maxlength'])
				{
				$errors[] = $lang['place_marking'] . " " .$lang['error_input_too_long'];
				}
			if (!empty($_POST['subject'])
				and mb_strlen($_POST['subject']) > $settings['subject_maxlength'])
				{
				$errors[] = $lang['subject_marking'] . " " .$lang['error_input_too_long'];
				}
			if (!empty($_POST['text'])
				and mb_strlen($_POST['text']) > $settings['text_maxlength'])
				{
				$lang['error_text_too_long'] = str_replace("[length]", mb_strlen($_POST['text']), $lang['error_text_too_long']);
				$lang['error_text_too_long'] = str_replace("[maxlength]", $settings['text_maxlength'], $lang['error_text_too_long']);
				$errors[] = $lang['error_text_too_long'];
				}
			# trim and complete data:
			$_POST['fixed'] = empty($_POST['fixed']) ? 0 : 1;
			$_POST['show_signature'] = empty($_POST['show_signature']) ? 0 : 1;
			$_POST['user_id'] = empty($_POST['user_id']) ? 0 : intval($_POST['user_id']);
			$_POST['email_notify'] = empty($_POST['email_notify']) ? 0 : 1;
			$_POST['hp'] = empty($_POST['hp']) ? "" : trim($_POST['hp']);
			$_POST['place'] = empty($_POST['place']) ? "" : trim($_POST['place']);
			if (empty($_POST['p_category'])
				 or !array_key_exists($_POST['p_category'], $categories))
				{
				$_POST['p_category'] = 0;
				}
			# CAPTCHA check:
			if (isset($_POST['save_entry'])
			&& empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_posting'] == 1)
				{
				if($settings['captcha_type'] == 1)
					{
					if ($captcha->check_captcha($_SESSION['captcha_session'], $_POST['captcha_code']) !== TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				else
					{
					if ($captcha->check_math_captcha($_SESSION['captcha_session'][2], $_POST['captcha_code']) !== TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				} # End: CAPTCHA check
			# end check data
			if (empty($errors)
				and empty($_POST['preview'])
				and (isset($_POST['save_entry'])
					and $_POST['save_entry'] == outputLangDebugInAttributes($lang['submit_button'])))
				{
				switch ($action)
					{
					case "new":
						# save new entry
						$newPostingQuery = "INSERT INTO ". $db_settings['forum_table'] ." SET
						pid = ". intval($_POST['id']) .",
						tid = ". intval($Thread) .",
						uniqid = '". mysql_real_escape_string($_POST['uniqid']) ."',
						time = NOW(),
						last_answer = NOW(),
						user_id = ". intval($_POST['user_id']) .",
						name = '". mysql_real_escape_string($_POST['name']) ."',
						subject = '". mysql_real_escape_string($_POST['subject']) ."',
						email = '". mysql_real_escape_string($_POST['email']) ."',
						hp = '". mysql_real_escape_string($_POST['hp']) ."',
						place = '". mysql_real_escape_string($_POST['place']) ."',
						ip_addr = INET_ATON('". mysql_real_escape_string($_SERVER["REMOTE_ADDR"]) ."'),
						text = '". mysql_real_escape_string($_POST['text']) ."',
						show_signature = ". intval($_POST['show_signature']) .",
						email_notify = ". intval($_POST['email_notify']) .",
						category = ". intval($_POST['p_category']) .",
						fixed = ". intval($_POST['fixed']);
						$result = mysql_query($newPostingQuery, $connid);
						if (!$result) die($lang['db_error']);
						# get the id of the saved posting
						$getNewIDQuery = "SELECT
						id
						FROM ". $db_settings['forum_table'] ."
						WHERE id = LAST_INSERT_id()";
						$result = mysql_query($getNewIDQuery, $connid);
						if (!$result) die($lang['db_error']);
						$newID = mysql_fetch_assoc($result);
						$newID = $newID['id'];
						# set the thread id for a new thread
						# derive it from the posting id
						if (intval($_POST['id']) == 0)
							{
							$newPostingUpdateQuery = "UPDATE ". $db_settings['forum_table'] ." SET
							tid = id,
							time = time
							WHERE id = ". $newID;
							if (!mysql_query($newPostingUpdateQuery, $connid))
								{
								die($lang['db_error']);
								}
							}
						# actualise time stamp of last answer for all postings in the thread
						if (intval($_POST['id']) != 0)
							{
							$updateLastAnswerQuery = "UPDATE ".$db_settings['forum_table']." SET
							time = time,
							last_answer = NOW()
							WHERE tid = ". $Thread;
							if (!mysql_query($updateLastAnswerQuery, $connid))
								{
								die($lang['db_error']);
								}
							}
						# get last entry (to redirect to it):
						$redirectQuery = "SELECT
						tid AS counter,
						pid,
						id,
						(SELECT COUNT(*) FROM ". $db_settings['forum_table'] ."
							WHERE tid = counter) AS count
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". $newID;
						$redirectResult = mysql_query($redirectQuery, $connid);
						$redirect = mysql_fetch_assoc($redirectResult);						
						# check for wished email notification:
						if ($settings['email_notification'] == 1)
							{
							$mail_text = unbbcode($_POST['text']);
							$PostAddress = $settings['forum_address'];
							if ($settings['standard'] == "board")
								{
								$PostAddress .= "board_entry.php?id=".$redirect["counter"]."#p".$redirect["id"];
								}
							else if ($settings['standard'] == "mix")
								{
								$PostAddress .= "mix_entry.php?id=".$redirect["counter"]."#p".$redirect["id"];
								}
							else
								{
								$PostAddress .= "forum_entry.php?id=".$redirect["id"];
								}
							$emailParentUserQuery = "SELECT
							t1.user_id,
							IF(t1.user_id > 0, t2.user_name, t1.name) AS name,
							IF(t1.user_id > 0, t2.user_email, t1.email) AS email,
							t1.subject,
							t1.text
							FROM ". $db_settings['forum_table'] ." AS t1 LEFT JOIN ". $db_settings['userdata_table'] ." AS t2
							ON t2.user_id = t1.user_id
							WHERE t1.id = ". intval($_POST['id']) ."
								AND t1.email_notify = 1
							LIMIT 1";
							$emailParentUserResult = mysql_query($emailParentUserQuery, $connid);
							if (mysql_num_rows($emailParentUserResult) == 1)
								{
								$parent = mysql_fetch_assoc($emailParentUserResult);
								$emailbody = $lang['email_text'].$lang['email_original_post'];
								$emailbody = str_replace("[recipient]", $parent["name"], $emailbody);
								$emailbody = str_replace("[name]", $_POST['name'], $emailbody);
								$emailbody = str_replace("[subject]", $_POST['subject'], $emailbody);
								$emailbody = str_replace("[text]", $mail_text, $emailbody);
								$emailbody = str_replace("[posting_address]", $PostAddress, $emailbody);
								$emailbody = str_replace("[original_subject]", $parent["subject"], $emailbody);
								$emailbody = str_replace("[original_text]", unbbcode($parent["text"]), $emailbody);
								$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
								$emailbody = stripslashes($emailbody);
								$emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
								$an = mb_encode_mimeheader($parent["name"],"UTF-8")." <".$parent["email"].">";
								$emailsubject = strip_tags($lang['email_subject']);
								$sent = processEmail($an, $emailsubject, $emailbody);
								if ($sent === true)
									{
									$sent = "ok";
									}
								unset($emailsubject);
								unset($emailbody);
								unset($an);
								mysql_free_result($emailParentUserResult);
								}
							$threadNotifyQuery = "SELECT
							t1.user_name AS name,
							t1.user_email AS email,
							t2.user_id
							FROM ". $db_settings['userdata_table'] ." AS t1,
							". $db_settings['usersubscripts_table'] ." AS t2
							WHERE t1.user_id = t2.user_id AND t2.tid = ". $redirect["counter"];
							$threadNotifyResult = mysql_query($threadNotifyQuery, $connid);
							if (!$threadNotifyResult) die($lang['db_error']);
							while ($field = mysql_fetch_assoc($threadNotifyResult))
								{
								$emailbody = str_replace("[recipient]", $field["name"], $lang['email_text']);
								$emailbody = str_replace("[name]", $_POST['name'], $emailbody);
								$emailbody = str_replace("[subject]", $_POST['subject'], $emailbody);
								$emailbody = str_replace("[text]", $mail_text, $emailbody);
								$emailbody = str_replace("[posting_address]", $PostAddress, $emailbody);
								$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
								$emailbody = stripslashes($emailbody);
								$emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
								$an = mb_encode_mimeheader($field["name"],"UTF-8")." <".$field["email"].">";
								$emailsubject = strip_tags($lang['email_subject']);
								$sent1[] = processEmail($an, $emailsubject, $emailbody);
								unset($emailsubject);
								unset($emailbody);
								unset($an);
								}
							mysql_free_result($threadNotifyResult);
							}
						# send message to admins and moderators:
						$emailbody = (intval($id) > 0) ? strip_tags($lang['admin_email_text_reply']) : strip_tags($lang['admin_email_text']);
						$emailbody = str_replace("[name]", $_POST['name'], $emailbody);
						$emailbody = str_replace("[subject]", $_POST['subject'], $emailbody);
						$emailbody = str_replace("[text]", $mail_text, $emailbody);
						$emailbody = str_replace("[posting_address]", $PostAddress, $emailbody);
						$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
						$emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
						$emailsubject = str_replace("[subject]", $subject, $lang['admin_email_subject']);
						// Schauen, wer eine E-Mail-Benachrichtigung will:
						$listAdminModEmailQuery = "SELECT
						user_name,
						user_email
						FROM ".$db_settings['userdata_table']."
						WHERE user_type IN('admin', 'mod')
							AND new_posting_notify = '1'";
						$en_result = mysql_query(, $connid);
						if (!$en_result) die($lang['db_error']);
						while ($admin_array = mysql_fetch_assoc($en_result))
							{
							$ind_emailbody = str_replace("[admin]", $admin_array['user_name'], $emailbody);
							$an = mb_encode_mimeheader($admin_array['user_name'],"UTF-8")." <".$admin_array['user_email'].">";
							$sent2[] = processEmail($an, $emailsubject, $ind_emailbody);
							unset($ind_emailbody);
							unset($an);
							}
						mysql_free_result($en_result);

						# for redirect:
						$further_tid = $redirect["counter"];
						$further_id = $redirect["id"];
						$further_page = 0;
						if ((!empty($_SESSION[$setting['session_prefix'] .'curr_view'])
								and $_SESSION[$setting['session_prefix'] .'curr_view'] == 'board')
							or (!empty($_SESSION[$setting['session_prefix'] .'user_view'])
								and $_SESSION[$setting['session_prefix'] .'user_view'] == 'board')
							or (!empty($_COOKIE['curr_view'])
								and $_COOKIE['curr_view'] == 'board')
							or (!empty($_COOKIE['user_view'])
								and $_COOKIE['user_view'] == 'board'))
							{
							# there are more postings in the thread than
							# the setting for postings per page allows
							if ($redirect['count'] > $settings['answers_per_topic'])
								{
								$further_page = floor($redirect['count']/$settings['answers_per_topic']);
								}
							}
						# set cookies, if wished and function is active:
						if ($settings['remember_userdata'] == 1)
							{
							if (isset($_POST['setCookie'])
								and $_POST['setCookie'] == 1)
								{
								setcookie("user_name",$name,time()+(3600*24*30));
								setcookie("user_email",$email,time()+(3600*24*30));
								setcookie("user_hp",$hp,time()+(3600*24*30));
								setcookie("user_place",$place,time()+(3600*24*30));
								}
							}
						$refer = 1;
					break;
					case "edit":
						if ($authorisation['edit'] == 1
						&& ($field['locked'] == 0
						|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
						&& ($_SESSION[$settings['session_prefix'].'user_type'] == 'admin'
						|| $_SESSION[$settings['session_prefix'].'user_type'] == 'mod'))))
							{
							if (!($settings['edit_period'] > 0
							&& $field["edit_diff"] > $field["time"]
							&& (empty($_SESSION[$settings['session_prefix'].'user_type'])
							|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
							&& $_SESSION[$settings['session_prefix'].'user_type'] != 'admin'
							&& $_SESSION[$settings['session_prefix'].'user_type'] != 'mod'))))
								{
								$editPostingQuery = "SELECT
								tid,
								tid AS counter,
								(SELECT COUNT(*) FROM ". $db_settings['forum_table'] ."
									WHERE tid = counter) AS count,
								name,
								subject,
								text
								FROM ". $db_settings['forum_table'] ."
								WHERE id = ". intval($_POST['id']);
								$editPostingResult = mysql_query($editPostingQuery, $connid);
								if (!$editPostingResult) die($lang['db_error']);
								$field = mysql_fetch_assoc($editPostingResult);
								mysql_free_result($editPostingResult);
								}
							else
								{
								$show = "no authorization";
								$reason = str_replace('[minutes]',$settings['edit_period'],$lang['edit_period_over']);
								}
							}
						else
							{
							$show = "no authorization";
							$reason = $lang['thread_locked_error'];
							}
					break;
					}
				} # End: if (empty($errors) and empty($_POST['preview']) and ...)
			else
				{
				$show = "form";
				}
			} # End: if (isset($_POST['form']))
		} # End: if (($settings['entries_by_users_only'] == 1 ...)
	else
		{
		header("Location: ". $settings['forum_address'] ."login.php?msg=noentry");
		die('<a href="login.php?msg=noentry">further...</a>');
		}
	} # End: if (($settings['access_for_users_only'] == 1 ...)
else
	{
	header("Location: ". $settings['forum_address'] ."login.php?msg=noaccess");
	die('<a href="login.php?msg=noaccess">further...</a>');
	}
