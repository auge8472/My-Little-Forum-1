<?php

/**
 *	collection of functions for output control
 * @author: Heiko August
 */



/**
 * generates the list of functions
 * to manipulate marked threads
 *
 * @param string $refer
 * @return string $output
 */
function outputManipulateMarked($refer='') {
global $settings,$lang;

$r  = '';

if (isset($_SESSION[$settings['session_prefix'].'user_type'])
	and $_SESSION[$settings['session_prefix'].'user_type']=='admin')
	{
	$ref = (!empty($refer)) ? '&amp;refer='.$refer : '';
	$r .= '<p class="marked-threads">'."\n";
	$r .= '<img src="img/marked.gif" alt="[x]" width="9" height="9" /> ';
	$r .= $lang['marked_threads_actions']."\n";
	$r .= '<a href="admin.php?action=delete_marked_threads'.$ref.'">';
	$r .= $lang['delete_marked_threads'].'</a> - '."\n";
	$r .= '<a href="admin.php?action=lock_marked_threads'.$ref.'">';
	$r .= $lang['lock_marked_threads'].'</a> - '."\n";
	$r .= '<a href="admin.php?action=unlock_marked_threads'.$ref.'">';
	$r .= $lang['unlock_marked_threads'].'</a> - '."\n";
	$r .= '<a href="admin.php?action=unmark'.$ref.'">';
	$r .= $lang['unmark_threads'].'</a> - '."\n";
	$r .= '<a href="admin.php?action=invert_markings'.$ref.'">';
	$r .= $lang['invert_markings'].'</a> - '."\n";
	$r .= '<a href="admin.php?action=mark_threads'.$ref.'">';
	$r .= $lang['mark_threads'].'</a>'."\n";
	$r .= '</p>'."\n";
	}

return $r;
} # End: outputManipulateMarked



/**
 * generates the form for categories
 *
 * @param array $categories
 * @param integer $category
 * @return string $output
 */
function outputCategoriesList($categories, $category) {
global $lang;

$r = '';

if($categories != false && $categories != "not accessible")
	{
	$r .= "\n".'<form method="get" action="'.$_SERVER['SCRIPT_NAME'].'" title="'.$lang['choose_category_formtitle'].'">'."\n".'<div class="inline-form">'."\n";
	$r .= '<select class="kat" size="1" name="category" onchange="this.form.submit();">'."\n";
	$r .= '<option value="0"';
	$r .= (isset($category) && $category==0) ? ' selected="selected"' : '';
	$r .= '>'.$lang['show_all_categories'].'</option>'."\n";
	while(list($key, $val) = each($categories))
		{
		if($key!=0)
			{
			$r .= '<option value="'.$key.'"';
			$r .= ($key==$category) ? ' selected="selected"' : '';
			$r .= '>'.$val.'</option>'."\n";
			}
		}
	$r .= '</select>'."\n".'<noscript><p class="inline-form"> <input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></p></noscript></div>'."\n".'</form>'."\n";
	}

return $r;
} # End: outputCategoriesList



/**
 * generates the link to the posting form in top- and subnavigation
 *
 * @param integer $category
 * @param string $view (optional)
 * @return string $output
 */
function outputPostingLink($category,$view="forum") {
global $lang;

$r = '';

$r .= '<a class="textlink" href="posting.php?view='.$view;
$r .= !empty($category) ? '&amp;category='.$category : '';
$r .= '" title="'.$lang['new_entry_linktitle'].'">'.$lang['new_entry_linkname'].'</a>';

return $r;
} # End: outputPostingLink



/**
 * generates posting authr name string
 *
 *
 */
