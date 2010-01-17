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

#require_once("function.tab2space.php");
require_once('functions/funcs.output.php');
require_once('functions/funcs.processing.php');

#
# Entferne bei aktivem magic_quotes_gpc die Slashes in den uebergebenen Werten
# siehe: http://php.net/manual/en/security.magicquotes.disabling.html
#
/**
 * disables magic_quotes from given variables of different types
 *
 * @param variable [$value]
 * @return variable [$value]
 * @since 1.7.7
 * @link http://php.net/manual/en/security.magicquotes.disabling.html
 */
function stripslashes_deep($value) {
$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
return $value;
} # Ende: stripslashes_deep



/**
 * reads the forum settings from the database
 *
 */
function get_settings() {
global $lang, $connid, $db_settings, $settings;

$result = mysql_query("SELECT name, value FROM ".$db_settings['settings_table'], $connid);
if (!$result) die($lang['db_error']);
while ($line = mysql_fetch_assoc($result))
	{
	$settings[$line['name']] = $line['value'];
	}
mysql_free_result($result);
} # End: get_settings



/**
 * returns the list of own settings for a registred and logged user
 *
 * @param integer $user_id
 * @return array $list 
 */
function getMyOwnSettings($user_id) {
global $db_settings, $connid;

$list = array();

$result = mysql_query("SELECT name, value FROM ".$db_settings['usersettings_table']." WHERE user_id = ".intval($user_id), $connid);
if (!$result) die($lang['db_error']);
while ($line = mysql_fetch_assoc($result))
	{
	$list[$line['name']] = $line['value'];
	}
return $list;
} # End: getMyOwnSettings



/**
 * reads the category names and ids from the database
 *
 * @return array $categories
 * @return bool false
 */
function get_categories() {
global $lang, $settings, $connid, $db_settings;

$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
list($category_count) = mysql_fetch_row($count_result);
mysql_free_result($count_result);

if ($category_count > 0)
	{
	if (empty($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$categoriesQuery = "SELECT
		id,
		category
		FROM ".$db_settings['category_table']."
		WHERE accession = 0
		ORDER BY category_order ASC";
		}
	else if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "user")
		{
		$categoriesQuery = "SELECT
		id,
		category
		FROM ".$db_settings['category_table']."
		WHERE accession = 0 OR accession = 1
		ORDER BY category_order ASC";
		}
	else if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "mod" || $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
		{
		$categoriesQuery = "SELECT
		id,
		category
		FROM ".$db_settings['category_table']."
		WHERE accession = 0 OR accession = 1 OR accession = 2
		ORDER BY category_order ASC";
		}
	$result = mysql_query($categoriesQuery, $connid);
	if (!$result) die($lang['db_error']);
	$categories[0]='';
	while ($line = mysql_fetch_assoc($result))
		{
		$categories[$line['id']] = $line['category'];
		}
	mysql_free_result($result);
	return $categories;
	}
else return false;
} # End: get_categories



/**
 * reads the categories ids from the categories list
 *
 * @param array $categories
 * @return array $category_ids
 * @return bool false
 */
function get_category_ids($categories) {
if($categories!=false)
	{
	while(list($key) = each($categories))
		{
		$category_ids[] = $key;
		}
	return $category_ids;
	}
else return false;
} # End: get_categories_ids



/**
 * lists the access rights to the categories
 *
 * @return array $category_accession
 * @return bool false
 */
function category_accession() {
global $settings, $lang, $connid, $db_settings;

$result = mysql_query("SELECT id, accession FROM ".$db_settings['category_table'], $connid);
while ($line = mysql_fetch_assoc($result))
	{
	$category_accession[$line['id']] = $line['accession'];
	}
mysql_free_result($result);
$r = isset($category_accession) ? $category_accession : false;

return $r;
} # End: category_accession



/**
 * Seiten-Navigation für forum.php, board.php und mix.php
 *
 * @param integer $page
 * @param integer $entries_per_page
 * @param integer $entry_count
 * @param string $order
 * @param string $descasc
 * @param integer $category
 * @param string $action (optional)
 * @return string $output
 */
