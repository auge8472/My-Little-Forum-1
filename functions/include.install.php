<?php
function stripslashes_deep($value) {
$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
return $value;
} # Ende: stripslashes_deep



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