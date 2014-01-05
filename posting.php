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
		$header_href = ($settings['standard'] == 'thread') ? 'forum.php' : $settings['standard'] .'.php';
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
		$header_href = ($settings['standard'] == 'thread') ? 'forum.php' : $settings['standard'] .'.php';
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
		$header_href = ($settings['standard'] == 'thread') ? 'forum.php' : $settings['standard'] .'.php';
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
			if (!empty($_POST['email'])
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
						if ((!empty($_SESSION[$settings['session_prefix'] .'curr_view'])
								and $_SESSION[$settings['session_prefix'] .'curr_view'] == 'board')
							or (!empty($_SESSION[$settings['session_prefix'] .'user_view'])
								and $_SESSION[$settings['session_prefix'] .'user_view'] == 'board')
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
								# unnoticed editing for admins and mods:
								if (isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin"
								&& $settings['dont_reg_edit_by_admin'] == 1
								|| isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& $_SESSION[$settings['session_prefix'].'user_type'] == "mod"
								&& $settings['dont_reg_edit_by_mod'] == 1
								|| ($field['text'] == $_POST['text']
								&& $field['subject'] == $_POST['subject']
								&& $field['name'] == $_POST['name']
								&& isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
								|| $_SESSION[$settings['session_prefix'].'user_type'] == "mod")))
									{
									$updatePostingQuery = "UPDATE ". $db_settings['forum_table'] ." SET
									time = time,
									last_answer = last_answer,
									edited = edited,
									name = '". mysql_real_escape_string($_POST['name']) ."',
									subject = '". mysql_real_escape_string($_POST['subject']) ."',
									category = ". intval($_POST['p_category']) .",
									email = '". mysql_real_escape_string($_POST['email']) ."',
									hp = '". mysql_real_escape_string($_POST['hp']) ."',
									place = '". mysql_real_escape_string($_POST['place']) ."',
									text = '". mysql_real_escape_string($_POST['text']) ."',
									email_notify = '". intval($_POST['email_notify']) ."',
									show_signature = '". intval($_POST['show_signature']) ."',
									fixed = ". intval($_POST['fixed']) ."
									WHERE id = ". intval($_POST['id']);
									}
								else
									{
									$updatePostingQuery = "UPDATE ". $db_settings['forum_table'] ." SET
									time = time,
									last_answer = last_answer,
									edited = NOW(),
									edited_by = '". mysql_real_escape_string($_SESSION[$settings['session_prefix']."user_name"]) ."',
									name = '". mysql_real_escape_string($_POST['name']) ."',
									subject = '". mysql_real_escape_string($_POST['subject']) ."',
									category = ". intval($_POST['p_category']) .",
									email = '". mysql_real_escape_string($_POST['email']) ."',
									hp = '". mysql_real_escape_string($_POST['hp']) ."',
									place = '". mysql_real_escape_string($_POST['place']) ."',
									text = '". mysql_real_escape_string($_POST['text']) ."',
									email_notify = '". intval($_POST['email_notify']) ."',
									show_signature = '". intval($_POST['show_signature']) ."',
									fixed = ". intval($_POST['fixed']) ."
									WHERE id = ". intval($_POST['id']);
									}
								$postingUpdateResult = mysql_query($updatePostingQuery, $connid);
								if (!$postingUpdateResult) die($lang['db_error']);
								# generate code for redirection
								$further_tid = $field['counter'];
								$further_id = $_POST['id'];
								$further_page = 0;
								if ((!empty($_SESSION[$settings['session_prefix'] .'curr_view'])
								and $_SESSION[$settings['session_prefix'] .'curr_view'] == 'board')
								or (!empty($_SESSION[$settings['session_prefix'] .'user_view'])
								and $_SESSION[$settings['session_prefix'] .'user_view'] == 'board')
								or (!empty($_COOKIE['curr_view'])
								and $_COOKIE['curr_view'] == 'board')
								or (!empty($_COOKIE['user_view'])
								and $_COOKIE['user_view'] == 'board'))
									{
									# there are more postings in thread than
									# the setting for postings per page allows
									if ($field['count'] > $settings['answers_per_topic'])
										{
										$further_page = floor($field['count']/$settings['answers_per_topic']);
										}
									}
								$refer = 1;
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
		else
			{
			# the page was requested to add a new or to edit or delete an existing posting
			switch ($action)
				{
				case "new":
				# in case of a not logged in user, read the cookies with userdata
					if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						if (isset($_COOKIE['user_name']))
							{
							$name = $_COOKIE['user_name']; $setcookie = 1;
							}
						if (isset($_COOKIE['user_email']))
							{
							$email = $_COOKIE['user_email'];
							}
						if (isset($_COOKIE['user_hp']))
							{
							$hp = $_COOKIE['user_hp'];
							}
						if (isset($_COOKIE['user_place']))
							{
							$place = $_COOKIE['user_place'];
							}
						}
					$show_signature = 1;
					# if message is a reply:
					if (intval($_GET['id']) > 0)
						{
						$oldMessageQuery = "SELECT
						tid,
						pid,
						name,
						subject,
						category,
						text,
						locked
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". intval($_GET['id']);
						$oldMessageResult = mysql_query($oldMessageQuery, $connid);
						if (!$oldMessageResult) die($lang['db_error']);
						if (mysql_num_rows($oldMessageResult) != 1)
							{
							$postingID = 0;
							$show = "form";
							}
						else
							{
							$oldMessage = mysql_fetch_assoc($oldMessageResult);
							# Zitatzeichen an den Anfang jeder Zeile stellen:
							$oldMessage['text'] = preg_replace("/^/m", $settings['quote_symbol']." ", $oldMessage['text']);
							if ($oldMessage['locked'] > 0
							&& (empty($_SESSION[$settings['session_prefix'].'user_type'])
							|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
							&& $_SESSION[$settings['session_prefix'].'user_type'] != 'admin'
							&& $_SESSION[$settings['session_prefix'].'user_type'] != 'mod')))
								{
								$show = "no authorization";
								$reason = $lang['thread_locked_error'];
								}
							else
								{
								$postingID = $oldMessage['pid'];
								$show = "form";
								}
							}
						mysql_free_result($oldMessageResult);
						}
					else
						{
						$postingID = 0;
						$show = "form";
						}
				break;
				case "edit":
					if ($authorisation['edit'] == 1)
						{
						# fetch data of message which should be edited:
						$editMessageQuery = "SELECT
						tid,
						pid,
						t1.user_id,
						IF(t1.user_id > 0, t2.user_name, t1.name) AS name,
						IF(t1.user_id > 0, t2.user_email, t1.email) AS email,
						IF(t1.user_id > 0, t2.user_hp, t1.hp) AS hp,
						IF(t1.user_id > 0, t2.user_place, t1.place) AS place,
						IF(t1.user_id > 0, t2.signature, '') AS signature,
						subject,
						category,
						text,
						email_notify,
						show_signature,
						locked,
						fixed,
						UNIX_TIMESTAMP(time) AS time,
						UNIX_TIMESTAMP(NOW() - INTERVAL ". $settings['edit_period'] ." MINUTE) AS edit_diff
						FROM ". $db_settings['forum_table'] ." AS t1 LEFT JOIN ". $db_settings['userdata_table'] ." AS t2
							ON t2.user_id = t1.user_id
						WHERE t1.id = ". intval($_GET['id']);
						$editMessageResult = mysql_query($editMessageQuery, $connid);
						if (!$editMessageResult) die($lang['db_error']);
						$oldMessage = mysql_fetch_assoc($editMessageResult);
						mysql_free_result($editMessageResult);
						if ($oldMessage['locked'] > 0 &&
						(empty($_SESSION[$settings['session_prefix'].'user_type'])
						|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'admin'
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'mod')))
							{
							$show = "no authorization";
							$reason = $lang['thread_locked_error'];
							}
						else if ($settings['edit_period'] > 0
						&& $oldMessage["edit_diff"] > $oldMessage["time"]
						&& (empty($_SESSION[$settings['session_prefix'].'user_type'])
						|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'admin'
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'mod')))
							{
							$show = "no authorization";
							$reason = str_replace('[minutes]',$settings['edit_period'],$lang['edit_period_over']);
							}
						else
							{
							$show = "form";
							}
						}
					else
						{
						$show = "no authorization";
						}
				break;
				case "delete":
					if ($authorisation['delete'] == 1)
						{
						$deleteQuery = "SELECT
						tid,
						pid,
						UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS tp_time,
						name,
						subject,
						category
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". intval($_GET['id']);
						$deleteResult = mysql_query($deleteQuery, $connid);
						if(!$deleteResult) die($lang['db_error']);
						$deletePosting = mysql_fetch_assoc($deleteResult);
						$show = "delete form";
						}
					else
						{
						$show = "no authorization";
						}
				break;
				case "delete ok":
					if ($authorisation['delete'] == 1)
						{
						# select parent posting
						$parentIdQuery = "SELECT
						pid
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". intval($_GET['id']);
						$parentIdResult = mysql_query($parentIdQuery,$connid);
						if (!$parentIdResult) die($lang['db_error']);
						$parentId = mysql_fetch_assoc($parentIdResult);
						if ($parentId["pid"] == 0)
							{
							$deleteThreadQuery = "DELETE FROM ". $db_settings['forum_table'] ."
							WHERE tid = ". intval($_GET['id']);
							$deleteThreadResult = mysql_query($deleteThreadQuery, $connid);
							}
						else
							{
							$allLastAnswersQuery = "SELECT
							tid,
							time,
							last_answer
							FROM ". $db_settings['forum_table'] ."
							WHERE id = ". intval($_GET['id']);
							$allLastAnswersResult = mysql_query($allLastAnswersQuery, $connid);
							$allLastAnswers = mysql_fetch_assoc($allLastAnswersResult);
							mysql_free_result($allLastAnswersResult);

							# if message is newest in topic:
							if ($allLastAnswers['time'] == $allLastAnswers['last_answer'])
								{
								# search last answer and actualise "last_answer":
								$lastAnswerQuery = "SELECT
								time
								FROM ". $db_settings['forum_table'] ."
								WHERE tid = ". intval($allLastAnswers['tid']) ."
								AND time < '". $allLastAnswers['time'] ."'
								ORDER BY time DESC
								LIMIT 1";
								$lastAnswerResult = mysql_query($lastAnswerQuery, $connid);
								$lastAnswer = mysql_fetch_assoc($lastAnswerResult);
								mysql_free_result($lastAnswerResult);
								$updateLastAnswerQuery = "UPDATE ". $db_settings['forum_table'] ." SET
								time = time,
								last_answer = '". $lastAnswer['time'] ."'
								WHERE tid = ". intval($allLastAnswers['tid']);
								$update_result = mysql_query($updateLastAnswerQuery, $connid);
								}
							# delete message:
							$deleteMessageQuery = "DELETE FROM ". $db_settings['forum_table'] ."
							WHERE id = ". intval($_GET['id']);
							$delete_result = mysql_query($deleteMessageQuery,$connid);
							} # if ($parentId["pid"] == 0) else
						if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
							and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
							{
							if ($_SESSION[$settings['session_prefix'].'user_view'] == 'board'
								or $_SESSION[$settings['session_prefix'].'user_view'] == 'mix')
								{
								$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'.php';
								}
							else
								{
								$header_href = 'forum.php';
								}
							}
						else
							{
							if ($settings['standard'] == 'thread')
								{
								$header_href = 'forum.php';
								}
							else
								{
								$header_href = $settings['standard'] .'.php';
								}
							}
						header('location: '. $settings['forum_address'] . $header_href);
						die('<a href="'. $header_href .'">further...</a>');
						}
					else
						{
						$show = "no authorization";
						}
				break;
				}
			}

		switch ($action)
			{
			case "new":
				$wo = ($postingID == 0) ? $lang['new_entry_marking'] : $lang['answer_marking'];
			break;
			case "edit";
				$wo = $lang['edit_marking'];
			break;
			case "delete";
				$wo = $lang['delete_marking'];
			break;
			}
		$wo = strip_tags($wo);
		if (!empty($oldMessage['name']))
			{
			$lang['back_to_posting_linkname'] = str_replace("[name]", htmlspecialchars($oldMessage['name']), $lang['back_to_posting_linkname']);
			$lang['answer_on_posting_marking'] = str_replace("[name]", htmlspecialchars($oldMessage['name']), $lang['answer_on_posting_marking']);
			}
		$subnav_1 = '';
		if (($action == "new"
		&& ((isset($_GET['id']) and $_GET['id'] > 0)
			or (isset($_POST['id']) and $_POST['id'] > 0)))
		|| $action == "edit"
		|| $action == "delete")
			{
			if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
				and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
				{
				if ($_SESSION[$settings['session_prefix'].'user_view'] == 'board'
					or $_SESSION[$settings['session_prefix'].'user_view'] == 'mix')
					{
					$subnav1['href'] = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php';
					$subnav1['linktext'] = $lang['back_to_topic_linkname'];
					}
				else
					{
					$subnav1['href'] = 'forum_entry.php';
					$subnav1['linktext'] = !empty($oldMessage['name']) ? $lang['back_to_posting_linkname'] : $lang['back_linkname'];
					}
				}
			else
				{
				$subnav1['href'] = ($settings['standard'] == 'thread') ? 'forum_entry.php' : $settings['standard'] .'_entry.php';
				$subnav1['linktext'] = !empty($oldMessage['name']) ? $lang['back_to_posting_linkname'] : $lang['back_linkname'];
				}
			$subnav['href'] .= !empty($_POST['id']) ? '?id='. intval($_POST['id']) : '?id='. intval($_GET['id']);
			}
		else if ($action == "new"
		&& ((isset($_GET['id']) and $_GET['id'] == 0)
			or (isset($_POST['id']) and $_POST['id'] == 0)))
			{
			if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
				and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
				{
				if ($_SESSION[$settings['session_prefix'].'user_view'] == 'board'
					or $_SESSION[$settings['session_prefix'].'user_view'] == 'mix')
					{
					$subnav1['href'] = $_SESSION[$settings['session_prefix'].'user_view'] .'.php';
					}
				else
					{
					$subnav1['href'] = 'forum.php';
					}
				}
			else
				{
				if ($settings['standard'] == 'thread')
					{
					$subnav1['href'] = 'forum.php';
					}
				else
					{
					$subnav1['href'] = $settings['standard'] .'.php';
					}
				}
			$subnav1['linktext'] = $lang['back_to_overview_linkname'];
			}
		else
			{
			if ($settings['standard'] == 'thread')
				{
				$subnav1['href'] = 'forum.php';
				}
			else
				{
				$subnav1['href'] = $settings['standard'] .'.php';
				}
			$subnav1['linktext'] = $lang['back_to_overview_linkname'];
			}
		$subnav_1 = '<a class="textlink" href="'. $subnav1['href'].$subnav['query'] .'">'. htmlspecialchars($subnav1['linktext']) .'</a>';

		parse_template();
		echo $header;
		echo outputDebugSession();
		$output = '';
		switch ($show)
			{
			case "form":
				# generate a captcha in case of not signed on user and setting is on
				if (empty($_SESSION[$settings['session_prefix'].'user_id'])
					&& $settings['captcha_posting'] == 1)
					{
					if ($settings['captcha_type'] == 1)
						{
						$_SESSION['captcha_session'] = $captcha->generate_code();
						}
					else
						{
						$_SESSION['captcha_session'] = $captcha->generate_math_captcha();
						}
					}
				# load template for posting form
				$postingTFile = 'data/templates/posting.new.xml';
				$postingTemplate = simplexml_load_file($postingTFile, null, LIBXML_NOCDATA);
				$tBody = $xml->body;
				# page header
				$addInfo = '';
				if ($action == "edit")
					{
					$pageHeader = $lang['edit_marking'];
					}
				else
					{
					if ((isset($_GET['id']) and $_GET['id'] > 0)
						or (isset($_POST['id']) and $_POST['id'] > 0))
						{
						$pageHeader = $lang['answer_marking'];
						$addInfo = '<p class="postingforma">'.$lang['answer_on_posting_marking'].'</p>';
						}
					else
						{
						$pageHeader = $lang['new_entry_marking'];
						}
					}
				$tBody = str_replace('{pageHeader}', $pageHeader, $tBody);
				$tBody = str_replace('{additionalInfo}', $addInfo, $tBody);
				# error messages, if present:
				$errorMessages = '';
				if (isset($errors))
					{
					$errorMessages = errorMessages($errors);
					}
				$tBody = str_replace('{errorMessages}', $errorMessages, $tBody);
				# preview:
				$previewOutput = '';
				if (isset($preview)
				&& empty($errors))
					{
					if (isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						if ($action != "edit")
							{
							$previewQuery = "SELECT
							user_name,
							user_email,
							hide_email,
							user_hp,
							user_place,
							signature
							FROM ". $db_settings['userdata_table'] ."
							WHERE user_id = '". intval($_SESSION[$settings['session_prefix']."user_id"]) ."'
							LIMIT 1";
							$previewResult = mysql_query($previewQuery, $connid);
							if (!$preview_result) die($lang['db_error']);
							$previewUserData = mysql_fetch_assoc($previewResult);
							mysql_free_result($previewResult);
							} # End: if ($action != "edit")
						} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))
					# load one of the templates for the preview
					if (isset($_SESSION[$settings['session_prefix'].'user_view'])
					and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
						{
						$prTemplate = file_get_contents('data/templates/posting.'. $_SESSION[$settings['session_prefix'].'user_view'] .'.html');
						$isView = $_SESSION[$settings['session_prefix'].'user_view'];
						}
					else if (isset($_COOKIE['user_view'])
					and in_array($_COOKIE['user_view'], $possViews))
						{
						$prTemplate = file_get_contents('data/templates/posting.'. $_COOKIE['user_view'] .'.html');
						$isView = $_COOKIE['user_view'];
						}
					else
						{
						$prTemplate = file_get_contents('data/templates/posting.'. $settings['standard'] .'.html');
						$isView = $settings['standard'];
						}
					$mark['admin'] = false;
					$mark['mod'] = false;
					$mark['user'] = false;
					$prAuthorinfo = outputAuthorInfo($mark, $_POST, 0, 0, $isView);
					$prSubject = htmlspecialchars($subject);
					if (empty($_POST['text']))
						{
						$prText = $lang['no_text'];
						}
					else
						{
						$prText = $_POST['text'];
						$prText = ($settings['autolink'] == 1) ? make_link($prText) : $prText;
						$prText = ($settings['bbcode'] == 1) ? bbcode($prText) : $prText;
						$prText = ($settings['smilies'] == 1) ? smilies($prText) : $prText;
						$prText = zitat($prText);
						}
					if ($show_signature == 1
					&& !empty($previewUserData['signature']))
						{
						$prSignature = $previewUserData['signature'];
						$prSignature = $settings['signature_separator']."\n".$prSignature;
						$prSignature = ($settings['autolink'] == 1) ? make_link($prSignature) : $prSignature;
						$prSignature = ($settings['bbcode'] == 1) ? bbcode($prSignature) : $prSignature;
						$prSignature = ($settings['smilies'] == 1) ? smilies($prSignature) : $prSignature;
						$prSignature = '<div class="signature">'. $prSignature .'</div>'."\n";
						}
					else
						{
						$prSignature = '';
						}
					$prThreadHeadline = ($isView == 'thread') ? $lang['whole_thread_marking'] : '';
					$prThread = ($isView == 'thread') ? '...' : '';
					$prTemplate = str_replace('{postingheadline}', $prSubject, $prTemplate);
					$prTemplate = str_replace('{authorinfo}', $prAuthorinfo, $prTemplate);
					$prTemplate = str_replace('{editmenu}', '', $prTemplate);
					$prTemplate = str_replace('{answer-locked}', '', $prTemplate);
					$prTemplate = str_replace('{posting}', $prText, $prTemplate);
					$prTemplate = str_replace('{signature}', $prSignature, $prTemplate);
					$prTemplate = str_replace('{threadheadline}', $prThreadHeadline, $prTemplate);
					$prTemplate = str_replace('{thread}', $prThread, $prTemplate);
					$prTemplate = str_replace('{postingID}', $entry['user_id'], $prTemplate);
					$prTemplate = ($isView == 'board') ? '<table class="normaltab">'. $prTemplate .'</table>' : $prTemplate;
					$previewOutput .= '<h3 class="caution">'.$lang['preview_headline'].'</h3>'."\n";
					$previewOutput .= $prTemplate;
					$previewOutput .= '<hr class="entryline" />'."\n";
					} # End: if (isset($preview) && empty($errors))
				$tBody = str_replace('{preview}', $previewOutput, $tBody);
				$tBody = str_replace('{postingID}', isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0), $tBody);
				$tBody = str_replace('{postingUniqueID}', uniqid(""), $tBody);
				$tBody = str_replace('{postingAction}', htmlspecialchars($action), $tBody);
				# set parents id if posting should be edited
				$tParentID = '';
				if ($action == 'edit')
					{
					$tParentID = $xml->parentid;
					$tParentID = str_replace('{parentID}', isset($oldMessage['pid']) ? intval($oldMessage['pid']) : (isset($_POST['pid']) ? intval($_POST['pid']) : 0), $tParentID);
					}
				$tBody = str_replace('{fieldPID}', $tParentID, $tBody);
				# set user id of the parent posting if current one should be edited
				$tParentUID = '';
				if (isset($oldMessage['user_id']))
					{
					$tParentUID = $xml->parentuserid;
					$tParentUID = str_replace('{parentUserID}', !empty($oldMessage['user_id']) ? intval($oldMessage['user_id']) : 0, $tParentUID);
					}
				$tBody = str_replace('{fieldUID}', $tParentUID, $tBody);
				# set user name of the parent posting if current one should be edited
				$tParentName = '';
				if (isset($oldMessage['name']))
					{
					$tParentName = $xml->parentname;
					$tParentName = str_replace('{parentName}', htmlspecialchars($oldMessage['name']), $tParentName);
					}
				$tBody = str_replace('{fieldParentName}', $tParentName, $tBody);
				# set thread id if current postingis an answer or should be edited
				$tThreadID = '';
				if (isset($oldMessage['tid']))
					{
					$tThreadID = $xml->threadid;
					$tThreadID = str_replace('{threadID}', intval($oldMessage['tid']), $tThreadID);
					}
				$tBody = str_replace('{fieldTID}', $tThreadID, $tBody);
				# set the session id if necessary
				$tSessionID = '';
				if (empty($_SESSION[$settings['session_prefix'].'user_id'])
					and $settings['captcha_posting'] == 1)
					{
					$tSessionID = $xml->sessionid;
					$tSessionID = str_replace('{sessionName}', session_name(), $tSessionID);
					$tSessionID = str_replace('{sessionID}', session_id(), $tSessionID);
					}
				$tBody = str_replace('{fieldSID}', $tSessionID, $tBody);
				# form fields for a new posting of an unregistered visitor
				# respectively editing a posting of an unregistered visitor
				$tUnreg = '';
				if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
					or ($action == "edit"
					and $oldMessage['user_id'] == 0))
					{
					$tUnreg = $xml->unregistered;
					$tUnreg = str_replace('{markOption}', $lang['optional_marking'], $tUnreg);
					$tUnreg = str_replace('{labelName}', $lang['name_marking'], $tUnreg);
					$tUnreg = str_replace('{labelEmail}', $lang['email_marking'], $tUnreg);
					$tUnreg = str_replace('{labelHomepage}', $lang['hp_marking'], $tUnreg);
					$tUnreg = str_replace('{labelPlace}', $lang['place_marking'], $tUnreg);
					$tUnreg = str_replace('{maxLenName}', intval($settings['name_maxlength']), $tUnreg);
					$tUnreg = str_replace('{maxLenEmail}', intval($settings['email_maxlength']), $tUnreg);
					$tUnreg = str_replace('{maxLenHomepage}', intval($settings['hp_maxlength']), $tUnreg);
					$tUnreg = str_replace('{maxLenPlace}', intval($settings['place_maxlength']), $tUnreg);
					$tUnreg = str_replace('{postingName}', !empty($_POST['name']) : htmlspecialchars($_POST['name']) : '', $tUnreg);
					$tUnreg = str_replace('{postingEmail}', !empty($_POST['email']) : htmlspecialchars($_POST['email']) : '', $tUnreg);
					$tUnreg = str_replace('{postingHomepage}', !empty($_POST['hp']) : htmlspecialchars($_POST['hp']) : '', $tUnreg);
					$tUnreg = str_replace('{postingPlace}', !empty($_POST['place']) : htmlspecialchars($_POST['place']) : '', $tUnreg);
					# cookies controls
					$tCookies = '';
					if ($settings['remember_userdata'] == 1
					&& !isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						$tCookies = $xml->cookies;
						$tCookies = str_replace('{formCheckSetCookie}', (isset($_POST['setcookie']) && $_POST['setcookie'] == 1) ? ' checked="checked"' : '', $tCookies);
						$tCookies = str_replace('{rememberUserData}', $lang['remember_userdata_cbm'], $tCookies);
						# cookies content, if cookies was set before
						$tCookiesDel = '';
						if (isset($_COOKIE['user_name'])
							or isset($_COOKIE['user_email'])
							or isset($_COOKIE['user_hp'])
							or isset($_COOKIE['user_hp']))
							{
							$tCookiesDel = $xml->deletecookies;
							$tCookiesDel = str_replace('{deleteCookieTitle}', outputLangDebugInAttributes($lang['delete_cookies_linktitle']), $tCookiesDel);
							$tCookiesDel = str_replace('{deleteCookieName}', $lang['delete_cookies_linkname'], $tCookiesDel);
							} # End: if (isset($_COOKIE['user_name']) ...)
						$tCookies = str_replace('{deleteExistingCookies}', $tCookiesDel, $tCookies);
						} # End: if ($settings['remember_userdata'] == 1 ...)
					$tUnreg = str_replace('{cookieBlock}', $tCookies, $tUnreg);
					} # End: if (!isset($_SESSION[$settings['session_prefix'].'user_id']) ...)
				$tBody = str_replace('{forUnregistered}', $tUnreg, $tBody);
			break;
			# End: switch ($show)->case "form"
			case "no authorization":
				$output .= '<p class="caution">'. $lang['no_authorization'] .'</p>'."\n";
				if (isset($reason))
					{
					$output .= '<p>'. $reason .'</p>'."\n";
					}
			break;
			# End: switch ($show)->case "no authorization"
			case "delete form":
				$lang['thread_info'] = str_replace("[name]", htmlspecialchars($deletePosting["name"]), $lang['thread_info']);
				$lang['thread_info'] = str_replace("[time]", strftime($lang['time_format'], $deletePosting["tp_time"]), $lang['thread_info']);
				$output .= '<h2>'. $lang['delete_marking'] .'</h2>'."\n";
				$output .= '<p>'. $lang['delete_posting_sure'];
				$output .= ($deletePosting["pid"] == 0) ? '<br />'. $lang['delete_whole_thread'] : '';
				$output .= '</p>'."\n";
				$output .= '<p><b>'. htmlspecialchars($deletePosting["subject"]) .'</b>&nbsp;'. $lang['thread_info'] .'</p>'."\n";
				$output .= '<form action="posting.php" method="get" accept-charset="UTF-8">'."\n";
				$output .= '<input type="hidden" name="action" value="delete ok" />'."\n";
				$output .= '<input type="hidden" name="id" value="'. intval($_GET['id']) .'" />'."\n";
				$output .= '<p><input type="submit" name="delete" value="'. outputLangDebugInAttributes($lang['delete_posting_ok']) .'" /></p>'."\n";
				$output .= '</form>'."\n";
			break;
			# End: switch ($show)->case "delete form"
			}
		echo $output;
		echo $footer;
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
