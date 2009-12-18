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

include_once("inc.php");

if (count($_GET) > 0)
	{
	foreach($_GET as $key => $value)
		{
		$$key = $value;
		}
	}
if (count($_POST) > 0)
	{
	foreach($_POST as $key => $value)
		{
		$$key = $value;
		}
	}

if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

if (isset($_POST['category'])) $category = intval($_POST['category']);
if (isset($_POST['p_category'])) $p_category = intval($_POST['p_category']);

# look for banned user:
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$lockQuery = "SELECT user_lock
	FROM ".$db_settings['userdata_table']."
	WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."'
	LIMIT 1";
	$lock_result = mysql_query($lockQuery, $connid);
	if (!$lock_result) die($lang['db_error']);
	$lock_result_array = mysql_fetch_assoc($lock_result);
	mysql_free_result($lock_result);

	if ($lock_result_array['user_lock'] > 0)
		{
		header("location: ".$settings['forum_address']."user.php");
		die("<a href=\"user.php\">further...</a>");
		}
	} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))

if (isset($_GET['lock'])
&& isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
|| isset($_GET['lock'])
&& isset($_SESSION[$settings['session_prefix'].'user_id'])
&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
	{
	$lockQuery = "SELECT
	tid,
	locked
	FROM ".$db_settings['forum_table']."
	WHERE id = ".intval($id)."
	LIMIT 1";
	$lock_result = mysql_query($lockQuery, $connid);
	if (!$lock_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($lock_result);
	mysql_free_result($lock_result);

	$locker = ($field['locked']==0) ? 1 : 0;
	$relockQuery = "UPDATE ".$db_settings['forum_table']." SET
	time=time,
	last_answer=last_answer,
	edited=edited,
	locked='".$locker."'
	WHERE tid = ".intval($field['tid']);
	@mysql_query($relockQuery, $connid);

	if (empty($page)) $page = 0;
	if (empty($order)) $order = "time";
	if (empty($descasc)) $descasc = "DESC";
	if (isset($_GET['view']))
		{
		if ($view=="board")
			{
			header("location: ".$settings['forum_address']."board_entry.php?id=".$field['tid']."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
			}
		else
			{
			header("location: ".$settings['forum_address']."mix_entry.php?id=".$field['tid']."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
			}
		}
	else
		{
		header("location: ".$settings['forum_address']."forum_entry.php?id=".$id."&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category);
		}
	} # if (isset($_GET['lock']) ...)

if (($settings['access_for_users_only'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name']))
|| $settings['access_for_users_only'] != 1)
	{
	if (($settings['entries_by_users_only'] == 1
	&& isset($_SESSION[$settings['session_prefix'].'user_name']))
	|| $settings['entries_by_users_only'] != 1)
		{
		$categories = get_categories();
		if ($categories == "not accessible")
			{
			header("location: ".$settings['forum_address']."index.php");
			die("<a href=\"index.php\">further...</a>");
			}

		# delete array for error messages
		unset($errors);
		unset($Thread);
		if (empty($descasc)) $descasc="DESC";
		# safety: forbid editing of postings
		$edit_authorization = 0;
		# safety: forbid deletion of postings
		$delete_authorization = 0;

		if (empty($action)) $action = "new";

		# Falls editiert oder gelöscht werden soll, schauen, ob der User dazu berechtigt ist:
		if ($action=="edit" || $action == "delete" || $action == "delete ok")
			{
			$userIdQuery = "SELECT user_id
			FROM ".$db_settings['forum_table']."
			WHERE id = ".intval($id)."
			LIMIT 1";
			$user_id_result = mysql_query($userIdQuery, $connid);
			if (!$user_id_result) die($lang['db_error']);
			$result_array = mysql_fetch_assoc($user_id_result);
			mysql_free_result($user_id_result);

			$userTypeQuery = "SELECT user_type
			FROM ".$db_settings['userdata_table']."
			WHERE user_id = ".intval($result_array["user_id"])."
			LIMIT 1";
			$user_type_result = mysql_query($userTypeQuery, $connid);
			if (!$user_type_result) die($lang['db_error']);
			$user_result_array = mysql_fetch_array($user_type_result);
			mysql_free_result($user_type_result);

			# ist da jemand bekanntes?
			if (isset($_SESSION[$settings['session_prefix'].'user_id']))
				{
				# Admin darf alles:
				if ($_SESSION[$settings['session_prefix'].'user_type'] == "admin")
					{
					$edit_authorization = 1;
					$delete_authorization = 1;
					}
				# Moderator darf alles außer Postings von Admins editieren/löschen:
				else if ($_SESSION[$settings['session_prefix'].'user_type'] == "mod")
					{
					if ($user_result_array["user_type"] != "admin")
						{
						$edit_authorization = 1;
						$delete_authorization = 1;
						}
					}
				# User darf (falls aktiviert) nur seine eigenen Postings editieren/löschen:
				else if ($_SESSION[$settings['session_prefix'].'user_type'] == "user")
					{
					# Schauen, ob es sich um einen eigenen Eintrag handelt:
					if ($result_array["user_id"] == $_SESSION[$settings['session_prefix'].'user_id'])
						{
						if ($settings['user_edit'] == 1) $edit_authorization = 1;
						if ($settings['user_delete'] == 1) $delete_authorization = 1;
						}
					}
				}
			} # Ende Überprüfung der Berechtigung

		# wenn das Formular noch nicht abgeschickt wurde:
		if (empty($form))
			{
			switch ($action)
				{
				case "new":
				# Cookies mit Userdaten einlesen, falls es sich um einen
				# nicht angemeldeten User handelt und Cookies vorhanden sind:
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
					$id = (!isset($id) or $id < 0) ? 0 : (int)$id;

					if (empty($show_signature))
						{
						$show_signature = 1;
						}

					# if message is a reply:
					if ($id != 0)
						{
						$messageQuery = "SELECT
						tid,
						pid,
						name,
						subject,
						category,
						text,
						locked
						FROM ".$db_settings['forum_table']."
						WHERE id = ".intval($id);
						$result = mysql_query($messageQuery, $connid);
						if (!$result) die($lang['db_error']);
						$field = mysql_fetch_assoc($result);
						if (mysql_num_rows($result) != 1)
							{
							$id = 0;
							}
						else
							{
							$thema = $field["tid"];
							$subject = $field["subject"];
							$p_category = $field["category"];
							$text = $field["text"];
							$aname = $field["name"];
							$text = $text;
							# Zitatzeichen an den Anfang jeder Zeile stellen:
							$text = preg_replace("/^/m", $settings['quote_symbol']." ", $text);
#							$text = addslashes($text);
							}
						mysql_free_result($result);

						if ($field['locked'] > 0
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
							$show = "form";
							}
						}
					else
						{
						$show = "form";
						}
				break;

				case "edit":
					if ($edit_authorization == 1)
						{
						# fetch data of message which should be edited:
						$editQuery = "SELECT
						tid,
						pid,
						user_id,
						name,
						email,
						hp,
						place,
						subject,
						category,
						text,
						email_notify,
						show_signature,
						locked,
						fixed,
						UNIX_TIMESTAMP(time) AS time,
						UNIX_TIMESTAMP(NOW() - INTERVAL ".$settings['edit_period']." MINUTE) AS edit_diff
						FROM ".$db_settings['forum_table']."
						WHERE id = ".intval($id);
						$edit_result = mysql_query($editQuery, $connid);
						if (!$edit_result) die($lang['db_error']);
						$field = mysql_fetch_assoc($edit_result);
						mysql_free_result($edit_result);

						$thema = $field["tid"];
						$tid = $field["tid"];
						$pid = $field["pid"];
						$p_user_id = $field["user_id"];
						$name = $field["name"];
						$aname = $field["name"];
						$email = $field["email"];
						$hp = $field["hp"];
						$place = $field["place"];
						$subject = $field["subject"];
						$p_category = $field["category"];
						$text = $field["text"];
						$email_notify = $field["email_notify"];
						$show_signature = $field["show_signature"];
						$fixed = $field["fixed"];
						if ($field['locked'] > 0 &&
						(empty($_SESSION[$settings['session_prefix'].'user_type'])
						|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'admin'
						&& $_SESSION[$settings['session_prefix'].'user_type'] != 'mod')))
							{
							$show = "no authorization";
							$reason = $lang['thread_locked_error'];
							}
						else if ($settings['edit_period'] > 0
						&& $field["edit_diff"] > $field["time"]
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
					if ($delete_authorization == 1)
						{
						$deleteQuery = "SELECT
						tid,
						pid,
						UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS tp_time,
						name,
						subject,
						category
						FROM ".$db_settings['forum_table']."
						WHERE id = ".intval($id);
						$delete_result = mysql_query($deleteQuery, $connid);
						if(!$delete_result) die($lang['db_error']);
						$field = mysql_fetch_assoc($delete_result);
						$aname = $field["name"];
						$thema = $field["tid"];
						$show = "delete form";
						}
					else
						{
						$show = "no authorization";
						}
				break;

				case "delete ok":
					if ($delete_authorization == 1)
						{
						$postingIdQuery = "SELECT pid
						FROM ".$db_settings['forum_table']."
						WHERE id = ".intval($id);
						$pid_result = mysql_query($postingIdQuery,$connid);
						if (!$pid_result) die($lang['db_error']);
						$feld = mysql_fetch_assoc($pid_result);

						if ($feld["pid"] == 0)
							{
							$deleteThreadQuery = "DELETE FROM ".$db_settings['forum_table']."
							WHERE tid = ".intval($id);
							$delete_result = mysql_query($deleteThreadQuery, $connid);
							}
						else
							{
							$allLastAnswersQuery = "SELECT
							tid,
							time,
							last_answer
							FROM ".$db_settings['forum_table']."
							WHERE id = ".intval($id);
							$last_answer_result = mysql_query($allLastAnswersQuery, $connid);
							$field = mysql_fetch_assoc($last_answer_result);
							mysql_free_result($last_answer_result);

							# if message is newest in topic:
							if ($field['time'] == $field['last_answer'])
								{
								# search last answer and actualise "last_answer":
								$lastAnswerQuery = "SELECT
								time
								FROM ".$db_settings['forum_table']."
								WHERE tid = ".intval($field['tid'])."
								AND time < '".$field['time']."'
								ORDER BY time DESC
								LIMIT 1";
								$last_answer_result = mysql_query($lastAnswerQuery, $connid);
								$field2 = mysql_fetch_assoc($last_answer_result);
								mysql_free_result($last_answer_result);
								$updateLastAnswerQuery = "UPDATE ".$db_settings['forum_table']." SET
								time=time,
								last_answer='".$field2['time']."'
								WHERE tid=".intval($field['tid']);
								$update_result = mysql_query($updateLastAnswerQuery, $connid);
								}
							# delete message:
							$deleteMessageQuery = "DELETE FROM ".$db_settings['forum_table']."
							WHERE id = ".intval($id);
							$delete_result = mysql_query($deleteMessageQuery,$connid);
							} # if ($feld["pid"] == 0) else

						if (isset($page) && isset($order) && isset($category) && isset($descasc)) 
							{
							$qs="?page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category;
							}
						else
							{
							$qs = "";
							}

						if(isset($view))
							{
							if ($view=='board')
								{
								header("location: ".$settings['forum_address']."board.php".$qs);
								die("<a href=\"board.php".$qs."\">further...</a>");
								}
							else
								{
								header("location: ".$settings['forum_address']."mix.php".$qs);
								die("<a href=\"mix.php".$qs."\">further...</a>");
								}
							}
						else
							{
							header("location: ".$settings['forum_address']."forum.php".$qs);
							die("<a href=\"forum.php".$qs."\">further...</a>");
							}
						}
					else
						{
						$show = "no authorization";
						}
				break;
				}
			} #if (empty($form))

		# form submitted:
		else if (isset($form))
			{
			$fixed = (empty($_POST['fixed'])) ? 0 : $_POST['fixed'];
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
					if ($id != 0)
						{
						$threadIdQuery = "SELECT
						tid,
						locked
						FROM ".$db_settings['forum_table']."
						WHERE id=".intval($id);
						$tid_result = mysql_query($threadIdQuery, $connid);
						if (!$tid_result) die($lang['db_error']);

						if (mysql_num_rows($tid_result) != 1)
							{
							die($lang['db_error']);
							}
						else
							{
							$field = mysql_fetch_assoc($tid_result);
							$Thread = $field['tid'];
							if ($field['locked'] > 0)
								{
								unset($action);
								$show = "no authorization";
								$reason = $lang['thread_locked_error'];
								}
							}
						mysql_free_result($tid_result);
						}
					else if ($id == 0)
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
					UNIX_TIMESTAMP(NOW() - INTERVAL ".$settings['edit_period']." MINUTE) AS edit_diff
					FROM ".$db_settings['forum_table']."
					WHERE id = ".intval($id);
					$edit_result = mysql_query($postingQuery, $connid);
					if (!$edit_result) die($lang['db_error']);
					$field = mysql_fetch_assoc($edit_result);
					mysql_free_result($edit_result);
					if (empty($name))
						{
						$name = $field["name"];
						}
				break;
				}

			# trim and complete data:
			if (empty($email)) $email = "";
			if (empty($hp)) $hp = "";
			if (empty($place)) $place = "";
			#if (empty($hide_email)) $hide_email = 0;
			if (empty($show_signature)) $show_signature = 0;
			if (empty($user_id)) $user_id = 0;
			if (empty($email_notify)) $email_notify = 0;
			if (empty($p_category)) $p_category = 0;
			if (isset($name)) $name = trim($name);
			if (isset($subject)) $subject = trim($subject);
			if (isset($text)) $text = trim($text);
			if (isset($email)) $email = trim($email);
			if (isset($hp)) $hp = trim($hp);
			if (isset($place)) $place = trim($place);
			# end trim and complete data

			# check data:
			# double entry?
			$uniqueIdQuery = "SELECT COUNT(*)
			FROM ".$db_settings['forum_table']."
			WHERE uniqid = '".$uniqid."'
			AND time > NOW()-10000";
			$uniqid_result = mysql_query($uniqueIdQuery, $connid);
			list($uniqid_count) = mysql_fetch_row($uniqid_result);
			mysql_free_result($uniqid_result);
			if ($uniqid_count > 0)
				{
				header("location: ".$settings['forum_address']."index.php");
				die("<a href=\"index.php\">further...</a>");
				}

			# check for not accepted words:
			$badWordQuery = "SELECT list
			FROM ".$db_settings['banlists_table']."
			WHERE name = 'words'
			LIMIT 1";
			$result = mysql_query($badWordQuery, $connid);
			if (!$result) die($lang['db_error']);
			$data = mysql_fetch_assoc($result);
			mysql_free_result($result);

			if (trim($data['list']) != '')
				{
				$not_accepted_words = explode(',',trim($data['list']));
				foreach ($not_accepted_words as $not_accepted_word)
					{
					if ($not_accepted_word!=''
					&& (preg_match("/".$not_accepted_word."/i",$name)
					|| preg_match("/".$not_accepted_word."/i",$text)
					|| preg_match("/".$not_accepted_word."/i",$subject)
					|| preg_match("/".$not_accepted_word."/i",$email)
					|| preg_match("/".$not_accepted_word."/i",$hp)
					|| preg_match("/".$not_accepted_word."/i",$place)))
						{
						$errors[] = $lang['error_not_accepted_word'];
						break;
						}
					}
				}

			if (!isset($name) || $name == "")
				{
				$errors[] = $lang['error_no_name'];
				}
			# name reserved?
			if (!isset($_SESSION[$settings['session_prefix'].'user_id']))
				{
				$reservedUsernameQuery = "SELECT user_name
				FROM ".$db_settings['userdata_table']."
				WHERE user_name = '".mysql_real_escape_string($name)."'";
				$result = mysql_query($reservedUsernameQuery,$connid);
				if (!$result) die($lang['db_error']);
				$field = mysql_fetch_assoc($result);
				mysql_free_result($result);

				if ($name != "" and strtolower($field["user_name"]) == strtolower($name))
					{
					$lang['error_name_reserved'] = str_replace("[name]", htmlspecialchars($name), $lang['error_name_reserved']);
					$errors[] = $lang['error_name_reserved'];
					}
				}
			# check the given email address for format name@domain.tld
			if (!empty($email) and !preg_match("/^[^@]+@.+\.\D{2,}$/", $email)) 
				{
				$errors[] = $lang['error_email_wrong'];
				}
			# if (!empty($hp) and !preg_match("[hier fehlt noch die Reg-Ex]", $hp))
			# $errors[] = $lang['error_hp_wrong'];
			if (($email == ""
			&& isset($email_notify)
			&& $email_notify == 1
			&& !isset($_SESSION[$settings['session_prefix'].'user_id']))
			|| ($email == ""
			&& isset($email_notify)
			&& $email_notify == 1
			&& isset($p_user_id)
			&& $p_user_id == 0))
				{
				$errors[] = $lang['error_no_email_to_notify'];
				}
			if (empty($subject))
				{
				$errors[] = $lang['error_no_subject'];
				}
			if (empty($settings['empty_postings_possible'])
			|| (isset($settings['empty_postings_possible'])
			&& $settings['empty_postings_possible'] != 1))
				{
				if (empty($text))
					{
					$errors[] = $lang['error_no_text'];
					}
				}
			if (mb_strlen($name) > $settings['name_maxlength'])
				{
				$errors[] = $lang['name_marking']." ".$lang['error_input_too_long'];
				}
			if (mb_strlen($email) > $settings['email_maxlength'])
				{
				$errors[] = $lang['email_marking']." ".$lang['error_input_too_long'];
				}
			if (mb_strlen($hp) > $settings['hp_maxlength'])
				{
				$errors[] = $lang['hp_marking'] . " " .$lang['error_input_too_long'];
				}
			if (mb_strlen($place) > $settings['place_maxlength'])
				{
				$errors[] = $lang['place_marking'] . " " .$lang['error_input_too_long'];
				}
			if (mb_strlen($subject) > $settings['subject_maxlength'])
				{
				$errors[] = $lang['subject_marking'] . " " .$lang['error_input_too_long'];
				}
			if (mb_strlen($text) > $settings['text_maxlength'])
				{
				$lang['error_text_too_long'] = str_replace("[length]", mb_strlen($text), $lang['error_text_too_long']);
				$lang['error_text_too_long'] = str_replace("[maxlength]", $settings['text_maxlength'], $lang['error_text_too_long']);
				$errors[] = $lang['error_text_too_long'];
				}
			$text_arr = explode(" ",$name);
			$countWordsName = count($text_arr);
			for ($i=0;$i<$countWordsName;$i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['name_word_maxlength'])
					{
					$error_nwtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
					$errors[] = $error_nwtl;
					}
				}
			$text_arr = explode(" ",$place);
			$countWordsPlace = count($text_arr);
			for ($i=0;$i<$countWordsPlace;$i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['place_word_maxlength'])
					{
					$error_pwtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['place_word_maxlength']))."...", $lang['error_place_word_too_long']);
					$errors[] = $error_pwtl;
					}
				}
			$text_arr = explode(" ",$subject);
			$countWordsSubject = count($text_arr);
			for ($i=0;$i<$countWordsSubject;$i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['subject_word_maxlength'])
					{
					$error_swtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['subject_word_maxlength']))."...", $lang['error_subject_word_too_long']);
					$errors[] = $error_swtl;
					}
				}
			$text_arr = str_replace("\n", " ", $text);
			if ($settings['bbcode'] == 1)
				{
				$text_arr = preg_replace("#\[b\](.+?)\[/b\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $text_arr);
				$text_arr = preg_replace("#\[link\](.+?)\[/link\]#is", "", $text_arr);
				$text_arr = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2", $text_arr);
				$text_arr = preg_replace("#\[url\](.+?)\[/url\]#is", "", $text_arr);
				$text_arr = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "\\2", $text_arr);
				}
			if ($settings['bbcode_img'] == 1 && $settings['bbcode_img'] == 1)
				{
				$text_arr = preg_replace("#\[img\](.+?)\[/img\]#is", "[img]", $text_arr);
				$text_arr = preg_replace("#\[img-l\](.+?)\[/img\]#is", "[img] ", $text_arr);
				$text_arr = preg_replace("#\[img-r\](.+?)\[/img\]#is", "[img]", $text_arr);
				}
			if ($settings['autolink'] == 1)
				{
				$text_arr = text_check_link($text_arr);
				}
			$text_arr = explode(" ",$text_arr);
			$countWordsText = count($text_arr);
			for ($i=0;$i<$countWordsText;$i++)
				{
				trim($text_arr[$i]);
				$laenge = mb_strlen($text_arr[$i]);
				if ($laenge > $settings['text_word_maxlength'])
					{
					$error_twtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['error_text_word_too_long']);
					$errors[] = $error_twtl;
					}
				}

			# CAPTCHA check:
			if (isset($_POST['save_entry'])
			&& empty($_SESSION[$settings['session_prefix'].'user_id'])
			&& $settings['captcha_posting']==1)
				{
				if($settings['captcha_type']==1)
					{
					if ($captcha->check_captcha($_SESSION['captcha_session'],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				else
					{
					if ($captcha->check_math_captcha($_SESSION['captcha_session'][2],$_POST['captcha_code'])!=TRUE) $errors[] = $lang['captcha_code_invalid'];
					}
				}
			# end check data
			if(empty($errors) && empty($preview) && isset($_POST['save_entry']))
				{
				switch ($action)
					{
					case "new":
						$newPostingQuery = "INSERT INTO ".$db_settings['forum_table']." SET
						pid = ".intval($id).",
						tid = ".intval($Thread).",
						uniqid = '".$uniqid."',
						time = NOW(),
						last_answer = NOW(),
						user_id = ".intval($user_id).",
						name = '".mysql_real_escape_string($name)."',
						subject = '".mysql_real_escape_string($subject)."',
						email = '".mysql_real_escape_string($email)."',
						hp = '".mysql_real_escape_string($hp)."',
						place = '".mysql_real_escape_string($place)."',
						ip = '".$_SERVER["REMOTE_ADDR"]."',
						text = '".mysql_real_escape_string($text)."',
						show_signature = ".intval($show_signature).",
						email_notify = ".intval($email_notify).",
						category = ".intval($p_category).",
						fixed = ".intval($fixed);
						$result = mysql_query($newPostingQuery, $connid);
						if (!$result) die($lang['db_error']);
						# set the thread id for the new thread
						if ($id == 0)
							{
							if (!mysql_query("UPDATE ".$db_settings['forum_table']." SET tid=id, time=time WHERE id = LAST_INSERT_id()", $connid))
								{
								die($lang['db_error']);
								}
							}
						# wann auf Thread als letztes geantwortet wurde aktualisieren (für Board-Ansicht):
						if ($id != 0)
							{
							if (!mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=NOW() WHERE tid=".$Thread, $connid))
								{
								die($lang['db_error']);
								}
							}
						# letzten Eintrag ermitteln (um darauf umzuleiten):
						$redirectQuery = "SELECT
						tid,
						pid,
						id
						FROM ".$db_settings['forum_table']."
						WHERE id = LAST_INSERT_ID()";
						$result_neu = mysql_query($redirectQuery, $connid);
						$neu = mysql_fetch_assoc($result_neu);
						
						$ip = $_SERVER["REMOTE_ADDR"];
						$mail_text = unbbcode($text);

						# Schauen, ob eine E-Mail-Benachrichtigung versendet werden soll:
						if ($settings['email_notification'] == 1)
							{
							$emailUserQuery = "SELECT
							user_id,
							name,
							email,
							subject,
							text,
							email_notify
							FROM ".$db_settings['forum_table']."
							WHERE id = ".intval($id)."
							LIMIT 1";
							$parent_result = mysql_query($emailUserQuery, $connid);
							$parent = mysql_fetch_assoc($parent_result);
							if ($parent["email_notify"] == 1)
								{
								# wenn das Posting von einem registrierten User stammt,
								# E-Mail-Adresse aus den User-Daten holen:
								if ($parent["user_id"] > 0)
									{
									$emailUserIdQuery = "SELECT
									user_name,
									user_email
									FROM ".$db_settings['userdata_table']."
									WHERE user_id = '".intval($parent["user_id"])."'
									LIMIT 1";
									$email_result = mysql_query($emailUserIdQuery, $connid);
									if (!$email_result) die($lang['db_error']);
									$field = mysql_fetch_assoc($email_result);
									mysql_free_result($email_result);

									$parent["name"] = $field["user_name"];
									$parent["email"] = $field["user_email"];
									}
								$emailbody = str_replace("[recipient]", $parent["name"], $lang['email_text']);
								$emailbody = str_replace("[name]", $name, $emailbody);
								$emailbody = str_replace("[subject]", $subject, $emailbody);
								$emailbody = str_replace("[text]", $mail_text, $emailbody);
								if ($settings['standard'] == "board")
									{
									$PostAddress = $settings['forum_address']."board_entry.php?id=".$neu["tid"]."#p".$neu["id"];
									}
								else if ($settings['standard'] == "mix")
									{
									$PostAddress = $settings['forum_address']."mix_entry.php?id=".$neu["tid"]."#p".$neu["id"];
									}
								else
									{
									$PostAddress = $settings['forum_address']."forum_entry.php?id=".$neu["id"];
									}
								$emailbody = str_replace("[posting_address]", $PostAddress, $emailbody);
								$emailbody = str_replace("[original_subject]", $parent["subject"], $emailbody);
								$emailbody = str_replace("[original_text]", $parent["text"], $emailbody);
								$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
								$emailbody = stripslashes($emailbody);
#								$emailbody = str_replace(htmlspecialchars($settings['quote_symbol']), ">", $emailbody);
								$emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
								$header  = "From: ".mb_encode_mimeheader($settings['forum_name'],"UTF-8")." <".$settings['forum_email'].">\n";
								#$header .= "Reply-To: $name <$absender>\n";
								#$header .= "Reply-To: <".$forum_email.">\n";
								$header .= "X-Mailer: Php/" . phpversion(). "\n";
								$header .= "X-Sender-ip: $ip\n";
								$header .= "Content-Type: text/plain; charset=UTF-8\n";
								$header .= "Content-Transfer-Encoding: 8bit\n";
								$an = mb_encode_mimeheader($parent["name"],"UTF-8")." <".$parent["email"].">";
								if ($settings['mail_parameter']!='')
									{
									if (@mail($an, $lang['email_subject'], $emailbody, $header,$settings['mail_parameter']))
										{
										$sent = "ok";
										}
									}
								else
									{
									if (@mail($an, $lang['email_subject'], $emailbody, $header))
										{
										$sent = "ok";
										}
									}
								unset($header);
								unset($emailbody);
								}
							}
						# E-Mail-Benachrichtigung an Admins und Moderatoren:
						if ($id > 0)
							{
							$emailbody = str_replace("[name]", $name, $lang['admin_email_text_reply']);
							}
						else
							{
							$emailbody = str_replace("[name]", $name, $lang['admin_email_text']);
							}
						$emailbody = str_replace("[subject]", $subject, $emailbody);
						$emailbody = str_replace("[text]", $mail_text, $emailbody);
						if ($settings['standard'] == "board")
							{
							$adminPostAddress = $settings['forum_address']."board_entry.php?id=".$neu["tid"]."#p".$neu["id"];
							}
						else if ($settings['standard'] == "mix")
							{
							$adminPostAddress = $settings['forum_address']."mix_entry.php?id=".$neu["tid"]."#p".$neu["id"];
							}
						else
							{
							$adminPostAddress = $settings['forum_address']."forum_entry.php?id=".$neu["id"];
							}
						$emailbody = str_replace("[posting_address]", $adminPostAddress, $emailbody);
						$emailbody = str_replace("[forum_address]", $settings['forum_address'], $emailbody);
						$emailbody = $emailbody;
#						$emailbody = str_replace(htmlspecialchars($settings['quote_symbol']), ">", $emailbody);
						$emailbody = str_replace($settings['quote_symbol'], ">", $emailbody);
						$header  = "From: ".mb_encode_mimeheader($settings['forum_name'],"UTF-8")." <".$settings['forum_email'].">\n";
						$header .= "X-Mailer: Php/" . phpversion(). "\n";
						$header .= "X-Sender-ip: $ip\n";
						$header .= "Content-Type: text/plain charset=UTF-8;\n";
						$header .= "Content-Transfer-Encoding: 8bit\n";
						$adminSubject = mb_encode_mimeheader($lang['admin_email_subject'],"UTF-8");
#						$adminSubject = mb_encode_mimeheader(str_replace("[subject]", stripslashes($subject), $lang['admin_email_subject']),"UTF-8");
						// Schauen, wer eine E-Mail-Benachrichtigung will:
						$en_result = mysql_query("SELECT user_name, user_email FROM ".$db_settings['userdata_table']." WHERE new_posting_notify='1'", $connid);
						if (!$en_result) die($lang['db_error']);
						while ($admin_array = mysql_fetch_assoc($en_result))
							{
							$ind_emailbody = str_replace("[admin]", $admin_array['user_name'], $emailbody);
							$an = mb_encode_mimeheader($admin_array['user_name'],"UTF-8")." <".$admin_array['user_email'].">";
							if ($settings['mail_parameter']!='')
								{
								if (@mail($an, $adminSubject, $ind_emailbody, $header, $settings['mail_parameter']))
									{
									$sent2 = "ok";
									}
								}
							else
								{
								if(@mail($an, $adminSubject, $ind_emailbody, $header))
									{
									$sent2 = "ok";
									}
								}
							}
						mysql_free_result($en_result);

						# Cookies setzen, falls gewünscht und Funktion aktiv:
						if ($settings['remember_userdata'] == 1)
							{
							if (isset($setcookie) && $setcookie==1)
								{
								setcookie("user_name",$name,time()+(3600*24*30));
								setcookie("user_email",$email,time()+(3600*24*30));
								setcookie("user_hp",$hp,time()+(3600*24*30));
								setcookie("user_place",$place,time()+(3600*24*30));
								}
							}

						# for redirect:
						$further_tid = $neu["tid"];
						$further_id = $neu["id"];
						$refer = 1;
					break;

					case "edit":
						if ($edit_authorization == 1
						&& ($field['locked'] == 0
						|| (isset($_SESSION[$settings['session_prefix'].'user_type'])
						&& ($_SESSION[$settings['session_prefix'].'user_type']=='admin'
						|| $_SESSION[$settings['session_prefix'].'user_type']=='mod'))))
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
								name,
								subject,
								text
								FROM ".$db_settings['forum_table']."
								WHERE id = ".intval($id);
								$tid_result = mysql_query($editPostingQuery, $connid);
								if (!$tid_result) die($lang['db_error']);
								$field = mysql_fetch_assoc($tid_result);
								mysql_free_result($tid_result);
								# unnoticed editing for admins and mods:
								if (isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& $_SESSION[$settings['session_prefix'].'user_type']=="admin"
								&& $settings['dont_reg_edit_by_admin']==1
								|| isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& $_SESSION[$settings['session_prefix'].'user_type']=="mod"
								&& $settings['dont_reg_edit_by_mod']==1
								|| ($field['text'] == $text
								&& $field['subject'] == $subject
								&& $field['name'] == $name
								&& isset($_SESSION[$settings['session_prefix'].'user_type'])
								&& ($_SESSION[$settings['session_prefix'].'user_type']=="admin"
								|| $_SESSION[$settings['session_prefix'].'user_type']=="mod")))
									{
									$updatePostingQuery = "UPDATE ".$db_settings['forum_table']." SET
									time = time,
									last_answer = last_answer,
									edited = edited,
									name = '".mysql_real_escape_string($name)."',
									subject = '".mysql_real_escape_string($subject)."',
									category = ".intval($p_category).",
									email = '".mysql_real_escape_string($email)."',
									hp = '".mysql_real_escape_string($hp)."',
									place = '".mysql_real_escape_string($place)."',
									text = '".mysql_real_escape_string($text)."',
									email_notify = '".intval($email_notify)."',
									show_signature = '".intval($show_signature)."',
									fixed = ".intval($fixed)."
									WHERE id = ".intval($id);
									}
								else
									{
									$updatePostingQuery = "UPDATE ".$db_settings['forum_table']." SET
									time = time,
									last_answer = last_answer,
									edited = NOW(),
									edited_by = '".mysql_real_escape_string($_SESSION[$settings['session_prefix']."user_name"])."',
									name = '".mysql_real_escape_string($name)."',
									subject = '".mysql_real_escape_string($subject)."',
									category = ".intval($p_category).",
									email = '".mysql_real_escape_string($email)."',
									hp = '".mysql_real_escape_string($hp)."',
									place = '".mysql_real_escape_string($place)."',
									text = '".mysql_real_escape_string($text)."',
									email_notify = '".intval($email_notify)."',
									show_signature = '".intval($show_signature)."',
									fixed = ".intval($fixed)."
									WHERE id = ".intval($id);
									}
								$posting_update_result = mysql_query($updatePostingQuery, $connid);
								$category_update_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, category=".intval($p_category)." WHERE tid = '".$field["tid"]."'", $connid);

								if (isset($back))
									{
									$further_tid = $back;
									}
								$further_id = $id;
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
				} # Ende "if (empty($errors) && empty($preview) && isset($_POST['save_entry']))"
			else
				{
				$show="form";
				}

			if (isset($refer))
				{
				if (isset($page) && isset($order) && isset($category) && isset($descasc))
					{
					$qs = "&page=".$page."&order=".$order."&descasc=".$descasc."&category=".$category;
					}
				else if (isset($category))
					{
					$qs = "&category=".$category;
					}
				else
					{
					$qs = "";
					}
				if (isset($view) && $view=="board")
					{
					header("location: ".$settings['forum_address']."board_entry.php?id=".$further_tid.$qs);
					die("<a href=\"board_entry.php?id=".$further_tid.$qs."\">further...</a>");
					}
				else if (isset($view) && $view=="mix")
					{
					header("location: ".$settings['forum_address']."mix_entry.php?id=".$further_id.$qs);
					die("<a href=\"mix_entry.php?id=".$further_tid.$qs."\">further...</a>");
					}
				else
					{
					header("location: ".$settings['forum_address']."forum_entry.php?id=".$further_id.$qs);
					die("<a href=\"forum_entry.php?id=".$further_id.$qs."\">further...</a>");
					}
				exit(); # Skript beenden
				}
			} # Ende "if (isset(form))"

		switch ($action)
			{
			case "new":
				$wo = ($id == 0) ? $lang['new_entry_marking'] : $lang['answer_marking'];
			break;
			case "edit";
				$wo = $lang['edit_marking'];
			break;
			case "delete";
				$wo = $lang['delete_marking'];
			break;
			}

		if (isset($aname))
			{
			$lang['back_to_posting_linkname'] = str_replace("[name]", htmlspecialchars(stripslashes($aname)), $lang['back_to_posting_linkname']);
			$lang['answer_on_posting_marking'] = str_replace("[name]", htmlspecialchars(stripslashes($aname)), $lang['answer_on_posting_marking']);
			}

		$subnav_1 = '';
		if ($action == "new" && $id != 0 || $action == "edit" || $action == "delete")
			{
			if (empty($view))
				{
				if (isset($page) && isset($order) && isset($category) && isset($descasc))
					{
					if (isset($aname))
						{
						$subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_posting_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_linkname'].'</a>';
						}
					}
				else
					{
					if (isset($aname))
						{
						$subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;descasc='.$descasc.'">'.$lang['back_to_posting_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="forum_entry.php?id='.$id.'&amp;descasc='.$descasc.'">'.$lang['back_linkname'].'</a>';
						}
					}
				}
			else
				{
				if ($view=="board")
					{
					if (isset($page) && isset($order) && isset($category) && isset($descasc))
						{
						$subnav_1 .= '<a class="textlink" href="board_entry.php?id='.$thema.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="board_entry.php?id='.$thema.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
						}
					}
				else
					{
					if (isset($page) && isset($order) && isset($category) && isset($descasc))
						{
						$subnav_1 .= '<a class="textlink" href="mix_entry.php?id='.$thema.'&amp;page='.$page.'&amp;category='.$category.'&amp;order='.$order.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="mix_entry.php?id='.$thema.'&amp;descasc='.$descasc.'">'.$lang['back_to_topic_linkname'].'</a>';
						}
					}
				}
			}
		else if ($action == "new" && $id == 0)
			{
			if (empty($view))
				{
				if (isset($category))
					{
					$subnav_1 .= '<a class="textlink" href="forum.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
					}
				else
					{
					$subnav_1 .= '<a class="textlink" href="forum.php">'.$lang['back_to_overview_linkname'].'</a>';
					}
				}
			else if (isset($view))
				{
				if ($view=="board")
					{
					if (isset($category))
						{
						$subnav_1 .= '<a class="textlink" href="board.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="board.php">'.$lang['back_to_overview_linkname'].'</a>';
						}
					}
				else
					{
					if (isset($category))
						{
						$subnav_1 .= '<a class="textlink" href="mix.php?category='.$category.'">'.$lang['back_to_overview_linkname'].'</a>';
						}
					else
						{
						$subnav_1 .= '<a class="textlink" href="mix.php">'.$lang['back_to_overview_linkname'].'</a>';
						}
					}
				}
			}

		parse_template();
		echo $header;

		switch ($show)
			{
			case "form":
				if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
					{
					if($settings['captcha_type']==1)
						{
						$_SESSION['captcha_session'] = $captcha->generate_code();
						}
					else
						{
						$_SESSION['captcha_session'] = $captcha->generate_math_captcha();
						}
					}
				# Überschrift:
				if ($action == "new")
					{
					if ($id == 0)
						{
						echo '<h2 class="postingform">'.$lang['new_entry_marking'].'</h2>'."\n";
						}
					else
						{
						echo '<h2 class="postingform">'.$lang['answer_marking'].'</h2>'."\n";
						echo '<p class="postingforma">'.$lang['answer_on_posting_marking'].'</p>'."\n";
						}
					}
				if ($action == "edit")
					{
					echo '<h2 class="postingform">'.$lang['edit_marking'].'</h2>'."\n";
					}

				# error messages, if present:
				if (isset($errors))
					{
					echo errorMessages($errors);
					}

				# preview:
				if (isset($preview) && empty($errors))
					{
					if (isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						if ($action=="edit")
							{
							$pr_id = $p_user_id;
							}
						else
							{
							$pr_id = $_SESSION[$settings['session_prefix']."user_id"];
							}
						$previewQuery = "SELECT
						user_name,
						user_email,
						hide_email,
						user_hp,
						user_place,
						signature
						FROM ".$db_settings['userdata_table']."
						WHERE user_id = '".intval($pr_id)."'
						LIMIT 1";
						$preview_result = mysql_query($previewQuery, $connid);
						if (!$preview_result) die($lang['db_error']);
						$field = mysql_fetch_assoc($preview_result);
						mysql_free_result($preview_result);
						$pr_name = $field["user_name"];
						$pr_email = $field["user_email"];
						$hide_email = $field["hide_email"];
						$pr_hp = $field["user_hp"];
						$pr_place = $field["user_place"];
						$pr_signature = $field["signature"];
						} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))
					if (empty($pr_name)) $pr_name = $name;
					if (empty($pr_email)) $pr_email = $email;
					if (empty($hide_email)) $hide_email = 0;
					if (empty($pr_hp)) $pr_hp = $hp;
					if (empty($pr_place)) $pr_place = $place;
					# current time:
					list($pr_time) = mysql_fetch_row(mysql_query("SELECT UNIX_TIMESTAMP(NOW() + INTERVAL ".$time_difference." HOUR)"));
					echo '<p class="caution">'.$lang['preview_headline'].'</p>'."\n";
					if (isset($view))
						{
						echo '<table class="normaltab">';
						echo '<tr>';
						echo '<td class="autorcell" rowspan="2" valign="top">';
						echo '<b>'.htmlspecialchars($pr_name).'</b><br />';
						if ($pr_hp != "")
							{
							if (substr($pr_hp,0,7) != "http://"
							&& substr($pr_hp,0,8) != "https://"
							&& substr($pr_hp,0,6) != "ftp://"
							&& substr($pr_hp,0,9) != "gopher://"
							&& substr($pr_hp,0,7) != "news://")
								{
								$pr_hp = "http://".$pr_hp;
								}
							echo '<a href="'.$pr_hp.'"><img src="img/homepage.gif" alt="'.$lang['homepage_alt'].'" width="13" height="13" /></a>';
							} # End: if ($pr_hp != "")
						if (($pr_email != ""  && $hide_email != 1) && $pr_hp != "")
							{
							echo "&nbsp;";
							}
						if ($pr_email != "" && $hide_email != 1)
							{
							echo '<a href="contact.php"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="'.str_replace("[name]", htmlspecialchars($pr_name), $lang['email_to_user_linktitle']).'" width="13" height="10" /></a>';
							}
						if (($pr_email != "" && $hide_email != 1) or $pr_hp !="")
							{
							echo "<br />";
							}
						echo "<br />";
						if ($pr_place != "")
							{
							echo htmlspecialchars($pr_place); echo ", <br />";
							}
						echo strftime($lang['time_format'],$pr_time);
						echo '<div class="autorcellwidth">&nbsp;</div></td>';
						echo '<td class="titlecell"><h2>'.htmlspecialchars($subject).'</h2></td>';
						echo '</tr><tr>';
						echo '<td class="postingcell" valign="top">';
						if ($text == "")
							{
							echo $lang['no_text'];
							}
						else
							{
							$pr_text = $text;
							$pr_text = htmlspecialchars($pr_text);
							$pr_text = nl2br($pr_text);
							$pr_text = zitat($pr_text);
							if ($settings['autolink'] == 1)
								{
								$pr_text = make_link($pr_text);
								}
							if ($settings['bbcode'] == 1)
								{
								$pr_text = bbcode($pr_text);
								}
							if ($settings['smilies'] == 1)
								{
								$pr_text = smilies($pr_text);
								}
							echo '<p class="postingboard">'.$pr_text.'</p>'."\n";
							}
						if ($show_signature == 1 && $pr_signature != "")
							{
							$pr_signature = htmlspecialchars($pr_signature);
							$pr_signature = nl2br($pr_signature);
							if ($settings['autolink'] == 1)
								{
								$pr_signature = make_link($pr_signature);
								}
							if ($settings['bbcode'] == 1)
								{
								$pr_signature = bbcode($pr_signature);
								}
							if ($settings['smilies'] == 1)
								{
								$pr_signature = smilies($pr_signature);
								}
							echo '<p class="signature">'.$settings['signature_separator'].$pr_signature.'</p>'."\n";
							}
						echo '</td>'."\n";
						echo '</tr>'."\n";
						echo '</table>'."\n";
						} # End: if (isset($view))
					else
						{
						echo '<div class="preview">'."\n";
						echo '<h2 class="postingheadline">'.htmlspecialchars($subject).'</h2>'."\n";
						$email_hp = "";
						$place_wc = "";
						$place_c = "";
						if (($pr_email != "" && $hide_email != 1) or $pr_hp != "")
							{
							$email_hp = " ";
							}
						if ($pr_hp != "")
							{
							$pr_hp = amendProtocol($pr_hp);
							$email_hp .= '<a href="'.$pr_hp.'" title="'.htmlspecialchars($pr_hp).'"><img src="img/homepage.gif" alt="'.$lang['homepage_alt'].'" width="13" height="13" /></a>';
							}
						if ($pr_email != ""  && $hide_email != 1 && $pr_hp != "")
							{
							$email_hp .= " ";
							}
						if ($pr_email != "" && $hide_email != 1)
							{
							$email_hp .= '<a href="contact.php"><img src="img/email.gif" alt="'.$lang['email_alt'].'" title="';
							$email_hp .= str_replace("[name]", htmlspecialchars($pr_name), $lang['email_to_user_linktitle']);
							$email_hp .= '" width="13" height="10" /></a>';
							}
						if ($pr_place != "")
							{
							$place_c = htmlspecialchars($pr_place) . ", "; $place_wc = htmlspecialchars($pr_place);
							}
						$lang['forum_author_marking'] = str_replace("[name]", htmlspecialchars($pr_name), $lang['forum_author_marking']);
						$lang['forum_author_marking'] = str_replace("[email_hp]", $email_hp, $lang['forum_author_marking']);
						$lang['forum_author_marking'] = str_replace("[place, ]", $place_c, $lang['forum_author_marking']);
						$lang['forum_author_marking'] = str_replace("[place]", $place_wc, $lang['forum_author_marking']);
						$lang['forum_author_marking'] = str_replace("[time]", strftime($lang['time_format'],$pr_time), $lang['forum_author_marking']);
						echo '<p class="author">'.$lang['forum_author_marking'].'</p>'."\n";
						if ($text == "")
							{
							echo $lang['no_text'];
							} # End: if ($text == "")
						else
							{
							$pr_text = $text;
							$pr_text = htmlspecialchars($pr_text);
							$pr_text = nl2br($pr_text);
							$pr_text = zitat($pr_text);
							if ($settings['autolink'] == 1)
								{
								$pr_text = make_link($pr_text);
								}
							if ($settings['bbcode'] == 1)
								{
								$pr_text = bbcode($pr_text);
								}
							if ($settings['smilies'] == 1)
								{
								$pr_text = smilies($pr_text);
								}
							echo '<p class="posting">'.$pr_text.'</p>'."\n";
							} # End: if ($text == "") else
						if ($show_signature == 1 && $pr_signature != "")
							{
							$pr_signature = htmlspecialchars($pr_signature);
							$pr_signature = nl2br($pr_signature);
							if ($settings['autolink'] == 1)
								{
								$pr_signature = make_link($pr_signature);
								}
							if ($settings['bbcode'] == 1)
								{
								$pr_signature = bbcode($pr_signature);
								}
							if ($settings['smilies'] == 1)
								{
								$pr_signature = smilies($pr_signature);
								}
							echo '<p class="signature">'.$settings['signature_separator'].$pr_signature.'</p>'."\n";
							}
						echo '</div>'."\n";
						}
					} # if (isset($preview) && empty($errors))
		# Ende Vorschau
				echo '<form action="posting.php" method="post" id="entryform" accept-charset="UTF-8">';
				if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
					{
					echo '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />'."\n";
					}
				echo '<input type="hidden" name="form" value="true" />'."\n";
				echo '<input type="hidden" name="id" value="'.intval($id).'" />'."\n";
				echo ($action == "edit") ? '<input type="hidden" name="pid" value="'.intval($pid).'" />'."\n" : '';
				echo '<input type="hidden" name="uniqid" value="'.uniqid("").'" />';
				echo '<input type="hidden" name="action" value="'.htmlspecialchars($action).'" />'."\n";
				echo (isset($p_user_id)) ? '<input type="hidden" name="p_user_id" value="'.$p_user_id.'" />'."\n" : '';
				echo (isset($aname)) ? '<input type="hidden" name="aname" value="'.htmlspecialchars($aname).'" />'."\n" : '';
				echo (isset($view)) ? '<input type="hidden" name="view" value="'.$view.'" />'."\n" : '';
				echo (isset($back)) ? '<input type="hidden" name="back" value="'.$back.'" />'."\n" : '';
				echo (isset($thema)) ? '<input type="hidden" name="thema" value="'.$thema.'" />'."\n" : '';
				echo (isset($page)) ? '<input type="hidden" name="page" value="'.$page.'" />'."\n" : '';
				echo (isset($order)) ? '<input type="hidden" name="order" value="'.$order.'" />'."\n" : '';
				echo (isset($descasc)) ? '<input type="hidden" name="descasc" value="'.$descasc.'" />'."\n" : '';
				echo (isset($category)) ? '<input type="hidden" name="category" value="'.$category.'" />'."\n" : '';
				echo '<table class="normal">'."\n";
				# Formularfelder für unbekannte User bzw. wenn
				# Posting unbekannter User editiert wird:
				if (!isset($_SESSION[$settings['session_prefix'].'user_id']) or $action == "edit" && $p_user_id == 0)
					{
					echo '<tr>'."\n";
					echo '<td><b>'.$lang['name_marking'].'</b></td>'."\n";
					echo '<td><input type="text" size="40" name="name" value="';
					echo (isset($name)) ? htmlspecialchars($name) : '';
					echo '" maxlength="'.$settings['name_maxlength'].'" /></td>';
					echo '</tr><tr>'."\n";
					echo '<td><b>'.$lang['email_marking'].'</b></td>'."\n";
					echo '<td><input type="text" size="40" name="email" value="';
					echo (isset($email)) ? htmlspecialchars($email) : '';
					echo '" maxlength="'.$settings['email_maxlength'].'" />&nbsp;';
					echo '<span class="xsmall">'.$lang['optional_marking'].'</span></td>'."\n";
					echo '</tr><tr>'."\n";
					echo '<td><b>'.$lang['hp_marking'].'</b></td>'."\n";
					echo '<td><input type="text" size="40" name="hp" value="';
					echo (isset($hp)) ? htmlspecialchars($hp) : '';
					echo '" maxlength="'.$settings['hp_maxlength'].'&nbsp;';
					echo '<span class="xsmall">'.$lang['optional_marking'].'</span></td>'."\n";
					echo '</tr><tr>'."\n";
					echo '<td><b>'.$lang['place_marking'].'</b></td>'."\n";
					echo '<td><input type="text" size="40" name="place" value="';
					echo (isset($place)) ? htmlspecialchars($place) : '';
					echo '" maxlength="'.$settings['place_maxlength'].'" />&nbsp;';
					echo '<span class="xsmall">'.$lang['optional_marking'].'</span></td>'."\n";
					echo '</tr>';
					if ($settings['remember_userdata'] == 1 && !isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						echo '<tr>'."\n";
						echo '<td>&nbsp;</td><td><span class="small"><input type="checkbox" name="setcookie" value="1"';
						echo (isset($setcookie) && $setcookie == 1) ? ' checked="checked"' : '';
						echo ' />&nbsp;'.$lang['remember_userdata_cbm'];
						if (isset($_COOKIE['user_name'])
						|| isset($_COOKIE['user_email'])
						or isset($_COOKIE['user_hp'])
						or isset($_COOKIE['user_hp']))
							{
							echo '&nbsp;&nbsp;&nbsp;<a onclick="javascript:delete_cookie(); return false;"';
							echo ' href="delete_cookie.php" title="'.$lang['delete_cookies_linktitle'].'"><img border="0"';
							echo ' src="img/dc.gif" name="dc" alt="" width="12" height="9">'.$lang['delete_cookies_linkname'].'</a>';
							}
						echo '</span></td>'."\n";
						echo '</tr>';
						}
					}
				if ($categories != false)
					{
					echo '<tr>'."\n";
					echo '<td><b>'.$lang['category_marking'].'</b></td>'."\n";
					echo '<td><select size="1" name="p_category">'."\n";
					if (empty($id) || $id == 0 || $action=="edit" && isset($pid) && $pid == 0)
						{
						while (list($key, $val) = each($categories))
							{
							if ($key!=0)
								{
								echo '<option value="'.$key.'"';
								if ((isset($category)
								&& $category!=0
								&& $key==$category
								&& empty($p_category))
								|| (isset($p_category)
								&& $key==$p_category))
									{
									echo ' selected="selected"';
									}
								echo '>'.htmlspecialchars($val).'</option>'."\n";
								}
							}
						}
					else
						{
						echo '<option value="'.$p_category.'">';
						if (isset($categories[$p_category]))
							{
							echo $categories[$p_category];
							}
						echo '</option>'."\n";
						}
					echo '</select></td>'."\n";
					echo '</tr>';
					}
				echo '<tr>'."\n";
				echo '<td><b>'.$lang['subject_marking'].'</b></td>'."\n";
				echo '<td><input type="text" size="50" name="subject" value="';
				echo (isset($subject)) ? htmlspecialchars($subject) : '';
				echo '" maxlength="'.$settings['subject_maxlength'].'" /></td>'."\n";
				echo '</tr><tr>'."\n";
				echo '<td colspan="2"><b>'.$lang['text_marking'].'</b>';
				if ($action == "new" && $id != 0)
					{
					echo '&nbsp;&nbsp;<span class="small">'.str_replace('[delete_link]','<a class="sln" href="javascript:clear();">'.$lang['delete_link'].'</a>',$lang['delete_quoted_text']).'</span>';
					}
				echo '</td>'."\n";
				echo '</tr><tr>'."\n";
				echo '<td colspan="2">'."\n";
				echo '<table class="normal" border="0" cellpadding="0" cellspacing="0">'."\n";
				echo '<tr>'."\n".'<td valign="top">'."\n";
				echo '<textarea cols="78" rows="20" name="text">';
				if (isset($text))
					{
					echo htmlspecialchars($text);
					}
				echo '</textarea></td>'."\n";
				echo '<td id="buttonspace">';
				if ($settings['bbcode'] == 1)
					{
					echo '<input class="bbcode-button" style="font-weight: bold;" type="button" name="bold" value="';
					echo $lang['bbcode_bold'].'" title="'.$lang['bbcode_bold_title'].'" onclick="bbcode(\'b\');" /><br />'."\n";
					echo '<input class="bbcode-button" style="font-style: italic;" type="button" name="italic" value="';
					echo $lang['bbcode_italic'].'" title="'.$lang['bbcode_italic_title'].'" onclick="bbcode(\'i\');" /><br />'."\n";
					echo '<input class="bbcode-button" style="color: #0000ff; text-decoration: underline;" type="button" name="link2" value="';
					echo $lang['bbcode_link'].'" title="'.$lang['bbcode_link_title'].'" onclick="';
					echo "insert_link('entryform','text','".$lang['bbcode_link_linktext']."','".$lang['bbcode_link_url']."');\" /><br />\n";
					if ($settings['bbcode_img']==1)
						{
						echo '<input class="bbcode-button" type="button" name="image" value="'.$lang['bbcode_image'].'"';
						echo ' title="'.$lang['bbcode_image_title'].'" onclick="bbcode(\'img\');" /><br />'."\n";
						if ($settings['upload_images']==1)
							{
							echo '<input class="bbcode-button" type="button" name="imgupload" value="';
							echo $lang['upload_image'].'" title="'.$lang['upload_image_title'].'" onclick="upload();" /><br />'."\n";
							}
						}
					echo '<br />'."\n";
					}
				if ($settings['smilies'] == 1)
					{
					$smiley_buttons = 6;
					$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
					list($smilies_count) = mysql_fetch_row($count_result);
					mysql_free_result($count_result);
					$result = mysql_query("SELECT file, code_1, title FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC LIMIT ".$smiley_buttons, $connid);
					$i=1;
					while ($data = mysql_fetch_assoc($result))
						{
						echo '<button class="smiley-button" name="smiley" type="button" value="';
						echo stripslashes($data['code_1']).'" title="'.$lang['smiley_title'].'" onclick="';
						echo "insert('".stripslashes($data['code_1'])."');\">";
						echo '<img src="img/smilies/'.stripslashes($data['file']).'" alt="'.htmlspecialchars($data['code_1']).'" /></button>'."\n";
						if($i % 2 == 0)
							{
							?><br /><?php
							}
						$i++;
						}
					mysql_free_result($result);
					if($smilies_count > $smiley_buttons)
						{
						if($i % 2 == 0)
							{
							?><br /><?php
							}
						echo '<span class="small"><a href="javascript:more_smilies()" title="'.$lang['more_smilies_linktitle'].'">'.$lang['more_smilies_linkname'].'</a></span>'."\n";
						}
					}
				echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";
				echo '</td>'."\n";
				echo '</tr>'."\n";
				if ((isset($_SESSION[$settings['session_prefix'].'user_id'])
				&& $action=="new")
				|| (isset($_SESSION[$settings['session_prefix'].'user_id'])
				&& $action=="edit" && $p_user_id > 0))
					{
					echo '<tr>'."\n";
					echo '<td colspan="2"><input type="checkbox" name="show_signature" value="1"';
					echo (isset($show_signature) && $show_signature==1) ? 'checked="checked"' : '';
					echo ' />&nbsp;'.$lang['show_signature_cbm'].'</td>'."\n";
					echo '</tr>';
					}
				if ($settings['email_notification'] == 1)
					{
					echo '<tr>'."\n";
					echo '<td colspan="2"><input type="checkbox" name="email_notify" value="1"';
					echo (isset($email_notify) && $email_notify==1) ? 'checked="checked"' : '';
					echo ' />&nbsp;'.$lang['email_notification_cbm'].'</td>'."\n";
					echo '</tr>';
					}
				else
					{
					echo '<input type="hidden" name="email_b" value="" />'."\n";
					}
				if (isset($_SESSION[$settings['session_prefix'].'user_type'])
				&& ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
				|| $_SESSION[$settings['session_prefix'].'user_type'] == "mod")
				&& (empty($id) || $id == 0 || $action=="edit" && isset($pid) && $pid == 0))
					{
					echo '<tr>'."\n";
					echo '<td colspan="2"><input type="checkbox" name="fixed" value="1"';
					echo (isset($fixed) && $fixed==1) ? 'checked="checked"' : '';
					echo ' />&nbsp;'.$lang['fix_thread'].'</td>'."\n";
					echo '</tr>';
					}
				if (empty($_SESSION[$settings['session_prefix'].'user_id']) && $settings['captcha_posting']==1)
					{
					echo '<tr>'."\n";
					echo '<td colspan="2"><b>'.$lang['captcha_marking'].'</b></td>'."\n";
					echo '</tr>';
					if($settings['captcha_type']==1)
						{
						echo '<tr>'."\n";
						echo '<td colspan="2"><img class="captcha" src="captcha/captcha_image.php?';
						echo SID.'" alt="'.$lang['captcha_image_alt'].'" width="180" height="40"/></td>'."\n";
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
						echo '<td colspan="2">'.$_SESSION['captcha_session'][0].' + '.$_SESSION['captcha_session'][1].' = ';
						echo '<input type="text" name="captcha_code" value="" size="5" /></td>'."\n";
						echo '</tr>';
						}
					}
				echo '<tr>'."\n";
				echo '<td colspan="2"><input type="submit" name="save_entry" value="';
				echo $lang['submit_button'].'" title="'.$lang['submit_button_title'].'" />&nbsp;';
				echo '<input type="submit" name="preview" value="';
				echo $lang['preview_button'].'" title="'.$lang['preview_button_title'].'" />&nbsp;';
				echo '<input type="reset" value="'.$lang['reset_button'].'" title="'.$lang['reset_button_title'].'" /></td>'."\n";
				echo "</tr>\n</table>\n</form>\n";
				if (!isset($_SESSION[$settings['session_prefix'].'user_id']) || isset($_SESSION[$settings['session_prefix'].'user_id']) && $action=="edit" )
					{
					echo '<p class="xsmall" style="margin-top: 30px;">'.$lang['email_exp'].'</p>'."\n";
					}
			break;
			# End: switch ($show)->case "form"
			case "no authorization":
				echo '<p class="caution">'.$lang['no_authorization'].'</p>'."\n";
				if (isset($reason))
					{
					echo '<p>'.$reason.'</p>'."\n";
					}
			break;
			# End: switch ($show)->case "no authorization"
			case "delete form":
				$lang['thread_info'] = str_replace("[name]", htmlspecialchars($field["name"]), $lang['thread_info']);
				$lang['thread_info'] = str_replace("[time]", strftime($lang['time_format'],$field["tp_time"]), $lang['thread_info']);
				echo '<h2>'.$lang['delete_marking'].'</h2>'."\n";
				echo '<p>'.$lang['delete_posting_sure'];
				echo ($field["pid"]==0) ? '<br />'.$lang['delete_whole_thread'] : '';
				echo '</p>'."\n";
				echo '<p><b>'.htmlspecialchars($field["subject"]).'</b>&nbsp;'.$lang['thread_info'].'</p>'."\n";
				echo '<form action="posting.php" method="post" accept-charset="UTF-8">'."\n";
				echo '<input type="hidden" name="action" value="delete ok" />'."\n";
				echo '<input type="hidden" name="id" value="'.intval($id).'" />'."\n";
				if (isset($view))
					{
					echo '<input type="hidden" name="view" value="'.$view.'" />'."\n";
					}
				if (isset($page))
					{
					echo '<input type="hidden" name="page" value="'.$page.'" />'."\n";
					}
				if (isset($order))
					{
					echo '<input type="hidden" name="order" value="'.$order.'" />'."\n";
					}
				if (isset($descasc))
					{
					echo '<input type="hidden" name="descasc" value="'.$descasc.'" />'."\n";
					}
				if (isset($category))
					{
					echo '<input type="hidden" name="category" value="'.$category.'" />'."\n";
					}
				echo '<p><input type="submit" name="delete" value="'.$lang['delete_posting_ok'].'" /></p>'."\n";
				echo '</form>'."\n";
			break;
			# End: switch ($show)->case "delete form"
			}
		echo $footer;
		} # End: if (($settings['entries_by_users_only'] == 1 ...)
	else
		{
		header("location: ".$settings['forum_address']."login.php?msg=noentry"); die("<a href=\"login.php?msg=noentry\">further...</a>");
		}
	} # End: if (($settings['access_for_users_only'] == 1 ...)
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess"); die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>