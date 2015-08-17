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
require_once('functions/funcs.db.php');
require_once('functions/funcs.output.php');
require_once('functions/funcs.processing.php');
require_once('data/extern/stringparser_bbcode/src/stringparser_bbcode.class.php');

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
global $lang, $connid, $db_settings;

$r = array();

$result = mysql_query("SELECT name, value FROM ".$db_settings['settings_table'], $connid);
if (!$result) die($lang['db_error']);
while ($line = mysql_fetch_assoc($result))
	{
	$r[$line['name']] = $line['value'];
	}
mysql_free_result($result);

return $r;
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
if($categories !== false)
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
 * page navigation for forum.php, board.php and mix.php
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
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?page='.$new_index_before;
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.$ic.'">';
		$output .= '<img src="img/prev.png" alt="&laquo;" title="';
		$output .= strip_tags($lang['previous_page_linktitle']).'" width="12" height="9"></a>&nbsp;';
		}
	# if ($new_index_before >= 0 && $new_index_after < $site_count) $output .= " ";
	$page_count = ceil($entry_count / $entries_per_page);
	$output .= '<form action="'.$_SERVER["SCRIPT_NAME"].'" method="get" title="';
	$output .= strip_tags($lang['choose_page_formtitle']).'"><div class="inline-form">'."\n";
	$output .= isset($order) ? '<input type="hidden" name="order" value="'.$order.'">' : '';
	$output .= isset($descasc) ? '<input type="hidden" name="descasc" value="'.$descasc.'">' : '';
	$output .= (isset($category) and $category > 0) ? '<input type="hidden" name="category" value="'.$category.'">' : '';
	$output .= (isset($action) && $action!="") ? '<input type="hidden" name="action" value="'.$action.'">' : '';
	$output .= (isset($_GET['letter'])) ? '<input type="hidden" name="letter" value="'.$_GET['letter'].'">' : '';
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
	$output .= '</select>'."\n".'<noscript>&nbsp;<input type="image" name="" value="" src="img/submit.png" alt="&raquo;"></noscript></div></form>'."\n";
	if ($new_index_after < $site_count)
		{
		$output .= '&nbsp;<a href="'.$_SERVER["SCRIPT_NAME"]."?page=".$new_index_after;
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.$ic.'">';
		$output .= '<img src="img/next.png" alt="&raquo;" title="';
		$output .= strip_tags($lang['next_page_linktitle']).'" width="12" height="9"></a>';
		}
	}

return $output;
} # End: nav



