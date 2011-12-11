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

include("lang/english_add.php");
$lang_add = outputLangDebugOrNot($lang_add, "english_add.php");
include("lang/".strip_tags($lang['additional_language_file']));
$lang_add = outputLangDebugOrNot($lang_add, strip_tags($lang['additional_language_file']));




if (isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_SESSION[$settings['session_prefix'].'user_type'])
&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
	{

	// remove not activated user accounts:
	$delInactiveUserQuery = "DELETE FROM ".$db_settings['userdata_table']."
	WHERE registered < (NOW() - INTERVAL 24 HOUR)
	AND activate_code != ''
	AND logins=0";
	@mysql_query($delInactiveUserQuery, $connid);

	unset($errors);
	if (isset($_GET['action'])) $action = $_GET['action'];
	if (isset($_POST['action'])) $action = $_POST['action'];

// SQL-Dump:
if (isset($_GET['backup']))
	{
?><html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php echo $settings['forum_name']; ?> - SQL</title>
</head>
<body>
<?php
	switch ($_GET['backup'])
		{
		case 1:
			sql_forum();
			sql_categories();
			sql_userdata();
			sql_settings();
			sql_smilies();
			sql_banlists();
		break;
		case 2:
			sql_forum();
		break;
		case 3:
			sql_forum_marked();
		break;
		case 4:
			sql_userdata();
		break;
		case 5:
			sql_categories();
		break;
		case 6:
			sql_settings();
		break;
		case 7:
			sql_smilies();
		break;
		case 8:
			sql_banlists();
		break;
		}
?></body>
</html><?php
	exit;
	}

if(isset($_POST['sql_submit']))
	{
	$sql = $_POST['sql'];
	$passUserQuery = "SELECT
	user_pw
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_id = '". $_SESSION[$settings['session_prefix'].'user_id'] ."'
	LIMIT 1";

	$pw_result = mysql_query($passUserQuery, $connid);
	if (!$pw_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($pw_result);
	mysql_free_result($pw_result);

	if ($_POST['sql_pw']=='')
		{
		$errors[] = $lang['error_form_uncompl'];
		}
	else
		{
		if ($field['user_pw'] != md5(trim($_POST['sql_pw'])))
			{
			$errors[] = $lang['pw_wrong'];
			}
		}

	if (empty($errors))
		{
		$sql_querys = split_sql($sql);
		foreach ($sql_querys as $sql_query)
			{
			#echo $sql_query.'<br />';
			mysql_query($sql_query, $connid) or $errors[] = $lang_add['mysql_error'] . mysql_error($connid);
			if (isset($errors)) break;
			}	
		if (empty($errors))
			{
			$action = 'import_sql_ok';
			}
		else
			{
			$action='import_sql';
			}
		}
	else
		{
		$action='import_sql';
		}
	}

if (isset($_GET['mark']))
	{
	$getMarkedQuery = "SELECT
	marked
	FROM ". $db_settings['forum_table'] ."
	WHERE id='". $_GET['mark'] ."'
	LIMIT 1";
	$mark_result = mysql_query($getMarkedQuery, $connid);
	if (!$mark_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($mark_result);
	mysql_free_result($mark_result);
	if ($field['marked']==0) $marked = 1; else $marked = 0;
	$setMarkedQuery = "UPDATE ".$db_settings['forum_table']." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	marked = '".$marked."'
	WHERE tid = '". $_GET['mark'] ."'";

	mysql_query($setMarkedQuery, $connid);
	$url = $_GET['refer']."?id=".$_GET['mark']."&category=".$_GET['category']."&page=".$_GET['page']."&order=".$_GET['order'];
	header('Location: '.$url);
	die('<a href="'.$url.'">further...</a>');
	}

if (isset($_POST['new_category']))
	{
	$new_category = trim($_POST['new_category']);
	$new_category = str_replace('"','\'',$new_category);
	$accession = intval($_POST['accession']);
	if($new_category!='')
		{
		#if(preg_match("/\"/i",$new_category) || preg_match("/</i",$new_category) || preg_match("/>/i",$new_category)) $errors[] = $lang_add['category_invalid_chars'];

		# does this category already exist?
		$searchForCategoryQuery = "SELECT
		category
		FROM ".$db_settings['category_table']."
		WHERE category = '". mysql_real_escape_string($new_category) ."'
		LIMIT 1";
		$category_result = mysql_query($searchForCategoryQuery, $connid);
		if (!$category_result) die($lang['db_error']);
		$field = mysql_fetch_assoc($category_result);
		mysql_free_result($category_result);

		if (mb_strtolower($field["category"]) == mb_strtolower($new_category)) $errors[] = $lang_add['category_already_exists'];

		if(empty($errors))
			{
			$countCategoriesQuery = "SELECT
			COUNT(*)
			FROM ".$db_settings['category_table'];
			$count_result = mysql_query($countCategoriesQuery, $connid);
			list($category_count) = mysql_fetch_row($count_result);
			mysql_free_result($count_result);
			$saveNewCategoryQuery = "INSERT INTO ".$db_settings['category_table']." SET
			category_order = ". $category_count ."+1,
			category = '". mysql_real_escape_string($new_category) ."',
			accession = ". $accession;
			mysql_query($saveNewCategoryQuery, $connid);
			header("location: ".$settings['forum_address']."admin.php?action=categories");
			exit();
			}
		}
	$action='categories';
	}

if (isset($_GET['edit_user']))
	{
	$edit_user_id = intval($_GET['edit_user']);
	$getOneUserQuery = "SELECT
	user_type,
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
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_id = '". $edit_user_id ."'";
	$result = mysql_query($getOneUserQuery, $connid) or die($lang['db_error']);
	$field = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$edit_user_type = $field["user_type"];
	$user_email = $field["user_email"];
	$hide_email = $field["hide_email"];
	$edit_user_name = $field["user_name"];
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
	$action = 'edit_user';
	}

if (isset($_POST['edit_user_submit']))
	{
	# import posted data:
	$edit_user_id = intval($_POST['edit_user_id']);
	$edit_user_name = trim($_POST['edit_user_name']);
	$edit_user_type = trim($_POST['edit_user_type']);
	$user_email = trim($_POST['user_email']);
	$hide_email = trim($_POST["hide_email"]);
	$user_real_name = trim($_POST['user_real_name']);
	$user_hp = trim($_POST['user_hp']);
	$user_place = trim($_POST['user_place']);
	$profile = trim($_POST['profile']);
	$signature = trim($_POST['signature']);
	$user_view = trim($_POST['user_view']);
	$personal_messages = trim($_POST['personal_messages']);
	$user_time_difference = trim($_POST['user_time_difference']);
	if (isset($_POST['new_posting_notify']))
		{
		$new_posting_notify = trim($_POST['new_posting_notify']);
		}
	else
		{
		$new_posting_notify = 0;
		}
	if (isset($_POST['new_user_notify']))
		{
		$new_user_notify = trim($_POST['new_user_notify']);
		}
	else
		{
		$new_user_notify = 0;
		}

	# check data:
	if (empty($user_view) or $user_view == '')
		{
		$user_view = $standard;
		}
	# does the name already exist?
	$doesNameExistsQuery = "SELECT
	user_id,
	user_name
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_name = '". mysql_real_escape_string($edit_user_name) ."'";
	$name_result = mysql_query($doesNameExistsQuery, $connid) or die($lang['db_error']);
	$field = mysql_fetch_assoc($name_result);
	mysql_free_result($name_result);
	if ($edit_user_id != $field['user_id']
	&& mb_strtolower($field["user_name"]) == mb_strtolower($edit_user_name))
		{
		$errors[] = str_replace("[name]", htmlspecialchars($edit_user_name), $lang['error_name_reserved']);
		}
	if (mb_strlen($user_real_name) > $settings['name_maxlength'])
		{
		$errors[] = $lang['user_real_name']." ".$lang['error_input_too_long'];
		}
	if (mb_strlen($user_hp) > $settings['hp_maxlength'])
		{
		$errors[] = $lang['user_hp']." ".$lang['error_input_too_long'];
		}
	if (mb_strlen($user_place) > $settings['place_maxlength'])
		{
		$errors[] = $lang['user_place']." ".$lang['error_input_too_long'];
		}
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
			$error_twtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_prof_word_too_long']);
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
	for ($i=0;$i<count($text_arr);$i++)
		{
		trim($text_arr[$i]);
		$laenge = strlen($text_arr[$i]);
		if ($laenge > $settings['text_word_maxlength'])
			{
			$error_twtl = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$settings['text_word_maxlength']))."...", $lang['err_sig_word_too_long']);
			$errors[] = $error_twtl;
			}
		}
	# end of checking

	# save if no errors:
	if (empty($errors))
		{
		$updateUserDataQuery = "UPDATE ".$db_settings['userdata_table']." SET
		user_name = '". mysql_real_escape_string($edit_user_name) ."',
		user_type = '". mysql_real_escape_string($edit_user_type) ."',
		user_email = '". mysql_real_escape_string($user_email) ."',
		user_real_name = '". mysql_real_escape_string($user_real_name) ."',
		hide_email = '". intval($hide_email) ."',
		user_hp = '". mysql_real_escape_string($user_hp) ."',
		user_place = '". mysql_real_escape_string($user_place) ."',
		profile = '". mysql_real_escape_string($profile) ."',
		signature = '". mysql_real_escape_string($signature) ."',
		last_login = last_login,
		registered = registered,
		user_view = '". mysql_real_escape_string($user_view) ."',
		new_posting_notify = '". intval($new_posting_notify) ."',
		new_user_notify = '". intval($new_user_notify) ."',
		personal_messages = '". intval($personal_messages) ."',
		time_difference = '". intval($user_time_difference) ."'
		WHERE user_id = ". intval($edit_user_id);
		@mysql_query($updateUserDataQuery, $connid) or die($lang['db_error']);
		$updateUserNameInPostings = "UPDATE ". $db_settings['forum_table'] ." SET
		time = time,
		last_answer = last_answer,
		edited = edited,
		name = '". mysql_real_escape_string($edit_user_name) ."'
		WHERE user_id = ". $edit_user_id;
		@mysql_query($updateUserNameInPostings, $connid);
		header('location: '.$settings['forum_address'].'admin.php?action=user');
		die('<a href="admin.php?action=user">further...</a>');
		}
	$action = 'edit_user';
	}

if (isset($_GET['edit_category']))
	{
	$selectCategoryData = "SELECT
	id,
	category_order,
	category,
	accession FROM ". $db_settings['category_table'] ."
	WHERE id = '". intval($_GET['edit_category']) ."'
	LIMIT 1";
	$category_result = mysql_query($selectCategoryData, $connid);
	if (!$category_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($category_result);
	mysql_free_result($category_result);

	$id = $field['id'];
	$category = $field['category'];
	$accession = $field['accession'];
	$action = "edit_category";
	}

if (isset($_GET['delete_category']))
	{
	$categoryDeleteQuery = "SELECT
	id,
	category
	FROM ". $db_settings['category_table'] ."
	WHERE id = '". intval($_GET['delete_category']) ."'
	LIMIT 1";
	$category_result = mysql_query($categoryDeleteQuery, $connid);
	if (!$category_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($category_result);
	mysql_free_result($category_result);

	$category_id = $field['id'];
	$category_name = $field['category'];
	$action = "delete_category";
	}

if (isset($_POST['edit_category_submit']))
	{
	$id = intval($_POST['id']);
	$category = trim($_POST['category']);
	$category = str_replace('"','\'',$category);
	$accession = intval($_POST['accession']);
	# does this category already exist?
	$categoryExistsQuery = "SELECT
	COUNT(*)
	FROM ". $db_settings['category_table'] ."
	WHERE category LIKE '". mysql_real_escape_string($category) ."'
	AND id != ". intval($id);
	$count_result = mysql_query($categoryExistsQuery, $connid);
	if (!$count_result) die($lang['db_error']);
	list($category_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);

	if ($category_count > 0) $errors[] = $lang_add['category_already_exists'];
	if (empty($errors))
		{
		$editCategoryQuery = "UPDATE ". $db_settings['category_table'] ." SET
		category='". mysql_real_escape_string($category) ."',
		accession=". $accession ."
		WHERE id=". intval($id);
		mysql_query($editCategoryQuery, $connid);
		header("location: ".$settings['forum_address']."admin.php?action=categories");
		die();
		}
	$action = 'edit_category';
	} # End: if (isset($_POST['edit_category_submit']))

if (isset($_POST['not_displayed_entries_submit']))
	{
	if ($_POST['mode'] == "delete")
		{
		$delEntriesinInvalidCatQuery = "DELETE FROM ". $db_settings['forum_table'] ."
		WHERE category";
		if (isset($category_ids_query))
			{
			$delEntriesinInvalidCatQuery .= " NOT IN (". $category_ids_query .")";
			}
		else
			{
			$delEntriesinInvalidCatQuery .= " != 0";
			}
		@mysql_query($delEntriesinInvalidCatQuery, $connid);
		}
	else
		{
		if(isset($category_ids_query))
			{
			mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category NOT IN (".$category_ids_query.")", $connid);
			}
		else
			{
			mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category != 0", $connid);
			}
		}
	header("location: ".$settings['forum_address']."admin.php?action=categories");
	die();
	}

if (isset($_GET['move_up_category']))
	{
	$category_result = mysql_query("SELECT category_order FROM ".$db_settings['category_table']." WHERE id = ".$_GET['move_up_category']." LIMIT 1", $connid);
	if (!$category_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($category_result);
	mysql_free_result($category_result);
	if ($field['category_order'] > 1)
		{
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=0 WHERE category_order=".$field['category_order']."-1", $connid);
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=category_order-1 WHERE category_order=".$field['category_order'], $connid);
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$field['category_order']." WHERE category_order=0", $connid);
		}
	header("location: ".$settings['forum_address']."admin.php?action=categories");
	die();
	}

if (isset($_GET['move_down_category']))
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
	list($category_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);

	$category_result = mysql_query("SELECT category_order FROM ".$db_settings['category_table']." WHERE id = ".$_GET['move_down_category']." LIMIT 1", $connid);
	if (!$category_result) die($lang['db_error']);
	$field = mysql_fetch_array($category_result);
	mysql_free_result($category_result);
	if ($field['category_order'] < $category_count)
		{
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=0 WHERE category_order=".$field['category_order']."+1", $connid);
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=category_order+1 WHERE category_order=".$field['category_order'], $connid);
		mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$field['category_order']." WHERE category_order=0", $connid);
		}
	header("location: ".$settings['forum_address']."admin.php?action=categories");
	die();
	}

