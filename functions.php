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


function get_settings() {
global $lang, $connid, $db_settings, $settings;

$result = mysql_query("SELECT name, value FROM ".$db_settings['settings_table'], $connid);
if(!$result) die($lang['db_error']);
while ($line = mysql_fetch_array($result))
	{
	$settings[$line['name']] = $line['value'];
	}
mysql_free_result($result);
} # End: get_settings

function get_categories() {
global $lang, $settings, $connid, $db_settings;

$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['category_table'], $connid);
list($category_count) = mysql_fetch_row($count_result);
mysql_free_result($count_result);

if ($category_count > 0)
	{
	if (empty($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$result = mysql_query("SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 ORDER BY category_order ASC", $connid);
		}
	else if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "user")
		{
		$result = mysql_query("SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 ORDER BY category_order ASC", $connid);
		}
	else if (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "mod" || $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
		{
		$result = mysql_query("SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 OR accession = 2 ORDER BY category_order ASC", $connid);
		}
	if(!$result) die($lang['db_error']);	$categories[0]='';
	while ($line = mysql_fetch_array($result))
		{
		$categories[$line['id']] = stripslashes($line['category']);
		}
	mysql_free_result($result);
	return $categories;
	}
else return false;
} # End: get_categories

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

function category_accession() {
global $settings, $lang, $connid, $db_settings;

$result = mysql_query("SELECT id, accession FROM ".$db_settings['category_table'], $connid);
while ($line = mysql_fetch_array($result))
	{
	$category_accession[$line['id']] = $line['accession'];
	}
mysql_free_result($result);
if (isset($category_accession)) return $category_accession; else return false;
} # End: category_accession