/**
 * amend the protocol to a given link
 *
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

$bbcode = new StringParser_BBCode ();
$bbcode->addFilter(STRINGPARSER_FILTER_PRE, 'convertLineBreaks');
$bbcode->addParser(array('block','inline','link','listitem'), 'htmlspecialchars');
$bbcode->addParser(array('block','inline','link','listitem'), 'nl2br');
$bbcode->addParser('list', 'bbcodeStripContents');

# codes
$bbcode->addCode('b', 'simple_replace', null, array ('start_tag' => '<strong>', 'end_tag' => '</strong>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('i', 'simple_replace', null, array ('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('u', 'simple_replace', null, array ('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array ('listitem', 'block', 'inline'), array ());
$bbcode->addCode('del', 'simple_replace', null, array ('start_tag' => '<del>', 'end_tag' => '</del>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('ins', 'simple_replace', null, array ('start_tag' => '<ins>', 'end_tag' => '</ins>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('sub', 'simple_replace', null, array ('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('sup', 'simple_replace', null, array ('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline', array ('listitem', 'block', 'inline', 'link'), array ());
$bbcode->addCode('code', 'simple_replace', null, array ('start_tag' => '<code>', 'end_tag' => '</code>'), 'inline', array ('listitem', 'block', 'inline'), array ());
$bbcode->addCode('url', 'usecontent?', 'bbcodeDoURL', array ('usecontent_param' => 'default'), 'link', array ('listitem', 'block', 'inline'), array ('link'));
$bbcode->addCode('link', 'usecontent?', 'bbcodeDoURL', array ('usecontent_param' => 'default'), 'link', array ('listitem', 'block', 'inline'), array ('link'));

# code flags
$bbcode->setCodeFlag('b', 'case_sensitive', false);
$bbcode->setCodeFlag('i', 'case_sensitive', false);
$bbcode->setCodeFlag('u', 'case_sensitive', false);

#$bbcode->setCodeFlag('*', 'closetag', BBCODE_CLOSETAG_OPTIONAL);
#$bbcode->setCodeFlag('*', 'closetag.before.newline', BBCODE_NEWLINE_DROP);
#$bbcode->setCodeFlag('list', 'paragraph_type', BBCODE_PARAGRAPH_BLOCK_ELEMENT);
#$bbcode->setCodeFlag('list', 'opentag.before.newline', BBCODE_NEWLINE_DROP);
#$bbcode->setCodeFlag('list', 'closetag.before.newline', BBCODE_NEWLINE_DROP);
$bbcode->setRootParagraphHandling(true);

# do the parsing
$string = $bbcode->parse($string);

#$string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
#$string = preg_replace_callback("#\[link\](.+?)\[/link\]#is", "shorten_link", $string);
#$string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "<a href=\"\\1\">\\2</a>", $string);
#$string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
#$string = preg_replace_callback("#\[url\](.+?)\[/url\]#is", "shorten_link", $string);
#$string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "<a href=\"\\1\">\\2</a>", $string);
#$string = preg_replace_callback("#\[code\](.+?)\[/code\]#is", "parse_code", $string);
$string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "<a href=\"".$_SERVER['SCRIPT_NAME']."?id=\\1\">\\1</a>", $string);
$string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "<a href=\"".$_SERVER['SCRIPT_NAME']."?id=\\1\">\\2</a>", $string);
if ($settings['bbcode_img'] == 1)
	{
	$string = preg_replace("#\[img\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"margin: 5px 0px 5px 0px\">", $string);
	$string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: left; margin: 0px 5px 5px 0px\">", $string);
	$string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "<img src=\"\\1\" alt=\"[image]\" style=\"float: right; margin: 0px 0px 5px 5px\">", $string);
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
function shorten_link($url) {
global $settings;

$t = $url;
#if (strlen($url) > $settings['text_word_maxlength'])
#$t = substr($url, 0, ($settings['text_word_maxlength']/2)) . "..." . substr($url, - ($settings['text_word_maxlength']-3-$settings['text_word_maxlength']/2));

return $t;
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
$string = str_replace('<br>','',$string);
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
$string = preg_replace("#\[link\]www\.(.+?)\[/link\]#ise", "'http://www.'.processUrlEncode('\\1', false)", $string);
$string = preg_replace("#\[link\](.+?)\[/link\]#ise", "processUrlEncode('\\1')", $string);
$string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#ise", "'\\2 --> '.processUrlEncode('\\1')", $string);
$string = preg_replace("#\[url\]www\.(.+?)\[/url\]#ise", "'http://www.'.processUrlEncode('\\1', false)", $string);
$string = preg_replace("#\[url\](.+?)\[/url\]#ise", "processUrlEncode('\\1')", $string);
$string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#ise", "'\\2 --> '.processUrlEncode('\\1')", $string);
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
	$string = ($data['code_1']!='') ? str_replace($data['code_1'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_1'].'"'.$title.'>', $string) : $string;
	$string = ($data['code_2']!='') ? str_replace($data['code_2'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_2'].'"'.$title.'>', $string) : $string;
	$string = ($data['code_3']!='') ? str_replace($data['code_3'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_3'].'"'.$title.'>', $string) : $string;
	$string = ($data['code_4']!='') ? str_replace($data['code_4'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_4'].'"'.$title.'>', $string) : $string;
	$string = ($data['code_5']!='') ? str_replace($data['code_5'], '<img src="img/smilies/'.$data['file'].'" alt="'.$data['code_5'].'"'.$title.'>', $string) : $string;
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

return str_replace('<p>'.$settings['quote_symbol'].' ', '<p class="citation">'.$settings['quote_symbol'].' ', $string);

} # End: zitat



/**
 * make code in blocks to codeblocks
 *
 * @param string $string
 * @return string $string
 */
function codeblock($string) {
global $settings;


if (preg_match("#<p><code>(.+?)</code></p>#is", $string))
	{
	return preg_replace("#<p><code>(.+?)</code></p>#ise", "'<pre><code>'.br2nl('\\1').'</code></pre>'", $string);
	}
else
	{
	return $string;
	}

return $string;
} # End: codeblock



/**
 *
 * @param string
 * @return string
 */
function br2nl($data) {
return preg_replace( '!<br.*>!iU', "", $data );
}



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
 * replaces the template placeholders with the computed contents
 * 
 */
function parse_template() {
global $settings, $lang, $header, $cssLink, $additionalJS, $footer, $wo, $ao, $topnav, $subnav_1, $subnav_2, $footer_info_dump, $search, $show_postings, $counter;

$template = implode("",file($settings['template']));

if ($settings['home_linkaddress'] != "" && $settings['home_linkname'] != "") $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "\\1", $template);
else $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "", $template);