function nav($page, $entries_per_page, $entry_count, $order, $descasc, $category, $action="") {
global $lang, $select_submit_button;

$output = "";
if ($entry_count > $entries_per_page)
	{
	$output .= "&nbsp;";
	$new_index_before = $page - 1;
	$new_index_after = $page + 1;
	$site_count = ceil($entry_count / $entries_per_page);
	$ic = '';
	$ic .= (isset($category) and $category > 0) ? '&amp;category='.intval($category) : '';
	$ic .= (isset($action) && $action!="") ? '&amp;action='.$action : '';
	$ic .= (isset($_GET['letter'])) ? '&amp;letter='.urlencode($_GET['letter']) : '';
	if ($new_index_before >= 0)
		{
		#  onmouseover="this.src='img/prev_mo.gif';" onmouseout="this.src='img/prev.gif';"
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?page='.$new_index_before;
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.$ic.'">';
		$output .= '<img src="img/prev.gif" alt="&laquo;" title="';
		$output .= $lang['previous_page_linktitle'].'" width="12" height="9" /></a>&nbsp;';
		}
	# if ($new_index_before >= 0 && $new_index_after < $site_count) $output .= " ";
	$page_count = ceil($entry_count / $entries_per_page);
	$output .= '<form action="'.$_SERVER["SCRIPT_NAME"].'" method="get" title="';
	$output .= $lang['choose_page_formtitle'].'"><div class="inline-form">'."\n";
	$output .= isset($order) ? '<input type="hidden" name="order" value="'.$order.'" />' : '';
	$output .= isset($descasc) ? '<input type="hidden" name="descasc" value="'.$descasc.'" />' : '';
	$output .= (isset($category) and $category > 0) ? '<input type="hidden" name="category" value="'.$category.'" />' : '';
	$output .= (isset($action) && $action!="") ? '<input type="hidden" name="action" value="'.$action.'" />' : '';
	$output .= (isset($_GET['letter'])) ? '<input type="hidden" name="letter" value="'.$_GET['letter'].'" />' : '';
	$output .= '<select class="kat" size="1" name="page" onchange="this.form.submit();">'."\n";
	$output .= '<option value="0"';
	$output .= ($page == 0) ? ' selected="selected"' : '';
	$output .= '>'.str_replace("[number]", "1", $lang['page_number']).'</option>'."\n";
	for($x=$page-9; $x<$page+10; $x++)
		{
		if ($x > 0 && $x < $page_count)
			{
			$output .= '<option value="'.$x.'"';
			$output .= ($page == $x) ? ' selected="selected"' : '';
			$output .= '>'.str_replace("[number]", $x+1, $lang['page_number']).'</option>'."\n";
			}
		}
	$output .= '</select>'."\n".'<noscript>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>'."\n";
	if ($new_index_after < $site_count)
		{
		# onmouseover="this.src='img/next_mo.gif';" onmouseout="this.src='img/next.gif';"
		$output .= '&nbsp;<a href="'.$_SERVER["SCRIPT_NAME"]."?page=".$new_index_after;
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.$ic.'">';
		$output .= '<img src="img/next.gif" alt="&raquo;" title="';
		$output .= $lang['next_page_linktitle'].'" width="12" height="9" /></a>';
		}
	}

return $output;
} # End: nav



/**
 * amend the protocol to a given link
 * @param string $link
 * @return string $link
 */
function amendProtocol($hp) {

if (substr($hp,0,7) != "http://"
	&& substr($hp,0,8) != "https://"
	&& substr($hp,0,6) != "ftp://"
	&& substr($hp,0,9) != "gopher://"
	&& substr($hp,0,7) != "news://")
	{
	$hp = "http://".$hp;
	}

return $hp;
} # End: amendProtocol



/**
 * makes URLs clickable
 *
 * @param string
 * @return string
 */
function make_link($string) {
$string = ' '.$string;
$string = preg_replace_callback("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "shorten_link", $string);
$string = preg_replace("#(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:/[^ \"\t\n\r<]*)?)#is", "\\1<a href=\"http://\\2\">\\2</a>", $string);
$string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $string);
$string = substr($string, 1);

return $string;
} # End: make_link