// Seiten-Navigation für forum.php, board.php und mix.php
function nav($page, $entries_per_page, $entry_count, $order, $descasc, $category, $action="") {
global $lang, $select_submit_button;

$output = "";
if ($entry_count > $entries_per_page)
	{
	$output .= "&nbsp; ";
	$new_index_before = $page - 1;
	$new_index_after = $page + 1;
	$site_count = ceil($entry_count / $entries_per_page);
	$ic = "";
	if (isset($category)) $ic .= "&amp;category=".urlencode($category);
	if (isset($action) && $action!="") $ic .= "&amp;action=".$action;
	if (isset($_GET['letter'])) $ic .= "&amp;letter=".urlencode($_GET['letter']);
	if ($new_index_before >= 0) $output .= "<a href=\"". basename($_SERVER["PHP_SELF"]) ."?page=".$new_index_before."&amp;order=".$order."&amp;descasc=".$descasc.$ic."\"><img src=\"img/prev.gif\" alt=\"&laquo;\" title=\"".$lang['previous_page_linktitle']."\" width=\"12\" height=\"9\" onmouseover=\"this.src='img/prev_mo.gif';\" onmouseout=\"this.src='img/prev.gif';\" /></a>&nbsp;";
	//if ($new_index_before >= 0 && $new_index_after < $site_count) $output .= " ";
	$page_count = ceil($entry_count/$entries_per_page);
	$output .= '<form action="'.basename($_SERVER["PHP_SELF"]).'" method="get" title="'.$lang['choose_page_formtitle'].'"><div style="display: inline;">';
	if (isset($order)) $output .= '<input type="hidden" name="order" value="'.$order.'" />';
	if (isset($descasc)) $output .= '<input type="hidden" name="descasc" value="'.$descasc.'" />';
	if (isset($category)) $output .= '<input type="hidden" name="category" value="'.$category.'" />';
	if (isset($action) && $action!="") $output .= '<input type="hidden" name="action" value="'.$action.'" />';
	if (isset($_GET['letter'])) $output .= '<input type="hidden" name="letter" value="'.$_GET['letter'].'" />';
	$output .= '<select class="kat" size="1" name="page" onchange="this.form.submit();">';
	if ($page == 0) $output .= '<option value="0" selected="selected">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
	else $output .= '<option value="0">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
	for($x=$page-9; $x<$page+10; $x++)
		{
		if ($x > 0 && $x < $page_count)
			{
			if ($page == $x) $output .= '<option value="'.$x.'" selected="selected">'.str_replace("[number]", $x+1, $lang['page_number']).'</option>';
			else $output .= '<option value="'.$x.'">'.str_replace("[number]", $x+1, $lang['page_number']).'</option>';
			}
		}
	$output .= '</select><noscript>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>';
	if ($new_index_after < $site_count) $output .= "&nbsp;<a href=\"". basename($_SERVER["PHP_SELF"]) ."?page=" .$new_index_after ."&amp;order=".$order."&amp;descasc=".$descasc.$ic."\"><img src=\"img/next.gif\" alt=\"&raquo;\" title=\"".$lang['next_page_linktitle']."\" width=\"12\" height=\"9\" onmouseover=\"this.src='img/next_mo.gif';\" onmouseout=\"this.src='img/next.gif';\" /></a>";
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

// makes URLs clickable:
function make_link($string) {
$string = ' ' . $string;
$string = preg_replace_callback("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "shorten_link", $string);
$string = preg_replace("#(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:/[^ \"\t\n\r<]*)?)#is", "\\1<a href=\"http://\\2\">\\2</a>", $string);
$string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $string);
$string = substr($string, 1);

return $string;
} # End: make_link

// function to hide the links from the checkings in posting.php
function text_check_link($string) {
$string = ' ' . $string;
$string = preg_replace("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "", $string);
$string = substr($string, 1);
return $string;
} # End: text_check_link

function bbcode($string) {
global $settings;

$string = preg_replace("#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $string);
$string = preg_replace("#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $string);
$string = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $string);
$string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
$string = preg_replace_callback("#\[link\](.+?)\[/link\]#is", "shorten_link", $string);
$string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "<a href=\"\\1\">\\2</a>", $string);
$string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
$string = preg_replace_callback("#\[url\](.+?)\[/url\]#is", "shorten_link", $string);
$string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "<a href=\"\\1\">\\2</a>", $string);
$string = preg_replace_callback("#\[code\](.+?)\[/code\]#is", "parse_code", $string);
$string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "<a href=\"".basename($_SERVER['PHP_SELF'])."?id=\\1\">\\1</a>", $string);$string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "<a href=\"".basename($_SERVER['PHP_SELF'])."?id=\\1\">\\2</a>", $string);
if ($settings['bbcode_img'] == 1)
	{
	$string = preg_replace("#\[img\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"margin: 5px 0px 5px 0px\" />", $string);
	$string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: left; margin: 0px 5px 5px 0px\" />", $string);
	$string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: right; margin: 0px 0px 5px 5px\" />", $string);
	}
$string=str_replace('javascript','javascr***',$string);
return $string;} # End: bbcode

function shorten_link($string) {
global $settings;

if(count($string) == 2) { $pre = ""; $url = $string[1]; }
else { $pre = $string[1]; $url = $string[2]; }

$shortened_url = $url;
if (strlen($url) > $settings['text_word_maxlength']) $shortened_url = substr($url, 0, ($settings['text_word_maxlength']/2)) . "..." . substr($url, - ($settings['text_word_maxlength']-3-$settings['text_word_maxlength']/2));
return $pre."<a href=\"".$url."\">".$shortened_url."</a>";
} # End: shorten_link

function parse_code($string) {
if (basename($_SERVER['PHP_SELF'])=='board_entry.php' || basename($_SERVER['PHP_SELF'])=='mix_entry.php')
	{
	$p_class='postingboard';
	}
else
	{
	$p_class='posting';
	}
$string = $string[1];
$string = str_replace('<br />','',$string);
$string = '</p><pre><code>'.$string.'</code></pre><p class="'.$p_class.'">';

return $string;
} # End: parse_code

