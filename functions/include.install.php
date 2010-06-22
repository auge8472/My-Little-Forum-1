<?php
function stripslashes_deep($value) {
$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
return $value;
} # Ende: stripslashes_deep



function update17to18($settings, $connid) {
global $db_settings, $lang_add;

# Alter settings table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["settings1"] = "ALTER TABLE ".$db_settings['settings_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["settings2"] = "ALTER TABLE ".$db_settings['settings_table']."
CHANGE name name VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE value value VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter banlist table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["banlist1"] = "ALTER TABLE ".$db_settings['banlists_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["banlist2"] = "ALTER TABLE ".$db_settings['banlists_table']."
CHANGE name name VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE list list TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter smilies table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["smilies1"] = "ALTER TABLE ".$db_settings['smilies_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["smilies2"]	= "ALTER TABLE ".$db_settings['smilies_table']."
CHANGE id id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE order_id order_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE file file VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_1 code_1 VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_2 code_2 VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_3 code_3 VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_4 code_4 VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE code_5 code_5 VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE title title VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
$alterTable["smilies3"] = "UPDATE ".$db_settings['smilies_table']." SET
code_2 = ''";
# Alter user online table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["uonline1"] = "ALTER TABLE ".$db_settings['useronline_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["uonline2"] = "ALTER TABLE ".$db_settings['useronline_table']."
CHANGE ip ip VARCHAR( 39 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE time time INT( 14 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE user_id user_id INT( 11 ) UNSIGNED NULL DEFAULT '0'";
# Alter user data table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["userdat1"] = "ALTER TABLE ".$db_settings['userdata_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["userdat2"] = "ALTER TABLE ".$db_settings['userdata_table']."
CHANGE user_id user_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE user_type user_type VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_name user_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_real_name user_real_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_pw user_pw VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_email user_email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE hide_email hide_email TINYINT(4) UNSIGNED NULL DEFAULT '0',
CHANGE user_hp user_hp VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_place user_place VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE signature signature VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE profile profile TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE logins logins INT(11) UNSIGNED NOT NULL DEFAULT '0',
CHANGE user_ip user_ip VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_view user_view VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE new_posting_notify new_posting_notify TINYINT(4) UNSIGNED NULL DEFAULT '0',
CHANGE new_user_notify new_user_notify TINYINT(4) UNSIGNED NULL DEFAULT '0',
CHANGE personal_messages personal_messages TINYINT(4) UNSIGNED NULL DEFAULT '0',
CHANGE user_lock user_lock TINYINT(4) UNSIGNED NULL DEFAULT '0',
CHANGE pwf_code pwf_code VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE activate_code activate_code VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
# Alter posting table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["posting1"] = "ALTER TABLE ".$db_settings['forum_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["posting2"] = "ALTER TABLE ".$db_settings['forum_table']."
CHANGE id id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE pid pid INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE tid tid INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE uniqid uniqid VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE edited_by edited_by VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE user_id user_id INT( 11 ) UNSIGNED NULL DEFAULT '0',
CHANGE name name VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE subject subject VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE category category INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE email email VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE hp hp VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE place place VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE ip ip VARCHAR( 39 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE text text TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE show_signature show_signature TINYINT( 4 ) UNSIGNED NULL DEFAULT '0',
CHANGE email_notify email_notify TINYINT( 4 ) UNSIGNED NULL DEFAULT '0',
CHANGE marked marked TINYINT( 4 ) UNSIGNED NULL DEFAULT '0',
CHANGE locked locked TINYINT( 4 ) UNSIGNED NULL DEFAULT '0',
CHANGE fixed fixed TINYINT( 4 ) UNSIGNED NULL DEFAULT '0',
CHANGE views views INT( 11 ) UNSIGNED NULL DEFAULT '0'";
# Alter category table, set text rows and table to utf8, numeral rows to unsigned
$alterTable["category1"] = "ALTER TABLE ".$db_settings['category_table']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$alterTable["category2"] = "ALTER TABLE ".$db_settings['category_table']."
CHANGE id id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE category_order category_order INT( 11 ) UNSIGNED NOT NULL,
CHANGE category category VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE description description VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE accession accession TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'";
$newTable["user_settings"] = "CREATE TABLE ".$db_settings['usersettings_table']." (
user_id int(12) unsigned NOT NULL,
name varchar(60) NOT NULL default '',
value varchar(40) NOT NULL default '',
PRIMARY KEY  (user_id,name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$newTable['us_template'] = "CREATE TABLE ".$db_settings['us_templates_table']." (
name varchar(60) NOT NULL,
value varchar(40) NOT NULL,
type enum('string','bool') NOT NULL default 'string'
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

# alter settings table (part 1)
@mysql_query($alterTable["settings1"], $connid) or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
# alter settings table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["settings2"], $connid) or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter banlist table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["banlist1"], $connid) or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter banlist table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["banlist2"], $connid) or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter smilies table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["smilies1"], $connid) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter smilies table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["smilies2"], $connid) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter smilies table (part 3)
if (empty($errors) and (!empty($_POST["DeleteSmilies"]) and $_POST["DeleteSmilies"]=="delete"))
	{
	@mysql_query($alterTable["smilies3"], $connid) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter user online table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["uonline1"], $connid) or $errors[] = str_replace("[table]",$db_settings['useronline_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter user online table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["uonline2"], $connid) or $errors[] = str_replace("[table]",$db_settings['useronline_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter user data table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat1"], $connid) or $errors[] = str_replace("[table]",$db_settings['userdata_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter user data table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat2"], $connid) or $errors[] = str_replace("[table]",$db_settings['userdata_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter posting data table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat1"], $connid) or $errors[] = str_replace("[table]",$db_settings['forum_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter posting data table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat2"], $connid) or $errors[] = str_replace("[table]",$db_settings['forum_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter category data table (part 1)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat1"], $connid) or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# alter category data table (part 2)
if (empty($errors))
	{
	@mysql_query($alterTable["userdat2"], $connid) or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# create new table for users own forum settings
if (empty($errors))
	{
	$mysql_query($newTable["user_settings"], $connid) or $errors[] = str_replace("[table]",$db_settings['usersettings_table'],$lang_add['db_alter_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
# insert new settings
if (empty($errors))
	{
	foreach ($newSetting as $Set)
		{
		@mysql_query($Set, $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
		}
	}
 
# set value 1.8 for version string
if (empty($errors))
	{
	@mysql_query("UPDATE ".$db_settings['settings_table']." SET value='1.8' WHERE name = 'version'", $connid) or $errors[] = $lang_add['db_update_error']. " (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

$return = (isset($errors)) ? $errors : false;
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
		$return["errnbr"] = "0002";
		}
	}
return $return;
} # Ende: auge_connect_db()



/**
 * sends any query to the database
 * @param string [$query]
 * @param resource [$sql]
 * @return bool [false]
 * @return bool [true]
 * @return array [$datasets]
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
		$return["errnbr"] = "0001";
		}
	else
		{
		# !true, !false, ressource number
		# SELECT, EXPLAIN, SHOW, DESCRIBE
		$b = auge_generate_answer($a);
		$return["status"] = $b;
		$return["errnbr"] = "0002";
		}
	}
return $return;
} # Ende: auge_ask_database($q,$s)



/**
 * puts datasets into an associated array
 * @param resource []
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
$settings['forum_name'] = 'my little forum';
$settings['forum_email'] = '';
$settings['forum_address'] = '';
$settings['home_linkaddress'] = '/';
$settings['home_linkname'] = '';
$settings['language_file'] = 'english.php';
$settings['template'] = 'template.html';
$settings['access_for_users_only'] = 0;
$settings['entries_by_users_only'] = 0;
$settings['register_by_admin_only'] = 0;
$settings['standard'] = "thread";
$settings['thread_view'] = 1;
$settings['board_view'] = 1;
$settings['mix_view'] = 0;
$settings['show_registered'] = 0;
$settings['show_posting_id'] = 0;
$settings['remember_userstandard'] = 1;
$settings['remember_userdata'] = 1;
$settings['remember_last_visit'] = 1;
$settings['all_views_direct'] = 0;
$settings['thread_depth_indent'] = 15;
$settings['thread_indent_mix'] = 20;
$settings['max_thread_indent_mix'] = 300;
$settings['thread_indent_mix_topic'] = 30;
$settings['max_thread_indent_mix_topic'] = 500;
$settings['empty_postings_possible'] = 0;
$settings['email_notification'] = 1;
$settings['user_edit'] = 1;
$settings['user_delete'] = 0;
$settings['show_if_edited'] = 1;
$settings['dont_reg_edit_by_admin'] = 1;
$settings['dont_reg_edit_by_mod'] = 0;
$settings['edit_period'] = 180;
$settings['edit_delay'] = 5;
$settings['bbcode'] = 1;
$settings['bbcode_img'] = 0;
$settings['upload_images'] = 0;
$settings['smilies'] = 1;
$settings['autolink'] = 1;
$settings['count_views'] = 0;
$settings['provide_rssfeed'] = 0;
$settings['autologin'] = 1;
$settings['admin_mod_highlight'] = 0;
$settings['user_highlight'] = 0;
$settings['topics_per_page'] = 40;
$settings['users_per_page'] = 40;
$settings['answers_per_topic'] = 50;
$settings['search_results_per_page'] = 20;
$settings['name_maxlength'] = 40;
$settings['name_word_maxlength'] = 25;
$settings['email_maxlength'] = 50;
$settings['hp_maxlength'] = 70;
$settings['place_maxlength'] = 40;
$settings['place_word_maxlength'] = 25;
$settings['subject_maxlength'] = 60;
$settings['subject_word_maxlength'] = 25;
$settings['text_maxlength'] = 5000;
$settings['profile_maxlength'] = 5000;
$settings['signature_maxlength'] = 255;
$settings['text_word_maxlength'] = 70;
$settings['signature_separator'] = '---<br />';
$settings['quote_symbol'] = '»';
$settings['count_users_online'] = 1;
$settings['last_reply_link'] = 0;
$settings['last_reply_name'] = 1;
$settings['time_difference'] = 0;
$settings['upload_max_img_size'] = 60;
$settings['upload_max_img_width'] = 600;
$settings['upload_max_img_height'] = 600;
$settings['mail_parameter'] = '';
$settings['forum_disabled'] = 0;
$settings['session_prefix'] = 'mlf_';
$settings['version'] = '1.8';
$settings['captcha_posting'] = 0;
$settings['captcha_contact'] = 0;
$settings['captcha_register'] = 0;
$settings['captcha_type'] = 0;
$settings['user_control_refresh'] = 0;
$settings['server_timezone'] = $server_tz;
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