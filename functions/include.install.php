<?php
function stripslashes_deep($value) {
$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
return $value;
} # Ende: stripslashes_deep



function update17to18($settings, $connid) {
global $db_settings, $lang_add;

# Alter settings table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["settings"] = "ALTER TABLE ".$db_settings['settings_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE name name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE value value VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD type VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD poss_values VARCHAR(160) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD standard VARCHAR(80) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD cat VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter banlist table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["banlist"] = "ALTER TABLE ".$db_settings['banlists_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE name name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE list list TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter smilies table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["smilies"]	= "ALTER TABLE ".$db_settings['smilies_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE id id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE order_id order_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE file file VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_1 code_1 VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_2 code_2 VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_3 code_3 VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_4 code_4 VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_5 code_5 VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE title title VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$alterTable["smilies1"] = "UPDATE ".$db_settings['smilies_table']." SET
code_2 = ''";
# Alter user online table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["uonline"] = "ALTER TABLE ".$db_settings['useronline_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE ip ip VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE time time INT(14) UNSIGNED NOT NULL DEFAULT '0',
CHANGE user_id user_id INT(11) UNSIGNED NULL DEFAULT '0'";
# Alter user data table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["userdat"] = "ALTER TABLE ".$db_settings['userdata_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE user_id user_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE user_type user_type VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_name user_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_real_name user_real_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_pw user_pw VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_email user_email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE hide_email hide_email TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE user_hp user_hp VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_place user_place VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE signature signature VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE profile profile TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE logins logins INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE user_ip user_ip VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD ip_addr INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE user_view user_view VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE new_posting_notify new_posting_notify TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE new_user_notify new_user_notify TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE personal_messages personal_messages TINYINT(1) UNSIGNED NULL DEFAULT '0',
ADD time_difference TINYINT(2) UNSIGNED NULL DEFAULT '0',
CHANGE user_lock user_lock TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE pwf_code pwf_code VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE activate_code activate_code VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter posting table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["posting"] = "ALTER TABLE ".$db_settings['forum_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE id id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE pid pid INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE tid tid INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE uniqid uniqid VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE time time DATETIME NOT NULL,
CHANGE edited_by edited_by VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_id user_id INT(11) UNSIGNED NULL DEFAULT '0',
CHANGE name name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE subject subject VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE category category INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE email email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE hp hp VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE place place VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE ip ip VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD ip_addr INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE text text TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE show_signature show_signature TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE email_notify email_notify TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE marked marked TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE locked locked TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE fixed fixed TINYINT(1) UNSIGNED NULL DEFAULT '0',
CHANGE views views INT(11) UNSIGNED NULL DEFAULT '0'";
# Alter category table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["category"] = "ALTER TABLE ".$db_settings['category_table']."
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci,
CHANGE id id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE category_order category_order INT(11) UNSIGNED NOT NULL,
CHANGE category category VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE description description VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE accession accession TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'";
$newTable["user_settings"] = "CREATE TABLE ".$db_settings['usersettings_table']." (
user_id int(12) unsigned NOT NULL,
name varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
value varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
PRIMARY KEY  (user_id,name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$newTable['us_template'] = "CREATE TABLE ".$db_settings['us_templates_table']." (
name varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
value varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
type enum('string','bool') NOT NULL default 'string'
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$newTable['user_subscriptions'] = "CREATE TABLE ".$db_settings['usersubscripts_table']." (
user_id int(12) unsigned NOT NULL,
tid int(12) unsigned NOT NULL,
UNIQUE KEY user_thread (user_id,tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$newTable['banned_ips'] = "CREATE TABLE ".$db_settings['banned_ips_table']." (
ip int(10) unsigned NOT NULL DEFAULT '0',
last_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
requests tinyint(2) unsigned NOT NULL DEFAULT '0',
UNIQUE KEY ip (ip)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$newSetting["reply_name"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'last_reply_name',
value = 1";
$newSetting["user_highlight"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'user_highlight',
value = 1";
$newSetting["show_posting_id"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'show_posting_id',
value = 0";
$newSetting["user_control_refresh"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'user_control_refresh',
value = 0";
$newSetting["server_tzone"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'server_timezone',
value = '".mysql_real_escape_string($settings['server_timezone'])."'";
$newSetting["control_refresh_template"] = "INSERT INTO ".$db_settings['us_templates_table']." SET
name = 'control_refresh',
value = 'false',
type = 'bool'";


# alter settings table
@mysql_query($alterTable["settings"], $connid) or $errors[] = str_replace("[table]", $db_settings['settings_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
# alter banlist table
if (empty($errors))
	{
	@mysql_query($alterTable["banlist"], $connid) or $errors[] = str_replace("[table]", $db_settings['banlists_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter smilies table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["smilies"], $connid) or $errors[] = str_replace("[table]", $db_settings['smilies_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter smilies table (part 2)
if (empty($errors)
	and (!empty($_POST["DeleteSmilies"])
	and $_POST["DeleteSmilies"] == "delete"))
	{
	@mysql_query($alterTable["smilies1"], $connid) or $errors[] = str_replace("[table]", $db_settings['smilies_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter user online table
if (empty($errors))
	{
	@mysql_query($alterTable["uonline"], $connid) or $errors[] = str_replace("[table]", $db_settings['useronline_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter user data table
if (empty($errors))
	{
	@mysql_query($alterTable["userdat"], $connid) or $errors[] = str_replace("[table]", $db_settings['userdata_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter posting data table
if (empty($errors))
	{
	@mysql_query($alterTable["posting"], $connid) or $errors[] = str_replace("[table]", $db_settings['forum_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# alter category data table
if (empty($errors))
	{
	@mysql_query($alterTable["category1"], $connid) or $errors[] = str_replace("[table]", $db_settings['category_table'],$lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# create new table for users own forum settings
if (empty($errors))
	{
	@mysql_query($newTable["user_settings"], $connid) or $errors[] = str_replace("[table]", $db_settings['usersettings_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# create new table for administrating users own forum settings
if (empty($errors))
	{
	@mysql_query($newTable['us_template'], $connid) or $errors[] = str_replace("[table]", $db_settings['us_templates_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# create new table for users own subscriptions
if (empty($errors))
	{
	@mysql_query($newTable['user_subscriptions'], $connid) or $errors[] = str_replace("[table]", $db_settings['usersubscripts_table'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# create new table for banned IPs
if (empty($errors))
	{
	@mysql_query($newTable['banned_ips'], $connid) or $errors[] = str_replace("[table]", $newTable['banned_ips'], $lang_add['db_alter_table_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}
# insert new settings
if (empty($errors))
	{
	foreach ($newSetting as $Set)
		{
		@mysql_query($Set, $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
		}
	}

# set value 1.8 for version string
if (empty($errors))
	{
	@mysql_query("UPDATE ".$db_settings['settings_table']." SET value='1.8' WHERE name = 'version'", $connid) or $errors[] = $lang_add['db_update_error']. " (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
	}

$return = (!empty($errors)) ? $errors : false;

return $return;
} # End: update17to18



/**
 * connects to a MySQL database
 * @param array [$db]
 * @return resource [$sql]
 * @since 1.8
 * @link http://termindbase.auge8472.de/dokumentation/dok.funcs_db.php
 */
function auge_connect_db($db) {
$s = @mysql_connect($db["host"],$db["user"],$db["pw"]);
$t = @mysql_select_db($db["db"],$s);

$return = array();

if ($s===false or $t===false)
	{
	$return["status"] = false;
	$return["errnbr"] = mysql_errno();
	}
else
	{
	# UTF-8 erzwingen (2008-07-29)
	$q = "SET NAMES utf8";
	$a = auge_ask_database($q,$s);
	if ($a===false)
		{
		$return["status"] = false;
		$return["errnbr"] = mysql_errno();
		}
	else
		{
		# $s: MySQL-resource-ID
		$return["status"] = $s;
		$return["errnbr"] = "0001";
		}
	}
return $return;
} # Ende: auge_connect_db()



/**
 * sends any query to the database
 * @param string [$query]
 * @param resource [$sql]
 * @return array [status: false, true, array; errnbr: numeric string]
 */
function auge_ask_database($q,$s) {
# $q: der auszufuehrende Query
# $s: die Kennung der DB-Verbindung
$return = array();

$a = @mysql_query($q,$s);

if ($a===false)
	{
	$return["status"] = false;
	$return["errnbr"] = mysql_errno();
	}
else
	{
	if ($a===true)
		{
		# INSERT, UPDATE, ALTER etc. pp.
		$return["status"] = true;
		$return["errnbr"] = "0002";
		}
	else
		{
		# !true, !false, ressource number
		# SELECT, EXPLAIN, SHOW, DESCRIBE
		$b = auge_generate_answer($a);
		$return["status"] = $b;
		$return["errnbr"] = "0003";
		}
	}
return $return;
} # Ende: auge_ask_database($q,$s)



/**
 * puts datasets into an associated array
 * @param resource [$resource number]
 * @return array [$datasets]
 */
function auge_generate_answer($a) {
$b = array();
while ($row = mysql_fetch_assoc($a))
	{
	$b[] = $row;
	}
return $b;
} # Ende: auge_generate_answer($a)



function errorMessages($error) {
global $lang;

$r  = "<h3>".$lang['error_headline']."</h3>\n";
$r .= "<ul>\n";
foreach ($error as $err)
	{
	$r .= " <li>".htmlspecialchars($err)."</li>\n";
	}
$r .= "</ul>\n";
return $r;
} # End: errorMessages



if (date_default_timezone_get())
	{
	$server_tz = date_default_timezone_get();
	}
else if (ini_get('date.timezone'))
	{
	$server_tz = ini_get('date.timezone');
	}
else
	{
	$server_tz = '';
	}


// default settings:
$settings['forum_name'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'my little forum 1', 'cat'=>'general');
$settings['forum_email'] =  array('type'=>'string', 'poss_values'=>'', 'standard'=>'', 'cat'=>'general');
$settings['forum_address'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'', 'cat'=>'general');
$settings['home_linkaddress'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'/', 'cat'=>'general');
$settings['home_linkname'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'', 'cat'=>'general');
$settings['language_file'] = array('type'=>'array', 'poss_values'=>'file:./lang/', 'standard'=>'english.php', 'cat'=>'general');
$settings['template'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'template.html', 'cat'=>'general');
$settings['access_for_users_only'] = array('type'=>'array', 'poss_values'=>'0:access_all_users, 1:access_only_reg_users', 'standard'=>'1', 'cat'=>'general');
$settings['entries_by_users_only'] = array('type'=>'array', 'poss_values'=>'0:access_all_users, 1:access_only_reg_users', 'standard'=>'1', 'cat'=>'security');
$settings['register_by_admin_only'] = array('type'=>'array', 'poss_values'=>'0:access_all_users, 1:access_only_reg_users', 'standard'=>'1', 'cat'=>'security');
$settings['standard'] = array('type'=>'array', 'poss_values'=>'thread:standard_thread, board:standard_board, mix:standard_mix', 'standard'=>'thread', 'cat'=>'views');
$settings['thread_view'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['board_view'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['mix_view'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['show_registered'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['show_posting_id'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['remember_userstandard'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'sessions');
$settings['remember_userdata'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'sessions');
$settings['remember_last_visit'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'sessions');
$settings['all_views_direct'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['thread_depth_indent'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'15', 'cat'=>'views');
$settings['thread_indent_mix'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'15', 'cat'=>'views');
$settings['max_thread_indent_mix'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'300', 'cat'=>'views');
$settings['thread_indent_mix_topic'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'15', 'cat'=>'views');
$settings['max_thread_indent_mix_topic'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'500', 'cat'=>'views');
$settings['empty_postings_possible'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['email_notification'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'general');
$settings['user_edit'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'general');
$settings['user_delete'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'general');
$settings['show_if_edited'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'postings');
$settings['dont_reg_edit_by_admin'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'postings');
$settings['dont_reg_edit_by_mod'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'postings');
$settings['edit_period'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'180', 'cat'=>'security');
$settings['edit_delay'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'5', 'cat'=>'security');
$settings['bbcode'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'postings');
$settings['bbcode_img'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'enhanced');
$settings['upload_images'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'enhanced');
$settings['smilies'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'enhanced');
$settings['autolink'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'enhanced');
$settings['count_views'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'postings');
$settings['provide_rssfeed'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'general');
$settings['autologin'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'general');
$settings['admin_mod_highlight'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['user_highlight'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'views');
$settings['topics_per_page'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'40', 'cat'=>'views');
$settings['users_per_page'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'40', 'cat'=>'views');
$settings['answers_per_topic'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'20', 'cat'=>'views');
$settings['search_results_per_page'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'40', 'cat'=>'views');
$settings['name_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'40', 'cat'=>'views');
$settings['name_word_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'25', 'cat'=>'views');
$settings['email_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'100', 'cat'=>'postings');
$settings['hp_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'200', 'cat'=>'postings');
$settings['place_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'40', 'cat'=>'postings');
$settings['place_word_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'25', 'cat'=>'postings');
$settings['subject_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'60', 'cat'=>'postings');
$settings['subject_word_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'25', 'cat'=>'postings');
$settings['text_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'7500', 'cat'=>'postings');
$settings['profile_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'5000', 'cat'=>'postings');
$settings['signature_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'255', 'cat'=>'postings');
$settings['text_word_maxlength'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'70', 'cat'=>'postings');
$settings['signature_separator'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'---', 'cat'=>'postings');
$settings['quote_symbol'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'>', 'cat'=>'postings');
$settings['count_users_online'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['last_reply_link'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['last_reply_name'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'views');
$settings['time_difference'] = array('type'=>'array', 'poss_values'=>'function:hours', 'standard'=>'0', 'cat'=>'general');
$settings['upload_max_img_size'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'60', 'cat'=>'enhanced');
$settings['upload_max_img_width'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'600', 'cat'=>'enhanced');
$settings['upload_max_img_height'] = array('type'=>'integer', 'poss_values'=>'', 'standard'=>'600', 'cat'=>'enhanced');
$settings['mail_parameter'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'', 'cat'=>'general');
$settings['forum_disabled'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'general');
$settings['session_prefix'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'mlf_', 'cat'=>'general');
$settings['version'] = array('type'=>'string', 'poss_values'=>'', 'standard'=>'1.8 beta', 'cat'=>'general');
$settings['captcha_posting'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'security');
$settings['captcha_contact'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'security');
$settings['captcha_register'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'1', 'cat'=>'security');
$settings['captcha_type'] = array('type'=>'array', 'poss_values'=>'0:captcha_type_math, 1:captcha_type_image', 'standard'=>'0', 'cat'=>'security');
$settings['user_control_refresh'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'enhanced');
$settings['user_control_sort_thread_threads'] = array('type'=>'array', 'poss_values'=>'0:0, 1:1', 'standard'=>'0', 'cat'=>'enhanced');
$settings['thread_view_sorter'] = array('type'=>'array', 'poss_values'=>'ASC:asc, DESC:desc', 'standard'=>'ASC', 'cat'=>'views');
#$settings['server_timezone'] = $server_tz;
#$settings['user_control_css'] = 0;


$smilies = array(
array('smile.gif', ':-)', '', '', '', '', ''),
array('wink.gif', ';-)', '', '', '', '', ''),
array('tongue.gif', ':-P', '', '', '', '', ''),
array('biggrin.gif', ':-D', '', '', '', '', ''),
array('neutral.gif', ':-|', '', '', '', '', ''),
array('frown.gif', ':-(', '', '', '', '', ''),
array('yes.gif', ':yes:', '', '', '', '', ''),
array('no.gif', ':no:', '', '', '', '', ''),
array('ok.gif', ':ok:', '', '', '', '', ''),
array('lol.gif', ':lol:', '', '', '', '', ''),
array('lol2.gif', ':lol2:', '', '', '', '', ''),
array('lol3.gif', ':lol3:', '', '', '', '', ''),
array('cool.gif', ':cool:', '', '', '', '', ''),
array('surprised.gif', ':surprised:', '', '', '', '', ''),
array('angry.gif', ':angry:', '', '', '', '', ''),
array('crying.gif', ':crying:', '', '', '', '', ''),
array('waving.gif', ':waving:', '', '', '', '', ''),
array('confused.gif', ':confused:', '', '', '', '', ''),
array('clap.gif', ':clap:', '', '', '', '', ''),
array('lookaround.gif', ':lookaround:', '', '', '', '', ''),
array('love.gif', ':love:', '', '', '', '', ''),
array('hungry.gif', ':hungry:', '', '', '', '', ''),
array('rotfl.gif', ':rotfl:', '', '', '', '', ''),
array('sleeping.gif', ':sleeping:', '', '', '', '', ''),
array('wink2.gif', ':wink:', '', '', '', '', ''),
array('flower.gif', ':flower:', '', '', '', '', '')
);

$usersettings[] = array('name'=>'control_refresh','value'=>'false','type'=>'bool');


?>