if (isset($_POST['delete_category_submit']))
	{
	$category_id = intval($_POST['category_id']);
	if($category_id > 0)
		{
		# delete category from category table:
		mysql_query("DELETE FROM ".$db_settings['category_table']." WHERE id=".$category_id, $connid);
		# reset order:
		$result = mysql_query("SELECT id FROM ".$db_settings['category_table']." ORDER BY category_order ASC", $connid);
		$i=1;
		while ($data = mysql_fetch_assoc($result))
			{
			mysql_query("UPDATE ".$db_settings['category_table']." SET category_order=".$i." WHERE id = ".intval($data['id']), $connid);
			$i++;
			}
		mysql_free_result($result);

		# what to to with the entries of deleted category:
		if ($_POST['delete_mode'] == "complete")
			{
			mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category = ".$category_id, $connid);
			}
		else
			{
			mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, category=".intval($_POST['move_category'])." WHERE category = ".$category_id, $connid);
			}
		header("location: ".$settings['forum_address']."admin.php?action=categories");
		die();
		}
	$action = 'categories';
	}


/**
 * sets debug type (standard: no)
 */
if (isset($_POST['debug_submitted'])) {
	$_SESSION['debug'] = $_POST['debug_type'];
	$action = 'debug';
	}


if (isset($_GET['delete_user']))
	{
	$user_id = intval($_GET['delete_user']);
	$user_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_id='".$user_id."' LIMIT 1", $connid);
	if (!$user_result) die($lang['db_error']);
	$user = mysql_fetch_assoc($user_result);
	mysql_free_result($user_result);
	$selected[] = $user_id;
	$selected_usernames[] = $user["user_name"];
	$action="delete_users_sure";
	}


if (isset($_POST['delete_user']))
	{
	if (isset($_POST['selected']))
		{
		$selected = $_POST['selected'];
		for ($x=0; $x<count($selected); $x++)
			{
			$user_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_id='".$selected[$x]."' LIMIT 1", $connid);
			if (!$user_result) die($lang['db_error']);
			$user = mysql_fetch_assoc($user_result);
			mysql_free_result($user_result);
			$selected_usernames[] = $user["user_name"];
			}
		$action="delete_users_sure";
		}
	else
		{
		$action="user";
		}
	}