function outputAuthorInfo($mark, $entry, $page, $order, $view, $category=0) {
global $lang, $settings;

$r = '';
$email_hp = '';
$place_c = '';
$place = '';
$editor = '';
$linktitle = '';
$entryIP = '';
$entryedit = '';
$entryID = '';
$answer = '';

# whole author string template
$authorstring = $lang['forum_author_marking'];
# editors template
$editstring = $lang['forum_edited_marking'];
# generate setting to show contact link if not present
$entry["hide_email"] = empty($entry["hide_email"]) ? 0 : $entry["hide_email"];
# generate string for posting ID
$entryID .= ($settings['show_posting_id'] == 1) ? '<span class="xsmall">Posting:&nbsp;#&nbsp;'.$entry['id'].'</span>' : '';
# generate string for name of the answered author
$answer = (!empty($entry['answer'])) ? '<span class="xsmall">@&nbsp;'.htmlspecialchars($entry['answer']).'</span>' : '';
# generate HTML cource code for userdata (hp, email, location)
if ($entry["email"]!="" && $entry["hide_email"] != 1 or $entry["hp"]!="")
	{
	$email_hp .= " ";
	}
if ($entry["hp"]!="")
	{
	$email_hp .= '<a href="'.amendProtocol($entry["hp"]).'" title="';
	$email_hp .= htmlspecialchars($entry["hp"]).'"><img src="img/homepage.gif" ';
	$email_hp .= 'alt="'.$lang['homepage_alt'].'" width="13" height="13" /></a>';
	}
if (($entry["email"]!="" && $entry["hide_email"] != 1) && $entry["hp"]!="") 
	{
	$email_hp .= "&nbsp;";
	}
if ($entry["email"]!="" && $entry["hide_email"] != 1)
	{
	$email_hp .= '<a href="contact.php?id='.$entry["id"];
	$email_hp .= !empty($page) ? '&amp;page='.$page : '';
	$email_hp .= !empty($order) ? '&amp;order='.$order : '';
	$email_hp .= !empty($category) ? '&amp;category='.intval($category) : '';
	$email_hp .= '" title="'.str_replace("[name]", htmlspecialchars($entry['name']), $lang['email_to_user_linktitle']).'"><img src="img/email.gif" alt="'.$lang['email_alt'].'" width="13" height="10" /></a>';
	}
if ($entry["place"] != "")
	{
	$place .= htmlspecialchars($entry['place']);
	}
# generate HTML source code of authors name
$name = outputAuthorsName($entry['name'], $mark, $entry['user_id']);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $entry['user_id'] > 0)
	{
	$linktitle = str_replace("[name]", htmlspecialchars($entry['name']), $lang['show_userdata_linktitle']);
	$uname .= '<a class="userlink" href="user.php?id='.$entry["user_id"].'" title="'.$linktitle.'">'.$name.'</a>';
	}
else
	{
	$uname = $name;
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin" ||
	isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "mod")
	{
	$entryIP = '<span class="xsmall">'.$entry['ip'].'</span>';
	}

if ($entry["edited_diff"] > 0
	&& $entry["edited_diff"] > $entry["time"]
	&& $settings['show_if_edited'] == 1)
	{
	$editstring = str_replace("[name]", htmlspecialchars($entry["edited_by"]), $editstring);
	$editstring = str_replace("[time]", strftime($lang['time_format'],$entry["e_time"]), $editstring);
	$entryedit .= '<span class="xsmall">'.$editstring.'</span>';
	}

