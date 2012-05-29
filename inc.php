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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

#ini_set('display_errors', 1);
#error_reporting(E_ALL);

ini_set('arg_separator.output', '&amp;');
header('Content-Type: text/html; charset=UTF-8');

#ini_set("session.use_trans_sid","0");
session_start();

if (!extension_loaded('mbstring')) include_once('/functions/funcs.mb_replacements.php');
include("db_settings.php");
include("functions.php");

mb_internal_encoding('UTF-8');

# additional headers (caching)
if (mb_strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) { header('Cache-Control: public, max-age=900'); }
$headerdate = gmdate('D, d M Y H:i:s',time()+60);
header('Expires: '.$headerdate.' GMT');

# for details see: http://de.php.net/manual/en/security.magicquotes.disabling.php
if (get_magic_quotes_gpc())
	{
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_SESSION = array_map('stripslashes_deep', $_SESSION);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}


$connid = connect_db($db_settings['host'], $db_settings['user'], $db_settings['pw'], $db_settings['db']);
get_settings();
include("lang/english.php");
$lang = outputLangDebugOrNot($lang, "english.php");
include("lang/".$settings['language_file']);
$lang = outputLangDebugOrNot($lang, $settings['language_file']);
setlocale(LC_ALL, $lang['locale']);
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$MyOwnSettings = getMyOwnSettings($_SESSION[$settings['session_prefix'].'user_id']);
	}

if (basename($_SERVER['SCRIPT_NAME'])!='login.php'
&& basename($_SERVER['SCRIPT_NAME'])!='info.php'
&& (!(isset($_SESSION[$settings['session_prefix'].'user_type'])
&& $_SESSION[$settings['session_prefix'].'user_type']=='admin'))
&& $settings['forum_disabled']==1)
	{
	if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin')
		{
		session_destroy();
		setcookie("auto_login", "", 0);
		}
	header('location: '.$settings['forum_address'].'info.php?info=1');
	die('<a href="info.php?info=1">further...</a>');
	}


/**
 * set default for debug mode (no)
 * if visitor is not admin and debug mode
 * is not setted manually to different value
 */
if (!isset($_SESSION[$settings['session_prefix'].'user_type'])
or $_SESSION[$settings['session_prefix'].'user_type']!='admin'
or ($_SESSION[$settings['session_prefix'].'user_type']=='admin'
and empty($_SESSION[$settings['session_prefix'].'debug']))) {
	$_SESSION[$settings['session_prefix'].'debug'] = 'no';
	}


/**
 * look if IP is banned
 */
$ip_result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'ips' LIMIT 1", $connid);
if (!$ip_result) die($lang['db_error']);
$data = mysql_fetch_assoc($ip_result);
mysql_free_result($ip_result);

if (trim($data['list']) != '')
	{
	$banned_ips_array = explode(',', trim($data['list']));
	if (in_array($_SERVER["REMOTE_ADDR"], $banned_ips_array))
		{
		die($lang['ip_no_access']);
		}
	}

// look if user is banned:
if (isset($_SESSION[$settings['session_prefix'].'user_name']))
	{
	$ban_result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'users' LIMIT 1", $connid);
	if (!$ban_result) die($lang['db_error']);
	$data = mysql_fetch_assoc($ban_result);
	mysql_free_result($ban_result);
	if (trim($data['list']) != '')
		{
		$banned_users_array = explode(',', mb_strtolower(trim($data['list'])));
		if (in_array(mb_strtolower($_SESSION[$settings['session_prefix'].'user_name']),$banned_users_array) && $_SESSION[$settings['session_prefix'].'user_type']!='admin')
			{
			session_destroy();
			setcookie("auto_login", "", 0);
			header("location: ".$settings['forum_address']."login.php?msg=user_banned");
			die($lang['user_banned']);
			}
		}
	}

