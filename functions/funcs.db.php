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

/**
 * This file collects functions for communication with the database
 * and manipulation of its content
 *
 * @author Heiko August <post@auge8472.de>
 * @since version 1.8
 */



/**
 * connects to the database
 *
 * @param string $host
 * @param string $user (username)
 * @param string $pw (password)
 * @param string $db (database name)
 * @return ressource (of the database connection)
 */
function connect_db($host,$user,$pw,$db) {
global $lang;

$connid = @mysql_connect($host, $user, $pw);
if (!$connid) die($lang['db_error']);

mysql_select_db($db, $connid) or die($lang['db_error']);
mysql_set_charset("utf8",$connid) or die($lang['db_error']);

return $connid;
} # End: connect_db



/**
 * sends any query to the database
 * @param string [$query]
 * @param resource [$sql]
 * @return array, bool [false|true]
 */
function dbaseAskDatabase($q,$s) {
# $q: der auszufuehrende Query
# $s: die Kennung der DB-Verbindung

$a = @mysql_query($q,$s);

if ($a===false)
	{
	$return = false;
	}
else
	{
	if ($a===true)
		{
		# INSERT, UPDATE, ALTER etc. pp.
		$return = true;
		}
	else
		{
		# !true, !false, ressource number
		# SELECT, EXPLAIN, SHOW, DESCRIBE
		$b = dbaseGenerateAnswer($a);
		$return = $b;
		}
	}
return $return;
} # Ende: dbaseAskDatabase($q,$s)



/**
 * puts datasets into an associated array
 * @param resource [$resource number]
 * @return array [$datasets]
 */
function dbaseGenerateAnswer($a) {
$b = array();
while ($row = mysql_fetch_assoc($a))
	{
	$b[] = $row;
	}
return $b;
} # Ende: dbaseGenerateAnswer($a)



/**
 * splits SQL output into lines and strips the slashes
 *
 * @author Alex
 * @param string $sql
 * @return array $lines
 */
function split_sql($sql) {
# remove comments and empty lines:
$lines = explode("\n", $sql);
$cleared_lines = array();
foreach($lines as $line)
	{
	$line = trim($line);
	if($line != '' && substr($line,0,1)!='#') $cleared_lines[] = $line;
	}
unset($lines);
$lines2 = $cleared_lines;
foreach($lines2 as $line)
	{
	$line = stripslashes($line);
	if(substr($line, -1)==';') $lines[] = substr($line,0,-1);
	}
return $lines;
} # End: split_sql


/**
 * Functions for SQL-Dump in (admin.php)
 */
