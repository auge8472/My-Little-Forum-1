<?php
// default settings:
$settings['forum_name'] = "my little forum";
$settings['forum_email'] = "";
$settings['forum_address'] = "";
$settings['home_linkaddress'] = "../";
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
array('smile.gif', ':-)'),
array('wink.gif', ';-)'),
array('tongue.gif', ':-P'),
array('biggrin.gif', ':-D'),
array('neutral.gif', ':-|'),
array('frown.gif', ':-('),
array('yes.gif', ':yes:'),
array('no.gif', ':no:'),
array('ok.gif', ':ok:'),
array('lol.gif', ':lol:'),
array('lol2.gif', ':lol2:'),
array('lol3.gif', ':lol3:'),
array('cool.gif', ':cool:'),
array('surprised.gif', ':surprised:'),
array('angry.gif', ':angry:'),
array('crying.gif', ':crying:'),
array('waving.gif', ':waving:'),
array('confused.gif', ':confused:'),
array('clap.gif', ':clap:'),
array('lookaround.gif', ':lookaround:'),
array('love.gif', ':love:'),
array('hungry.gif', ':hungry:'),
array('rotfl.gif', ':rotfl:'),
array('sleeping.gif', ':sleeping:'),
array('wink2.gif', ':wink:'),
array('flower.gif', ':flower:'),
);
function htmlsc($string) {
	global $lang;
	return htmlspecialchars($string, ENT_QUOTES, "ISO-8859-1", false);
}

// update functions:
function update13to14()
 {
  global $db_settings, $settings, $connid, $lang_add;
  @mysqli_query($connid, "ALTER TABLE forum_table RENAME ". $db_settings['forum_table']) or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "ALTER TABLE userdata_table RENAME ". $db_settings['userdata_table']) or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "ALTER TABLE useronline_table RENAME ". $db_settings['useronline_table']) or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." ADD fixed tinyint(4) NOT NULL default '0' AFTER locked") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "CREATE TABLE ". $db_settings['settings_table'] ." (name varchar(255) NOT NULL default '', value varchar(255) NOT NULL default '')") or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
  $settings['forum_address'] = 'http://'.$_SERVER['SERVER_NAME'].str_replace("install.php","",$_SERVER['SCRIPT_NAME']);
  while(list($key, $val) = each($settings))
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('". mysqli_real_escape_string($connid, $key) ."','". mysqli_real_escape_string($connid, $val) ."')") or $errors[] = str_replace("[setting]",$setting,$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
   }
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'template'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'thread_depth_indent'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'edit_period'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'mail_parameter'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'forum_disabled'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'version'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'forum_disabled'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'captcha_posting'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'captcha_contact'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'captcha_register'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'captcha_type'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";

  @mysqli_query($connid, "CREATE TABLE ". $db_settings['category_table'] ." (category_order int(11) NOT NULL, category varchar(255) NOT NULL default '', accession tinyint(4) NOT NULL default '0')") or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
  $categories_result = mysqli_query($connid, "SELECT DISTINCT category FROM ". $db_settings['forum_table'] ." ORDER BY category ASC");
  if(!$categories_result) die($comment_lang['db_error']);
  $i=1;
  while ($data = mysqli_fetch_assoc($categories_result))
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['category_table'] ." (category_order, category, accession) VALUES (". intval($i) .", '". mysqli_real_escape_string($connid, $data['category']) ."',0)") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
    $i++;
   }
  if(isset($errors)) return $errors; else return false;
 }

function update14to15()
 {
  global $db_settings, $settings, $connid, $lang_add;
  $settings_result = mysqli_query($connid, "SELECT value FROM ". $db_settings['settings_table'] ." WHERE name = 'upload_max_img_size' LIMIT 1") or die(mysqli_error($connid));
  if(!$settings_result) die($lang['db_error']);
  $settings_count = mysqli_num_rows($settings_result);
  mysqli_free_result($settings_result);
  if($settings_count != 1)
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('upload_max_img_size','60')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('upload_max_img_width','600')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('upload_max_img_height','600')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
   }
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('template','template.html')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('thread_depth_indent','15')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('not_accepted_words_file','')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('edit_period','180')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'thread_indent'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'max_thread_indent'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";
  if(isset($errors)) return $errors; else return false;
 }