// determine last visit:
if (empty($_SESSION[$settings['session_prefix']."user_id"])
&& $settings['remember_last_visit'] == 1)
	{
	if (isset($_COOKIE['last_visit']))
		{
		$c_last_visit = explode(".", $_COOKIE['last_visit']);
		$c_last_visit[0] = (isset($c_last_visit[0])) ? trim($c_last_visit[0]) : time();
		$c_last_visit[1] = (isset($c_last_visit[1])) ? trim($c_last_visit[1]) : time();
		if ($c_last_visit[1] < (time() - 600))
			{
			$c_last_visit[0] = $c_last_visit[1];
			$c_last_visit[1] = time();
			setcookie("last_visit", $c_last_visit[0].".".$c_last_visit[1], time()+(3600*24*30));
			}
		}
	else
		{
		setcookie("last_visit", time().".".time(), time()+(3600*24*30));
		}
	}

$last_visit = (isset($c_last_visit)) ? $c_last_visit[0] : time();

if (isset($_GET['category'])) $category = intval($_GET['category']);
$categories = get_categories();
$category_ids = get_category_ids($categories);
if ($category_ids !== false) $category_ids_query = implode(", ", $category_ids);
if (empty($category)) $category=0;

if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $category_accession = category_accession();

# count postings, threads, users and users online:
# no categories defined
if ($categories === false)
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0", $connid);
	list($thread_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table'], $connid);
	list($posting_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	}
# there are categories
else if (is_array($categories))
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category IN (".$category_ids_query.")", $connid);
	list($thread_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category IN (".$category_ids_query.")", $connid);
	list($posting_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	}
else
	{
	$thread_count = 0;
	$posting_count = 0;
	}

$possViews = array();
if ($settings['board_view'] == 1) $possViews[] = 'board';
if ($settings['thread_view'] == 1) $possViews[] = 'thread';
if ($settings['mix_view'] == 1) $possViews[] = 'mix';

# look for the current used view
if (isset($_GET['view']) and in_array($_GET['view'], $possViews))
	{
	$curr_view = $_GET['view'];
	}
else if (isset($_POST['view']) and in_array($_POST['view'], $possViews))
	{
	$curr_view = $_POST['view'];
	}
else if (isset($_SESSION[$settings['session_prefix'].'curr_view'])
	and in_array($_SESSION[$settings['session_prefix'].'curr_view'], $possViews))
	{
	$curr_view = $_SESSION[$settings['session_prefix'].'curr_view'];
	}
else if (isset($_COOKIE['curr_view']) and in_array($_COOKIE['curr_view'], $possViews))
	{
	$curr_view = $_COOKIE['curr_view'];
	}
else
	{
	if (isset($_SESSION[$settings['session_prefix']."user_view"]))
		{
		$curr_view = $_SESSION[$settings['session_prefix']."user_view"];
		}
	else
		{
		$curr_view = $settings['standard'];
		}
	}
# save the current used view into the session or a cookie
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	if ($curr_view != $_SESSION[$settings['session_prefix'].'curr_view'])
		{
		$_SESSION[$settings['session_prefix'].'curr_view'] = $curr_view;
		}
	}
else
	{
	if ($curr_view != $_COOKIE['curr_view'])
		{
		setcookie('curr_view', $curr_view, time()+(3600*24*30));
		}
	}

$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
list($user_count) = mysql_fetch_row($count_result);

if ($settings['count_users_online'] == 1)
	{
	user_online();
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE user_id > 0", $connid);
	list($useronline_count) = mysql_fetch_row($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE user_id = 0", $connid);
	list($guestsonline_count) = mysql_fetch_row($count_result);
	$counter = str_replace("[postings]", $posting_count, $lang['counter_uo']);
	$counter = str_replace("[threads]", $thread_count, $counter);
	$counter = str_replace("[users]", $user_count, $counter);
	$counter = str_replace("[total_online]", $useronline_count+$guestsonline_count, $counter);
	$counter = str_replace("[user_online]", $useronline_count, $counter);
	$counter = str_replace("[guests_online]", $guestsonline_count, $counter);
	}
else
	{
	$counter = str_replace("[forum_name]", '<a href="'.$settings['forum_address'].'">'.$settings['forum_name'].'</a>', $lang['counter']);
	$counter = str_replace("[contact]", '<a href="contact.php?forum_contact=true">'.$lang['contact_linkname'].'</a>', $counter);
	$counter = str_replace("[postings]", $posting_count, $counter);
	$counter = str_replace("[threads]", $thread_count, $counter);
	$counter = str_replace("[users]", $user_count, $counter);
	}
mysql_free_result($count_result);

$postingPages = array('posting.php','user.php');
$additionalJS = '';
if (in_array(basename($_SERVER['SCRIPT_NAME']), $postingPages))
	{
	$additionalJS .= '<script type="text/javascript">'."\n";
	if ($settings['bbcode'] == 1)
		{
		$additionalJS .= "var auge_buttons = \$A();\n";
		$additionalJS .= "auge_buttons[0] = \$H({value:'i', text:'".strip_tags($lang['bbcode_italic'])."', titel:'".strip_tags($lang['bbcode_italic_title']).".'});\n";
		$additionalJS .= "auge_buttons[1] = \$H({value:'b', text:'".strip_tags($lang['bbcode_bold'])."', titel:'".strip_tags($lang['bbcode_bold_title'])."'});\n";
		$additionalJS .= "auge_buttons[2] = \$H({value:'code', text:'".strip_tags($lang['bbcode_code'])."', titel:'".strip_tags($lang['bbcode_code_title'])."'});\n";
		if ($settings['bbcode_img']==1)
			{
			$additionalJS .= "auge_buttons[3] = \$H({value:'img', text:'".strip_tags($lang['bbcode_image'])."', titel:'".strip_tags($lang['bbcode_image_title'])."'});\n";
			}
		}
	if ($settings['upload_images']==1)
		{
		$additionalJS .= "\nvar auge_upload = \$H({text:'".strip_tags($lang['upload_image'])."', title:'".strip_tags($lang['upload_image_title'])."'});";
		}
	if ($settings['smilies'] == 1)
		{
		$result = mysql_query("SELECT file, code_1, title FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
		if (mysql_num_rows($result) > 0)
			{
			$additionalJS .= "\nvar auge_smilies = \$A();\n";
			$i=0;
			while ($data = mysql_fetch_assoc($result))
				{
				$additionalJS .= "auge_smilies[".$i."] = \$H({value:'".$data['code_1']."', url:'".$data['file']."', title: '".strip_tags($lang['smiley_title'])."'});\n";
				$i++;
				}
			$additionalJS .= "auge_smilies[".$i."] = \$H({value:'".strip_tags($lang['more_smilies_linkname'])."', url:'".$data['file']."', title: '".strip_tags($lang['more_smilies_linktitle'])."'});\n";
			}
		mysql_free_result($result);
		}
	$additionalJS .= "\nvar delete_text = '".strip_tags($lang['delete_link'])."';";
	$additionalJS .= "\n".'</script>';
	}

if ($settings['user_control_refresh']==1
	and (isset($MyOwnSettings['control_refresh'])
	and $MyOwnSettings['control_refresh'] == 'true')
	and (basename($_SERVER['SCRIPT_NAME']) == 'board.php'
	or basename($_SERVER['SCRIPT_NAME']) == 'forum.php'
	or basename($_SERVER['SCRIPT_NAME']) == 'mix.php'))
	{
	$loadTime = time();
	$reloadTime = $loadTime + 1200;
	$loadTime = strftime(strip_tags($lang['time_format']), $loadTime);
	$reloadTime = strftime(strip_tags($lang['time_format']), $reloadTime);
	$additionalJS .= '<meta http-equiv="refresh" content="1200" />'."\n";
	$lang['forum_load_message'] = str_replace('[load]', $loadTime, $lang['forum_load_message']);
	$lang['forum_load_message'] = str_replace('[reload]', $reloadTime, $lang['forum_load_message']);
	$lang['forum_load_message'] = '<p class="index">'.$lang['forum_load_message'].'</p>';
	}
else
	{
	$lang['forum_load_message'] = '';
	}

$time_difference = (isset($settings['time_difference'])) ? $settings['time_difference'] : 0;

if (isset($_SESSION[$settings['session_prefix'].'user_time_difference']))
	{
	$time_difference = $_SESSION[$settings['session_prefix'].'user_time_difference']+$time_difference;
	}
else if (isset($_COOKIE['user_time_difference']))
	{
	$time_difference = $_COOKIE['user_time_difference']+$time_difference;
	}

?>