$template = str_replace("{LANGUAGE}",strip_tags($lang['language']),$template);
$template = str_replace("{CHARSET}",strip_tags($lang['charset']),$template);
$title = isset($wo) ? $settings['forum_name']." - ".htmlspecialchars($wo) : $settings['forum_name'];
$description = isset($wo) ? $settings['forum_name'].": ".htmlspecialchars($wo) : $settings['forum_name'];
$template = str_replace("{TITLE}",$title,$template);
$template = str_replace("{DESCRIPTION}",$description,$template);
$template = str_replace("{LOAD-CSS}",$cssLink,$template);
$template = str_replace('{ADD-JS}',$additionalJS,$template);
$template = str_replace('{SCRIPT-VERSION-STRING}',$settings['version'],$template);
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
		$user_menu_admin = '<li><a href="admin.php" class="buttonize edge" title="'. strip_tags($lang['admin_area_linktitle']) .'"><span class="fa fa-server"></span>&nbsp;'. $lang['admin_area_linkname'] .'</a></li>'. "\n";
		}
	else
		{
		$user_menu_admin = "";
		}
	$user_menu = '<li><a href="user.php?id='. $_SESSION[$settings['session_prefix'].'user_id'] .'" class="buttonize edge" title="'. strip_tags($lang['own_userdata_linktitle']) .'"><span class="fa fa-user"></span>&nbsp;<b>'. htmlspecialchars($_SESSION[$settings['session_prefix'].'user_name']) .'</b></a></li>'. "\n" .'<li><a href="login.php" class="buttonize edge" title="'. strip_tags($lang['logout_linktitle']) .'"><span class="fa fa-sign-out"></span>&nbsp;'.$lang['logout_linkname'].'</a></li>'. "\n" .'<li><a href="user.php" class="buttonize edge" title="'. strip_tags($lang['user_area_linktitle']) .'"><span class="fa fa-users"></span>&nbsp;'. $lang['user_area_linkname'] .'</a></li>'. "\n" . $user_menu_admin;
	}
else
	{
	$user_menu = '<li><a href="login.php" class="buttonize edge" rel="nofollow" title="'. strip_tags($lang['login_linktitle']) .'"><span class="fa fa-sign-in"></span>&nbsp;'. $lang['login_linkname'] .'</a></li>'. "\n" .'<li><a href="register.php" class="buttonize edge" rel="nofollow" title="'. strip_tags($lang['register_linktitle']) .'"><span class="fa fa-user-plus"></span>&nbsp;'. $lang['register_linkname'] .'</a></li>'. "\n";
	}
$user_menu .= '<li><a href="search.php" class="buttonize edge" rel="nofollow" title="'. strip_tags($lang['search_formtitle']) .'"><span class="fa fa-search-plus"></span>&nbsp;'. $lang['search_linkname'] .'</a></li>';
$template = str_replace("{USER-MENU}", $user_menu, $template);

// Search:
$search_dump = "\n".'<form action="search.php" method="get" title="'. strip_tags($lang['search_formtitle']) .'"><div class="search">'."\n";
$search_dump .= '<input class="searchfield" type="search" id="search" name="search" value="" size="20"><button type="submit" name=""><span class="fa fa-search">&nbsp;</span>'. $lang['search_marking'] .'</button></div></form>'."\n";
$template = str_replace("{SEARCH}", $search_dump, $template);

// Sub navigation:
$tnd = (isset($topnav) and !empty($topnav)) ? $topnav : "";
$template = str_replace("{SUB-NAVIGATION-1}", '<ul class="left">'. $tnd .'</ul>', $template);

$subnav_2_dump = (isset($subnav_2)) ? $subnav_2 : "&nbsp;";
$template = str_replace("{SUB-NAVIGATION-2}",$subnav_2_dump,$template);

// Footer:
$template = str_replace("{COUNTER}",$counter,$template);

$rss_feed_link = '';
$rss_feed_button = '';
if ($settings['provide_rssfeed'] == 1 && $settings['access_for_users_only'] == 0)
	{ 
	$rss_feed_link = '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="rss.php">';
	$rss_feed_button = '<li><a href="rss.php" class="buttonize"><span class="fa fa-rss-square"></span>&nbsp;RSS Feed</a></li>'. "\n";
	}
$template = str_replace("{RSS-FEED-LINK}",$rss_feed_link,$template);
$template = str_replace("{RSS-FEED-BUTTON}",$rss_feed_button,$template);

$template_parts = explode("{CONTENT}",$template);
$header = (isset($template_parts[0])) ? $template_parts[0] : "";
$footer = (isset($template_parts[1])) ? $template_parts[1] : "";
} # End: parse_template
?>