if (isset($_POST['clear_userdata']))
	{
	switch ($_POST['clear_userdata'])
		{
		case 1:
			$clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND logins=0 AND registered<(NOW()-INTERVAL 2 DAY) ORDER BY user_name", $connid);
		break;
		case 2:
			$clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (logins<=1 AND last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
		break;
		case 3:
			$clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (logins<=3 AND last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
		break;
		case 4:
			$clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (last_login<(NOW()-INTERVAL 60 DAY))) ORDER BY user_name", $connid);
		break;
		case 5:
			$clear_result = mysql_query("SELECT user_id, user_name FROM ".$db_settings['userdata_table']." WHERE user_type != 'admin' AND user_type != 'mod' AND ((logins=0 AND registered<(NOW()-INTERVAL 2 DAY)) OR (last_login<(NOW()-INTERVAL 30 DAY))) ORDER BY user_name", $connid);
		break;
		}
	if (!$clear_result) die($lang['db_error']);
	while ($line = mysql_fetch_assoc($clear_result))
		{
		$selected_usernames[] = $line['user_name'];
		$selected[] = $line['user_id'];
		}
	mysql_free_result($clear_result);
	if (isset($selected))
		{
		$action="delete_users_sure";
		}
	else
		{
		$no_users_in_selection = true;
		$action="user";
		}
	}

if (isset($_POST['email_list'])) $action="email_list";

if (isset($_POST['delete_confirmed']))
	{
	if (isset($_POST['selected_confirmed']))
		{
		$selected_confirmed = $_POST['selected_confirmed'];
		for ($x=0; $x<count($selected_confirmed); $x++)
			{
			$delete_result = mysql_query("DELETE FROM ".$db_settings['userdata_table']." WHERE user_id = '".$selected_confirmed[$x]."'",$connid);
			$update_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, user_id='0', email_notify='0' WHERE user_id = '".$selected_confirmed[$x]."'", $connid);
			}
		}
	$action="user";
	}

if (isset($_GET['user_lock']))
	{
	$lock_result = mysql_query("SELECT user_lock FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_GET['user_lock']."' LIMIT 1", $connid);
	if (!$lock_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($lock_result);
	mysql_free_result($lock_result);
	$new_lock = ($field['user_lock'] == 0) ? 1 : 0;
	$updateUserLockQuery = "UPDATE ".$db_settings['userdata_table']." SET
	user_lock='".$new_lock."',
	last_login=last_login,
	registered=registered
	WHERE user_id='".intval($_GET['user_lock'])."'
	LIMIT 1";
	$update_result = mysql_query($updateUserLockQuery, $connid);
	$action="user";
	}

if (isset($_POST['delete_all_postings_confirmed']))
	{
	$pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
	if (!$pw_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($pw_result);
	mysql_free_result($pw_result);
	if ($_POST['delete_all_postings_confirm_pw']=="")
		{
		$errors[] = $lang['error_form_uncompl'];
		}
	else
		{
		if ($field['user_pw'] != md5(trim($_POST['delete_all_postings_confirm_pw'])))
			{
			$errors[] = $lang['pw_wrong'];
			}
		}
	if (empty($errors))
		{
		$empty_forum_result = mysql_query("DELETE FROM ".$db_settings['forum_table'], $connid);
		if (!$empty_forum_result) die($lang['db_error']);
		$action="main";
		}
	else
		{
		$action="empty";
		}
	}

if (isset($_POST['delete_db_confirmed']))
	{
	$pw_result = mysql_query("SELECT user_pw FROM ".$db_settings['userdata_table']." WHERE user_id = '".$_SESSION[$settings['session_prefix'].'user_id']."' LIMIT 1", $connid);
	if (!$pw_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($pw_result);
	mysql_free_result($pw_result);
	if ($_POST['delete_db_confirm_pw']=="" || empty($_POST['delete_modus']))
		{
		$errors[] = $lang['error_form_uncompl'];
		}
	else
		{
		if ($field['user_pw'] != md5(trim($_POST['delete_db_confirm_pw'])))
			{
			$errors[] = $lang['pw_wrong'];
			}
		}
	if (empty($errors))
		{
		echo '<pre>'."\n";
		echo 'Deleting table <b>'.$db_settings['forum_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['forum_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['userdata_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['userdata_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['useronline_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['useronline_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['settings_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['settings_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['category_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['category_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['smilies_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['smilies_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		echo 'Deleting table <b>'.$db_settings['banlists_table'].'</b>... ';
		if (mysql_query("DROP TABLE ".$db_settings['banlists_table'], $connid))
			{
			echo '<b style="color:green;">OK</b><br />';
			}
		else
			{
			$errors[] = mysql_error($connid);
			echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
			echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
			}
		if (empty($errors))
			{
			echo '<br /><b>'.$lang_add['tables_deleted'].'</b>';
			}
		else
			{
			echo '<br /><b>'.$lang_add['tables_deleted_error'].'</b>';
			}

		if ($_POST['delete_modus'] == "db")
			{
			unset($errors);
			echo '<br /><br />Deleting database <b>'.$db_settings['db'].'</b>... ';
			$result = mysql_list_tables($db_settings['db'],$connid);
			if (mysql_num_rows($result) == 0)
				{
				if (mysql_query("DROP DATABASE ".$db_settings['db'], $connid))
					{
					echo '<b style="color:green;">OK</b><br />';
					}
				else
					{
					$errors[] = mysql_error($connid);
					echo '<b style="color:red;">FAILED</b> (MySQL: '.mysql_errno($connid);
					echo '<br />'.mysql_error($connid).')<br /><br />'."\n";
					}
				}
			else
				{
				$errors[] = 'DB not empty';
				echo '<b style="color:red;">FAILED</b> (there are still tables in the database)<br />';
				}
			if (empty($errors))
				{
				echo '<br /><b>'.$lang_add['db_deleted'].'</b>';
				}
			else
				{
				echo '<br /><b>'.$lang_add['db_deleted_error'].'</b>';
				}
			}
		echo '</pre>';
		die();
		}
	$action="uninstall";
	}

if (isset($_POST['delete_marked_threads_confirmed']))
	{
	$del_marked_result = mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE marked='1'", $connid);
	if (!$del_marked_result) die($lang['db_error']);
	if (isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['unmark_confirmed']))
	{
	$remove_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='0'", $connid);
	if (!$remove_markings_result) die($lang['db_error']);
	if(isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['invert_markings_confirmed']))
	{
	$invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='2' WHERE marked='1'", $connid);
	$invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE marked='0'", $connid);
	$invert_markings_result = mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='0' WHERE marked='2'", $connid);
	if (isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['mark_threads_submitted']))
	{
	if($_POST['mark_threads'] == 1)
		{
		$limit = intval($_POST['n1'])-1;
		}
	else if($_POST['mark_threads'] == 2)
		{
		$limit = intval($_POST['n2'])-1;
		}
	if($limit >= 0)
		{
		# letzten Thread ermitteln, der nicht markiert werden soll:
		$mot_result =  mysql_query("SELECT tid FROM ".$db_settings['forum_table']." WHERE pid = '0' ORDER BY id DESC LIMIT ".$limit.", 1", $connid);
		if (!$mot_result) die($lang['db_error']);
		$field = mysql_fetch_assoc($mot_result);
		$last_thread = $field['tid'];
		mysql_free_result($mot_result);
		# ...und alle älteren markieren:
		if ($_POST['mark_threads'] == 1)
			{
			mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE tid < ".$last_thread, $connid);
			}
		if ($_POST['mark_threads'] == 2)
			{
			mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, marked='1' WHERE tid < ".$last_thread." AND time=last_answer", $connid);
			}
		}
	if (isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['lock_marked_threads_submitted']))
	{
	mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, locked='1' WHERE marked='1'", $connid);
	if (isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['unlock_marked_threads_submitted']))
	{
	mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, locked='0' WHERE marked='1'", $connid);
	if (isset($_POST['refer']))
		{
		$headerRefer = ($_POST['refer'] == 'board') ? 'board.php' : 'mix.php';
		}
	else
		{
		$headerRefer = 'forum.php';
		}
	header('Location: '.$settings['forum_address'].$headerRefer);
	die();
	}

if (isset($_POST['settings_submitted']))
	{
	# not checked checkboxes:
	if (empty($_POST['captcha_posting'])) $_POST['captcha_posting'] = 0;
	if (empty($_POST['captcha_contact'])) $_POST['captcha_contact'] = 0;
	if (empty($_POST['captcha_register'])) $_POST['captcha_register'] = 0;
	while(list($key, $val) = each($_POST))
		{
		if ($key != "settings_submitted")
			{
			mysql_query("UPDATE ".$db_settings['settings_table']." SET value='".$val."' WHERE name='".$key."' LIMIT 1", $connid);
			}
		}
	header('Location: '.$settings['forum_address'].'admin.php');
	die('<a href="admin.php">further...</a>');
	}

if (isset($_POST['ar_username']))
	{
	if (isset($_POST['ar_send_userdata']) && $_POST['ar_send_userdata'] != '')
		{
		$ar_send_userdata = true;
		}
	# überflüssige Leerzeichen abschneiden:
	$ar_username = trim($_POST['ar_username']);
	$ar_email = trim($_POST['ar_email']);
	$ar_pw = trim($_POST['ar_pw']);
	$ar_pw_conf = trim($_POST['ar_pw_conf']);
	# Any empty fields?
	if ($ar_username=="" or $ar_email=="")
		{
		$errors[] = $lang['error_form_uncompl'];
		}
	if (empty($errors))
		{
		if (($ar_pw=="" or $ar_pw_conf=="") && !isset($ar_send_userdata))
			{
			$errors[] = $lang_add['error_send_userdata'];
			}
		}
	# wenn alle Felder ausgefüllt wurden, weitere Überprüfungen durchführen:
	if (empty($errors))
		{
		# Is the name to long?
		if (mb_strlen($ar_username) > $settings['name_maxlength'])
			{
			$errors[] = $lang['name_marking'] . " " .$lang['error_input_too_long'];
			}
		# Is any part of the name to long?
		$text_arr = explode(" ",$ar_username);
		for ($i=0; $i<count($text_arr); $i++)
			{
			trim($text_arr[$i]);
			$laenge = mb_strlen($text_arr[$i]);
			if ($laenge > $settings['name_word_maxlength'])
				{
				$error_nwtl = str_replace("[word]", htmlspecialcharsmb_(substr($text_arr[$i],0,$settings['name_word_maxlength']))."...", $lang['error_name_word_too_long']);
				$errors[] = $error_nwtl;
				}
			}
		# schauen, ob der Name schon vergeben ist:
		$name_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_name = '".mysql_real_escape_string($ar_username)."'");
		if(!$name_result) die($lang['db_error']);
		$field = mysql_fetch_assoc($name_result);
		mysql_free_result($name_result);

		if (mb_strtolower($field["user_name"]) == mb_strtolower($ar_username) && $ar_username != "")
			{
			$lang['error_name_reserved'] = str_replace("[name]", htmlspecialchars($ar_username), $lang['error_name_reserved']);
			$errors[] = $lang['error_name_reserved'];
			}
		# Überprüfung ob die Email-Adresse das Format name@domain.tld hat:
		if (!preg_match("/^[^@]+@.+\.\D{2,5}$/", $ar_email))
			{
			$errors[] = $lang['error_email_wrong'];
			}
		if ($ar_pw_conf != $ar_pw)
			{
			$errors[] = $lang_add['error_pw_conf_wrong'];
			}
		}
	# wenn keine Fehler, dann neuen User Aufnehmen:
	if (empty($errors))
		{
		# neuen User in die Datenbank eintragen:
		# Passwort generieren, wenn kein Passwort eingegeben wurde:
		if($ar_pw=='')
			{
			$letters="abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789";
			mt_srand((double)microtime()*1000000);
			$ar_pw="";
			for($i=0; $i<8; $i++)
				{
				$ar_pw.=substr($letters,mt_rand(0,strlen($letters)-1),1);
				}
			}
		$encoded_ar_pw = md5($ar_pw);
		$newUserQuery = "INSERT INTO ".$db_settings['userdata_table']." SET
		user_type = 'user',
		user_name = '".mysql_real_escape_string($ar_username)."',
		user_pw = '".$encoded_ar_pw."',
		user_email = '".mysql_real_escape_string($ar_email)."',
		hide_email = 1,
		last_login = NOW(),
		last_logout = NOW(),
		user_ip = '".$_SERVER["REMOTE_ADDR"]."',
		registered = NOW(),
		user_view = '".$settings['standard']."',
		personal_messages = 1";
		$new_user_result = mysql_query($newUserQuery, $connid);
		if (!$new_user_result) die($lang['db_error']);

		# E-Mail an neuen User versenden:
		$send_error = '';
		if (isset($ar_send_userdata))
			{
			$ip = $_SERVER["REMOTE_ADDR"];
			$lang['new_user_email_txt_a'] = str_replace("[name]", $ar_username, strip_tags($lang['new_user_email_txt_a']));
			$lang['new_user_email_txt_a'] = str_replace("[password]", $ar_pw, $lang['new_user_email_txt_a']);
			$lang['new_user_email_txt_a'] = str_replace("[login_link]", $settings['forum_address']."login.php?username=".urlencode($ar_username)."&userpw=".$ar_pw, $lang['new_user_email_txt_a']);
			$lang['new_user_email_txt_a'] = $lang['new_user_email_txt_a'];
			$header = "From: ".$settings['forum_name']." <".$settings['forum_email'].">\n";
			$header .= "X-Mailer: Php/" . phpversion(). "\n";
			$header .= "X-Sender-ip: ".$_SERVER["REMOTE_ADDR"]."\n";
			$header .= "Content-Type: text/plain";
			$new_user_mailto = $ar_username." <".$ar_email.">";
			if ($settings['mail_parameter']!='')
				{
				if (!@mail($new_user_mailto, strip_tags($lang['new_user_email_sj']), $lang['new_user_email_txt_a'], $header, $settings['mail_parameter']))
					{
					$send_error = '&send_error=true';
					}
				}
			else
				{
				if (!@mail($new_user_mailto, strip_tags($lang['new_user_email_sj']), $lang['new_user_email_txt_a'], $header))
					{
					$send_error = '&send_error=true';
					}
				}
			}
		header('Location: '.$settings['forum_address'].'admin.php?action=user&new_user='.urlencode($ar_username).$send_error);
		die('<a href="admin.php?action=user&amp;new_user='.urlencode($ar_username).$send_error.'">further...</a>');
		}
	}

if (isset($_POST['rename_category_submitted']) && trim($_POST['new_category']) != "")
	{
	mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, category='".$_POST['new_category']."' WHERE category='".$_POST['old_category']."'", $connid);
	$data = file("db_settings.php");
	$row_count = count($data);
	$fp = fopen("db_settings.php", "w+");
	flock($fp, 2);
	for ($row=0; $row<$row_count; $row++)
		{
		if (mb_substr(trim($data[$row]),1,10) == "categories")
			{
			fwrite($fp, trim(str_replace($_POST['old_category'],$_POST['new_category'],$data[$row]))."\n");
			}
		else
			{
			fwrite($fp, trim($data[$row])."\n");
			}
		}
	flock($fp, 3);
	fclose($fp);

	header('Location: '.$settings['forum_address'].'admin.php');
	die('<a href="admin.php">further...</a>');
	}

if (isset($_POST['delete_category_confirmed']) && trim($_POST['delete_category']) != "")
	{
	mysql_query("DELETE FROM ".$db_settings['forum_table']." WHERE category = '".$_POST['delete_category']."'", $connid);
	header('Location: '.$settings['forum_address'].'admin.php');
	die('<a href="admin.php">further...</a>');
	}

if (isset($_POST['banlists_submit']))
	{
	if (trim($_POST['banned_users']) != '')
		{
		$banned_users_array = explode(',',$_POST['banned_users']);
		foreach($banned_users_array as $banned_user)
			{
			if(trim($banned_user)!='')
				{
				$banned_users_array_checked[] = trim($banned_user);
				}
			}
		$banned_users = implode(",", $banned_users_array_checked);
		}
	else
		{
		$banned_users = '';
		}
	mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_real_escape_string($banned_users)."' WHERE name='users'", $connid);
	if (trim($_POST['banned_ips']) != '')
		{
		$banned_ips_array = explode(',',$_POST['banned_ips']);
		foreach ($banned_ips_array as $banned_ip)
			{
			if (trim($banned_ip)!='')
				{
				$banned_ips_array_checked[] = trim($banned_ip);
				}
			}
		$banned_ips = implode(",", $banned_ips_array_checked);
		}
	else
		{
		$banned_ips = '';
		}
	mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_real_escape_string($banned_ips)."' WHERE name='ips'", $connid);
	if (trim($_POST['not_accepted_words']) != '')
		{
		$not_accepted_words_array = explode(',',$_POST['not_accepted_words']);
		foreach ($not_accepted_words_array as $not_accepted_word)
			{
			if (trim($not_accepted_word)!='') $not_accepted_words_array_checked[] = trim($not_accepted_word);
			}
		$not_accepted_words = implode(",", $not_accepted_words_array_checked);
		}
	else
		{
		$not_accepted_words = '';
		}
	mysql_query("UPDATE ".$db_settings['banlists_table']." SET list='".mysql_real_escape_string($not_accepted_words)."' WHERE name='words'", $connid);
	header('location: '.$settings['forum_address'].'admin.php');
	die('<a href="admin.php">further...</a>');
	}

if (isset($_POST['smiley_file']))
	{
	if (!file_exists('img/smilies/'.$_POST['smiley_file']))
		{
		$errors[] = $lang_add['smiley_file_doesnt_exist'];
		}
	if (trim($_POST['smiley_code'])=='')
		{
		$errors[] = $lang_add['smiley_code_error'];
		}
	if (empty($errors))
		{
		$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
		list($smilies_count) = mysql_fetch_row($count_result);
		mysql_free_result($count_result);
		$order_id = $smilies_count+1;
		$insertSmileyQuery = "INSERT INTO ".$db_settings['smilies_table']." SET
		order_id = ".intval($order_id).",
		file = '".mysql_real_escape_string($_POST['smiley_file'])."',
		code_1 = '".mysql_real_escape_string(trim($_POST['smiley_code']))."'";
		mysql_query($insertSmileyQuery, $connid) or die(mysql_error($connid));
		header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
		die();
		}
	else
		{
		$action='smilies';
		}
	}

if(isset($_GET['delete_smiley']))
	{
	mysql_query("DELETE FROM ".$db_settings['smilies_table']." WHERE id = ".intval($_GET['delete_smiley']), $connid);
	$result = mysql_query("SELECT id FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
	$i=1;
	while ($data = mysql_fetch_assoc($result))
		{
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$i." WHERE id = ".intval($data['id']), $connid);
		$i++;
		}
	mysql_free_result($result);
	header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
	die();
	}

if(isset($_GET['edit_smiley']))
	{
	$result = mysql_query("SELECT id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']." WHERE id = ".intval($_GET['edit_smiley'])." LIMIT 1", $connid);
	if(!$result) die($lang['db_error']);
	$data = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$id = $data['id'];
	$file = $data['file'];
	$code_1 = $data['code_1'];
	$code_2 = $data['code_2'];
	$code_3 = $data['code_3'];
	$code_4 = $data['code_4'];
	$code_5 = $data['code_5'];
	$title = $data['title'];
	$action='edit_smiley';
	}

if(isset($_POST['edit_smiley_submit']))
	{
	$id = intval($_POST['id']);
	$file = trim($_POST['file']);
	$code_1 = trim($_POST['code_1']);
	$code_2 = trim($_POST['code_2']);
	$code_3 = trim($_POST['code_3']);
	$code_4 = trim($_POST['code_4']);
	$code_5 = trim($_POST['code_5']);
	$title = trim($_POST['title']);

	if (!file_exists('img/smilies/'.$file))
		{
		$errors[] = $lang_add['smiley_file_doesnt_exist'];
		}
	if($code_1=='' && $code_2=='' && $code_3=='' && $code_4=='' && $code_5=='')
		{
		$errors[] = $lang_add['smiley_code_error'];
		}
	if(empty($errors))
		{
		$editSmileyUpdateQuery = "UPDATE ".$db_settings['smilies_table']." SET
		file='".mysql_real_escape_string($file)."',
		code_1='".mysql_real_escape_string($code_1)."',
		code_2='".mysql_real_escape_string($code_2)."',
		code_3='".mysql_real_escape_string($code_3)."',
		code_4='".mysql_real_escape_string($code_4)."',
		code_5='".mysql_real_escape_string($code_5)."',
		title='".mysql_real_escape_string($title)."'
		WHERE id=".intval($id);
		mysql_query($editSmileyUpdateQuery, $connid);
		header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
		die();
		}
	else
		{
		$action='edit_smiley';
		}
	}

if(isset($_GET['enable_smilies']))
	{
	mysql_query("UPDATE ".$db_settings['settings_table']." SET value=1 WHERE name='smilies'", $connid);
	header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
	die();
	}

if(isset($_GET['disable_smilies']))
	{
	mysql_query("UPDATE ".$db_settings['settings_table']." SET value=0 WHERE name='smilies'", $connid);
	header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
	die();
	}

if (isset($_GET['move_up_smiley']))
	{
	$result = mysql_query("SELECT order_id FROM ".$db_settings['smilies_table']." WHERE id = ".$_GET['move_up_smiley']." LIMIT 1", $connid);
	if (!$result) die($lang['db_error']);
	$field = mysql_fetch_assoc($result);
	mysql_free_result($result);
	if ($field['order_id'] > 1)
		{
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=0 WHERE order_id=".$field['order_id']."-1", $connid);
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=order_id-1 WHERE order_id=".$field['order_id'], $connid);
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$field['order_id']." WHERE order_id=0", $connid);
		}
	header('Location: '.$settings['forum_address'].'admin.php?action=smilies');
	die();
	}

if (isset($_GET['move_down_smiley']))
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
	list($smilies_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);

	$result = mysql_query("SELECT order_id FROM ".$db_settings['smilies_table']." WHERE id = ".$_GET['move_down_smiley']." LIMIT 1", $connid);
	if(!$result) die($lang['db_error']);
	$field = mysql_fetch_array($result);
	mysql_free_result($result);
	if ($field['order_id'] < $smilies_count)
		{
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=0 WHERE order_id=".$field['order_id']."+1", $connid);
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=order_id+1 WHERE order_id=".$field['order_id'], $connid);
		mysql_query("UPDATE ".$db_settings['smilies_table']." SET order_id=".$field['order_id']." WHERE order_id=0", $connid);
		}
	header("location: ".$settings['forum_address']."admin.php?action=smilies");
	die();
	}

if (empty($action)) $action="main";

$topnav = '<a class="textlink" href="';
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
	if ($action == "main")
		{
		$topnav .= '<span class="current-page">'.$lang_add['admin_area'].'</span>';
		}
	if ($action == "settings")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['forum_settings'] .'</span>';
		}
	if ($action == "advanced_settings")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=settings">'.$lang_add['forum_settings'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['advanced_settings'].'</span>';
		}
	if ($action == "debug")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['debug_administr'] .'</span>';
		}
	if ($action == "categories")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['category_administr'].'</span>';
		}
	if ($action == "delete_category")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=categories">'.$lang_add['category_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['delete_category'].'</span>';
		}
	if ($action == "edit_category")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=categories">'.$lang_add['category_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['cat_edit_hl'].'</span>';
		}
	if ($action == "user")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['user_administr'].'</span>';
		}
	if ($action == "edit_user")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['edit_user'].'</span>';
		}
	if ($action == "delete_users_sure")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['delete_user'].'</span>';
		}
	if ($action == "register")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['reg_user'].'</span>';
		}
	if ($action == "email_list")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['email_list'].'</span>';
		}
	if ($action == "clear_userdata")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['clear_userdata'].'</span>';
		}
	if ($action == "banlists")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['banlists'].'</span>';
		}
	if ($action == "empty")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['empty_forum'].'</span>';
		}
	if ($action == "backup")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['backup_restore'].'</span>';
		}
	if ($action == "import_sql" or $action == "import_sql_ok")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=backup">'.$lang_add['backup_restore'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['import_sql'].'</span>';
		}
	if ($action == "uninstall")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['uninstall'].'</span>';
		}
	if ($action == "smilies")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['smilies'].'</span>';
		}
	if ($action == "edit_smiley")
		{
		$topnav .= '<a class="textlink" href="admin.php">'.$lang_add['admin_area'].'</a>&nbsp;';
		$topnav .= '<a class="textlink" href="admin.php?action=smilies">'.$lang_add['smilies'].'</a>&nbsp;';
		$topnav .= '<span class="current-page">'.$lang_add['edit_smiley_hl'].'</span>';
		}
	if ($action == "delete_marked_threads")
		{
		$topnav .= '<span class="current-page">'.$lang_add['del_marked'].'</span>';
		}
	if ($action == "unmark")
		{
		$topnav .= '<span class="current-page">'.$lang_add['unmark_threads'].'</span>';
		}
	if ($action == "lock_marked_threads")
		{
		$topnav .= '<span class="current-page">'.$lang_add['lock_marked'].'</span>';
		}
	if ($action == "unlock_marked_threads")
		{
		$topnav .= '<span class="current-page">'.$lang_add['unlock_marked'].'</span>';
		}
	if ($action == "invert_markings")
		{
		$topnav .= '<span class="current-page">'.$lang_add['invert_markings'].'</span>';
		}
	if ($action == "mark_threads")
		{
		$topnav .= '<span class="current-page">'.$lang_add['mark_threads'].'</span>';
		}
	}

