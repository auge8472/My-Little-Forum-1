<?php
// default settings:
$settings['forum_name'] = "my little forum";
$settings['forum_email'] = "";
$settings['forum_address'] = "";
$settings['home_linkaddress'] = "/";
$settings['home_linkname'] = "";
$settings['language_file'] = "english.php";
$settings['template'] = "template.html";
$settings['access_for_users_only'] = 0;
$settings['entries_by_users_only'] = 0;
$settings['register_by_admin_only'] = 0;
$settings['standard'] = "thread";
$settings['thread_view'] = 1;
$settings['board_view'] = 1;
$settings['mix_view'] = 0;
$settings['show_registered'] = 0;
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
$settings['signature_separator'] = "---<br />";
$settings['quote_symbol'] = "»";
$settings['count_users_online'] = 1;
$settings['last_reply_link'] = 0;
$settings['last_reply_name'] = 1;
$settings['time_difference'] = 0;
$settings['upload_max_img_size'] = 60;
$settings['upload_max_img_width'] = 600;
$settings['upload_max_img_height'] = 600;
$settings['mail_parameter'] = "";
$settings['forum_disabled'] = 0;
$settings['session_prefix'] = "mlf_";
$settings['version'] = '1.7.7';
$settings['captcha_posting'] = 0;
$settings['captcha_contact'] = 0;
$settings['captcha_register'] = 0;
$settings['captcha_type'] = 0;

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
array('flower.gif', ':flower:', '', '', '', '', ''),
);

# Lists all versions wich can be updated.
# Update the list in the case of a new version 1.x!
$updateVersions = array("1.3","1.4","1.5","1.6");