function sql_forum() {
global $db_settings, $connid, $lang;

$sql_result = mysql_query("SELECT
id,
pid,
tid,
uniqid,
time,
last_answer,
edited,
edited_by,
user_id,
name,
subject,
category,
email,
hp,
place,
ip,
text,
show_signature,
email_notify,
marked,
locked,
fixed,
views
FROM ".$db_settings['forum_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
while ($field = mysql_fetch_assoc($sql_result))
	{
	echo "INSERT INTO ".$db_settings['forum_table']." SET
	id = ".$field['id'].",
	pid = ".$field['pid'].",
	tid = ".$field['tid'].",
	uniqid = '".$field['uniqid']."',
	time = '".$field['time']."',
	last_answer = '".$field['last_answer']."',
	edited = '".$field['edited']."',
	edited_by = '".htmlspecialchars($field['edited_by'])."',
	user_id = ".$field['user_id'].",
	name = '".htmlspecialchars($field['name'])."',
	subject = '".htmlspecialchars($field['subject'])."',
	category = ".$field['category'].",
	email = '".htmlspecialchars($field['email'])."',
	hp = '".htmlspecialchars($field['hp'])."',
	place = '".htmlspecialchars($field['place'])."',
	ip = '".$field['ip']."',
	text = '".htmlspecialchars($field['text'])."',
	show_signature = ".$field['show_signature'].",
	email_notify = ".$field['email_notify'].",
	marked = ".$field['marked'].",
	locked = ".$field['locked'].",
	fixed = ".$field['fixed'].",
	views = ".$field['views'].";<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
} # End: sql_forum



function sql_forum_marked() {
global $lang, $db_settings, $connid;

$sql_result= mysql_query("SELECT
id,
pid,
tid,
uniqid,
time,
last_answer,
edited,
edited_by,
user_id,
name,
subject,
category,
email,
hp,
place,
ip,
text,
show_signature,
email_notify,
marked,
locked,
fixed,
views
FROM ".$db_settings['forum_table']."
WHERE marked='1'", $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Marked forum entries (<?php echo $db_settings['forum_table']; ?>):<br /><br /><?php
while ($field = mysql_fetch_assoc($sql_result))
	{
	echo "INSERT INTO ".$db_settings['forum_table']." SET
	id = ".$field['id'].",
	pid = ".$field['pid'].",
	tid = ".$field['tid'].",
	uniqid = '".$field['uniqid']."''".htmlspecialchars($field['email'])."',
	time = '".$field['time']."',
	last_answer = '".$field['last_answer']."',
	edited = '".$field['edited']."',
	edited_by = '".htmlspecialchars($field['edited_by'])."',
	user_id = ".$field['user_id'].",
	name = '".htmlspecialchars($field['name'])."',
	subject = '".htmlspecialchars($field['subject'])."',
	category = ".$field['category'].",
	email = '".htmlspecialchars($field['email'])."',
	hp = '".htmlspecialchars($field['hp'])."',
	place = '".htmlspecialchars($field['place'])."',
	ip = '".$field['ip']."',
	text = '".htmlspecialchars($field['text'])."',
	show_signature = ".$field['show_signature'].",
	email_notify = ".$field['email_notify'].",
	marked = ".$field['marked'].",
	locked = ".$field['locked'].",
	fixed = ".$field['fixed'].",
	views = ".$field['views'].";<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
} # End: sql_forum_marked



function sql_userdata() {
global $lang, $db_settings, $connid;

$sql_result = mysql_query("SELECT
user_id,
user_type,
user_name,
user_real_name,
user_pw,
user_email,
hide_email,
user_hp,
user_place,
signature,
profile,
logins,
last_login,
last_logout,
user_ip,
registered,
user_view,
new_posting_notify,
new_user_notify,
personal_messages,
time_difference,
user_lock,
pwf_code,
activate_code
FROM ".$db_settings['userdata_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Userdata (<?php echo $db_settings['userdata_table']; ?>):<br /><br /><?php
while ($field = mysql_fetch_assoc($sql_result))
	{
	echo "INSERT INTO ".$db_settings['userdata_table']." SET
	user_id = ".$field['user_id'].",
	user_type = '".$field['user_type']."',
	user_name = '".htmlspecialchars($field['user_name'])."',
	user_real_name = '".htmlspecialchars($field['user_real_name'])."',
	user_pw = '".htmlspecialchars($field['user_pw'])."',
	user_email = '".htmlspecialchars($field['user_email'])."',
	hide_email = '".$field['hide_email']."',
	user_hp = '".htmlspecialchars($field['user_hp'])."',
	user_place = '".htmlspecialchars($field['user_place'])."',
	signature = '".htmlspecialchars($field['signature'])."',
	profile = '".htmlspecialchars($field['profile'])."',
	logins = ".$field['logins'].",
	last_login = '".$field['last_login']."',
	last_logout = '".$field['last_logout']."',
	user_ip = '".$field['user_ip']."',
	registered = '".$field['registered']."',
	user_view = '".$field['user_view']."',
	new_posting_notify = ".$field['new_posting_notify'].",
	new_user_notify = ".$field['new_user_notify'].",
	personal_messages = ".$field['personal_messages'].",
	time_difference = ".$field['time_difference'].",
	user_lock = ".$field['user_lock'].",
	pwf_code = '".$field['pwf_code']."',
	activate_code = '".$field['activate_code']."';<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
} # End: sql_userdata



function sql_categories() {
global $lang, $db_settings, $connid;

$sql_result = mysql_query("SELECT
id,
category_order,
category,
description,
accession
FROM ".$db_settings['category_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Categories (<?php echo $db_settings['category_table']; ?>):<br /><br /><?php
while ($field = mysql_fetch_assoc($sql_result))
	{
	echo "INSERT INTO ".$db_settings['category_table']." SET
	id = ".$field['id'].",
	category_order = ".$field['category_order'].",
	category = '".htmlspecialchars($field['category'])."',
	description = '".htmlspecialchars($field['description'])."',
	accession = ".$field['accession'].";<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
} # End: sql_categories



function sql_settings() {
global $lang, $db_settings, $connid;

$sql_result = mysql_query("SELECT
name,
value
FROM ".$db_settings['settings_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Settings (<?php echo $db_settings['settings_table']; ?>)<br /><br /><?php
while ($field = mysql_fetch_assoc($sql_result))
	{
	echo "INSERT INTO ".$db_settings['settings_table']." SET
	name = '".$field['name']."',
	value = '".htmlspecialchars($field['value'])."';<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
} # End: sql_settings



function sql_smilies() {
global $lang, $db_settings, $connid;

$sql_result = mysql_query("SELECT
id,
order_id,
file,
code_1,
code_2,
code_3,
code_4,
code_5,
title
FROM ".$db_settings['smilies_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Smilies (<?php echo $db_settings['smilies_table']; ?>)<br /><br /><?php
while ($field = mysql_fetch_array($sql_result))
	{
	echo "INSERT INTO ".$db_settings['smilies_table']." SET
	id = ".$field['id'].",
	order_id = ".$field['order_id'].",
	file = '".htmlspecialchars($field['file'])."',
	code_1 = '".htmlspecialchars($field['code_1'])."',
	code_2 = '".htmlspecialchars($field['code_2'])."',
	code_3 = '".htmlspecialchars($field['code_3'])."',
	code_4 = '".htmlspecialchars($field['code_4'])."',
	code_5 = '".htmlspecialchars($field['code_5'])."',
	title = '".htmlspecialchars($field['title'])."';<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
}



function sql_banlists() {
global $lang, $db_settings, $connid;

$sql_result = mysql_query("SELECT
name,
list
FROM ".$db_settings['banlists_table'], $connid);
if (!$sql_result) die($lang['db_error']);

?><pre># Banlists (<?php echo $db_settings['banlists_table']; ?>)<br /><br /><?php
while ($field = mysql_fetch_array($sql_result))
	{
	echo "INSERT INTO ".$db_settings['banlists_table']." SET
	name = '".$field['name']."',
	list = '".htmlspecialchars($field['list'])."';<br />";
	}
mysql_free_result($sql_result);
?><br /></pre><?php
}

?>