// strips bb codes for e-mail texts:
function unbbcode($string) {
global $settings;

$string = preg_replace("#\[b\](.+?)\[/b\]#is", "*\\1*", $string);
$string = preg_replace("#\[i\](.+?)\[/i\]#is", "\\1", $string);
$string = preg_replace("#\[u\](.+?)\[/u\]#is", "\\1", $string);
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

function smilies($string) {
global $connid, $db_settings;

$result = mysql_query("SELECT file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table'], $connid);
while($data = mysql_fetch_array($result))
	{
	if($data['title']!='') $title = ' title="'.stripslashes($data['title']).'"'; else $title='';
	if($data['code_1']!='') $string = str_replace($data['code_1'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_1']."\"".$title." />", $string);
	if($data['code_2']!='') $string = str_replace($data['code_2'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_2']."\"".$title." />", $string);
	if($data['code_3']!='') $string = str_replace($data['code_3'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_3']."\"".$title." />", $string);
	if($data['code_4']!='') $string = str_replace($data['code_4'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_4']."\"".$title." />", $string);
	if($data['code_5']!='') $string = str_replace($data['code_5'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_5']."\"".$title." />", $string);
	}
mysql_free_result($result);
return($string);
} # End: smilies

function zitat($string) {
global $settings;

$string = preg_replace("/^".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);
$string = preg_replace("/\\n".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);
$string = preg_replace("/\\n ".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlspecialchars($settings['quote_symbol'])." \\1</span>", $string);
return $string;
} # End: zitat

function rss_quote($string) {
global $settings;

$string = preg_replace("/^".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);
$string = preg_replace("/\\n".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);
$string = preg_replace("/\\n ".htmlspecialchars($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlspecialchars($settings['quote_symbol'])." \\1</i>", $string);
return $string;
} # End: rss_quote

/**
 * connects to the database:
 *
 */function connect_db($host,$user,$pw,$db) {
global $lang;

$connid = @mysql_connect($host, $user, $pw);  // Datenbankverbindung herstellen
if(!$connid) die($lang['db_error']);
mysql_select_db($db, $connid) or die($lang['db_error']);
mysql_query("SET NAMES utf8",$connid) or die($lang['db_error']);
return $connid;
} # End: connect_db

// counts the users which are online:
function user_online($user_online_period = 10) {
global $connid, $db_settings, $settings;

if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id']; else $user_id = 0;
$diff = time()-($user_online_period*60);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $ip = "uid_".$_SESSION[$settings['session_prefix'].'user_id'];
else $ip = $_SERVER['REMOTE_ADDR'];

@mysql_query("DELETE FROM ".$db_settings['useronline_table']." WHERE time < ".$diff, $connid);

list($is_online) = @mysql_fetch_row(@mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE ip= '".$ip."'", $connid));
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
$mark_admin = false; $mark_mod = false; $mark_user = false;
if (($settings['admin_mod_highlight'] == 1 or $settings['user_highlight'] == 1) && $parent_array[$id]["user_id"] > 0)
	{
	$userdata_result=mysql_query("SELECT user_type FROM ".$db_settings['userdata_table']." WHERE user_id = '".$parent_array[$id]["user_id"]."'", $connid);
	if (!$userdata_result) die($lang['db_error']);
	$userdata = mysql_fetch_assoc($userdata_result);
	mysql_free_result($userdata_result);
	if ($settings['admin_mod_highlight'] == 1)
		{
		if ($userdata['user_type'] == "admin") $mark_admin = true;
		else if ($userdata['user_type'] == "mod") $mark_mod = true;
		}
	if ($settings['user_highlight'] == 1)
		{
		if ($userdata['user_type'] == "user") $mark_user = true;
		}
	}

if ($mark_admin===true or $mark_mod===true or $mark_user===true)
	{
	$name  = '<span class="';
	if ($mark_admin==true)
		{
		$name .= 'admin-highlight" title="Administrator';
		}
	else if ($mark_mod==true)
		{
		$name .= 'mod-highlight" title="Moderator';
		}
	else if ($mark_user==true)
		{
		$name .= 'user-highlight" title="registrierter Benutzer';
		}
	$name .= '">'.htmlspecialchars($parent_array[$id]["name"]).'</span>';
	}
else
	{
	$name = htmlspecialchars($parent_array[$id]["name"]);
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1)
	{
	$sult = str_replace("[name]", htmlspecialchars($parent_array[$id]["name"]), $lang['show_userdata_linktitle']);
	$thread_info_a = str_replace("[name]", $name."<a href=\"user.php?id=".$parent_array[$id]["user_id"]."\" title=\"".$sult."\"><img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" /></a>", $lang['thread_info']);
	}
else if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1)
	{
	$thread_info_a = str_replace("[name]", $name."<img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" title=\"".$lang['registered_user_title']."\" />", $lang['thread_info']);
	}
else $thread_info_a = str_replace("[name]", $name, $lang['thread_info']);

$thread_info_b = str_replace("[time]", strftime($lang['time_format'],$parent_array[$id]["tp_time"]), $thread_info_a);
  ?><li><?php
if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]==0)
	{
    ?><span class="actthread"><?php echo htmlspecialchars($parent_array[$id]["subject"]); ?></span><?php
	}
else if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]!=0)
	{
    ?><span class="actreply"><?php echo htmlspecialchars($parent_array[$id]["subject"]); ?></span><?php
	}
else
	{
    ?><a class="<?php
	if ((($parent_array[$id]['pid']==0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]['last_answer']) || (($parent_array[$id]['pid']==0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]['last_answer'] > $last_visit))
		{
		echo 'threadnew';
		}
	else if ($parent_array[$id]['pid']==0)
		{
		echo 'thread';
		}
	else if ((($parent_array[$id]['pid']!=0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]['time']) || (($parent_array[$id]['pid']!=0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]['time'] > $last_visit))
		{
		echo 'replynew';
		}
	else
		{
		echo 'reply';
		}
	?>" href="forum_entry.php?id=<?php echo $parent_array[$id]['id'];
	if ($page != 0 || $category != 0 || $order != 'time') echo '&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order; ?>"><?php
	echo htmlspecialchars($parent_array[$id]["subject"]);
	?></a><?php
	}

echo " ".$thread_info_b;
if ($parent_array[$id]['pid']==0 && $category==0 && isset($categories[$parent_array[$id]['category']]) && $categories[$parent_array[$id]['category']]!='')
	{ ?> <a title="<?php echo str_replace('[category]', $categories[$parent_array[$id]['category']], $lang['choose_category_linktitle']);
	if (isset($category_accession[$parent_array[$id]['category']]) && $category_accession[$parent_array[$id]['category']] == 2) echo ' '.$lang['admin_mod_category'];
	else if (isset($category_accession[$parent_array[$id]['category']]) && $category_accession[$parent_array[$id]["category"]] == 1) echo " ".$lang['registered_users_category']; ?>" href="forum.php?category=<?php echo $parent_array[$id]['category']; ?>"><span class="<?php
	if (isset($category_accession[$parent_array[$id]['category']]) && $category_accession[$parent_array[$id]['category']] == 2) echo 'category-adminmod';
	else if (isset($category_accession[$parent_array[$id]['category']]) && $category_accession[$parent_array[$id]['category']] == 1) echo 'category-regusers';
	else echo 'category'; ?>">(<?php
	echo $categories[$parent_array[$id]['category']]; ?>)</span></a><?php
	}

if ($aktuellerEintrag == 0 && $parent_array[$id]["pid"]==0 && $parent_array[$id]["fixed"] == 1)
	{
		?> <img src="img/fixed.gif" width="9" height="9" title="<?php echo $lang['fixed']; ?>" alt="*" /><?php
	}
if ($parent_array[$id]["pid"]==0 && $settings['all_views_direct'] == 1)
	{
	echo " <span class=\"small\">";
	if ($settings['board_view']==1)
		{ ?><a href="board_entry.php?id=<?php echo $parent_array[$id]['tid']; ?>"><img src="img/board_d.gif" alt="[Board]" title="<?php echo $lang['open_in_board_linktitle']; ?>" width="12" height="9" /></a><?php
		}			
	if ($settings['mix_view'] == 1)
		{ ?><a href="mix_entry.php?id=<?php echo $parent_array[$id]['tid']; ?>"><img src="img/mix_d.gif" alt="[Mix]" title="<?php echo $lang['open_in_mix_linktitle']; ?>" width="12" height="9" /></a><?php
		}
	echo "</span>";
	}
if ($parent_array[$id]["pid"]==0 && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
	{
      ?> <a href="admin.php?mark=<?php echo $parent_array[$id]["tid"]; ?>&amp;refer=<?php echo basename($_SERVER["PHP_SELF"]); ?>&amp;page=<?php echo $page; ?>&amp;category=<?php echo urlencode($category); ?>&amp;order=<?php echo $order; ?>"><?php
	if ($parent_array[$id]['marked']==1)
		{
		echo '<img src="img/marked.gif" alt="[x]" width="9" height="9" title="'.$lang['demark_linktitle'].'" />';
		}
	else
		{
		echo '<img src="img/mark.gif" alt="[-]" title="'.$lang['mark_linktitle'].'" width="9" height="9" />';
		}
   ?></a><?php
	}

// display all branches of the thread tree:
if(isset($child_array[$id]) && is_array($child_array[$id]))
	{
    ?><ul class="<?php echo ($tiefe >= $settings['thread_depth_indent']) ? 'deep-reply' : 'reply'; ?>"><?php
	foreach ($child_array[$id] as $kind)
		{
		thread_tree($kind, $aktuellerEintrag, $tiefe+1);
		}
    ?></ul><?php
	}
  ?></li><?php
} # End: thread_tree



/**
 * replaces the template placeholders with the computed contents
 * 
 */
function parse_template() {
global $settings, $lang, $header, $footer, $wo, $ao, $topnav, $subnav_1, $subnav_2, $footer_info_dump, $search, $show_postings, $counter;

$template = implode("",file($settings['template']));

if ($settings['home_linkaddress'] != "" && $settings['home_linkname'] != "") $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "\\1", $template);
else $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "", $template);

$template = str_replace("{LANGUAGE}",$lang['language'],$template);
$template = str_replace("{CHARSET}",$lang['charset'],$template);
$title = stripslashes($settings['forum_name']); if (isset($wo)) $title .= " - ". htmlspecialchars(stripslashes($wo));
$template = str_replace("{TITLE}",$title,$template);
$template = str_replace("{FORUM-NAME}",stripslashes($settings['forum_name']),$template);
$template = str_replace('{HOME-ADDRESS}',$settings['home_linkaddress'],$template);
$template = str_replace('{HOME-LINK}',$settings['home_linkname'],$template);
$template = str_replace('{FORUM-INDEX-LINK}',$lang['forum_home_linkname'],$template);
$template = str_replace('{FORUM-INDEX-LINKTITLE}',$lang['forum_home_linktitle'],$template);
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
	$user_menu = '<a href="user.php?id='.$_SESSION[$settings['session_prefix'].'user_id'].'" title="'.$lang['own_userdata_linktitle'].'"><b>'.htmlspecialchars(stripslashes($_SESSION[$settings['session_prefix'].'user_name'])).'</b></a> | <a href="user.php" title="'.$lang['user_area_linktitle'].'">'.$lang['user_area_linkname'].'</a>'.$user_menu_admin.' | <a href="login.php" title="'.$lang['logout_linktitle'].'">'.$lang['logout_linkname'].'</a>';
	}
else
	{
	$user_menu = '<a href="login.php" title="'.$lang['login_linktitle'].'">'.$lang['login_linkname'].'</a> | <a href="register.php" title="'.$lang['register_linktitle'].'">'.$lang['register_linkname'].'</a>';
	}
$template = str_replace("{USER-MENU}",$user_menu,$template);

// Search:
$search_dump = '<form action="search.php" method="get" title="'.$lang['search_formtitle'].'"><div class="search">';
$search_dump .= $lang['search_marking'];
# if (isset($search)) $search_match = htmlspecialchars(stripslashes($search)); else $search_match = "";
$search_dump .= '<span class="normal">&nbsp;</span><input class="searchfield" type="text" name="search" value="" size="20" /><span class="normal">&nbsp;</span><input type="image" name="" src="img/submit.gif" alt="&raquo;" /></div></form>';
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