if ($view=='forum')
	{
	$authorstring = str_replace("[name]", $uname, $authorstring);
	$authorstring = str_replace("[email_hp]", $email_hp, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[time]", strftime($lang['time_format'],$entry["p_time"]), $authorstring);
	$entryID = !empty($entryID) ? ' - '.$entryID : '';
	$entryedit = (!empty($entryedit)) ? '<br />'.$entryedit : '';
	$r .= '<p class="author">'.$authorstring.'&nbsp;'.$entryIP.$entryID.$entryedit.'</p>'."\n";
	}
else if ($view=='board' or $view=='mix')
	{
	$place = (!empty($place)) ? '<br />'.$place : '';
	$entryedit = (!empty($entryedit)) ? '<br />'.$entryedit : '';
	if (!empty($entryIP) or !empty($entryID) or !empty($answer))
		{
		$separator = '<br /><br />';
		if (!empty($entryIP))
			{
			$entryID = (!empty($entryID)) ? '<br /><br />'.$entryID : '';
			$answer = (!empty($answer)) ? '<br />'.$answer : '';
			}
		else
			{
			if (!empty($answer))
				{
				$entryID = (!empty($entryID)) ? '<br />'.$entryID : '';
				}
			}
		}
	$r .= $uname.'<br />'."\n".$email_hp.$place."\n<br />".strftime($lang['time_format'],$entry["p_time"]).$entryedit.$separator.$entryIP.$answer.$entryID."\n";
	}
else
	{
	if (!empty($entryID))
		{
		$entryID = ' - '.$entryID;
		}
#	$r .= $name.$entryID;
	$r .= $name;
	}

return $r;
} # End: outputAuthorInfo



/**
 * generates the name part of the authors information
 *
 * @param string $name
 * @param array $mark
 * @param integer $user_id
 * @return string $output
 */
function outputAuthorsName($username, $mark, $user_id=0) {
global $settings, $lang;

$r = '';
$name = '<span class="';
$regimg = '';

if ($mark['admin']===true or $mark['mod']===true or $mark['user']===true)
	{
	if ($mark['admin']==true)
		{
		$name .= 'admin-highlight" title="'.$lang['ud_admin'];
		}
	else if ($mark['mod']==true)
		{
		$name .= 'mod-highlight" title="'.$lang['ud_mod'];
		}
	else if ($mark['user']===true)
		{
		$name .= 'user-highlight" title="'.$lang['ud_user'];
		}
	}
else
	{
	$name .= 'username';
	}
$name .= '">'.htmlspecialchars($username).'</span>';

# generate image for registered users
if ($settings['show_registered'] ==1
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $user_id > 0)
	{
	$regimg .= '<img src="img/registered.gif" alt="(R)" width="10" height="10" title="'.$lang['registered_user_title'].'" />';
	}

$r .= $name.$regimg;

return $r;
} # End: outputAuthorsName



/**
 * generates the menu for editing of a posting
 * @return string
 */
function outputPostingEditMenu($thread, $first = '') {
global $settings, $lang, $page, $order, $descasc, $category;

$r  = '';

if (($settings['user_edit'] == 1
	and (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]))
	or (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
	or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
	{
	$r .= "<ul class=\"menu\">\n";
	$r .= '<li><a href="posting.php?action=edit&amp;id=';
	$r .= $thread["id"].'&amp;view=board&amp;back='.$thread["tid"].'&amp;page='.$page;
	$r .= '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
	$r .= '" class="edit-posting" title="'.$lang['edit_linktitle'].'">';
	$r .= $lang['edit_linkname'].'</a></li>'."\n";
	if (($settings['user_delete'] == 1
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]))
		or (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?action=delete&amp;id=';
		$r .= $thread["id"].'&amp;back='.$thread["tid"].'&amp;view=board&amp;page=';
		$r .= $page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category=';
		$r .= $category.'" class="delete-posting" title="'.$lang['delete_linktitle'].'">';
		$r .= $lang['delete_linkname'].'</a></li>'."\n";
		}
	if ((!empty($first) and $first==='opener')
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?lock=true&amp;view=board&amp;id=';
		$r .= $thread["id"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc=';
		$r .= $descasc.'&amp;category='.$category.'" class="lock-posting" title="';
		$r .= ($thread['locked'] == 0) ? $lang['lock_linktitle'] : $lang['unlock_linktitle'];
		$r .= '">';
		$r .= ($thread['locked'] == 0) ? $lang['lock_linkname'] : $lang['unlock_linkname'];
		$r .= '</a></li>'."\n";
		}
	$r .= "</ul>\n";
	}

return $r;
} # End: outputPostingEditMenu
?>