/**
 * function to hide the links from the checkings in posting.php
 *
 * @param string $string
 * @return string $string
 */
function text_check_link($string) {
$string = ' '.$string;
$string = preg_replace("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "", $string);
$string = substr($string, 1);

return $string;
} # End: text_check_link



/**
 * replaces bb-codes in posting with HTML-source
 *
 * @param string $string
 * @retrun string $string
 */
function bbcode($string) {
global $settings;

$string = preg_replace("#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $string);
$string = preg_replace("#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $string);
$string = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $string);
$string = preg_replace("# -- (.+?) -- #is", " &ndash; \\1 &ndash; ", $string);
$string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
$string = preg_replace_callback("#\[link\](.+?)\[/link\]#is", "shorten_link", $string);
$string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "<a href=\"\\1\">\\2</a>", $string);
$string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
$string = preg_replace_callback("#\[url\](.+?)\[/url\]#is", "shorten_link", $string);
$string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "<a href=\"\\1\">\\2</a>", $string);
$string = preg_replace_callback("#\[code\](.+?)\[/code\]#is", "parse_code", $string);
$string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "<a href=\"".$_SERVER['SCRIPT_NAME']."?id=\\1\">\\1</a>", $string);
$string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "<a href=\"".$_SERVER['SCRIPT_NAME']."?id=\\1\">\\2</a>", $string);
if ($settings['bbcode_img'] == 1)
	{
	$string = preg_replace("#\[img\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"margin: 5px 0px 5px 0px\" />", $string);
	$string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: left; margin: 0px 5px 5px 0px\" />", $string);
	$string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: right; margin: 0px 0px 5px 5px\" />", $string);
	}
$string = str_replace('javascript','javascr***',$string);

return $string;
} # End: bbcode



/**
 * cuts the URL as link text(!) after $setting['text_word_maxlength']/2
 *
 * @param string $string
 * @return string $string
 */
function shorten_link($string) {
global $settings;

if (count($string) == 2) { $pre = ""; $url = $string[1]; }
else { $pre = $string[1]; $url = $string[2]; }

$shortened_url = $url;
if (strlen($url) > $settings['text_word_maxlength']) $shortened_url = substr($url, 0, ($settings['text_word_maxlength']/2)) . "..." . substr($url, - ($settings['text_word_maxlength']-3-$settings['text_word_maxlength']/2));

return $pre.'<a href="'.$url.'">'.htmlspecialchars($shortened_url).'</a>';
} # End: shorten_link



/**
 * returns the HTML code for the output of posted source code
 *
 * @param string $string
 * @return string $string
 */
function parse_code($string) {
global $view;
if (basename($_SERVER['PHP_SELF'])=='board_entry.php' || basename($_SERVER['PHP_SELF'])=='mix_entry.php')
	{
	$p_class='postingboard';
	}
else if (!empty($view) and ($view=='board' or $view=='mix'))
	{
	$p_class='postingboard';
	}
else
	{
	$p_class='posting';
	}
$string = $string[1];
$string = str_replace('<br />','',$string);
$string = '</p>'."\n".'<pre class="'.$p_class.'"><code>'.$string.'</code></pre>'."\n".'<p class="'.$p_class.'">';

return $string;
} # End: parse_code



/**
 * strips bb codes for e-mail texts:
 *
 * @param string $string
 * @return string $string
 */
function unbbcode($string) {
global $settings;

$string = preg_replace("#\[b\](.+?)\[/b\]#is", "*\\1*", $string);
$string = preg_replace("#\[i\](.+?)\[/i\]#is", "/\\1/", $string);
$string = preg_replace("#\[u\](.+?)\[/u\]#is", "_\\1_", $string);
$string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "http://www.\\1", $string);
$string = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $string);
$string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2 --> \\1", $string);
$string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "http://www.\\1", $string);
$string = preg_replace("#\[url\](.+?)\[/url\]#is", "\\1", $string);
$string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "\\2 --> \\1", $string);
$string = preg_replace("#\[code\](.+?)\[/code\]#is", "\\1", $string);
$string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $string);
$string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $string);
if (isset($bbcode_img) && $settings['bbcode_img'] == 1)
	{
	$string = preg_replace("#\[img\](.+?)\[/img\]#is", "\\1", $string);
	$string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "\\1", $string);
	$string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "\\1", $string);
	}
