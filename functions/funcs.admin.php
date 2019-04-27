<?php

/**
 * remove comments and empty lines from a query
 *
 * @param string $sql
 * @return array $lines
 */
function split_sql($sql) {
	// remove comments and empty lines:
	$lines = explode("\n", $sql);
	$cleared_lines = array();
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line != '' && substr($line,0,1) != '#') $cleared_lines[] = $line;
	}
	unset($lines);
	$lines2 = $cleared_lines;
	foreach ($lines2 as $line) {
		if (substr($line, -1) == ';') $lines[] = substr($line, 0, -1);
	}
	return $lines;
}

/**
 * Function for SQL-dump of the forum table
 */
function sql_forum() {
	global $db_settings, $connid, $lang;
	$sql_result = mysqli_query($connid, "SELECT id, pid, tid, uniqid, time, last_answer, edited, edited_by, user_id, name, subject, category, email, hp, place, ip, text, show_signature, email_notify, marked, locked, fixed, views FROM ".$db_settings['forum_table']);
	if (!$sql_result) die($lang['db_error']);
?><pre># Forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['forum_table']." VALUES (". intval($field['id']) .", ". intval($field['pid']) .", ". intval($field['tid']) .", '". mysql_real_escape_string($connid, $field['uniqid']) ."', ". intval($field['time']) .", '". mysql_real_escape_string($connid, $field['last_answer']) ."', '". mysql_real_escape_string($connid, $field['edited']) ."', '". mysql_real_escape_string($connid, $field['edited_by']) ."', ". intval($field['user_id']) .", '". mysql_real_escape_string($connid, $field['name']) ."', '". mysql_real_escape_string($connid, $field['subject']) ."', ". intval($field['category']) .", '". mysql_real_escape_string($connid, $field['email']) ."', '". mysql_real_escape_string($connid, $field['hp']) ."', '". mysql_real_escape_string($connid, $field['place']) ."', '". mysql_real_escape_string($connid, $field['ip']) ."', '". mysql_real_escape_string($connid, $field['text']) ."', ". intval($field['show_signature']) .", ". intval($field['email_notify']) .", ". intval($field['marked']) .", ". intval($field['locked']) .", ". intval($field['fixed']) .", ". intval($field['views']) .");<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the forum table
 */
function sql_forum_marked() {
	global $lang, $db_settings, $connid;
	$sql_result = mysqli_query($connid, "SELECT id, pid, tid, uniqid, time, last_answer, edited, edited_by, user_id, name, subject, category, email, hp, place, ip, text, show_signature, email_notify, marked, locked, fixed, views FROM ".$db_settings['forum_table']." WHERE marked='1'");
	if (!$sql_result) die($lang['db_error']);
?><pre># Marked forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['forum_table']." VALUES (". intval($field['id']) .", ". intval($field['pid']) .", ". intval($field['tid']) .", '". mysql_real_escape_string($connid, $field['uniqid']) ."', '". mysql_real_escape_string($connid, $field['time']) ."', '". mysql_real_escape_string($connid, $field['last_answer']) ."', '". mysql_real_escape_string($connid, $field['edited']) ."', '". mysql_real_escape_string($connid, $field['edited_by']) ."', ". intval($field['user_id']) .", '". mysql_real_escape_string($connid, $field['name']) ."', '". mysql_real_escape_string($connid, $field['subject']) ."', ". intval($field['category']) .", '". mysql_real_escape_string($connid, $field['email']) ."', '". mysql_real_escape_string($connid, $field['hp']) ."', '". mysql_real_escape_string($connid, $field['place']) ."', '". mysql_real_escape_string($connid, $field['ip']) ."', '". mysql_real_escape_string($connid, $field['text']) ."', ". intval($field['show_signature']) .", ". intval($field['email_notify']) .", ". intval($field['marked']) .", ". intval($field['locked']) .", ". intval($field['fixed']) .", ". intval($field['views']) .");<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the userdata table
 */
function sql_userdata() {
	global $lang, $db_settings, $connid;
	$sql_result = mysqli_query($connid, "SELECT user_id, user_type, user_name, user_real_name, user_pw, user_email, hide_email, user_hp, user_place, signature, profile, logins, last_login, last_logout, user_ip, registered, user_view, new_posting_notify, new_user_notify, personal_messages, time_difference, user_lock, pwf_code, activate_code FROM ".$db_settings['userdata_table']);
	if (!$sql_result) die($lang['db_error']);
?><pre># Userdata (<?php echo $db_settings['userdata_table']; ?>):<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['userdata_table']." VALUES (". intval($field['user_id']) .", '". mysqli_real_escape_string($connid, $field['user_type']) ."', '". mysqli_real_escape_string($connid, $field['user_name']) ."', '". mysqli_real_escape_string($connid, $field['user_real_name']) ."', '". mysqli_real_escape_string($connid, $field['user_pw']) ."', '". mysqli_real_escape_string($connid, $field['user_email']) ."', ". intval($field['hide_email']) .", '". mysqli_real_escape_string($connid, $field['user_hp']) ."', '". mysqli_real_escape_string($connid, $field['user_place']) ."', '". mysqli_real_escape_string($connid, $field['signature']) ."', '". mysqli_real_escape_string($connid, $field['profile'])."', ". intval($field['logins']) .", '". mysqli_real_escape_string($connid, $field['last_login']) ."', '". mysqli_real_escape_string($connid, $field['last_logout']) ."', '". mysqli_real_escape_string($connid, $field['user_ip'])."', '". mysqli_real_escape_string($connid, $field['registered']) ."', '". mysqli_real_escape_string($connid, $field['user_view']) ."', ". intval($field['new_posting_notify']).", ". intval($field['new_user_notify']).", ". intval($field['personal_messages']) .", ". intval($field['time_difference']) .", ". intval($field['user_lock']).", '". mysqli_real_escape_string($connid, $field['pwf_code']) ."', '". mysqli_real_escape_string($connid, $field['activate_code']) ."');<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the categories table
 */
function sql_categories() {
	global $lang, $db_settings, $connid;
	$sql_result = mysqli_query($connid, "SELECT id, category_order, category, description, accession FROM ".$db_settings['category_table']);
	if (!$sql_result) die($lang['db_error']);
?><pre># Categories (<?php echo $db_settings['category_table']; ?>):<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['category_table']." VALUES (". intval($field['id']) .", ". intval($field['category_order']).", '". mysqli_real_escape_string($connid, $field['category']) ."', '". mysqli_real_escape_string($connid, $field['description'])."', ". intval($field['accession']) .");<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the settings table
 */
function sql_settings() {
	global $lang, $db_settings, $connid;
	$sql_result = mysqli_query($connid, "SELECT name, value FROM ".$db_settings['settings_table']);
	if (!$sql_result) die($lang['db_error']);
?><pre># Settings (<?php echo $db_settings['settings_table']; ?>)<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['settings_table']." VALUES ('". mysqli_real_escape_string($connid, $field['name']) ."', '". mysqli_real_escape_string($connid, $field['value']) ."');<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the smilies table
 */
function sql_smilies() {
	global $lang, $db_settings, $connid;
	$sql_result = mysqli_query($connid, "SELECT id, order_id, file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']);
	if (!$sql_result) die($lang['db_error']);
?><pre># Smilies (<?php echo $db_settings['smilies_table']; ?>)<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['smilies_table']." VALUES (". intval($field['id']) .", ". intval($field['order_id']) .", '". mysqli_real_escape_string($connid, $field['file']) ."', '". mysqli_real_escape_string($connid, $field['code_1']) ."', '". mysqli_real_escape_string($connid, $field['code_2']) ."', '". mysqli_real_escape_string($connid, $field['code_3']) ."', '". mysqli_real_escape_string($connid, $field['code_4']) ."', '". mysqli_real_escape_string($connid, $field['code_5']) ."', '". mysqli_real_escape_string($connid, $field['title']) ."');<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

/**
 * Function for SQL-dump of the banlists table
 */
function sql_banlists() {
	global $lang, $db_settings, $connid;
	$sql_result=mysqli_query($connid, "SELECT name, list FROM ".$db_settings['banlists_table']);
	if(!$sql_result) die($lang['db_error']);
?><pre># Banlists (<?php echo $db_settings['banlists_table']; ?>)<br /><br /><?php
	while ($field = mysqli_fetch_assoc($sql_result)) {
		echo "INSERT INTO ".$db_settings['banlists_table']." VALUES ('". mysqli_real_escape_string($connid, $field['name']) ."', '". mysqli_real_escape_string($connid, $field['list']) ."');<br />";
	}
	mysqli_free_result($sql_result);
?><br /></pre><?php
}

?>