function update15to16()
 {
  global $db_settings, $settings, $connid, $smilies, $lang_add;
  // add settings:
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('mail_parameter','".  mysqli_real_escape_string($connid, $settings['mail_parameter']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('forum_disabled','".  mysqli_real_escape_string($connid, $settings['forum_disabled']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('session_prefix','mlf_')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('version','".  mysqli_real_escape_string($connid, $settings['version']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('users_per_page','40')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='".  mysqli_real_escape_string($connid, $settings['language_file']) ."' WHERE name = 'language_file'") or $errors[] = $lang_add['update_error']. " (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='".  mysqli_real_escape_string($connid, $settings['template']) ."' WHERE name = 'template'") or $errors[] = $lang_add['update_error']. " (MySQL: ".mysqli_error($connid).")";

  $settings_result = mysqli_query($connid, "SELECT value FROM ". $db_settings['settings_table'] ." WHERE name = 'edit_period' LIMIT 1") or die(mysqli_error($connid));
  if(!$settings_result) die($lang['db_error']);
  $settings_count = mysqli_num_rows($settings_result);
  mysqli_free_result($settings_result);
  if($settings_count != 1)
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('edit_period','180')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
   }
  @mysqli_query($connid, "DELETE FROM ". $db_settings['settings_table'] ." WHERE name = 'not_accepted_words_file'") or $errors[] = $lang_add['delete_entry_error']." (MySQL: ".mysqli_error($connid).")";

  // alter category table:
  if(empty($errors))
   {
    @mysqli_query($connid, "ALTER TABLE ". $db_settings['category_table'] ." ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
    @mysqli_query($connid, "ALTER TABLE ". $db_settings['category_table'] ." ADD description varchar(255) NOT NULL default '' AFTER category") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
   }
  // alter forum table:
  if(empty($errors))
   {
    @mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." ADD category_int INT NOT NULL default '0' AFTER category") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
    if(empty($errors))
     {
      $category_result = mysqli_query($connid, "SELECT id, category FROM ". $db_settings['category_table']);
      if(!$category_result) die($lang['db_error']);
      while($data = mysqli_fetch_assoc($category_result))
       {
        @mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, category_int=". intval($data['id']) ." WHERE category = '". mysqli_real_escape_string($connid, $data["category"]) ."'") or $errors[] = $lang_add['update_error']. " (MySQL: ".mysqli_error($connid).")";
        if(isset($errors)) break;
       }
      mysqli_free_result($category_result);
     }
    if(empty($errors)) @mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." DROP category") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
    if(empty($errors)) @mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." CHANGE category_int category INT(11) DEFAULT '0' NOT NULL") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
    if(empty($errors)) @mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." ADD INDEX category (category), ADD INDEX pid (pid), ADD INDEX fixed (fixed)") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
   }
  if(empty($errors))
   {
    @mysqli_query($connid, "ALTER TABLE ". $db_settings['userdata_table'] ." ADD activate_code varchar(255) NOT NULL default ''") or $errors[] = $lang_add['alter_table_error']." (MySQL: ".mysqli_error($connid).")";
   }
   // create smilies table:
   if(empty($errors))
    {
     @mysqli_query($connid, "CREATE TABLE ". $db_settings['smilies_table'] ." (id int(11) NOT NULL auto_increment, order_id int(11) NOT NULL default '0', file varchar(100) NOT NULL, code_1 varchar(50) NOT NULL, code_2 varchar(50) NOT NULL, code_3 varchar(50) NOT NULL, code_4 varchar(50) NOT NULL, code_5 varchar(50) NOT NULL, title varchar(255) NOT NULL, PRIMARY KEY (id))") or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
    }
   // insert smilies:
   if(empty($errors))
    {
     $order_id = 1;
     foreach($smilies as $smiley)
      {
       @mysqli_query($connid, "INSERT INTO ". $db_settings['smilies_table'] ." (order_id, file, code_1) VALUES (". intval($order_id) .",'". mysqli_real_escape_string($connid, $smiley[0]) ."','". mysqli_real_escape_string($connid, $smiley[1]) ."')") or $errors[] = str_replace("[setting]",$db_settings['settings_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
       $order_id++;
      }
    }
   if(empty($errors))
    {
     @mysqli_query($connid, "CREATE TABLE ". $db_settings['banlists_table'] ." (name varchar(255) NOT NULL default '', list text NOT NULL)") or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
    }
   if (empty($errors))
    {
     @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('users', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
     @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('ips', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
     @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('words', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
    }
   if(isset($errors)) return $errors; else return false;
 }

function update16()
 {
  global $db_settings, $settings, $connid, $smilies, $lang_add;
  $settings_result = mysqli_query($connid, "SELECT value FROM ". $db_settings['settings_table'] ." WHERE name = 'session_prefix' LIMIT 1") or die(mysqli_error($connid));
  if(!$settings_result) die($lang['db_error']);
  $settings_count = mysqli_num_rows($settings_result);
  mysqli_free_result($settings_result);
  if($settings_count != 1)
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('session_prefix','mlf_')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
   }
  $settings_result = mysqli_query($connid, "SELECT value FROM ". $db_settings['settings_table'] ." WHERE name = 'users_per_page' LIMIT 1") or die(mysqli_error($connid));
  if(!$settings_result) die($lang['db_error']);
  $settings_count = mysqli_num_rows($settings_result);
  mysqli_free_result($settings_result);
  if($settings_count != 1)
   {
    @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('users_per_page','40')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
   }
  if(isset($errors)) return $errors; else return false;
 }

function update16to17()
 {
  global $db_settings, $settings, $connid, $lang_add;
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('captcha_posting','". mysqli_real_escape_string($connid, $settings['captcha_posting']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('captcha_contact','". mysqli_real_escape_string($connid, $settings['captcha_contact']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('captcha_register','". mysqli_real_escape_string($connid, $settings['captcha_register']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('captcha_type','". mysqli_real_escape_string($connid, $settings['captcha_type']) ."')") or $errors[] = $lang_add['insert_settings_error']." (MySQL: ".mysqli_error($connid).")";
  @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='1.7.7' WHERE name = 'version'") or $errors[] = $lang_add['update_error']. " (MySQL: ".mysqli_error($connid).")";
  if(isset($errors)) return $errors; else return false;
 }

function update17() {
	global $db_settings, $settings, $connid, $lang_add;
	@mysqli_query($connid, "ALTER TABLE ". $db_settings['forum_table'] ." CHANGE time time timestamp NULL default NULL, CHANGE last_answer last_answer timestamp NULL default NULL, CHANGE edited edited timestamp NULL default NULL") or $errors[] = $lang_add['alter_table_error']. " (MySQL: ".mysqli_error($connid).")";
	@mysqli_query($connid, "ALTER TABLE ". $db_settings['userdata_table'] ." CHANGE last_login last_login timestamp NULL default NULL, CHANGE last_logout last_logout timestamp NULL default NULL, CHANGE registered registered timestamp NULL default NULL") or $errors[] = $lang_add['alter_table_error']. " (MySQL: ".mysqli_error($connid).")";
	@mysqli_query($connid, "ALTER TABLE ". $db_settings['smilies_table'] ." CHANGE file file varchar(100) NOT NULL default '', CHANGE title title varchar(255) NOT NULL default ''") or $errors[] = $lang_add['alter_table_error']. " (MySQL: ".mysqli_error($connid).")";
	@mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='1.7.7' WHERE name = 'version'") or $errors[] = $lang_add['update_error']. " (MySQL: ".mysqli_error($connid).")";
	if (isset($errors)) return $errors;
	return false;
}

$table_prefix = 'forum_';

if(isset($_POST['language']))
 {
  $language = $_POST['language'];
  $settings['language_file'] = $language;
 }

if(isset($_POST['installation_mode'])) $installation_mode = $_POST['installation_mode'];

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
    $connid = @mysqli_connect($_POST['host'], $_POST['user'], $_POST['pw']);
    if(!$connid) $errors[] = $lang_add['db_connection_error']." (MySQL: ".mysqli_connect_error().")";
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

    $db_settings_file = @fopen("db_settings.php", "w") or $errors[] = str_replace("CHMOD",$chmod,$lang_add['no_writing_permission']);
    flock($db_settings_file, 2);
    fwrite($db_settings_file, "<?php\n");
    fwrite($db_settings_file, "\$db_settings['host'] = \"".$db_settings['host']."\";\n");
    fwrite($db_settings_file, "\$db_settings['user'] = \"".$db_settings['user']."\";\n");
    fwrite($db_settings_file, "\$db_settings['pw'] = \"".$db_settings['pw']."\";\n");
    fwrite($db_settings_file, "\$db_settings['db'] = \"".$db_settings['db']."\";\n");
    fwrite($db_settings_file, "\$db_settings['settings_table'] = \"".$db_settings['settings_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['forum_table'] = \"".$db_settings['forum_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['category_table'] = \"".$db_settings['category_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['userdata_table'] = \"".$db_settings['userdata_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['smilies_table'] = \"".$db_settings['smilies_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['banlists_table'] = \"".$db_settings['banlists_table']."\";\n");
    fwrite($db_settings_file, "\$db_settings['useronline_table'] = \"".$db_settings['useronline_table']."\";\n");
    fwrite($db_settings_file, "?>\n");
    flock($db_settings_file, 3);
    fclose($db_settings_file);
   }

  if($installation_mode=='installation' && empty($errors))
   {
    // create database if desired:
    if(isset($_POST['create_database']))
     {
      @mysqli_query($connid, "CREATE DATABASE ". $db_settings['db']) or $errors[] = $lang_add['create_db_error']." (MySQL: ".mysqli_error($connid).")";
     }

    // select database:
    if (empty($errors))
     {
      @mysqli_select_db($connid, $db_settings['db']) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ". mysqli_error($connid) .")";
     }
     $tabledef['settings'] = "CREATE TABLE ". $db_settings['settings_table'] ." (
     name varchar(255) NOT NULL default '',
     value varchar(255) NOT NULL default '')";
     $tabledef['entries'] = "CREATE TABLE ". $db_settings['forum_table'] ." (
     id int(11) NOT NULL auto_increment,
     pid int(11) NOT NULL default '0',
     tid int(11) NOT NULL default '0',
     uniqid varchar(255) NOT NULL default '',
     time timestamp NULL default NULL,
     last_answer timestamp NULL default NULL,
     edited timestamp NULL default NULL,
     edited_by varchar(255) NOT NULL default '',
     user_id int(11) default '0',
     name varchar(255) NOT NULL default '',
     subject varchar(255) NOT NULL default '',
     category int(11) NOT NULL default '0',
     email varchar(255) NOT NULL default '',
     hp varchar(255) NOT NULL default '',
     place varchar(255) NOT NULL default '',
     ip varchar(255) NOT NULL default '',
     text text NOT NULL,
     show_signature tinyint(4) default '0',
     email_notify tinyint(4) default '0',
     marked tinyint(4) default '0',
     locked tinyint(4) default '0',
     fixed tinyint(4) default '0',
     views int(11) default '0',
     PRIMARY KEY (id),
     UNIQUE KEY id (id),
     KEY tid (tid),
     KEY category (category),
     KEY pid (pid),
     KEY fixed (fixed))";
     $tabledef['categories'] = "CREATE TABLE ". $db_settings['category_table'] ." (
     id int(11) NOT NULL auto_increment,
     category_order int(11) NOT NULL,
     category varchar(255) NOT NULL default '',
     description varchar(255) NOT NULL default '',
     accession tinyint(4) NOT NULL default '0',
     PRIMARY KEY (id))";
     $tabledef['userdata'] = "CREATE TABLE ". $db_settings['userdata_table'] ." (
     user_id int(11) NOT NULL auto_increment,
     user_type varchar(255) NOT NULL default '',
     user_name varchar(255) NOT NULL default '',
     user_real_name varchar(255) NOT NULL default '',
     user_pw varchar(255) NOT NULL default '',
     user_email varchar(255) NOT NULL default '',
     hide_email tinyint(4) default '0',
     user_hp varchar(255) NOT NULL default '',
     user_place varchar(255) NOT NULL default '',
     signature varchar(255) NOT NULL default '',
     profile text NOT NULL,
     logins int(11) NOT NULL default '0',
     last_login timestamp NULL default NULL,
     last_logout timestamp NULL default NULL,
     user_ip varchar(255) NOT NULL default '',
     registered timestamp NULL default NULL,
     user_view varchar(255) NOT NULL default '',
     new_posting_notify tinyint(4) default '0',
     new_user_notify tinyint(4) default '0',
     personal_messages tinyint(4) default '0',
     time_difference tinyint(4) default '0',
     user_lock tinyint(4) default '0',
     pwf_code varchar(255) NOT NULL default '',
     activate_code varchar(255) NOT NULL default '',
     PRIMARY KEY (user_id))";
     $tabledef['smilies'] = "CREATE TABLE ". $db_settings['smilies_table'] ." (
     id int(11) NOT NULL auto_increment,
     order_id int(11) NOT NULL default '0',
     file varchar(100) NOT NULL default '',
     code_1 varchar(50) NOT NULL default '',
     code_2 varchar(50) NOT NULL default '',
     code_3 varchar(50) NOT NULL default '',
     code_4 varchar(50) NOT NULL default '',
     code_5 varchar(50) NOT NULL default '',
     title varchar(255) NOT NULL default '',
     PRIMARY KEY (id))";
     $tabledef['banlists'] = "CREATE TABLE ". $db_settings['banlists_table'] ." (
     name varchar(255) NOT NULL default '',
     list text NOT NULL)";
     $tabledef['uonline'] = "CREATE TABLE ". $db_settings['useronline_table'] ." (
     ip char(15) NOT NULL default '',
     time int(14) NOT NULL default '0',
     user_id int(11) default '0')";
     // create tables:
     if (empty($errors))
      {
       @mysqli_query($connid, $tabledef['settings']) or $errors[] = str_replace("[table]",$db_settings['settings_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['entries']) or $errors[] = str_replace("[table]",$db_settings['forum_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['categories']) or $errors[] = str_replace("[table]",$db_settings['category_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['userdata']) or $errors[] = str_replace("[table]",$db_settings['userdata_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['smilies']) or $errors[] = str_replace("[table]",$db_settings['smilies_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['banlists']) or $errors[] = str_replace("[table]",$db_settings['banlists_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, $tabledef['uonline']) or $errors[] = str_replace("[table]",$db_settings['useronline_table'],$lang_add['create_table_error'])." (MySQL: ".mysqli_error($connid).")";
      }

     // insert admin in userdata table:
     if (empty($errors))
      {
       @mysqli_query($connid, "INSERT INTO ". $db_settings['userdata_table'] ." (user_type, user_name, user_real_name, user_pw, user_email, hide_email, profile, registered, user_view, personal_messages) VALUES ('admin','". mysqli_real_escape_string($connid, $_POST['admin_name']) ."','','". mysqli_real_escape_string($connid, md5(trim($_POST['admin_pw'])))."','". mysqli_real_escape_string($connid, $_POST['admin_email']) ."','1','',NOW(),'". mysqli_real_escape_string($connid, $settings['standard']) ."','1')") or $errors[] = $lang_add['insert_admin_error']." (MySQL: ".mysqli_error($connid).")";
      }

     // insert settings in settings table:
     if (empty($errors))
      {
       // insert default settings:
       while(list($key, $val) = each($settings))
        {
         @mysqli_query($connid, "INSERT INTO ". $db_settings['settings_table'] ." (name, value) VALUES ('". mysqli_real_escape_string($connid, $key) ."','". mysqli_real_escape_string($connid, $val) ."')") or $errors[] = str_replace("[setting]",$setting,$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
        }
       // update posted settings:
       @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='". mysqli_real_escape_string($connid, $_POST['forum_name'])."' WHERE name='forum_name' LIMIT 1") or $errors[] = str_replace("[setting]",$setting,$lang_add['update_settings_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='". mysqli_real_escape_string($connid, $_POST['forum_address']) ."' WHERE name='forum_address' LIMIT 1") or $errors[] = str_replace("[setting]",$setting,$lang_add['update_settings_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, "UPDATE ". $db_settings['settings_table'] ." SET value='". mysqli_real_escape_string($connid, $_POST['forum_email']) ."' WHERE name='forum_email' LIMIT 1") or $errors[] = str_replace("[setting]",$setting,$lang_add['update_settings_error'])." (MySQL: ".mysqli_error($connid).")";
      }

     // insert smilies in smilies table:
     if (empty($errors))
      {
       $order_id = 1;
       foreach($smilies as $smiley)
        {
         @mysqli_query($connid, "INSERT INTO ". $db_settings['smilies_table'] ." (order_id, file, code_1) VALUES (". intval($order_id) .",'". mysqli_real_escape_string($connid, $smiley[0])."','". mysqli_real_escape_string($connid, $smiley[1])."')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
         $order_id++;
        }
      }

     // insert banlists:
     if (empty($errors))
      {
       @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('users', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('ips', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
       @mysqli_query($connid, "INSERT INTO ". $db_settings['banlists_table'] ." VALUES ('words', '')") or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['insert_settings_error'])." (MySQL: ".mysqli_error($connid).")";
      }

    // still no errors, so the installation should have been successful!
    if(empty($errors)) $installed = true;
   }
  elseif($installation_mode=='update' && empty($errors))
   {
    @mysqli_select_db($connid, $db_settings['db']) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ".mysqli_error($connid).")";

    if(empty($errors))
     {
      // search version number of old forum:
      $version_result = @mysqli_query($connid, "SELECT value FROM ". $db_settings['settings_table'] ." WHERE name = 'version' LIMIT 1");
      if($version_result)
       {
        $field = mysqli_fetch_assoc($version_result);
        $version_count = mysqli_num_rows($version_result);
        mysqli_free_result($version_result);
       }
      if(empty($version_count) || $version_count != 1)
       {
        if(isset($_POST['old_version'])) $old_version = $_POST['old_version'];
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
 }

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
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
<h1><?php echo $lang_add['install_title']; ?></h1><?php

if(empty($installed))
 {
  if(empty($language))
   {
    ?><p><?php echo $lang_add['language_file_inst']; ?></p>
    <form action="install.php" method="post">
    <p><select name="language" size="1"><?php $handle=opendir('./lang/'); while ($file = readdir($handle)) { if (strrchr($file, ".")==".php" && strrchr($file, "_")!="_add.php") { ?><option value="<?php echo $file; ?>"<?php if ($settings['language_file'] ==$file) echo " selected=\"selected\""; ?>><?php echo ucfirst(str_replace(".php","",$file)); ?></option><?php } } closedir($handle); ?></select>
    <input type="submit" value="<?php echo $lang['submit_button_ok']; ?>" /></p>
    </form><?php
   }
  elseif(empty($installation_mode))
   {
    ?><p><?php echo $lang_add['installation_mode_inst']; ?></p>
    <form action="install.php" method="post"><div>
    <input type="hidden" name="language" value="<?php echo $language; ?>" />
    <p><input type="radio" name="installation_mode" value="installation" checked="checked" /><?php echo $lang_add['installation_mode_installation']; ?><br />
    <input type="radio" name="installation_mode" value="update" /><?php echo $lang_add['installation_mode_update']; ?></p>
    <p><input type="submit" value="<?php echo $lang['submit_button_ok']; ?>" /></p>
    </div></form><?php
   }
  else
   {
    switch($installation_mode)
     {
      case 'installation':
       ?><p><?php echo $lang_add['installation_instructions']; ?></p><br /><?php
       if(isset($errors))
        {
         ?><p class="caution" style="margin-top: 10px;"><?php echo $lang['error_headline']; ?><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><p>&nbsp;</p><?php
        }
       ?><form action="install.php" method="post">
       <table class="admintab" border="0" cellpadding="5" cellspacing="1">
       <tr>
       <td class="admintab-hl" colspan="2"><h2><?php echo $lang_add['inst_basic_settings']; ?></h2><p><?php echo $lang_add['inst_main_settings_d']; ?></p></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['forum_name']; ?></b><br /><span class="small"><?php echo $lang_add['forum_name_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="forum_name" value="<?php if (isset($_POST['forum_name'])) echo htmlsc($_POST['forum_name']); else echo $settings['forum_name']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['forum_address']; ?></b><br /><span class="small"><?php echo $lang_add['forum_address_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="forum_address" value="<?php if (isset($_POST['forum_address'])) echo $_POST['forum_address']; else { if ($settings['forum_address'] != "") echo $settings['forum_address']; else echo "http://".$_SERVER['SERVER_NAME'].str_replace("install.php","",$_SERVER['SCRIPT_NAME']); } ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['forum_email']; ?></b><br /><span class="small"><?php echo $lang_add['forum_email_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="forum_email" value="<?php if (isset($_POST['forum_email'])) echo $_POST['forum_email']; else echo "@"; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-hl" colspan="2"><h2><?php echo $lang_add['inst_admin_settings']; ?></h2><p><?php echo $lang_add['inst_admin_settings_d']; ?></p></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_admin_name']; ?></b><br /><span class="small"><?php echo $lang_add['inst_admin_name_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="admin_name" value="<?php if (isset($_POST['admin_name'])) echo $_POST['admin_name']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_admin_email']; ?></b><br /><span class="small"><?php echo $lang_add['inst_admin_email_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="admin_email" value="<?php if (isset($_POST['admin_email'])) echo $_POST['admin_email']; else echo "@"; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_admin_pw']; ?></b><br /><span class="small"><?php echo $lang_add['inst_admin_pw_d']; ?></span></td>
       <td class="admintab-r"><input type="password" name="admin_pw" value="<?php if (isset($_POST['admin_pw'])) echo $_POST['admin_pw']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_admin_pw_conf']; ?></b><br /><span class="small"><?php echo $lang_add['inst_admin_pw_conf_d']; ?></span></td>
       <td class="admintab-r"><input type="password" name="admin_pw_conf" value="<?php if (isset($_POST['admin_pw_conf'])) echo $_POST['admin_pw_conf']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-hl" colspan="2"><h2><?php echo $lang_add['inst_db_settings']; ?></h2><p><?php echo $lang_add['inst_db_settings_d']; ?><br />
       <input type="checkbox" name="create_database" value="true"<?php if (isset($_POST['create_database'])) echo ' checked="checked"'; ?>> <?php echo $lang_add['create_database']; ?><br />
       <input type="checkbox" name="dont_overwrite_settings" value="true"<?php if (isset($_POST['dont_overwrite_settings'])) echo ' checked="checked"'; ?>> <?php echo $lang_add['dont_overwrite_settings']; ?></p></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_host']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_host_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="host" value="<?php if (isset($_POST['host'])) echo $_POST['host']; else echo $db_settings['host']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_name']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_name_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="db" value="<?php if (isset($_POST['db'])) echo $_POST['db']; else echo $db_settings['db']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_user']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_user_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="user" value="<?php if (isset($_POST['user'])) echo $_POST['user']; else echo $db_settings['user']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_pw']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_pw_d']; ?></span></td>
       <td class="admintab-r"><input type="password" name="pw" value="<?php if (isset($_POST['pw'])) echo $_POST['pw']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_table_prefix']; ?></b><br /><span class="small"><?php echo $lang_add['inst_table_prefix_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="table_prefix" value="<?php if (isset($_POST['table_prefix'])) echo $_POST['table_prefix']; else echo $table_prefix; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-hl" colspan="2"><input type="submit" name="form_submitted" value="<?php echo $lang_add['forum_install_ok']; ?>" /><input type="hidden" name="language" value="<?php echo $language; ?>" /><input type="hidden" name="installation_mode" value="installation" /></td>
       </tr>
       </table>
       </form><?php
      break;
      case 'update':
       ?><p><?php echo $lang_add['update_instructions']; ?></p><br /><?php
       if(isset($errors))
        {
         ?><p class="caution" style="margin-top: 10px;"><?php echo $lang['error_headline']; ?><ul><?php foreach($errors as $error) { ?><li><?php echo $error; ?></li><?php } ?></ul></p><p>&nbsp;</p><?php
        }
       ?><form action="install.php" method="post"><?php if(isset($select_version))
        {
         ?><p><?php echo $lang_add['select_version']; ?>
         <select name="old_version" size="1">
         <option value="1.3">1.3</option>
         <option value="1.4">1.4</option>
         <option value="1.5">1.5</option>
         <option value="1.6">1.6</option>
         <option value="1.7" selected="selected">1.7</option>
         </select></p><?php
        }
       ?><table class="admintab" border="0" cellpadding="5" cellspacing="1">
       <tr>
       <td class="admintab-hl" colspan="2"><h2><?php echo $lang_add['inst_db_settings']; ?></h2>
       <p><input type="checkbox" name="dont_overwrite_settings" value="true"<?php if (isset($_POST['dont_overwrite_settings'])) echo ' checked="checked"'; ?>> <?php echo $lang_add['dont_overwrite_settings']; ?></p></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_host']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_host_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="host" value="<?php if (isset($_POST['host'])) echo $_POST['host']; else echo $db_settings['host']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_name']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_name_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="db" value="<?php if (isset($_POST['db'])) echo $_POST['db']; else echo $db_settings['db']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_user']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_user_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="user" value="<?php if (isset($_POST['user'])) echo $_POST['user']; else echo $db_settings['user']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_db_pw']; ?></b><br /><span class="small"><?php echo $lang_add['inst_db_pw_d']; ?></span></td>
       <td class="admintab-r"><input type="password" name="pw" value="<?php if (isset($_POST['pw'])) echo $_POST['pw']; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-l"><b><?php echo $lang_add['inst_table_prefix']; ?></b><br /><span class="small"><?php echo $lang_add['inst_table_prefix_d']; ?></span></td>
       <td class="admintab-r"><input type="text" name="table_prefix" value="<?php if (isset($_POST['table_prefix'])) echo $_POST['table_prefix']; else echo $table_prefix; ?>" size="40" /></td>
       </tr>
       <tr>
       <td class="admintab-hl" colspan="2"><input type="submit" name="form_submitted" value="<?php echo $lang_add['forum_update_ok']; ?>" /><input type="hidden" name="language" value="<?php echo $language; ?>" /><input type="hidden" name="installation_mode" value="update" /></td>
       </tr>
       </table>
       </form><?php
      break;
     }
   }
 }
else
 {
  ?><p class="caution" style="background-image:url(http://www.mylittlehomepage.net/mylittleforum/install/x.gif);"><?php echo $lang_add['installation_complete']; ?></p>
  <p><?php echo $lang_add['installation_complete_exp']; ?></p>
  <p><a href="index.php"><?php echo $lang_add['installation_complete_link']; ?></a></p><?php
 }
?></div></body>
</html>