return $string;
} # End: unbbcode



/**
 * replaces text smilies with smiley images
 *
 * @param string $string
 * @return string $string
 */
function smilies($string) {
global $connid, $db_settings;

$result = mysql_query("SELECT file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table'], $connid);
while($data = mysql_fetch_assoc($result))
	{
	$title = ($data['title']!='') ? ' title="'.htmlspecialchars($data['title']).'"' : '';
	$string = ($data['code_1']!='') ? str_replace($data['code_1'], '<img src="img/smilies'.$data['file'].'" alt="'.$data['code_1'].'"'.$title.' />', $string) : $string;
	$string = ($data['code_2']!='') ? str_replace($data['code_2'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_2'].'"'.$title.' />', $string) : $string;
	$string = ($data['code_3']!='') ? str_replace($data['code_3'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_3'].'"'.$title.' />', $string) : $string;
	$string = ($data['code_4']!='') ? str_replace($data['code_4'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_4'].'"'.$title.' />', $string) : $string;
	$string = ($data['code_5']!='') ? str_replace($data['code_5'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_5'].'"'.$title.' />', $string) : $string;
	}
mysql_free_result($result);

return $string;
} # End: smilies



/**
 * puts the quote symbol to the begin of a line
 *
 * @param string $string
 * @return string $string
 */
function zitat($string) {
global $settings;

$string = preg_replace("/^".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);
$string = preg_replace("/\\n".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);
$string = preg_replace("/\\n ".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);

return $string;
} # End: zitat



/**
 * puts the quote symbol to the begin of a line for RSS-feeds
 *
 * @param string $string
 * @return string $string
 */
function rss_quote($string) {
global $settings;

$string = preg_replace("/^".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);
$string = preg_replace("/\\n".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);
$string = preg_replace("/\\n ".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);

return $string;
} # End: rss_quote



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
if(!$connid) die($lang['db_error']);

mysql_select_db($db, $connid) or die($lang['db_error']);
mysql_query("SET NAMES utf8",$connid) or die($lang['db_error']);

return $connid;
} # End: connect_db



/**
 * counts the users which are online
 *
 * @param integer $user_online_period (in minutes, optional)
 * @return
 */
function user_online($user_online_period = 10) {
global $connid, $db_settings, $settings;

if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$user_id = $_SESSION[$settings['session_prefix'].'user_id'];
	}
else
	{
	$user_id = 0;
	}
$diff = time()-($user_online_period*60);

if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$ip = "uid_".$_SESSION[$settings['session_prefix'].'user_id'];
	}
else
	{
	$ip = $_SERVER['REMOTE_ADDR'];
	}

@mysql_query("DELETE FROM ".$db_settings['useronline_table']." WHERE time < ".$diff, $connid);

list($is_online) = @mysql_fetch_row(@mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE ip= '".mysql_real_escape_string($ip)."'", $connid));
if ($is_online > 0) @mysql_query("UPDATE ".$db_settings['useronline_table']." SET time='".time()."', user_id='".$user_id."' WHERE ip='".$ip."'", $connid);
else @mysql_query("INSERT INTO ".$db_settings['useronline_table']." SET time='".time()."', ip='".$ip."', user_id='".$user_id."'", $connid);

#list($user_online) = @mysql_fetch_row(@mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table'], $connid));
#return $user_online;
} # End: user_online



/**
 * displays the error messages
 *
 * @param array $errors
 * @return string $messages
 */
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



/**
 * displays the thread tree
 *
 * @param int $id
 * @param int $aktuellerEinrag
 * @param int $tiefe
 */
function thread_tree($id, $aktuellerEintrag = 0, $tiefe = 0) {
global $settings, $lang, $parent_array, $child_array, $page, $category, $order, $db_settings, $connid, $last_visit, $categories, $category_accession;

// highlighting of admins, mods and users:
$mark['admin'] = false; $mark['mod'] = false; $mark['user'] = false;
if (($settings['admin_mod_highlight'] == 1 or $settings['user_highlight'] == 1) && $parent_array[$id]["user_id"] > 0)
	{
	$userdata_result=mysql_query("SELECT user_type FROM ".$db_settings['userdata_table']." WHERE user_id = '".$parent_array[$id]["user_id"]."'", $connid);
	if (!$userdata_result) die($lang['db_error']);
	$userdata = mysql_fetch_assoc($userdata_result);
	mysql_free_result($userdata_result);
	if ($settings['admin_mod_highlight'] == 1)
		{
		if ($userdata['user_type'] == "admin") $mark['admin'] = true;
		else if ($userdata['user_type'] == "mod") $mark['mod'] = true;
		}
	if ($settings['user_highlight'] == 1)
		{
		if ($userdata['user_type'] == "user") $mark['user'] = true;
		}
	}

$name = outputAuthorsName($parent_array[$id]["name"], $mark, $parent_array[$id]['user_id']);

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1)
	{
	$sult = str_replace("[name]", htmlspecialchars($parent_array[$id]["name"]), $lang['show_userdata_linktitle']);
	$thread_info_a = str_replace("[name]", '<a href="user.php?id='.$parent_array[$id]["user_id"].'" title="'.$sult.'">'.$name.'</a>', $lang['thread_info']);
	}
else $thread_info_a = str_replace("[name]", $name, $lang['thread_info']);

$thread_info_b = str_replace("[time]", strftime($lang['time_format'],$parent_array[$id]["tp_time"]), $thread_info_a);

echo '<li>';
if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]==0)
	{
	echo '<span class="actthread">'.htmlspecialchars($parent_array[$id]["subject"]).'</span>';
	}
else if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]!=0)
	{
	echo '<span class="actreply">'.htmlspecialchars($parent_array[$id]["subject"]).'</span>';
	}
else
	{
	echo '<a class="';
	if ((($parent_array[$id]['pid']==0)
		&& isset($_SESSION[$settings['session_prefix'].'newtime'])
		&& $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]['last_answer'])
		|| (($parent_array[$id]['pid']==0)
		&& empty($_SESSION[$settings['session_prefix'].'newtime'])
		&& $parent_array[$id]['last_answer'] > $last_visit))
		{
		echo 'threadnew';
		}
	else if ($parent_array[$id]['pid']==0)
		{
		echo 'thread';
		}
	else if ((($parent_array[$id]['pid']!=0)
		&& isset($_SESSION[$settings['session_prefix'].'newtime'])
		&& $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]['time'])
		|| (($parent_array[$id]['pid']!=0)
		&& empty($_SESSION[$settings['session_prefix'].'newtime'])
		&& $parent_array[$id]['time'] > $last_visit))
		{
		echo 'replynew';
		}
	else
		{
		echo 'reply';
		}
	echo '" href="forum_entry.php?id='.$parent_array[$id]['id'];
	if ($page != 0 || $category != 0 || $order != 'time')
		{
		echo '&amp;page='.$page.'&amp;category='.intval($category).'&amp;order='.$order;
		}
	echo '">'.htmlspecialchars($parent_array[$id]["subject"]).'</a>';
	}

echo " ".$thread_info_b;

if ($parent_array[$id]['pid']==0
	&& $category==0
	&& isset($categories[$parent_array[$id]['category']])
	&& $categories[$parent_array[$id]['category']]!='')
	{
	# Is it a admin/mods-only category?
	if (isset($category_accession[$parent_array[$id]['category']])
		&& $category_accession[$parent_array[$id]['category']] == 2)
		{
		$titleAdd = ' '.$lang['admin_mod_category'];
		$catClassName = 'category-adminmod';
		}
	# Is it a registered users (including admins/mods) category?
	else if (isset($category_accession[$parent_array[$id]['category']])
		&& $category_accession[$parent_array[$id]["category"]] == 1)
		{
		$titleAdd = " ".$lang['registered_users_category'];
		$catClassName = 'category-regusers';
		}
	else
		{
		$titleAdd = '';
		$catClassName = 'category';
		}
	echo '&nbsp;<a title="'.str_replace('[category]', $categories[$parent_array[$id]['category']], $lang['choose_category_linktitle']).$titleAdd;
	echo '" href="forum.php?category='.intval($parent_array[$id]['category']).'"><span class="';
	echo $catClassName.'">('.$categories[$parent_array[$id]['category']].')</span></a>';
	}

if ($aktuellerEintrag == 0 && $parent_array[$id]["pid"]==0 && $parent_array[$id]["fixed"] == 1)
	{
	echo ' <img src="img/fixed.gif" width="9" height="9" title="'.$lang['fixed'].'" alt="*" />';
	}
if ($parent_array[$id]["pid"]==0 && $settings['all_views_direct'] == 1)
	{
	echo '&nbsp;';
	if ($settings['board_view']==1)
		{
		echo '<a href="board_entry.php?id='.$parent_array[$id]['tid'];
		echo ($category > 0) ? '&amp;category='.intval($category) : '';
		echo '&amp;view=board"><img src="img/board_d.gif" alt="[Board]" title="';
		echo $lang['open_in_board_linktitle'].'" width="12" height="9" /></a>';
		}
	if ($settings['mix_view'] == 1)
		{
		echo '<a href="mix_entry.php?id='.$parent_array[$id]['tid'];
		echo ($category > 0) ? '&amp;category='.intval($category) : '';
		echo '&amp;view=mix"><img src="img/mix_d.gif" alt="[Mix]" title="';
		echo $lang['open_in_mix_linktitle'].'" width="12" height="9" /></a>';
		}
	echo "</span>";
	}

if ($parent_array[$id]["pid"]==0
	&& isset($_SESSION[$settings['session_prefix'].'user_type'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
	{
	echo '<a href="admin.php?mark='.$parent_array[$id]["tid"].'&amp;refer=';
	echo basename($_SERVER["SCRIPT_NAME"]).'&amp;page='.$page.'&amp;order='.$order;
	echo ($category > 0) ? '&amp;category='.intval($category) : '';
	echo '"><img src="';
	if ($parent_array[$id]['marked']==1)
		{
		echo 'img/marked.gif" alt="[x]" title="'.$lang['demark_linktitle'].'"';
		}
	else
		{
		echo 'img/mark.gif" alt="[-]" title="'.$lang['mark_linktitle'].'"';
		}
	echo ' width="9" height="9" /></a>';
	}

// display all branches of the thread tree:
if(isset($child_array[$id]) && is_array($child_array[$id]))
	{
	echo '<ul class="';
	echo ($tiefe >= $settings['thread_depth_indent']) ? 'deep-reply' : 'reply';
	echo '">';
	foreach ($child_array[$id] as $kind)
		{
		thread_tree($kind, $aktuellerEintrag, $tiefe+1);
		}
	echo '</ul>';
	}
echo '</li>';
} # End: thread_tree



/**
 * replaces the template placeholders with the computed contents
 * 
 */
function parse_template() {
global $settings, $lang, $header, $additionalJS, $footer, $wo, $ao, $topnav, $subnav_1, $subnav_2, $footer_info_dump, $search, $show_postings, $counter;

$template = implode("",file($settings['template']));

if ($settings['home_linkaddress'] != "" && $settings['home_linkname'] != "") $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "\\1", $template);
else $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "", $template);

$template = str_replace("{LANGUAGE}",$lang['language'],$template);
$template = str_replace("{CHARSET}",$lang['charset'],$template);
$title = isset($wo) ? $settings['forum_name']." - ".htmlspecialchars($wo) : $settings['forum_name'];
$template = str_replace("{TITLE}",$title,$template);
$template = str_replace("{DESCRIPTION}",$settings['forum_name'],$template);
$template = str_replace('{ADD-JS}',$additionalJS,$template);
$template = str_replace("{FORUM-NAME}",$settings['forum_name'],$template);
$template = str_replace('{HOME-ADDRESS}',$settings['home_linkaddress'],$template);
$template = str_replace('{HOME-LINK}',$settings['home_linkname'],$template);
$template = str_replace('{LOAD-TIME}',$lang['forum_load_message'],$template);
$template = str_replace('{FORUM-ADDRESS}',$settings['forum_address'],$template);
$template = str_replace('{CONTACT}',$lang['contact_linkname'],$template);

// User menu:
if (isset($_SESSION[$settings['session_prefix']."user_name"]))
	{
	if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=="admin")
		{
		$user_menu_admin = ' | <a href="admin.php" title="'.$lang['admin_area_linktitle'].'">'.$lang['admin_area_linkname'].'</a>';
		}
	else
		{
		$user_menu_admin = "";
		}
	$user_menu = '<a href="user.php?id='.$_SESSION[$settings['session_prefix'].'user_id'].'" title="'.$lang['own_userdata_linktitle'].'"><b>'.htmlspecialchars($_SESSION[$settings['session_prefix'].'user_name']).'</b></a> | <a href="user.php" title="'.$lang['user_area_linktitle'].'">'.$lang['user_area_linkname'].'</a>'.$user_menu_admin.' | <a href="login.php" title="'.$lang['logout_linktitle'].'">'.$lang['logout_linkname'].'</a>';
	}
else
	{
	$user_menu = '<a href="login.php" rel="nofollow" title="'.$lang['login_linktitle'].'">'.$lang['login_linkname'].'</a> | <a href="register.php" rel="nofollow" title="'.$lang['register_linktitle'].'">'.$lang['register_linkname'].'</a>';
	}
$user_menu .= ' | <a href="search.php" rel="nofollow" title="'.$lang['search_formtitle'].'">'.$lang['search_linkname'].'</a>';
$template = str_replace("{USER-MENU}",$user_menu,$template);

// Search:
$search_dump = "\n".'<form action="search.php" method="get" title="'.$lang['search_formtitle'].'"><div class="search">'."\n";
$search_dump .= '<label for="search">'.$lang['search_marking'].'&nbsp;</label>';
# if (isset($search)) $search_match = htmlspecialchars(stripslashes($search)); else $search_match = "";
$search_dump .= '<input class="searchfield" type="text" id="search" name="search" value="" size="20" />&nbsp;<input type="image" name="" src="img/submit.gif" alt="&raquo;" /></div></form>'."\n";
$template = str_replace("{SEARCH}",$search_dump,$template);

// Sub navigation:
$tnd = (isset($topnav)) ? $topnav : "";
$template = str_replace("{LOCATION}",$tnd,$template);

$subnav_1_dump = (isset($subnav_1)) ? $subnav_1 : "&nbsp;";
$template = str_replace("{SUB-NAVIGATION-1}",$subnav_1_dump,$template);

$subnav_2_dump = (isset($subnav_2)) ? $subnav_2 : "&nbsp;";
$template = str_replace("{SUB-NAVIGATION-2}",$subnav_2_dump,$template);

// Footer:
$template = str_replace("{COUNTER}",$counter,$template);
if ($settings['provide_rssfeed'] == 1 && $settings['access_for_users_only'] == 0) 
	{ 
	$rss_feed_link = '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="rss.php" />';
	$rss_feed_button = '<a href="rss.php"><img src="img/rss.png" width="14" height="14" alt="RSS Feed" /></a><br />'; 
	}
else 
	{
	$rss_feed_link = '';
	$rss_feed_button = '';
	}
$template = str_replace("{RSS-FEED-LINK}",$rss_feed_link,$template);
$template = str_replace("{RSS-FEED-BUTTON}",$rss_feed_button,$template);

$template_parts = explode("{CONTENT}",$template);
$header = (isset($template_parts[0])) ? $template_parts[0] : "";
$footer = (isset($template_parts[1])) ? $template_parts[1] : "";
} # End: parse_template
?>