// update functions:
function update13to14() {
global $db_settings, $settings, $connid, $lang_add;

# Queries to renaming and altering database tables.
$alter["rename_ft"] = "ALTER TABLE forum_table RENAME ".$db_settings['forum_table'];
$alter["rename_udt"] = "ALTER TABLE userdata_table RENAME ".$db_settings['userdata_table'];
$alter["rename_uot"] = "ALTER TABLE useronline_table RENAME ".$db_settings['useronline_table'];
$alter["add_ft_fixed"] = "ALTER TABLE ".$db_settings['forum_table']." ADD fixed tinyint(4) NOT NULL default '0' AFTER locked";
$alter["create_fst"] = "CREATE TABLE ".$db_settings['settings_table']." (name varchar(255) NOT NULL default '', value varchar(255) NOT NULL default '')";

@mysql_query($alter["rename_ft"], $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query($alter["rename_udt"], $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query($alter["rename_uot"], $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query($alter["add_ft_fixed"], $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query($alter["create_fst"], $connid) or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

# Create the address for the forum page (only domain with directories and trailing slash)
$settings['forum_address'] = 'http://'.$_SERVER['SERVER_NAME'].str_replace("install.php","",$_SERVER['SCRIPT_NAME']);

while(list($key, $val) = each($settings))
	{
	# Query to put every single setting into a database table row
	$fillSetting = "INSERT INTO ".$db_settings['settings_table']." SET
	name = '".$key."',
	value = '".mysql_real_escape_string($val)."'";
	@mysql_query($fillSetting, $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'template'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'thread_depth_indent'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'edit_period'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'mail_parameter'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'forum_disabled'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'version'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'forum_disabled'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'captcha_posting'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'captcha_contact'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'captcha_register'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'captcha_type'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

# Query to create a separate database table for categories
$createCategoryTable = "CREATE TABLE ".$db_settings['category_table']." (
category_order int(11) unsigned NOT NULL,
category varchar(255) NOT NULL default '',
accession tinyint(4) unsigned NOT NULL default '0'
)";
@mysql_query($createCategoryTable, $connid) or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
$categories_result = mysql_query("SELECT DISTINCT category FROM ".$db_settings['forum_table']." ORDER BY category ASC", $connid);
if(!$categories_result) die($comment_lang['db_error']);
$i=1;
while ($data = mysql_fetch_array($categories_result))
	{
	@mysql_query("INSERT INTO ".$db_settings['category_table']." (category_order, category, accession) VALUES (".intval($i).", '".mysql_real_escape_string($data['category'])."',0)", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	$i++;
	}

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update13to14



function update14to15() {
global $db_settings, $settings, $connid, $lang_add;

$listSettings = "SELECT
value
FROM ".$db_settings['settings_table']."
WHERE name = 'upload_max_img_size'
LIMIT 1";

$settings_result = mysql_query($listSettings, $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_read_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
if(!$settings_result) die($lang['db_error']);

$settings_count = mysql_num_rows($settings_result);
mysql_free_result($settings_result);
if($settings_count != 1)
	{
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('upload_max_img_size','60')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('upload_max_img_width','600')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('upload_max_img_height','600')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('template','template.html')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('thread_depth_indent','15')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('not_accepted_words_file','')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('edit_period','180')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'thread_indent'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'max_thread_indent'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update14to15



function update15to16() {
global $db_settings, $settings, $connid, $smilies, $lang_add;

$newSettings = array(
array("type"="insert",
"query"="INSERT INTO ".$db_settings['settings_table']." SET
name = 'mail_parameter',
value = '".mysql_real_escape_string($settings['mail_parameter'])."'"),
array("type"="insert",
"query"="INSERT INTO ".$db_settings['settings_table']." SET
name = 'forum_disabled',
value = '".intval($settings['forum_disabled'])."'"),
array("type"="insert",
"query"="INSERT INTO ".$db_settings['settings_table']." SET
name = 'session_prefix',
value = 'mlf_'"),
array("type"="insert",
"query"="INSERT INTO ".$db_settings['settings_table']." SET
name = 'version',
value = '".mysql_real_escape_string($settings['version'])."'"),
array("type"="insert",
"query"="INSERT INTO ".$db_settings['settings_table']." SET
name = 'users_per_page',
value = '40'"),
array("type"="update",
"query"="UPDATE ".$db_settings['settings_table']." SET
value='".mysql_real_escape_string($settings['language_file'])."'
WHERE name = 'language_file'"),
array("type"="update",
"query"="UPDATE ".$db_settings['settings_table']." SET
value='".mysql_real_escape_string($settings['template'])."'
WHERE name = 'template'"));

// add settings:
foreach ($newSettings as $nSet)
	{
	$errorMessage = ($nSet["type"]=="update") ? $lang_add['db_update_error'] : $lang_add['db_insert_settings_error'];
	@mysql_query($nSet["query"], $connid) or $errors[] = $errorMessage." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	$errorMessage = $lang_add['db_insert_settings_error'];
	}

$settings_result = mysql_query("SELECT value FROM ".$db_settings['settings_table']." WHERE name = 'edit_period' LIMIT 1", $connid) or die(mysql_error());
if(!$settings_result) die($lang['db_error']);

$settings_count = mysql_num_rows($settings_result);
mysql_free_result($settings_result);

if($settings_count != 1)
	{
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('edit_period','180')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

@mysql_query("DELETE FROM ".$db_settings['settings_table']." WHERE name = 'not_accepted_words_file'", $connid) or $errors[] = $lang_add['db_delete_entry_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

// alter category table:
if(empty($errors))
	{
	@mysql_query("ALTER TABLE ".$db_settings['category_table']." ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	@mysql_query("ALTER TABLE ".$db_settings['category_table']." ADD description varchar(255) NOT NULL default '' AFTER category", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

// alter forum table:
if(empty($errors))
	{
	@mysql_query("ALTER TABLE ".$db_settings['forum_table']." ADD category_int INT NOT NULL default '0' AFTER category", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	if(empty($errors))
		{
		$category_result = mysql_query("SELECT id, category FROM ".$db_settings['category_table'], $connid);
		if(!$category_result) die($lang['db_error']);
		while($data = mysql_fetch_array($category_result))
			{
			@mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, category_int=".intval($data['id'])." WHERE category = '".mysql_real_escape_string($data["category"])."'", $connid) or $errors[] = $lang_add['db_update_error']. " (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			if(isset($errors)) break;
			}
		mysql_free_result($category_result);
		}
	if(empty($errors)) @mysql_query("ALTER TABLE ".$db_settings['forum_table']." DROP category", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	if(empty($errors)) @mysql_query("ALTER TABLE ".$db_settings['forum_table']." CHANGE category_int category INT(11) DEFAULT '0' NOT NULL", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	if(empty($errors)) @mysql_query("ALTER TABLE ".$db_settings['forum_table']." ADD INDEX category (category), ADD INDEX pid (pid), ADD INDEX fixed (fixed)", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

if(empty($errors))
	{
	@mysql_query("ALTER TABLE ".$db_settings['userdata_table']." ADD activate_code varchar(255) NOT NULL default ''", $connid) or $errors[] = $lang_add['db_alter_table_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
// create smilies table:
if(empty($errors))
	{
	$createSmileyTable = "CREATE TABLE ".$db_settings['smilies_table']." (
	id int(11) NOT NULL auto_increment,
	order_id int(11) NOT NULL default '0',
	file varchar(100) NOT NULL,
	code_1 varchar(50) NOT NULL,
	code_2 varchar(50) NOT NULL,
	code_3 varchar(50) NOT NULL,
	code_4 varchar(50) NOT NULL,
	code_5 varchar(50) NOT NULL,
	title varchar(255) NOT NULL,
	PRIMARY KEY (id))";
	@mysql_query($createSmileyTable, $connid) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
// insert smilies:
if(empty($errors))
	{
	$order_id = 1;
	foreach($smilies as $smiley)
		{
		@mysql_query("INSERT INTO ".$db_settings['smilies_table']." (order_id, file, code_1, code_2, code_3, code_4, code_5, title) VALUES (".$order_id.",'".$smiley[0]."','".$smiley[1]."','".$smiley[2]."','".$smiley[3]."','".$smiley[4]."','".$smiley[5]."','".$smiley[6]."')", $connid) or $errors[] = str_replace("[setting]",$db_settings['settings_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
		$order_id++;
		}
	}
if(empty($errors))
	{
	@mysql_query("CREATE TABLE ".$db_settings['banlists_table']." (name varchar(255) NOT NULL default '', list text NOT NULL)", $connid) or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}
if (empty($errors))
	{
	@mysql_query("INSERT INTO ".$db_settings['banlists_table']." VALUES ('users', '')", $connid) or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	@mysql_query("INSERT INTO ".$db_settings['banlists_table']." VALUES ('ips', '')", $connid) or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	@mysql_query("INSERT INTO ".$db_settings['banlists_table']." VALUES ('words', '')", $connid) or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update15to16



function update16() {
global $db_settings, $settings, $connid, $smilies, $lang_add;

$settings_result = mysql_query("SELECT value FROM ".$db_settings['settings_table']." WHERE name = 'session_prefix' LIMIT 1", $connid) or die(mysql_error());
if(!$settings_result) die($lang['db_error']);

$settings_count = mysql_num_rows($settings_result);
mysql_free_result($settings_result);

if($settings_count != 1)
	{
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('session_prefix','mlf_')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}db_alter_table_error

$settings_result = mysql_query("SELECT value FROM ".$db_settings['settings_table']." WHERE name = 'users_per_page' LIMIT 1", $connid) or die(mysql_error());
if(!$settings_result) die($lang['db_error']);

$settings_count = mysql_num_rows($settings_result);
mysql_free_result($settings_result);

if($settings_count != 1)
	{
	@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('users_per_page','40')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update16



function update16to17() {
global $db_settings, $settings, $connid, $lang_add;

@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('captcha_posting','".mysql_real_escape_string($settings['captcha_posting'])."')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('captcha_contact','".mysql_real_escape_string($settings['captcha_contact'])."')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('captcha_register','".mysql_real_escape_string($settings['captcha_register'])."')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("INSERT INTO ".$db_settings['settings_table']." (name, value) VALUES ('captcha_type','".mysql_real_escape_string($settings['captcha_type'])."')", $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
@mysql_query("UPDATE ".$db_settings['settings_table']." SET value='1.7.6' WHERE name = 'version'", $connid) or $errors[] = $lang_add['db_update_error']. " (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update16to17

function update17() {
global $db_settings, $settings, $connid, $lang_add;

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
$newSetting["reply_name"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'last_reply_name',
value = 1";
$newSetting["user_highlight"] = "INSERT INTO ".$db_settings['settings_table']." SET
name = 'user_highlight',
value = 1";

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
# insert new settings
if (empty($errors))
	{
	foreach ($newSetting as $Set)
		{
		@mysql_query($Set, $connid) or $errors[] = $lang_add['db_insert_settings_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
		}
	}

# set value 1.7.7 for version string
if (empty($errors))
	{
	@mysql_query("UPDATE ".$db_settings['settings_table']." SET value='1.7.7' WHERE name = 'version'", $connid) or $errors[] = $lang_add['db_update_error']. " (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
	}

$return = (isset($errors)) ? $errors : false;
return $return;
} # End: update17

$table_prefix = 'forum_';

if (isset($_POST['language']))
	{
	$language = $_POST['language'];
	$settings['language_file'] = $language;
	}

if (isset($_POST['installation_mode'])) $installation_mode = $_POST['installation_mode'];

include("lang/".$settings['language_file'] );
include("lang/".$lang['additional_language_file']);
include("db_settings.php");

unset($errors);

if (isset($_POST['form_submitted']))
	{
	// all fields filled out?
	foreach ($_POST as $post)
		{
		if (trim($post) == "") { $errors[] = $lang['error_form_uncompl']; break; }
		}

	if (empty($errors) && $installation_mode=='installation')
		{
		if($_POST['admin_pw'] != $_POST['admin_pw_conf']) $errors[] = $lang_add['inst_pw_conf_error'];
		}

	// try to connect the database with posted access data:
	if (empty($errors))
		{
		$connid = @mysql_connect($_POST['host'], $_POST['user'], $_POST['pw']);
		if(!$connid) $errors[] = $lang_add['db_connection_error']." (MySQL: ".mysql_errno()."<br />".mysql_error().")";
		}
	// overwrite database settings file:
	if (empty($errors) && empty($_POST['dont_overwrite_settings']))
		{
		clearstatcache();
		$chmod = decoct(fileperms("db_settings.php"));

		$db_settings['host'] = $_POST['host'];
		$db_settings['user'] = $_POST['user'];
		$db_settings['pw'] = $_POST['pw'];
		$db_settings['db'] = $_POST['db'];
		$db_settings['settings_table'] = $_POST['table_prefix'].'settings';
		$db_settings['forum_table'] = $_POST['table_prefix'].'entries';
		$db_settings['category_table'] = $_POST['table_prefix'].'categories';
		$db_settings['userdata_table'] = $_POST['table_prefix'].'userdata';
		$db_settings['smilies_table'] = $_POST['table_prefix'].'smilies';
		$db_settings['banlists_table'] = $_POST['table_prefix'].'banlists';
		$db_settings['useronline_table'] = $_POST['table_prefix'].'useronline';
		# content of db_settings.php
		$fileSettingsContent  = "<?php\n";
		$fileSettingsContent .= "\$db_settings['host'] = \"".$db_settings['host']."\";\n";
		$fileSettingsContent .= "\$db_settings['user'] = \"".$db_settings['user']."\";\n";
		$fileSettingsContent .= "\$db_settings['pw'] = \"".$db_settings['pw']."\";\n";
		$fileSettingsContent .= "\$db_settings['db'] = \"".$db_settings['db']."\";\n";
		$fileSettingsContent .= "\$db_settings['settings_table'] = \"".$db_settings['settings_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['forum_table'] = \"".$db_settings['forum_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['category_table'] = \"".$db_settings['category_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['userdata_table'] = \"".$db_settings['userdata_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['smilies_table'] = \"".$db_settings['smilies_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['banlists_table'] = \"".$db_settings['banlists_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['useronline_table'] = \"".$db_settings['useronline_table']."\";\n";
		$fileSettingsContent .= "?>";

		$db_settings_file = @fopen("db_settings.php", "w") or $errors[] = str_replace("CHMOD",$chmod,$lang_add['no_writing_permission']);
		flock($db_settings_file, 2);
		fwrite($db_settings_file, $fileSettingsContent);
		flock($db_settings_file, 3);
		fclose($db_settings_file);
		}

	if($installation_mode=='installation' && empty($errors))
		{
		// create database if desired:
		if(isset($_POST['create_database']))
			{
			@mysql_query("CREATE DATABASE ".$db_settings['db'], $connid) or $errors[] = $lang_add['db_create_db_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		// select database:
		if (empty($errors))
			{
			@mysql_select_db($db_settings['db'], $connid) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		// create tables:
		if (empty($errors))
			{
			# create settings table
			$table["settings"] = "CREATE TABLE ".$db_settings['settings_table']." (
			name varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
			value varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '')";
			# create posting table
			$table["postings"] = "CREATE TABLE ".$db_settings['forum_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			pid int(11) unsigned NOT NULL default '0',
			tid int(11) unsigned NOT NULL default '0',
			uniqid varchar(255) NOT NULL default '',
			time timestamp(14) NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			last_answer timestamp(14) NOT NULL default '0000-00-00 00:00:00',
			edited timestamp(14) NOT NULL default '0000-00-00 00:00:00',
			edited_by varchar(255) NOT NULL default '',
			user_id int(11) unsigned default '0',
			name varchar(255) NOT NULL default '',
			subject varchar(255) NOT NULL default '',
			category int(11) unsigned NOT NULL default '0',
			email varchar(255) NOT NULL default '',
			hp varchar(255) NOT NULL default '',
			place varchar(255) NOT NULL default '',
			ip varchar(39) NOT NULL default '',
			text text NOT NULL,
			show_signature tinyint(4) unsigned default '0',
			email_notify tinyint(4) unsigned default '0',
			marked tinyint(4) unsigned default '0',
			locked tinyint(4) unsigned default '0',
			fixed tinyint(4) unsigned default '0',
			views int(11) unsigned default '0',
			PRIMARY KEY (id),
			KEY tid (tid),
			KEY category (category),
			KEY pid (pid),
			KEY fixed (fixed)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create category table
			$table["category"] = "CREATE TABLE ".$db_settings['category_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			category_order int(11) unsigned NOT NULL,
			category varchar(255) NOT NULL default '',
			description varchar(255) NOT NULL default '',
			accession tinyint(4) unsigned NOT NULL default '0',
			PRIMARY KEY (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create userdata table
			$table["userdata"] = "CREATE TABLE ".$db_settings['userdata_table']." (
			user_id int(11) unsigned NOT NULL auto_increment,
			user_type varchar(255) NOT NULL default '',
			user_name varchar(255) NOT NULL default '',
			user_real_name varchar(255) NOT NULL default '',
			user_pw varchar(255) NOT NULL default '',
			user_email varchar(255) NOT NULL default '',
			hide_email tinyint(4) unsigned default '0',
			user_hp varchar(255) NOT NULL default '',
			user_place varchar(255) NOT NULL default '',
			signature varchar(255) NOT NULL default '',
			profile text NOT NULL,
			logins int(11) unsigned NOT NULL default '0',
			last_login timestamp(14) NOT NULL,
			last_logout timestamp(14) NOT NULL,
			user_ip varchar(39) NOT NULL default '',
			registered timestamp(14) NOT NULL,
			user_view varchar(255) NOT NULL default '',
			new_posting_notify tinyint(4) unsigned default '0',
			new_user_notify tinyint(4) unsigned default '0',
			personal_messages tinyint(4) unsigned default '0',
			time_difference tinyint(4) unsigned default '0',
			user_lock tinyint(4) unsigned default '0',
			pwf_code varchar(255) NOT NULL default '',
			activate_code varchar(255) NOT NULL default '',
			PRIMARY KEY (user_id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create smilies table
			$table["smilies"] = "CREATE TABLE ".$db_settings['smilies_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			order_id int(11) unsigned NOT NULL default '0',
			file varchar(100) NOT NULL,
			code_1 varchar(50) NOT NULL default '',
			code_2 varchar(50) NOT NULL default '',
			code_3 varchar(50) NOT NULL default '',
			code_4 varchar(50) NOT NULL default '',
			code_5 varchar(50) NOT NULL default '',
			title varchar(255) NOT NULL,
			PRIMARY KEY (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create banlist table
			$table["banlists"] = "CREATE TABLE ".$db_settings['banlists_table']." (
			name varchar(255) NOT NULL default '',
			list text NOT NULL
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			#create useronline table
			$table["useronline"] = "CREATE TABLE ".$db_settings['useronline_table']." (
			ip char(15) NOT NULL default '',
			time int(14) unsigned NOT NULL default '0',
			user_id int(11) unsigned default '0'
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			@mysql_query($table["settings"], $connid) or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["postings"], $connid) or $errors[] = str_replace("[table]",$db_settings['forum_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["category"], $connid) or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["userdata"], $connid) or $errors[] = str_replace("[table]",$db_settings['userdata_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["smilies"], $connid) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["banlists"], $connid) or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($table["useronline"], $connid) or $errors[] = str_replace("[table]",$db_settings['useronline_table'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		// insert admin in userdata table:
		if (empty($errors))
			{# unformatieren INSERT INTO * SET ...
			$fillUserdata = "INSERT INTO ".$db_settings['userdata_table']." SET
			user_type = 'admin',
			user_name = '".mysql_real_escape_string($_POST['admin_name'])."',
			user_real_name = '',
			user_pw = '".md5(trim($_POST['admin_pw']))."',
			user_email = '".mysql_real_escape_string($_POST['admin_email'])."',
			hide_email = '1',
			profile = '',
			registered = NOW(),
			user_view = '".$settings['standard']."',
			personal_messages = '1'";
			@mysql_query($fillUserdata, $connid) or $errors[] = $lang_add['db_insert_admin_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		// insert settings in settings table:
		if (empty($errors))
			{
			// insert default settings:
			while(list($key, $val) = each($settings))
				{
				$fillSetting = "INSERT INTO ".$db_settings['settings_table']." SET
				name = '".mysql_real_escape_string($key)."',
				value = '".mysql_real_escape_string($val)."'";
				@mysql_query($fillSetting, $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				# empty $fillSetting for the next loop
				$fillSetting = "";
				}
			// update posted settings:
			$updateSetting["forum_name"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_name'])."'
			WHERE name='forum_name' LIMIT 1";
			$updateSetting["forum_address"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_address'])."'
			WHERE name='forum_address' LIMIT 1";
			$updateSetting["forum_email"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_email'])."'
			WHERE name='forum_email' LIMIT 1";
			@mysql_query($updateSetting["forum_name"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($updateSetting["forum_address"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($updateSetting["forum_email"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		// insert smilies in smilies table:
		if (empty($errors))
			{
			$order_id = 1;
			foreach($smilies as $smiley)
				{
				$fillSmiley = "INSERT INTO ".$db_settings['smilies_table']." SET
				order_id = ".intval($order_id).",
				file = '".mysql_real_escape_string($smiley[0])."',
				code_1 = '".mysql_real_escape_string($smiley[1])."',
				code_2 = '".mysql_real_escape_string($smiley[2])."',
				code_3 = '".mysql_real_escape_string($smiley[3])."',
				code_4 = '".mysql_real_escape_string($smiley[4])."',
				code_5 = '".mysql_real_escape_string($smiley[5])."',
				title = '".mysql_real_escape_string($smiley[6])."'";
				@mysql_query($fillSmiley, $connid) or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				# empty $fillSmiley for the next loop
				$fillSmiley = "";
				$order_id++;
				}
			}

		// insert banlists:
		if (empty($errors))
			{
			$templateBanlist = array("users","ips","words");
			foreach ($templatebanlist as $val)
				{
				$fillBanlist = "INSERT INTO ".$db_settings['banlists_table']." SET
				name = ".mysql_real_escape_string($val).",
				list = ''";
				@mysql_query($fillBanlist, $connid) or $errors[] = str_replace("[setting]",$db_settings['banlists_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				# empty $fillBanlist for the next loop
				$fillBanlist = "";
				}
			}
		// still no errors, so the installation should have been successful!
		if(empty($errors)) $installed = true;
		}
	else if($installation_mode=='update' && empty($errors))
		{
		@mysql_select_db($db_settings['db'], $connid) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";

		if(empty($errors))
			{
			// search version number of old forum:
			$getVersion = "SELECT value
			FROM ".$db_settings['settings_table']."
			WHERE name = 'version'
			LIMIT 1";
			$version_result = @mysql_query($getVersion, $connid);
			if($version_result)
				{
				$field = mysql_fetch_assoc($version_result);
				$version_count = mysql_num_rows($version_result);
				mysql_free_result($version_result);
				}
			if(empty($version_count) || $version_count != 1)
				{
				if(isset($_POST['old_version']))
					{
					$old_version = $_POST['old_version'];
					}
				else
					{
					$errors[] = $lang_add['no_version_found'];
					$select_version = true;
					}
				}
			else
				{
				$old_version = $field['value'];
				}
			}

		if(empty($errors))
			{
			switch($old_version)
				{
				case 1.3:
					$errors = update13to14();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update14to15();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update15to16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16to17();
					if($errors==false) unset($errors);
				break;
				case 1.4:
					$errors = update14to15();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update15to16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16to17();
					if($errors==false) unset($errors);
				break;
				case 1.5:
					$errors = update15to16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16to17();
					if($errors==false) unset($errors);
				break;
				case 1.6:
					$errors = update16();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update16to17();
					if($errors==false) unset($errors);
					if(empty($errors)) $errors = update17();
					if($errors==false) unset($errors);
				break;
				case 1.7:
					$errors = update17();
					if($errors==false) unset($errors);
				break;
				default:
					$errors[] = $lang_add['version_not_supported'];
				break;
				}
			}
		if(empty($errors)) $installed = true;
		}
	} # End: if (isset($_POST['form_submitted']))

# Generierung der Ausgabe
$output  = "";

if (empty($installed))
	{
	if (empty($language))
		{
		$handle=opendir('./lang/');
		while ($file = readdir($handle))
			{
			if (strrchr($file, ".")==".php" && strrchr($file, "_")!="_add.php")
				{
				$languageFile[] = $file;
				}
			}
		closedir($handle);
		$output .= '<h2>'.$lang_add['language_file_inst'].'</h2>';
		$output .= '<form action="install.php" method="post">';
		$output .= '<select>';
		foreach ($languageFile as $langFile)
			{
			$output .= '<option value="'.$langFile.'"';
			$output .= ($settings['language_file'] ==$file) ? ' selected="selected"' : '';
			$output .= '>'.ucfirst(str_replace(".php","",$file)).'</option>';
			}
		$output .= '</select>';
		$output .= '<input type="submit" value="'.$lang['submit_button_ok'].'" /></p>';
		$output .= '</form>';
		}
	else if (empty($installation_mode))
		{
		$output .= '<h2>'.$lang_add['installation_mode_inst'].'</h2>';
		$output .= '<form action="install.php" method="post"><div>';
		$output .= '<input type="hidden" name="language" value="'.$language.'" />';
		$output .= '<p><input type="radio" name="installation_mode" value="installation" checked="checked" />';
		$output .= $lang_add['installation_mode_installation'].'<br />';
		$output .= '<input type="radio" name="installation_mode" value="update" />'.$lang_add['installation_mode_update'].'</p>';
		$output .= '<p><input type="submit" value="'.$lang['submit_button_ok'].'" /></p>';
		$output .= '</div></form>';
		}
	else
		{
		switch($installation_mode)
			{
			case 'installation':
				$output .= '<h2>'.$lang_add['installation_instructions'].'</h2>';
      		if(isset($errors))
					{
         		$output .= errorMessages($errors);
					}
				$output .= '<form action="install.php" method="post">';
				$output .= '<fieldset>';
				$output .= '<legend>'.$lang_add['inst_basic_settings'].'</legend>';
				$output .= '<p>'.$lang_add['inst_main_settings_d'].'</p>';
				$output .= '<table class="admintab">';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['forum_name'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['forum_name_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="forum_name" value="';
				$output .= (isset($_POST['forum_name'])) ? htmlspecialchars($_POST['forum_name']) : $settings['forum_name'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['forum_address'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['forum_address_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="forum_address" value="';
				$output .= (isset($_POST['forum_address'])) ? htmlspecialchars($_POST['forum_address']) : ($settings['forum_address'] != "") ? $settings['forum_address'] : "http://".$_SERVER['SERVER_NAME'].str_replace("install.php","",$_SERVER['SCRIPT_NAME']);
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['forum_email'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['forum_email_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="forum_email" value="';
				$output .= (isset($_POST['forum_email'])) ? htmlspecialchars($_POST['forum_email']) : "@";
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</fieldset>';
				$output .= '<fieldset>';
				$output .= '<legend>'.$lang_add['inst_admin_settings'].'</legend>';
				$output .= '<p>'.$lang_add['inst_admin_settings_d'].'</p>';
				$output .= '<table class="admintab">';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_admin_name'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_admin_name_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="admin_name" value="';
				$output .= (isset($_POST['admin_name'])) ? htmlspecialchars($_POST['admin_name']) : '';
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_admin_email'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_admin_email_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="admin_email" value="';
				$output .= (isset($_POST['admin_email'])) ? htmlspecialchars($_POST['admin_email']) : "@";
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_admin_pw'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_admin_pw_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="password" name="admin_pw" value="" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_admin_pw_conf'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_admin_pw_conf_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="admin_pw_conf" value="" size="40" /></td>';
				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</fieldset>';
				$output .= '<fieldset>';
				$output .= '<legend>'.$lang_add['inst_db_settings'].'</legend>';
				$output .= '<p>'.$lang_add['inst_db_settings_d'].'</p>';
				$output .= '<ul>';
				$output .= '<li><input type="checkbox" name="create_database" value="true"';
				$output .= (isset($_POST['create_database'])) ? ' checked="checked"' : '';
				$output .= '>'.$lang_add['create_database'].'</li>';
				$output .= '<li><input type="checkbox" name="dont_overwrite_settings" value="true"';
				$output .= (isset($_POST['dont_overwrite_settings'])) ? ' checked="checked"' : '';
				$output .= '>'.$lang_add['dont_overwrite_settings'].'</li>';
				$output .= '</ul>';
				$output .= '<table class="admintab">';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_host'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_host_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="host" value="';
				$output .= (isset($_POST['host'])) ? htmlspecialchars($_POST['host']) : $db_settings['host'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_name'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_name_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="db" value="';
				$output .= (isset($_POST['db'])) ? htmlspecialchars($_POST['db']) : $db_settings['db'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_user'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="user" value="';
				$output .= (isset($_POST['user'])) ? htmlspecialchars($_POST['user']) : $db_settings['user'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_pw'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="password" name="pw" value="';
				$output .= (isset($_POST['pw'])) ? htmlspecialchars($_POST['pw']) : '';
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_table_prefix'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_table_prefix_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="table_prefix" value="';
				$output .= (isset($_POST['table_prefix'])) ? htmlspecialchars($_POST['table_prefix']) : $table_prefix;
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</fieldset>';
				$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_install_ok'].'" /></p>';
				$output .= '<input type="hidden" name="language" value="'.$language.'" />';
				$output .= '<input type="hidden" name="installation_mode" value="installation" />';
				$output .= '</form>';
			break;
			case 'update':
				$output .= '<h2>'.$lang_add['update_instructions'].'</h2>';
      		if(isset($errors))
					{
         		$output .= errorMessages($errors);
					}
				$output .= '<form action="install.php" method="post">';
				if(isset($select_version) and isset($old_version))
					{
					$output .= '<h3>'.$lang_add['select_version'].'</h3>';
					$output .= '<select name="old_version" size="1">';
					foreach ($updateVersion as $updatable)
						{
						$output .= '<option value="'.$updatable.'"'
						$output .= (if ($updateVersion == $updatable)) ? ' selected="selected"' : '';
						$output .= '>'.$updatable.'</option>';
						}
					$output .= '</select>';
					}
				$output .= '<h3>'.$lang_add['select_version'].'</h3>';
				$output .= '<p><input type="checkbox" name="DeleteSmilies" value="delete" selected="selected" />'.$lang_add["delete_2char_smilies"].'<br /><span>'.$lang_add['delete_2char_smilies_d'].'</span></p>';
				$output .= '<fieldset>';
				$output .= '<legend>'.$lang_add['inst_db_settings'].'</legend>';
				$output .= '<ul>';
				$output .= '<li><input type="checkbox" name="dont_overwrite_settings" value="true"';
				$output .= (isset($_POST['dont_overwrite_settings'])) ? ' checked="checked"' : '';
				$output .= '>'.$lang_add['dont_overwrite_settings'].'</li>';
				$output .= '</ul>';
				$output .= '<table class="admintab">';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_host'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_host_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="host" value="';
				$output .= (isset($_POST['host'])) ? htmlspecialchars($_POST['host']) : $db_settings['host'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_name'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_name_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="db" value="';
				$output .= (isset($_POST['db'])) ? htmlspecialchars($_POST['db']) : $db_settings['db'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_user'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="user" value="';
				$output .= (isset($_POST['user'])) ? htmlspecialchars($_POST['user']) : $db_settings['user'];
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_pw'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="password" name="pw" value="';
				$output .= (isset($_POST['pw'])) ? htmlspecialchars($_POST['pw']) : '';
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '<tr>';
				$output .= '<td class="admintab-l"><b>'.$lang_add['inst_table_prefix'].'</b><br />';
				$output .= '<span class="small">'.$lang_add['inst_table_prefix_d'].'</span></td>';
				$output .= '<td class="admintab-r"><input type="text" name="table_prefix" value="';
				$output .= (isset($_POST['table_prefix'])) ? htmlspecialchars($_POST['table_prefix']) : $table_prefix;
				$output .= '" size="40" /></td>';
				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</fieldset>';
				$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_update_ok'].'" /></p>';
				$output .= '<input type="hidden" name="language" value="'.$language.'" />';
				$output .= '<input type="hidden" name="installation_mode" value="update" />';
				$output .= '</form>';
			break;
			}
		}
	}
else
	{
	$output .= '<p class="caution" style="background-image:url(http://www.mylittlehomepage.net/mylittleforum/install/x.gif);">'.$lang_add['installation_complete'].'</p>
	<p>'.$lang_add['installation_complete_exp'].'</p>
	<p><a href="index.php">'.$lang_add['installation_complete_link'].'</a></p>';
	}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $settings['forum_name']." - ".$lang_add['install_title']; ?></title>
<style type="text/css">
<!--
body                { font-family: Verdana,Arial,Helvetica,sans-serif; color: #000000; font-size:13px; background-color: #fffff3; margin: 0px; padding: 20px; }
h1                  { margin: 0px 0px 20px 0px; font-size: 18px; font-weight: bold; }
table.admintab      { border: 1px solid #bacbdf; }
td.admintab-hl      { width: 100%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #d2ddea; }
td.admintab-hl h2   { margin: 3px 0px 3px 0px; font-size: 15px; font-weight: bold; }
td.admintab-hl p    { font-size: 11px; line-height: 16px; margin: 0px 0px 3px 0px; padding: 0px; }
td.admintab-l       { width: 50%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #f5f5f5; }
td.admintab-r       { width: 50%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #f5f5f5; }
.caution            { color: red; font-weight: bold; }
.small              { font-size: 11px; line-height:16px; }
a:link              { color: #0000cc; text-decoration: none; }
a:visited           { color: #0000cc; text-decoration: none; }
a:hover             { color: #0000ff; text-decoration: underline; }
a:active            { color: #ff0000; text-decoration: none; }
-->
</style>
</head>
<body>
<div>
<h1><?php echo $lang_add['install_title']; ?></h1>
<?php echo $output; ?>
</div>
</body>
</html>