parse_template();
echo $header;

switch ($action)
	{
	case "main":
		echo '<ul class="linklist">'."\n";
		echo '<li><a class="textlink" href="admin.php?action=settings">'.$lang_add['forum_settings'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=debug">'.$lang_add['debug_administr'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=categories">'.$lang_add['category_administr'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=user">'.$lang_add['user_administr'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=smilies">'.$lang_add['smilies'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=banlists">'.$lang_add['banlists'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=empty">'.$lang_add['empty_forum'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=backup">'.$lang_add['backup_restore'].'</a></li>'."\n";
		echo '<li><a class="textlink" href="admin.php?action=uninstall">'.$lang_add['uninstall'].'</a></li>'."\n";
		echo '</ul>'."\n";
	break;
	case "debug":
		echo '<form action="admin.php" method="post">'."\n";
		echo '<table class="normaltab">'."\n";
		echo '<tr>'."\n";
		echo '<td class="c"><label for="debug-none">'.$lang_add['debug_none'].'</label><br />';
		echo '<span class="info">'.$lang_add['debug_none_d'].'</span></td>'."\n";
		echo '<td class="d"><input type="radio" name="debug_type" id="debug-none" value="no"';
		echo ($_SESSION['debug'] == 'no') ? ' checked="checked"' : '';
		echo ' /></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="c"><label for="debug-lang">'.$lang_add['debug_lang'].'</label><br />';
		echo '<span class="info">'.$lang_add['debug_lang_d'].'</span></td>'."\n";
		echo '<td class="d"><input type="radio" name="debug_type" id="debug-lang" value="lang"';
		echo ($_SESSION['debug'] == 'lang') ? ' checked="checked"' : '';
		echo ' /></td>'."\n";
		echo '</tr>'."\n";
		echo '</table>'."\n";
		echo '<p><input type="submit" name="debug_submitted"';
		echo ' value="'.outputLangDebugInAttributes($lang_add['settings_sb']).'" /></p>'."\n";
		echo '</form>'."\n";
	break;
	case "categories":
		# look if there are entries in not existing categories:
		$entriesWOCategories = "SELECT COUNT(*) FROM ".$db_settings['forum_table']."
		WHERE category ";
		if (isset($category_ids_query))
			{
			$entriesWOCategories .= "NOT IN (".$category_ids_query.")";
			}
		else
			{
			$entriesWOCategories .= "!= 0";
			}
		$count_result = mysql_query($entriesWOCategories, $connid);
		list($entries_count) = mysql_fetch_row($count_result);
		mysql_free_result($count_result);
		if ($entries_count > 0)
			{
			$cat_select = '<select class="kat" size="1" name="move_category">'."\n";
			if ($categories!=false)
				{
				while (list($key, $val) = each($categories))
					{
					if ($key!=0)
						{
						$cat_select .= '<option value="'.intval($key);
						$cat_select .= '">'.htmlspecialchars($val).'</option>'."\n";
						}
					}
				}
			else
				{
				$cat_select .= '<option value="0">-</option>'."\n";
				}
			$cat_select .= '</select>'."\n";
			# <div style="margin:0px 0px 20px 0px; padding:10px; border:1px dotted red;">
			echo '<form action="admin.php" method="post">'."\n";
			echo '<p>'.$lang_add['entries_in_not_ex_cat'].'</p>'."\n";
			echo '<p><input type="radio" name="mode" value="delete" checked="checked" />';
			echo $lang_add['entries_in_not_ex_cat_delete'].'<br />'."\n";
			echo '<input type="radio" name="mode" value="move" />';
			echo str_replace("[category]",$cat_select,$lang_add['entries_in_not_ex_cat_move']).'</p>'."\n";
			echo '<p><input type="submit" name="not_displayed_entries_submit" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'"></p>'."\n";
			echo '</form>'."\n";
			}
		$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
		list($categories_count) = mysql_fetch_row($count_result);
		mysql_free_result($count_result);
		if (isset($errors))
			{
			echo errorMessages($errors);
			}
		if ($categories_count > 0)
			{
			$result = mysql_query("SELECT id, category_order, category, accession FROM ".$db_settings['category_table']." ORDER BY category_order ASC", $connid);
			if(!$result) die($lang['db_error']);
			
			echo '<table class="normaltab">'."\n";
			echo '<tr class="titlerow">'."\n";
			echo '<th>'.$lang_add['cat_hl'].'</th>'."\n";
			echo '<th>'.$lang_add['cat_accessible'].'</th>'."\n";
			echo '<th>'.$lang_add['cat_topics'].'</th>'."\n";
			echo '<th>'.$lang_add['cat_entries'].'</th>'."\n";
			echo '<th colspan="2">'.$lang_add['cat_actions'].'</th>'."\n";
			echo '<th>'.$lang_add['cat_move'].'</th>'."\n";
			echo '</tr>';

			$i=0;
			while ($line = mysql_fetch_assoc($result))
				{
				$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category = '".intval($line['id'])."' AND pid = 0", $connid);
				list($threads_in_category) = mysql_fetch_row($count_result);
				mysql_free_result($count_result);
				$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category = '".intval($line['id'])."'", $connid);
				list($postings_in_category) = mysql_fetch_row($count_result);
				mysql_free_result($count_result);
				$rowClass = ($i % 2 == 0) ? "a" : "b";
				echo '<tr class="'.$rowClass.'">'."\n";
				echo '<td><b>'.$line['category'].'</b></td>'."\n";
				echo '<td>'."\n";
				if ($line['accession']==2) echo $lang_add['cat_accession_mod_admin'];
				else if ($line['accession']==1) echo $lang_add['cat_accession_reg_users'];
				else echo $lang_add['cat_accession_all'].'</td>'."\n";
				echo '<td>'.$threads_in_category.'</td>'."\n";
				echo '<td>'.$postings_in_category.'</td>'."\n";
				echo '<td><a href="admin.php?edit_category='.$line['id'].'">'.$lang_add['cat_edit'].'</a></td>'."\n";
				echo '<td><a href="admin.php?delete_category='.$line['id'].'">'.$lang_add['cat_delete'].'</a></td>'."\n";
				echo '<td><a href="admin.php?move_up_category='.$line['id'].'">'."\n";
				echo '<img src="img/up.png" alt="up" width="11" height="11" /></a>&nbsp;';
				echo '<a href="admin.php?move_down_category='.$line['id'].'">';
				echo '<img src="img/down.png" alt="down" width="11" height="11" /></a></td>'."\n";
				echo '</tr>';
				$i++;
				}
				mysql_free_result($result);
				echo "\n".'</table>'."\n";
				}
			else
				{
				echo '<p><i>'.$lang_add['no_categories'].'</i></p>'."\n";
				}
			echo '<form action="admin.php" method="post"><div>'."\n";
			echo '<label for="cat-name">'.$lang_add['new_category'].'</label><br />'."\n";
			echo '<input type="text" name="new_category" id="cat-name" value="';
			echo isset($new_category) ? htmlspecialchars($new_category) : '';
			echo '" size="25" /><br />'."\n";
			echo '<b>'.$lang_add['accessible_for'].'</b><br />'."\n";
			echo '<input type="radio" name="accession" id="access-all" value="0"';
			if (empty($accession) || isset($accession) && $accession == 0)
				{
				echo ' checked="ckecked"';
				}
			echo ' /><label for="access-all">'.$lang_add['cat_accession_all'].'</label><br />'."\n";
			echo '<input type="radio" name="accession" id="access-user" value="1"';
			if (isset($accession) && $accession == 1)
				{
				echo ' checked="ckecked"';
				}
			echo ' /><label for="access-user">'.$lang_add['cat_accession_reg_users'].'</label><br />'."\n";
			echo '<input type="radio" name="accession" id="access-mod-admin" value="2"';
			if (isset($accession) && $accession == 2)
				{
				echo ' checked="ckecked"';
				}
			echo ' /><label for="access-mod-admin">'.$lang_add['cat_accession_mod_admin'].'</label><br /><br />'."\n";
			echo '<input type="submit" value="'.outputLangDebugInAttributes($lang['submit_button_ok']).'" /></div></form>'."\n";
		break;
		case "user":
			$order = isset($_GET['order']) ? $_GET['order'] : "user_id";
			$sam = isset($_GET['sam']) ? (int)$_GET['sam'] : 50;
			$descasc = isset($_GET['descasc']) ? $_GET['descasc'] : "ASC";
			$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
			$category = empty($category) ? 0 : intval($category);

			if (isset($_GET['search_user'])) $search_user = $_GET['search_user'];
			if (isset($_GET['letter'])) $letter = $_GET['letter'];

			$ul = $page * $settings['users_per_page'];

			if (isset($letter))
				{
				$userByLetterQuery = "SELECT
				user_id,
				user_name,
				user_type,
				user_email,
				logins,
				UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time,
				UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time,
				INET_NTOA(ip_addr) AS ip_address,
				user_lock
				FROM ".$db_settings['userdata_table']."
				WHERE user_name LIKE '".mysql_real_escape_string($_GET['letter'])."%'
				ORDER BY ".$order." ".$descasc."
				LIMIT ".$ul.", ".$settings['users_per_page'];
				$result = mysql_query($userByLetterQuery, $connid);
				}
			else if (isset($search_user))
				{
				$userByNameQuery = "SELECT
				user_id,
				user_name,
				user_type,
				user_email,
				logins,
				UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time,
				UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time,
				INET_NTOA(ip_addr) AS ip_address,
				user_lock
				FROM ".$db_settings['userdata_table']."
				WHERE user_name LIKE '".mysql_real_escape_string($search_user)."%'
				OR user_email LIKE '".mysql_real_escape_string($search_user)."%'
				ORDER BY ".$order." ".$descasc."
				LIMIT ".$ul.", ".$settings['users_per_page'];
				$result = mysql_query($userByNameQuery, $connid);
				}
			else
				{
				$userWOFilter = "SELECT
				user_id,
				user_name,
				user_type,
				user_email,
				logins,
				UNIX_TIMESTAMP(last_login + INTERVAL ".$time_difference." HOUR) AS last_login_time,
				UNIX_TIMESTAMP(registered + INTERVAL ".$time_difference." HOUR) AS registered_time,
				INET_NTOA(ip_addr) AS ip_address,
				user_lock
				FROM ".$db_settings['userdata_table']."
				ORDER BY ".$order." ".$descasc."
				LIMIT ".$ul.", ".$settings['users_per_page'];
				$result = mysql_query($userWOFilter, $connid);
				}
			if (!$result) die($lang['db_error']);
			$result_count = mysql_num_rows($result);

			# schauen, wieviele User vorhanden sind:
			$user_count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
			list($user_count) = mysql_fetch_row($user_count_result);
			mysql_free_result($user_count_result);

			if (isset($_GET['letter']) && $_GET['letter']!="")
				{
				$su_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table']."
				WHERE user_name LIKE '".mysql_real_escape_string($_GET['letter'])."%'", $connid);
				}
			else
				{
				$su_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
				}
			list($sel_user_count) = mysql_fetch_row($su_result);
			mysql_free_result($su_result);

			if ($user_count < $sam) $sam = $user_count;
			$alphabet = range('A', 'Z');

			echo '<table>'."\n";
			echo '<tr>'."\n";
			echo '<td>'.str_replace("[number]", $user_count, $lang['num_reg_users']).'</td>'."\n";
			echo '<td style="text-align:right;">'.$lang_add['search_user'];
			echo '<form action="'.$_SERVER['SCRIPT_NAME'].'" method="get" style="display:inline">'."\n";
			echo '<input type="hidden" name="action" value="user" />'."\n";
			echo '<input type="text" name="search_user" value="';
			echo (isset($search_user)) ? htmlspecialchars($search_user) : '';
			echo '" size="25">&nbsp;<input type="image" name="" value="" src="img/submit.png" alt="&raquo;" />';
			echo '</form>'."\n";
			if (empty($serach_user) || trim($search_user==''))
				{
				echo '&nbsp;';
				echo '<form action="'.$_SERVER["SCRIPT_NAME"].'" method="get" style="display:inline">'."\n";
				echo '<input type="hidden" name="action" value="user" />'."\n";
				echo '<select class="kat" size="1" name="letter" onchange="this.form.submit();">'."\n";
				echo '<option value="">A-Z</option>'."\n";
				foreach ($alphabet as $lett)
					{
					echo '<option value="'.$lett.'"';
					echo (isset($_GET['letter']) && $_GET['letter'] == $lett) ? ' selected="selected"' : '';
					echo '>'.$lett.'</option>'."\n";
					}
				echo '</select>&nbsp;<input type="image" name="" value="" src="img/submit.png" alt="&raquo;" /></form>'."\n";
				echo nav($page, $settings['users_per_page'], $sel_user_count, $order, $descasc, $category, $action);
				}
			echo '</td>'."\n";
			echo '</tr>'."\n";
			echo '</table>'."\n";

			if ($result_count > 0)
				{
				$currDescAsc = strtolower($descasc);
				if (isset($_GET['new_user']))
					{
					echo '<p class="caution">';
					echo str_replace("[name]", htmlspecialchars(urldecode($_GET['new_user'])), $lang_add['new_user_registered']);
					if (isset($_GET['send_error']))
						{
						echo '<br />'.$lang_add['userdata_send_error'];
						}
					echo '</p>'."\n".'<p><a class="textlink" href="admin.php?action=register">'.$lang_add['reg_another_user'].'</a></p>'."\n";
					}
				if (isset($no_users_in_selection))
					{
					echo '<p class="caution">'.$lang_add['no_users_in_sel'].'</p>'."\n";
					}
				echo '<form action="admin.php" method="post">'."\n";
				echo '<table class="normaltab">'."\n";
				echo '<tr class="titlerow">'."\n";
				echo '<th>&nbsp;</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=user_id&amp;descasc=';
				echo ($descasc=="ASC" && $order=="user_id") ? 'DESC' : 'ASC';
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_id'].'</a>';
				if ($order=="user_id")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=user_name&amp;descasc=';
				echo ($descasc=="ASC" && $order=="user_name") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_name'].'</a>';
				if ($order=="user_name")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=user_email&amp;descasc=';
				echo ($descasc=="ASC" && $order=="user_email") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_email'].'</a>';
				if ($order=="user_email")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=user_type&amp;descasc=';
				echo ($descasc=="ASC" && $order=="user_type") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_type'].'</a>';
				if ($order=="user_type")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=registered&amp;descasc=';
				echo ($descasc=="ASC" && $order=="registered") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_registered'].'</a>';
				if ($order=="registered")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=logins&amp;descasc=';
				echo ($descasc=="ASC" && $order=="logins") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['user_logins'].'</a>';
				if ($order=="logins")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=last_login&amp;descasc=';
				echo ($descasc=="ASC" && $order=="last_login") ? "DESC" : "ASC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang_add['last_login'].'</a>';
				if ($order=="last_login")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th><a href="admin.php?action=user&amp;order=user_lock&amp;descasc=';
				echo ($descasc=="DESC" && $order=="user_lock") ? "ASC" : "DESC";
				echo '&amp;ul='.$ul.'&amp;sam='.$sam.'" title="'.$lang['order_linktitle'].'">'.$lang['lock'].'</a>';
				if ($order=="user_lock")
					{
					echo outputImageDescAsc($currDescAsc);
					}
				echo '</th>'."\n";
				echo '<th colspan="2">&nbsp;</th>'."\n";
				echo '</tr>'."\n";
				$i=0;
				while ($zeile = mysql_fetch_assoc($result))
					{
					$rowClass = ($i % 2 == 0) ? "a" : "b";
					echo '<tr class="'.$rowClass.'">'."\n";
					echo '<td><input type="checkbox" name="selected[]" value="'.$zeile["user_id"].'" /></td>'."\n";
					echo '<td class="info">'.$zeile["user_id"].'</td>'."\n";
					echo '<td><a href="user.php?id='.$zeile["user_id"].'" title="';
					echo str_replace("[name]", htmlspecialchars($zeile["user_name"]), $lang['show_userdata_linktitle']).'"><b>';
					echo htmlspecialchars($zeile["user_name"]).'</b></a></td>'."\n";
					echo '<td class="info"><a href="mailto:'.$zeile["user_email"].'" title="';
					echo str_replace("[name]", htmlspecialchars($zeile["user_name"]), $lang_add['mailto_user_lt']);
					echo '">'.htmlspecialchars($zeile["user_email"]).'</a></td>'."\n";
					echo '<td class="info">'."\n";
					if ($zeile["user_type"] == "admin") echo $lang['ud_admin'];
					elseif ($zeile["user_type"] == "mod") echo $lang['ud_mod'];
					else echo $lang['ud_user'];
					echo '</td>'."\n";
					echo '<td class="info" title="'. htmlspecialchars($zeile['ip_address']);
					echo '">'.strftime($lang['time_format'], $zeile["registered_time"]).'</td>'."\n";
					echo '<td class="info">'.$zeile["logins"].'</td>'."\n";
					echo '<td class="info">';
					echo ($zeile["logins"] > 0) ? strftime($lang['time_format'], $zeile["last_login_time"]) : "&nbsp;";
					echo '</td>'."\n";
					echo '<td class="info">';
					if ($zeile["user_lock"] == 0)
						{
						echo '<a href="admin.php?user_lock='.$zeile["user_id"].'&amp;order='.$order.'&amp;descasc=';
						echo $descasc.'&amp;ul='.$ul.'&amp;sam='.$sam.'" title="';
						echo str_replace("[name]", htmlspecialchars($zeile["user_name"]), $lang['lock_user_lt']);
						echo '">'.$lang['unlocked'].'</a>';
						}
					else
						{
						echo '<a style="color: red;" href="admin.php?user_lock='.$zeile["user_id"].'&amp;order='.$order;
						echo '&amp;descasc='.$descasc.'&amp;ul='.$ul.'&amp;sam='.$sam.'" title="';
						echo str_replace("[name]", htmlspecialchars($zeile["user_name"]), $lang['unlock_user_lt']);
						echo '">'.$lang['locked'].'</a>';
						}
					echo '</td>'."\n";
					echo '<td class="info"><a href="admin.php?edit_user='.$zeile["user_id"].'&amp;order='.$order.'&amp;descasc='.$descasc;
					echo '&amp;ul='.$ul.'&amp;sam='.$sam.'">'.$lang_add['edit_link'].'</a></td>'."\n";
					echo '<td class="info"><a href="admin.php?delete_user='.$zeile["user_id"].'&amp;order='.$order.'&amp;descasc='.$descasc;
					echo '&amp;ul='.$ul.'&amp;sam='.$sam.'">'.$lang_add['delete_link'].'</a></td>'."\n";
					echo '</tr>'."\n";
					$i++;
					}
				mysql_free_result($result);
				echo '</table>'."\n";
				echo '<div style="margin:5px 0px 0px 7px; padding:0px;"><img src="img/selected_arrow.png" alt="" width="35"';
				echo ' height="20" border="0"><input type="submit" name="delete_user" value="'.outputLangDebugInAttributes($lang_add['delete_users_sb']);
				echo '" title="'.$lang_add['delete_users_sb_title'].'" /></div>'."\n".'</form>'."\n";
				}
			else
				{
				echo '<p><i>'.$lang['no_users'].'</i></p>'."\n";
				}
			echo '<ul class="linklist">'."\n";
			echo '<li><a class="textlink" href="admin.php?action=register">'.$lang_add['reg_user'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?action=email_list">'.$lang_add['email_list'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?action=clear_userdata">'.$lang_add['clear_userdata'].'</a></li>'."\n";
			echo '</ul>'."\n";
		break;
		case "register":
			echo '<p>'.$lang_add['register_exp'].'</p>'."\n";
			if (isset($errors)) { errorMessages($errors); }
			echo '<form action="admin.php" method="post">'."\n";
			echo '<input type="hidden" name="action" value="register">'."\n";
			echo '<b>'.$lang['username_marking'].'</b><br />'."\n";
			echo '<input type="text" size="25" name="ar_username" value="';
			echo (isset($ar_username)) ? htmlspecialchars($ar_username) : '';
			echo '" maxlength="'.$name_maxlength.'" /><br /><br /><b>'.$lang['user_email_marking'].'</b><br />'."\n";
			echo '<input type="text" size="25" name="ar_email" value="';
			echo (isset($ar_email)) ? htmlspecialchars($ar_email) : '';
			echo '" maxlength="'.$email_maxlength.'" /><br /><br /><b>'.$lang_add['pw_marking'].'</b><br />'."\n";
			echo '<input type="password" size="25" name="ar_pw" maxlength="50"><br /><br /><b>';
			echo $lang_add['pw_conf_marking'].'</b><br />'."\n";
			echo '<input type="password" size="25" name="ar_pw_conf" maxlength="50"><br /><br />'."\n";
			echo '<input type="checkbox" name="ar_send_userdata" value="true"';
			echo (isset($ar_send_userdata)) ? ' checked="checked"' : '';
			echo ' />'.$lang_add['ar_send_userdata'].'<br /><br />'."\n";
			echo '<input type="submit" name="pw_submit" value="'.$lang['new_pw_subm_button'];
			echo '" title="'.$lang['new_pw_subm_button_title'].'">'."\n";
			echo '</form>'."\n";
		break;
		case "settings":
			$std = (isset($settings['time_difference'])) ? $settings['time_difference'] : 0;
			echo '<form action="admin.php" method="post">'."\n";
			echo '<table class="normaltab">'."\n";
			echo '<tr>'."\n";
			echo '<td class="c"><label for="forum-name">'.$lang_add['forum_name'].'</label><br />';
			echo '<span class="info">'.$lang_add['forum_name_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="forum_name" id="forum-name" value="';
			echo htmlspecialchars($settings['forum_name']).'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="forum-addr">'.$lang_add['forum_address'].'</label><br />';
			echo '<span class="info">'.$lang_add['forum_address_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="forum_address" id="forum-addr" value="';
			echo $settings['forum_address'].'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="forum-mail">'.$lang_add['forum_email'].'</label><br />';
			echo '<span class="info">'.$lang_add['forum_email_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="forum_email" id="forum-mail" value="';
			echo $settings['forum_email'].'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="home-link">'.$lang_add['home_link'].'</label><br />';
			echo '<span class="info">'.$lang_add['home_link_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="home_linkaddress" id="home-link" value="';
			echo $settings['home_linkaddress'].'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="home-linkname">'.$lang_add['home_link_name'].'</label><br />';
			echo '<span class="info">'.$lang_add['home_link_name_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="home_linkname" id="home-linkname" value="';
			echo htmlspecialchars($settings['home_linkname']).'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="forum-template">'.$lang_add['template_file'].'</label><br />';
			echo '<span class="info">'.$lang_add['template_file_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="template" id="forum-template" value="';
			echo $settings['template'].'" size="40" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="forum-lang">'.$lang_add['language_file'].'</label><br />';
			echo '<span class="info">'.$lang_add['language_file_d'].'</span></td>'."\n";
			echo '<td class="d"><select name="language_file" id="forum-lang" size="1">'."\n";
			$handle=opendir('./lang/');
			while ($file = readdir($handle))
				{
				if (strrchr($file, ".") == ".php" && strrchr($file, "_") != "_add.php")
					{
					echo '<option value="'.$file.'"';
					echo ($settings['language_file'] == $file) ? ' selected="selected"' : '';
					echo '>'.ucfirst(str_replace(".php","",$file)).'</option>'."\n";
					}
				}
			closedir($handle);
			echo '</select></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="topic-pp">'.$lang_add['topics_per_page'].'</b><br />';
			echo '<span class="info">'.$lang_add['topics_per_page_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="topics_per_page" id="topic-pp" value="';
			echo $settings['topics_per_page'].'" size="5" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="server-timediff">'.$lang_add['forum_time_difference'].'</b><br />';
			echo '<span class="info">'.$lang_add['forum_time_difference_d'].'</span></td>'."\n";
			echo '<td class="d"><select name="time_difference" id="server-timediff" size="1">'."\n";
			for ($h = -24; $h <= 24; $h++)
				{
				echo '<option value="'.$h.'"';
				echo ($std==$h) ? ' selected="selected"' : '';
				echo '>'.$h.'</option>'."\n";
				}
			echo '</select></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['thread_view'].'<br />';
			echo '<span class="info">'.$lang_add['thread_view_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="thread_view" id="view-thread-1" value="1"';
			echo ($settings['thread_view']==1) ? ' checked="checked"' : '';
			echo ' /><label for="view-thread-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="thread_view" id="view-thread-0" value="0"';
			echo ($settings['thread_view']==0) ? ' checked="checked"' : '';
			echo ' /><label for="view-thread-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['board_view'].'<br />';
			echo '<span class="info">'.$lang_add['board_view_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="board_view" id="view-board-1" value="1"';
			echo ($settings['board_view']==1) ? ' checked="checked"' : '';
			echo ' /><label for="view-board-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="board_view" id="view-board-0" value="0"';
			echo ($settings['board_view']==0) ? ' checked="checked"' : '';
			echo ' /><label for="view-board-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['mix_view'].'<br />';
			echo '<span class="info">'.$lang_add['mix_view_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="mix_view" id="view-mix-1" value="1"';
			echo ($settings['mix_view']==1) ? ' checked="checked"' : '';
			echo ' /><label for="view-mix-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="mix_view" id="view-mix-0" value="0"';
			echo ($settings['mix_view']==0) ? ' checked="checked"' : '';
			echo ' /><label for="view-mix-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['standard'].'<br />';
			echo '<span class="info">'.$lang_add['standard_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="standard" id="standard-thread" value="thread"';
			echo ($settings['standard']=="thread") ? ' checked="checked"' : '';
			echo ' /><label for="standard-thread">'.$lang_add['standard_thread'].'</label>';
			echo '<input type="radio" name="standard" id="standard-board" value="board"';
			echo ($settings['standard']=="board") ? ' checked="checked"' : '';
			echo ' /><label for="standard-board">'.$lang_add['standard_board'].'</label>';
			echo '<input type="radio" name="standard" id="standard-mix" value="mix"';
			echo ($settings['standard']=="mix") ? ' checked="checked"' : '';
			echo ' /><label for="standard-mix">'.$lang_add['standard_mix'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['accession'].'<br />';
			echo '<span class="info">'.$lang_add['accession_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="access_for_users_only" id="access-user" value="1"';
			echo ($settings['access_for_users_only']==1) ? ' checked="checked"' : '';
			echo ' /><label for="access-user">'.$lang_add['access_only_reg_users'].'</label>';
			echo '<input type="radio" name="access_for_users_only" id="access-all" value="0"';
			echo ($settings['access_for_users_only']==0) ? ' checked="checked"' : '';
			echo ' /><label for="access-all">'.$lang_add['access_all_users'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['entry_perm'].'<br />';
			echo '<span class="info">'.$lang_add['entry_perm_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="entries_by_users_only" id="entry-user" value="1"';
			echo ($settings['entries_by_users_only']==1) ? ' checked="checked"' : '';
			echo ' /><label for="entry-user">'.$lang_add['access_only_reg_users'].'</label>';
			echo '<input type="radio" name="entries_by_users_only" id="entry-all" value="0"';
			echo ($settings['entries_by_users_only']==0) ? ' checked="checked"' : '';
			echo ' /><label for="entry-all">'.$lang_add['access_all_users'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['register_perm'].'<br />';
			echo '<span class="info">'.$lang_add['register_perm_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="register_by_admin_only" id="regist-admin" value="1"';
			echo ($settings['register_by_admin_only']==1) ? ' checked="checked"' : '';
			echo ' /><label for="regist-admin">'.$lang_add['register_only_admin'].'</label>';
			echo '<input type="radio" name="register_by_admin_only" id="regist-self" value="0"';
			echo ($settings['register_by_admin_only']==0) ? ' checked="checked"' : '';
			echo ' /><label for="regist-self">'. $lang_add['register_self'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['mark_reg_users'].'<br />';
			echo '<span class="info">'.$lang_add['mark_reg_users_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="show_registered" id="mark-reg-1" value="1"';
			echo ($settings['show_registered']==1) ? ' checked="checked"' : '';
			echo ' /><label for="mark-reg-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="show_registered" id="mark-reg-0" value="0"';
			echo ($settings['show_registered']==0) ? ' checked="checked"' : '';
			echo ' /><label for="mark-reg-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['email_notification'].'<br />';
			echo '<span class="info">'.$lang_add['email_notification_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="email_notification" id="notify-author-1" value="1"';
			echo ($settings['email_notification']==1) ? ' checked="checked"' : '';
			echo ' /><label for="notify-author-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="email_notification" id="notify-author-0" value="0"';
			echo ($settings['email_notification']==0) ? ' checked="checked"' : '';
			echo ' /><label for="notify-author-0">'.$lang['no'].'</td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['edit_own_entries'].'<br />';
			echo '<span class="info">'.$lang_add['edit_own_entries_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="user_edit" id="edit-own-1" value="1"';
			echo ($settings['user_edit']==1) ? ' checked="checked"' : '';
			echo ' /><label for="edit-own-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="user_edit" id="edit-own-0" value="0"';
			echo ($settings['user_edit']==0) ? ' checked="checked"' : '';
			echo ' /><label for="edit-own-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="edit-period">'.$lang_add['edit_period'].'</label><br />';
			echo '<span class="info">'.$lang_add['edit_period_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="edit_period" id="edit-period" value="';
			echo $settings['edit_period'].'" size="5" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['show_if_edited'].'<br />';
			echo '<span class="info">'.$lang_add['show_if_edited_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="show_if_edited" id="display-edit-1" value="1"';
			echo ($settings['show_if_edited']==1) ? ' checked="checked"' : '';
			echo ' /><label for="display-edit-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="show_if_edited" id="display-edit-0" value="0"';
			echo ($settings['show_if_edited']==0) ? ' checked="checked"' : '';
			echo ' /><label for="display-edit-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="edit-delay">'.$lang_add['edit_delay'].'</label><br />';
			echo '<span class="info">'.$lang_add['edit_delay_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="text" name="edit_delay" id="edit-delay" value="';
			echo $settings['edit_delay'].'" size="5" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['admin_unnoticeable_edit'].'<br />';
			echo '<span class="info">'.$lang_add['admin_unnoticeable_edit_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="dont_reg_edit_by_admin" id="admin-edit-1" value="1"';
			echo ($settings['dont_reg_edit_by_admin']==1) ? ' checked="checked"' : '';
			echo ' /><label for="admin-edit-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="dont_reg_edit_by_admin" id="admin-edit-0" value="0"';
			echo ($settings['dont_reg_edit_by_admin']==0) ? ' checked="checked"' : '';
			echo ' /><label for="admin-edit-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['mod_unnoticeable_edit'].'<br />';
			echo '<span class="info">'.$lang_add['mod_unnoticeable_edit_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="dont_reg_edit_by_mod" id="mod-edit-1" value="1"';
			echo ($settings['dont_reg_edit_by_mod']==1) ? ' checked="checked"' : '';
			echo ' /><label for="mod-edit-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="dont_reg_edit_by_mod" id="mod-edit-0" value="0"';
			echo ($settings['dont_reg_edit_by_mod']==0) ? ' checked="checked"' : '';
			echo ' /><label for="mod-edit-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['bbcode'].'<br />';
			echo '<span class="info">'.$lang_add['bbcode_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="bbcode" id="bbcode-1" value="1"';
			echo ($settings['bbcode']==1) ? ' checked="checked"' : '';
			echo ' /><label for="bbcode-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="bbcode" id="bbcode-0" value="0"';
			echo ($settings['bbcode']==0) ? ' checked="checked"' : '';
			echo ' /><label for="bbcode-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['bbcode_img'].'<br />';
			echo '<span class="info">'.$lang_add['bbcode_img_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="bbcode_img" id="bbimg-1" value="1"';
			echo ($settings['bbcode_img']==1) ? ' checked="checked"' : '';
			echo ' /><label for="bbimg-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="bbcode_img" id="bbimg-0" value="0"';
			echo ($settings['bbcode_img']==0) ? ' checked="checked"' : '';
			echo ' /><label for="bbimg-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['upload_images'].'<br />';
			echo '<span class="info">'.$lang_add['upload_images_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="upload_images" id="upload-img-1" value="1"';
			echo ($settings['upload_images']==1) ? ' checked="checked"' : '';
			echo ' /><label for="upload-img-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="upload_images" id="upload-img-0" value="0"';
			echo ($settings['upload_images']==0) ? ' checked="checked"' : '';
			echo ' /><label for="upload-img-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['smilies'].'<br />';
			echo '<span class="info">'.$lang_add['smilies_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="smilies" id="smilies-1" value="1"';
			echo ($settings['smilies']==1) ? ' checked="checked"' : '';
			echo ' /><label for="smilies-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="smilies" id="smilies-0" value="0"';
			echo ($settings['smilies']==0) ? ' checked="checked"' : '';
			echo ' /><label for="smilies-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['autolink'].'<br />';
			echo '<span class="info">'.$lang_add['autolink_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="autolink" id="autolink-1" value="1"';
			echo ($settings['autolink']==1) ? ' checked="checked"' : '';
			echo ' /><label for="autolink-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="autolink" id="autolink-0" value="0"';
			echo ($settings['autolink']==0) ? ' checked="checked"' : '';
			echo ' /><label for="autolink-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['count_views'].'<br />';
			echo '<span class="info">'.$lang_add['count_views_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="count_views" id="count-views-1" value="1"';
			echo ($settings['count_views']==1) ? ' checked="checked"' : '';
			echo ' /><label for="count-views-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="count_views" id="count-views-0" value="0"';
			echo ($settings['count_views']==0) ? ' checked="checked"' : '';
			echo ' /><label for="count-views-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['count_users_online'].'<br />';
			echo '<span class="info">'.$lang_add['count_users_online_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="count_users_online" id="count-online-1" value="1"';
			echo ($settings['count_users_online']==1) ? ' checked="checked"' : '';
			echo ' /><label for="count-online-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="count_users_online" id="count-online-0" value="0"';
			echo ($settings['count_users_online']==0) ? ' checked="checked"' : '';
			echo ' /><label for="count-online-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['rss_feed'].'<br />';
			echo '<span class="info">'.$lang_add['rss_feed_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="provide_rssfeed" id="rss-feed-1" value="1"';
			echo ($settings['provide_rssfeed']==1) ? ' checked="checked"' : '';
			echo ' /><label for="rss-feed-1">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="provide_rssfeed" id="rss-feed-0" value="0"';
			echo ($settings['provide_rssfeed']==0) ? ' checked="checked"' : '';
			echo ' /><label for="rss-feed-0">'.$lang['no'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['captcha'].'<br />';
			echo '<span class="info">'.$lang_add['captcha_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="checkbox" name="captcha_posting" id="captcha-post" value="1"';
			echo ($settings['captcha_posting']==1) ? ' checked="checked"' : '';
			echo ' /><label for="captcha-post">'.$lang_add['captcha_posting'].'</label><br />';
			echo '<input type="checkbox" name="captcha_contact" id="captcha-contact" value="1"';
			echo ($settings['captcha_contact']==1) ? ' checked="checked"' : '';
			echo ' /><label for="captcha-contact">'.$lang_add['captcha_contact'].'</label><br />';
			echo '<input type="checkbox" name="captcha_register" id="captcha-reg" value="1"';
			echo ($settings['captcha_register']==1) ? ' checked="checked"' : '';
			echo ' /><label for="captcha-reg">'.$lang_add['captcha_register'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['captcha_type'].'<br />';
			echo '<span class="info">'.$lang_add['captcha_type_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="captcha_type" id="captcha-type-0" value="0"';
			echo ($settings['captcha_type']==0) ? ' checked="checked"' : '';
			echo ' /><label for="captcha-type-0">'.$lang_add['captcha_type_math'].'</label><br />';
			echo '<input type="radio" name="captcha_type" id="captcha-type-1" value="1"';
			echo ($settings['captcha_type']==1) ? ' checked="checked"' : '';
			echo ' /><label for="captcha-type-1">'.$lang_add['captcha_type_image'].'</label></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['forum_disabled'].'<br />';
			echo '<span class="info">'.$lang_add['forum_disabled_d'].'</span></td>'."\n";
			echo '<td class="d"><input type="radio" name="forum_disabled" id="forum-disabled" value="1"';
			echo ($settings['forum_disabled']==1) ? ' checked="checked"' : '';
			echo ' /><label for="forum-disabled">'.$lang['yes'].'</label>';
			echo '<input type="radio" name="forum_disabled" id="forum-enabled" value="0"';
			echo ($settings['forum_disabled']==0) ? ' checked="checked"' : '';
			echo ' /><label for="forum-enabled">'.$lang['no'].'</label></td>'."\n";
			echo '</tr>'."\n";
			echo '</table>'."\n";
			echo '<p><input type="submit" name="settings_submitted"';
			echo ' value="'.outputLangDebugInAttributes($lang_add['settings_sb']).'" /></p>'."\n";
			echo '</form>'."\n";
			echo '<ul class="linklist">'."\n";
			echo '<li><a class="textlink" href="admin.php?action=advanced_settings">'.$lang_add['advanced_settings'].'</a></li>'."\n";
			echo '</ul>'."\n";
		break;
		case "advanced_settings":
			echo '<form action="admin.php" method="post">'."\n";
			echo '<table class="normaltab">'."\n";
			ksort($settings);
			while (list($key, $val) = each($settings))
				{
				echo '<tr>'."\n";
				echo '<td class="c"><b>'.$key.'</b></td>'."\n";
				echo '<td class="d"><input type="text" name="'.htmlspecialchars($key);
				echo '" value="'.htmlspecialchars($val).'" size="30" /></td>'."\n";
				echo '</tr>';
				}
			echo "\n".'</table>'."\n";
			echo '<p><input type="submit" name="settings_submitted" value="';
			echo outputLangDebugInAttributes($lang_add['settings_sb']).'" />&nbsp;<input type="reset" value="';
			echo outputLangDebugInAttributes($lang['reset_button']).'" /></p>'."\n".'</form>'."\n";
		break;
		case "delete_users_sure":
			echo '<h2>'.$lang_add['delete_users_hl'].'</h2>'."\n";
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<p>'.(count($selected)==1) ? $lang_add['delete_user_conf'] : $lang_add['delete_users_conf'].'</p>'."\n";
			echo '<ul class="linklist">'."\n";
			for ($x=0; $x<count($selected_usernames); $x++)
				{
				echo '<li><a href="user.php?id='.$selected[$x].'"><b>';
				echo htmlspecialchars($selected_usernames[$x]).'</b></a></li>'."\n";
				}
			echo '</ul>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			for ($x=0; $x<count($selected); $x++)
				{
				echo '<input type="hidden" name="selected_confirmed[]" value="'.$selected[$x].'" />'."\n";
				}
			echo '<input type="submit" name="delete_confirmed" value="';
			echo outputLangDebugInAttributes($lang['user_del_subm_b']).'" />'."\n";
			echo '</form>'."\n";
		break;
		case "empty":
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<p>'.$lang_add['empty_forum_note'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			echo '<b>'.$lang['password_marking'].'</b><br /><input type="password"';
			echo ' size="25" name="delete_all_postings_confirm_pw" /><br /><br />';
			echo '<input type="submit" name="delete_all_postings_confirmed" value="';
			echo outputLangDebugInAttributes($lang_add['empty_forum_sb']).'" />'."\n";
			echo '</form>'."\n";
		break;
		case "uninstall":
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<p>'.$lang_add['delete_db_note'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			echo '<input type="radio" name="delete_modus" value="tables"';
			echo ' checked="checked" /> '.$lang_add['delete_tables'].'<br />';
			echo '<input type="radio" name="delete_modus" value="db" /> ';
			echo str_replace("[database]",$db_settings['db'],$lang_add['delete_db']);
			echo '<br /><br /><b>'.$lang['password_marking'].'</b><br />';
			echo '<input type="password" size="25" name="delete_db_confirm_pw" /><br /><br />';
			echo '<input type="submit" name="delete_db_confirmed" value="';
			echo outputLangDebugInAttributes($lang_add['delete_db_note_sb']).'" />'."\n".'</form>'."\n";
		break;
		case "delete_marked_threads":
			$lang_add['del_marked_note'] = str_replace('[marked_symbol]', '<img src="img/marked.png" alt="[x]" width="9" height="9" />', $lang_add['del_marked_note']);
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<p>'.$lang_add['del_marked_note'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			echo '<input type="submit" name="delete_marked_threads_confirmed" value="';
			echo outputLangDebugInAttributes($lang_add['del_marked_sb']).'" />'."\n";
			echo '</form>'."\n";
		break;
		case "unmark":
			echo '<p>'.$lang_add['unmark_threads_note'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			echo '<input type="submit" name="unmark_confirmed" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" />'."\n".'</form>'."\n";
		break;
		case "invert_markings":
			echo '<p>'.$lang_add['invert_markings_note'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			echo '<input type="submit" name="invert_markings_confirmed" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></form>'."\n";
		break;
		case "mark_threads":
			echo '<form action="admin.php" method="post" style="display: inline;">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			$lang_add['mark_old_threads'] = str_replace('[number]', '<input type="text" name="n1" value="" size="4" />', $lang_add['mark_old_threads']);
			$lang_add['mark_old_threads_no_replies'] = str_replace('[number]', '<input type="text" name="n2" value="" size="4" />', $lang_add['mark_old_threads_no_replies']);
			echo '<p><input type="radio" name="mark_threads" value="1" checked="checked" />';
			echo $lang_add['mark_old_threads'].'</p>'."\n";
			echo '<p><input type="radio" name="mark_threads" value="2" /> ';
			echo $lang_add['mark_old_threads_no_replies'].'</p>'."\n";
			echo '<p><input type="submit" name="mark_threads_submitted" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></p></form>'."\n";
		break;
		case "lock_marked_threads":
			$lang_add['lock_marked_conf'] = str_replace('[marked_symbol]', '<img src="img/marked.png" alt="[x]" width="9" height="9" />', $lang_add['lock_marked_conf']);
			echo '<p>'.$lang_add['lock_marked_conf'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			echo '<input type="submit" name="lock_marked_threads_submitted" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></form>'."\n";
		break;
		case "unlock_marked_threads":
			$lang_add['unlock_marked_conf'] = str_replace('[marked_symbol]', '<img src="img/marked.png" alt="[x]" width="9" height="9" />', $lang_add['unlock_marked_conf']);
			echo '<p>'.$lang_add['unlock_marked_conf'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			if (isset($_GET['refer']))
				{
				echo '<input type="hidden" name="refer" value="';
				echo htmlspecialchars($_GET['refer']).'" />'."\n";
				}
			echo '<input type="submit" name="unlock_marked_threads_submitted" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></form>'."\n";
		break;
		case "delete_category":
			if (count($categories) > 1)
				{
				$cat_select = '<select class="kat" size="1" name="move_category" id="del-keep-cat">'."\n";
				while (list($key, $val) = each($categories))
					{
					if ($key != $category_id)
						{
						$cat_select .= '<option value="'.$key.'">'.$val.'</option>'."\n";
						}
					}
				$cat_select .= '</select>'."\n";
				}
			echo '<h2>'.str_replace("[category]",$category_name,$lang_add['del_cat_hl']).'</h2>'."\n";
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<form action="admin.php" method="post" style="display: inline;">'."\n";
			echo '<input type="hidden" name="category_id" value="';
			echo $category_id.'" />'."\n";
			if (count($categories) <= 1)
				{
				echo '<input type="hidden" name="move_category" value="0" />'."\n";
				}
			echo '<p><input type="radio" name="delete_mode" id="del-complete" value="complete"';
			echo ' checked="checked" /><label for="del-complete">'.$lang_add['del_cat_completely'].'</label></p>'."\n";
			echo '<p><input type="radio" name="delete_mode" id="del-keep" value="keep_entries" />';
			echo '<label for="del-keep">'.$lang_add['del_cat_keep_entries'].'</label>';
			if (count($categories) > 0)
				{
				echo ' <label for="del-keep-cat">'.str_replace("[category]",$cat_select,$lang_add['del_cat_move_entries']).'</label>';
				}
			echo '</p>'."\n".'<p><input type="submit" name="delete_category_submit" value="';
			echo outputLangDebugInAttributes($lang_add['del_cat_sb']).'" /></p></form>'."\n";
		break;
		case "edit_category":
			echo '<h2>'.$lang_add['cat_edit_hl'].'</h2>';
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<form action="admin.php" method="post"><div>'."\n";
			echo '<input type="hidden" name="id" value="'.$id.'" />'."\n";
			echo '<label for="cat-name">'.$lang_add['edit_category']."\n";
			echo '<input type="text" name="category" id="cat-name" value="';
			echo htmlspecialchars($category).'" size="25" /></label><br />'."\n";
			echo '<b>'.$lang_add['accessible_for'].'</b><br />'."\n";
			echo '<input type="radio" name="accession" id="access-all" value="0"';
			echo ($accession==0) ? ' checked="ckecked"' : '';
			echo ' /><label for="access-all">'.$lang_add['cat_accession_all'].'</label><br />'."\n";
			echo '<input type="radio" name="accession" id="access-user" value="1"';
			echo ($accession==1) ? ' checked="ckecked"' : '';
			echo ' /><label for="access-user">'.$lang_add['cat_accession_reg_users'].'</label><br />'."\n";
			echo '<input type="radio" name="accession" id="access-mod-admin" value="2"';
			echo ($accession==2) ? ' checked="ckecked"' : '';
			echo ' /><label for="access-mod-admin">'.$lang_add['cat_accession_mod_admin'].'</label><br /><br />'."\n";
			echo '<input type="submit" name="edit_category_submit" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></div></form>'."\n";
		break;
		case "backup":
			echo '<h2>'.$lang_add['backup_restore'].'</h2>';
			echo '<h3>'.$lang_add['backup'].'</h3>'."\n";
			echo '<ul class="linklist">'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=1">'.$lang_add['sql_complete'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=2">'.$lang_add['sql_forum'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=3">'.$lang_add['sql_forum_marked'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=4">'.$lang_add['sql_userdata'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=5">'.$lang_add['sql_categories'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=6">'.$lang_add['sql_settings'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=7">'.$lang_add['sql_smilies'].'</a></li>'."\n";
			echo '<li><a class="textlink" href="admin.php?backup=8">'.$lang_add['sql_banlists'].'</a></li>'."\n";
			echo '</ul>'."\n";
			echo '<h3>'.$lang_add['restore'].'</h3>'."\n";
			echo '<ul class="linklist">'."\n";
			echo '<li><a class="textlink" href="admin.php?action=import_sql">'.$lang_add['import_sql'].'</a></li>'."\n";
			echo '</ul>'."\n";
		break;
		case "import_sql":
			echo '<p class="caution">'.$lang['caution'].'</p>'."\n";
			echo '<p class="normal">'.$lang_add['import_sql_note'].'</p>'."\n";
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<form action="admin.php" method="post">'."\n";
			echo '<p><b>'.$lang_add['sql_dump'].'</b><br />'."\n";
			echo '<textarea name="sql" cols="70" rows="15">';
			echo (isset($sql)) ? htmlspecialchars($sql) : '';
			echo '</textarea></p>'."\n";
			echo '<p><label for="sql_pw">'.$lang['password_marking'].'</label><br />';
			echo '<input type="password" size="25" name="sql_pw" id="sql_pw" /></p>'."\n";
			echo '<p><input type="submit" name="sql_submit" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></p>'."\n";
			echo '</form>'."\n";
		break;
		case "import_sql_ok":
			echo '<p>'.$lang_add['import_sql_ok'].'</p>'."\n";
		break;
		case "email_list":
			$email_result = mysql_query("SELECT user_email FROM ".$db_settings['userdata_table'], $connid);
			if (!$email_result) die($lang['db_error']);
			while ($line = mysql_fetch_assoc($email_result))
				{
				$email_list[] = $line['user_email'];
				}
			mysql_free_result($email_result);
			echo '<textarea onfocus="if (this.value==this.defaultValue) this.select()"';
			echo ' readonly="readonly" cols="60" rows="15" />';
			echo implode(", ",$email_list).'</textarea>'."\n";
		break;
		case "clear_userdata":
			echo '<p>'.$lang_add['clear_userdata_expl'].'</p>'."\n";
			echo '<form action="admin.php" method="post">'."\n";
			echo '<table>'."\n";
			echo ' <tr>'."\n";
			echo '  <td><input type="radio" name="clear_userdata" value="1" checked="checked" /></td>'."\n";
			echo '  <td>'.$lang_add['clear_users_1'].'</td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td><input type="radio" name="clear_userdata" value="2" /></td>'."\n";
			echo '  <td>'.$lang_add['clear_users_2'].'</td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td><input type="radio" name="clear_userdata" value="3" /></td>'."\n";
			echo '  <td>'.$lang_add['clear_users_3'].'</td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td><input type="radio" name="clear_userdata" value="4" /></td>'."\n";
			echo '  <td>'.$lang_add['clear_users_4'].'</td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td><input type="radio" name="clear_userdata" value="5" /></td>'."\n";
			echo '  <td>'.$lang_add['clear_users_5'].'</td>'."\n";
			echo ' </tr>'."\n";
			echo '</table>'."\n";
			echo '<p><input type="submit" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></p>'."\n";
			echo '</form>'."\n";
		break;
		case "banlists":
			# get banned users:
			$result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'users' LIMIT 1", $connid);
			if (!$result) die($lang['db_error']);
			$data = mysql_fetch_assoc($result);
			$banned_users = str_replace(',',', ',$data['list']);
			mysql_free_result($result);
			# get banned ips:
			$result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'ips' LIMIT 1", $connid);
			if (!$result) die($lang['db_error']);
			$data = mysql_fetch_assoc($result);
			$banned_ips = str_replace(',',', ',$data['list']);
			mysql_free_result($result);
			# get not accepted words:
			$result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'words' LIMIT 1", $connid);
			if (!$result) die($lang['db_error']);
			$data = mysql_fetch_assoc($result);
			$not_accepted_words = str_replace(',',', ',$data['list']);
			mysql_free_result($result);
			echo '<form action="admin.php" method="post">'."\n";
			echo '<table class="normaltab">'."\n";
			echo ' <tr>'."\n";
			echo '  <td class="c"><label for="bann-user">'.$lang_add['banned_users'].'</label><br />';
			echo '<span class="info">'.$lang_add['banned_users_d'].'</span></td>'."\n";
			echo '  <td class="d"><textarea name="banned_users" id="bann-user" cols="50" rows="5">';
			if (isset($banned_users)) echo htmlspecialchars($banned_users);
			echo '</textarea></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="bann-ip">'.$lang_add['banned_ips'].'</label><br />';
			echo '<span class="info">'.$lang_add['banned_ips_d'].'</span></td>';
			echo '  <td class="d"><textarea name="banned_ips" id="bann-ip" cols="50" rows="5">';
			if (isset($banned_ips)) echo htmlspecialchars($banned_ips);
			echo '</textarea></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="bann-word">'.$lang_add['not_accepted_words'].'</label><br />';
			echo '<span class="info">'.$lang_add['not_accepted_words_d'].'</span></td>'."\n";
			echo '  <td class="d"><textarea name="not_accepted_words" id="bann-word" cols="50" rows="5">';
			if (isset($not_accepted_words)) echo htmlspecialchars($not_accepted_words);
			echo '</textarea></td>'."\n";
			echo ' </tr>'."\n";
			echo '</table>'."\n";
			echo '<p><input type="submit" name="banlists_submit" value="';
			echo outputLangDebugInAttributes($lang_add['banlists_submit']).'" /></p>'."\n";
			echo '</form>'."\n";
		break;
		case "smilies":
			if($settings['smilies'] == 1)
				{
				$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['smilies_table'], $connid);
				list($smilies_count) = mysql_fetch_row($count_result);
				mysql_free_result($count_result);
				$fp = opendir('img/smilies/');
				while ($file = readdir($fp))
					{
					if(preg_match('/\.gif$/i', $file) || preg_match('/\.png$/i', $file) || preg_match('/\.jpg$/i', $file))
						{
						$smiley_files[] = $file;
						}
					}
				closedir($fp);
				if ($smilies_count > 0)
					{
					$result = mysql_query("SELECT id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
					if (!$result) die($lang['db_error']);
					echo '<table class="normaltab">'."\n";
					echo ' <tr class="titlerow">'."\n";
					echo '  <th>'.$lang_add['edit_smilies_smiley'].'</th>'."\n";
					echo '  <th>'.$lang_add['edit_smilies_codes'].'</th>'."\n";
					echo '  <th>'.$lang_add['edit_smilies_title'].'</th>'."\n";
					echo '  <th colspan="2">'.$lang_add['edit_smilies_action'].'</th>'."\n";
					echo '  <th>'.$lang_add['edit_smilies_order'].'</th>'."\n";
					echo ' </tr>'."\n";
					$i=0;
					while ($line = mysql_fetch_assoc($result))
						{
						# remove used smilies from smiley array:
						if (isset($smiley_files))
							{
							unset($cleared_smiley_files);
							foreach ($smiley_files as $smiley_file)
								{
								if($line['file']!=$smiley_file) $cleared_smiley_files[] = $smiley_file;
								}
							if (isset($cleared_smiley_files)) $smiley_files = $cleared_smiley_files;
							else unset($smiley_files);
							}
						unset($codes);
						if (trim($line['code_1'])!='') $codes[] = stripslashes($line['code_1']);
						if (trim($line['code_2'])!='') $codes[] = stripslashes($line['code_2']);
						if (trim($line['code_3'])!='') $codes[] = stripslashes($line['code_3']);
						if (trim($line['code_4'])!='') $codes[] = stripslashes($line['code_4']);
						if (trim($line['code_5'])!='') $codes[] = stripslashes($line['code_5']);
						$codes_disp = implode(' &nbsp;',$codes);
						$rowClass = ($i % 2 == 0) ? "a" : "b";
						echo '<tr class="'.$rowClass.'">'."\n";
						echo '<td><img src="img/smilies/'.$line['file'].'" alt="'.$line['code_1'].'"';
						echo ($line['title']!='') ? 'title="'.$line['title'].'"' : '';
						echo '/></td>'."\n";
						echo '<td>'.$codes_disp.'</td>'."\n";
						echo '<td>'.$line['title'].'</td>'."\n";
						echo '<td><a href="admin.php?edit_smiley='.$line['id'].'">';
						echo $lang_add['edit_link'].'</a></td>'."\n";
						echo '<td><a href="admin.php?delete_smiley='.$line['id'].'">';
						echo $lang_add['delete_link'].'</a></td>'."\n";
						echo '<td><a href="admin.php?move_up_smiley='.$line['id'];
						echo '"><img src="img/up.png" alt="up" width="11" height="11" /></a>';
						echo '&nbsp;<a href="admin.php?move_down_smiley='.$line['id'];
						echo '"><img src="img/down.png" alt="down" width="11" height="11" /></a></td>'."\n";
						echo '</tr>'."\n";
						$i++;
						}
					mysql_free_result($result);
					echo '</table>'."\n";
					}
				else
					{
					echo '<p><i>'.$lang_add['no_smilies'].'</i></p>'."\n";
					}
				if (isset($errors))
					{
					echo errorMessages($errors);
					}
				if (isset($smiley_files)) $smiley_count = count($smiley_files);
				else $smiley_count = 0;
				if ($smiley_count > 0)
					{
					echo '<form action="admin.php" method="post">'."\n";
					echo '<table>'."\n";
					echo '<tr>'."\n";
					echo '<td>'.$lang_add['add_smiley_file'].'</td>'."\n";
					echo '<td>'.$lang_add['add_smiley_code'].'</td>'."\n";
					echo '<td>&nbsp;</td>'."\n";
					echo '</tr><tr>'."\n";
					echo '<td><select name="smiley_file" size="1">'."\n";
					foreach ($smiley_files as $smiley_file)
						{
						echo '<option value="'.htmlspecialchars($smiley_file);
						echo '">'.htmlspecialchars($smiley_file).'</option>'."\n";
						}
					echo '</select></td>'."\n";
					echo '<td><input type="text" name="smiley_code" size="10" /></td>'."\n";
					echo '<td><input type="submit" value="'.outputLangDebugInAttributes($lang['submit_button_ok']).'" /></td>'."\n";
					echo '</tr>'."\n";
					echo '</table>'."\n";
					echo '</form>'."\n";
					}
				else
					{
					echo '<p><i>'.$lang_add['no_other_smilies_in_folder'].'</i></p>'."\n";
					}
				}
			else
				{
				echo '<p><i>'.$lang_add['smilies_disabled'].'</i></p>'."\n";
				}
			echo '<p>';
			if ($settings['smilies']==1)
				{
				echo '<a href="admin.php?disable_smilies=true">'.$lang_add['disable_smilies'].'</a>';
				}
			else
				{
				echo '<a href="admin.php?enable_smilies=true">'.$lang_add['enable_smilies'].'</a>';
				}
			echo '</p>'."\n";
		break;
		case 'edit_smiley':
			if (isset($errors)) { echo errorMessages($errors); }
			echo '<form action="admin.php" method="post">'."\n";
			echo '<input type="hidden" name="id" value="'.$id.'" />'."\n";
			echo '<table class="normaltab">'."\n";
			echo '<tr>'."\n";
			echo '<td class="c"><label for="smiley-file">'.$lang_add['edit_smilies_smiley'].'</label></td>'."\n";
			echo '<td class="d"><select name="file" id="smiley-file" size="1">'."\n";
			$fp=opendir('img/smilies/');
			while ($dirfile = readdir($fp))
				{
				if(preg_match('/\.gif$/i', $dirfile)
				|| preg_match('/\.png$/i', $dirfile)
				|| preg_match('/\.jpg$/i', $dirfile))
					{
					echo '<option value="'.$dirfile.'"';
					echo ($dirfile == $file) ? ' selected="selected"' : '';
					echo '>'.$dirfile.'</option>'."\n";
					}
				}
			closedir($fp);
			echo '</select></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c">'.$lang_add['edit_smilies_codes'].'</td>'."\n";
			echo '<td class="d"><input type="text" name="code_1" size="7" value="';
			if (isset($code_1)) echo htmlspecialchars($code_1);
			echo '" /> <input type="text" name="code_2" size="7" value="';
			if (isset($code_2)) echo htmlspecialchars($code_2);
			echo '" /> <input type="text" name="code_3" size="7" value="';
			if (isset($code_3)) echo htmlspecialchars($code_3);
			echo '" /> <input type="text" name="code_4" size="7" value="';
			if (isset($code_4)) echo htmlspecialchars($code_4);
			echo '" /> <input type="text" name="code_5" size="7" value="';
			if (isset($code_5)) echo htmlspecialchars($code_5);
			echo '" /></td>'."\n";
			echo '</tr><tr>'."\n";
			echo '<td class="c"><label for="smiley-title">'.$lang_add['edit_smilies_title'].'</b></td>'."\n";
			echo '<td class="d"><input type="text" name="title" id="smiley-title" value="';
			if (isset($title)) echo htmlspecialchars($title);
			echo '" size="25" /></td>'."\n";
			echo '</tr>'."\n";
			echo '</table>'."\n";
			echo '<p><input type="submit" name="edit_smiley_submit" value="';
			echo outputLangDebugInAttributes($lang['submit_button_ok']).'" /></p>'."\n";
		break;
		case 'edit_user':
			if (isset($errors))
				{
				echo errorMessages($errors);
				}
			echo '<form action="admin.php" method="post"><div>'."\n";
			echo '<input type="hidden" name="edit_user_id" value="'.$edit_user_id.'" />'."\n";
			echo '<table class="normaltab">'."\n";
			echo ' <tr>'."\n";
			echo '  <td class="c"><label for="set-uname">'.$lang['username_marking'].'</label></td>'."\n";
			echo '  <td class="d"><input type="text" size="40" name="edit_user_name"';
			echo ' value="'.htmlspecialchars($edit_user_name).'" id="set-uname" /></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c">'.$lang_add['usertype_marking'].'</td>'."\n";
			echo '  <td class="d"><input type="radio" name="edit_user_type" value="user"';
			echo ($edit_user_type=="user") ? ' checked="checked"' : '';
			echo ' id="set-type-0" /><label for="set-type-0">'.$lang['ud_user'].'</label><br />';
			echo '<input type="radio" name="edit_user_type" value="mod"';
			echo ($edit_user_type=="mod") ? ' checked="checked"' : '';
			echo ' id="set-type-1" /><label for="set-type-1">'.$lang['ud_mod'].'</label><br />';
			echo '<input type="radio" name="edit_user_type" value="admin"';
			echo ($edit_user_type=="admin") ? ' checked="checked"' :'';
			echo ' id="set-type-2" /><label for="set-type-2">'.$lang['ud_admin'].'</label></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-email">'.$lang['user_email_marking'].'</label></td>'."\n";
			echo '  <td class="d"><input type="text" size="40" name="user_email" ';
			echo 'value="'.htmlspecialchars($user_email).'" id="set-email" /></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c">'.$lang['user_show_email'].'</td>'."\n";
			echo '  <td class="d"><input type="radio" name="hide_email" value="0"';
			echo ($hide_email=="0") ? ' checked="checked"' : '';
			echo ' id="show-mail-1" /><label for="show-mail-1">'.$lang['yes'].'</label><br />';
			echo '<input type="radio" name="hide_email" value="1"';
			echo ($hide_email=="1") ? ' checked="checked"' : '';
			echo ' id="show-mail-0" /><label for="show-mail-0">'.$lang['no'].'</label></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-r-name">'.$lang['user_real_name'].'</label></td>'."\n";
			echo '  <td class="d"><input type="text" size="40" name="user_real_name"';
			echo ' value="'.htmlspecialchars($user_real_name).'" maxlength="';
			echo $settings['name_maxlength'].'" id="set-r-name" /></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-hp">'.$lang['user_hp'].'</label></td>'."\n";
			echo '  <td class="d"><input type="text" size="40" name="user_hp" value="';
			echo htmlspecialchars($user_hp).'" maxlength="';
			echo $settings['hp_maxlength'].'" id="set-hp" /></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-place">'.$lang['user_place'].'</label></td>'."\n";
			echo '  <td class="d"><input type="text" size="40" name="user_place"';
			echo ' value="'.htmlspecialchars($user_place).'" maxlength="';
			echo $settings['place_maxlength'].'" id="set-place" /></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-profile">'.$lang['user_profile'].'</label></td>'."\n";
			echo '  <td class="d"><textarea cols="65" rows="4" name="profile" id="set-profile">';
			echo htmlspecialchars($profile).'</textarea></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="set-signature">'.$lang['user_signature'].'</label></td>'."\n";
			echo '  <td class="d"><textarea cols="65" rows="4" name="signature" id="set-signature">';
			echo htmlspecialchars($signature).'</textarea></td>'."\n";
			echo ' </tr>';
			if ($settings['thread_view'] != 0
			&& $settings['board_view'] != 0
			|| $settings['board_view'] != 0
			&& $settings['mix_view'] != 0
			|| $settings['thread_view'] != 0
			&& $settings['mix_view'] != 0)
				{
				echo '<tr>'."\n";
				echo '  <td class="c"><b>'.$lang['user_standard_view'].'</b></td>'."\n";
				echo '  <td class="d">'."\n";
				if ($settings['thread_view'] == 1)
					{
					echo '<input type="radio" name="user_view" id="view-thread" value="thread"';
					echo ($user_view=="thread") ? ' checked="checked"' : '';
					echo ' /><label for="view-thread">'.$lang['thread_view_linkname'].'</label><br />';
					}
				if ($settings['board_view'] == 1)
					{
					echo '<input type="radio" name="user_view" id="view-board" value="board"';
					echo ($user_view=="board") ? ' checked="checked"' : '';
					echo ' /><label for="view-board">'.$lang['board_view_linkname'].'</label><br />';
					}
				if ($settings['mix_view'] == 1)
					{
					echo '<input type="radio" name="user_view" id="view-mix" value="mix"';
					echo ($user_view=="mix") ? ' checked="checked"' : '';
					echo ' /><label for="view-mix">'.$lang['mix_view_linkname'].'</label>';
					}
				echo '</td>'."\n";
				echo ' </tr>'."\n";
				}
			echo ' <tr>'."\n";
			echo '  <td class="c">'.$lang['user_pers_msg'].'</td>'."\n";
			echo '  <td class="d"><input type="radio" name="personal_messages" value="1"';
			echo ($personal_messages=="1") ? ' checked="checked"' : '';
			echo ' id="pers-mess-1" /><label for="pers-mess-1">'.$lang['user_pers_msg_act'];
			echo '</label><br />';
			echo '<input type="radio" name="personal_messages" value="0"';
			echo ($personal_messages=="0") ? ' checked="checked"' : '';
			echo '  id="pers-mess-0" /><label for="pers-mess-0">'.$lang['user_pers_msg_deact'];
			echo '</label></td>'."\n";
			echo ' </tr><tr>'."\n";
			echo '  <td class="c"><label for="user-time-diff">'.$lang['user_time_diff'].'</b></td>'."\n";
			echo '  <td class="d"><select name="user_time_difference" id="user-time-diff" size="1">'."\n";
			for ($h = -24; $h <= 24; $h++)
				{
				echo '<option value="'.htmlspecialchars($h).'"';
				echo ($user_time_difference==$h) ? ' selected="selected"' : '';
				echo ' />'.htmlspecialchars($h).'</option>'."\n";
				}
			echo '</select></td>'."\n";
			echo ' </tr>';
			if ($edit_user_type=="admin" || $edit_user_type=="mod")
				{
				echo '<tr>'."\n";
				echo '  <td class="c">'.$lang['admin_mod_notif'].'</td>'."\n";
				echo '  <td class="d"><input type="checkbox" name="new_posting_notify" value="1"';
				echo ($new_posting_notify=="1") ? ' checked="checked"' : '';
				echo ' id="new-post" /><label for="new-post">'.$lang['admin_mod_notif_np'].'</label><br />';
				echo '  <input type="checkbox" name="new_user_notify" value="1"';
				echo ($new_user_notify=="1") ? ' checked=" checked"' : '';
				echo ' id="new-user" /><label for="new-user">'.$lang['admin_mod_notif_nu'].'</label></td>'."\n";
				echo ' </tr>';
				}
			echo "\n".'</table>'."\n";
			echo '<p><input type="submit" name="edit_user_submit" value="';
			echo outputLangDebugInAttributes($lang['userdata_subm_button']).'" />&nbsp;<input type="reset" value="';
			echo outputLangDebugInAttributes($lang['reset_button']).'" /></p>'."\n";
			echo '</div></form>'."\n";
		break;
		}
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."index.php");
	die("<a href=\"index.php\">further...</a>");
	}
?>
