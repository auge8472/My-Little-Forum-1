<?php

/**
 * collection of functions for generation of HTML-output
 * @author Heiko August
 */



/**
 * generates a single link
 *
 * @param string $url
 * @param string $text
 * @param string $title
 * @param string $class
 * @return string $link
 */
function outputSingleLink($url, $text, $title, $class) {	
$link = '<a href="{URL}" class="{Class}" title="{Title}">{Text}</a>';

$link = str_replace('{URL}', $url, $link);
$link = str_replace('{Class}', $class, $link);
$link = str_replace('{Title}', $title, $link);
$link = str_replace('{Text}', $text, $link);
return $link;
} # End: outputSingleLink


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
	and $_SESSION[$settings['session_prefix'].'user_type'] == 'admin')
	{
	$ref = (!empty($refer)) ? '&amp;refer='.$refer : '';
	$r .= '<div class="marked-threads">'."\n";
	$r .= ' <h2>'.$lang['marked_threads_actions'].'</h2>'."\n";
	$r .= ' <ul>'."\n";
	$r .= '  <li><a href="admin.php?action=delete_marked_threads'.$ref.'"><span class="fa fa-trash-o icon-trash-o"></span>';
	$r .= '&nbsp;'. $lang['delete_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=lock_marked_threads'.$ref.'"><span class="fa fa-lock icon-lock2"></span>';
	$r .= '&nbsp;'. $lang['lock_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=unlock_marked_threads'.$ref.'"><span class="fa fa-unlock-alt icon-unlock-alt"></span>';
	$r .= '&nbsp;'. $lang['unlock_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=unmark'.$ref.'"><span class="fa fa-check icon-check-square-o"></span>';
	$r .= '&nbsp;'. $lang['unmark_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=invert_markings'.$ref.'">';
	$r .= $lang['invert_markings'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=mark_threads'.$ref.'"><span class="fa fa-check-square-o"></span>';
	$r .= '&nbsp;'. $lang['mark_threads'].'</a></li>'."\n";
	$r .= ' </ul>'."\n";
	$r .= '</div>'."\n";
	}

return $r;
} # End: outputManipulateMarked



/**
 * generates a form for categories
 *
 * @param array $categories
 * @param integer $category
 * @return string $output
 */
function outputCategoriesList($categories, $category) {
global $lang;

$r = '';

if($categories !== false
	&& $categories != "not accessible")
	{
	$r .= "\n".'<form method="get" action="'. $_SERVER['SCRIPT_NAME'] .'" title="'. outputLangDebugInAttributes($lang['choose_category_formtitle']) .'">'."\n";
	$r .='<div class="inline-form">'."\n";
	$r .= '<select class="kat" size="1" name="category" onchange="this.form.submit();">'."\n";
	$r .= '<option value="0"';
	$r .= (isset($category) && $category == 0) ? ' selected="selected"' : '';
	$r .= '>'. $lang['show_all_categories'] .'</option>'."\n";
	while(list($key, $val) = each($categories))
		{
		if($key!=0)
			{
			$r .= '<option value="'. $key .'"';
			$r .= ($key == $category) ? ' selected="selected"' : '';
			$r .= '>'. $val .'</option>'."\n";
			}
		}
	$r .= '</select>'."\n".'<noscript><p class="inline-form"> <input type="image" name="" value="" src="img/submit.png" alt="&raquo;"></p></noscript></div>'."\n".'</form>'."\n";
	}

return $r;
} # End: outputCategoriesList



/**
 * generates the number of replies
 *
 * @param string $threadID
 * @param string $connid
 * @return integer $replies
 */
function outputGetReplies($threadID, $connid) {
global $db_settings;

# as first count members of a thread including the opening posting
$replyResult = mysql_query("SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE tid = ". $threadID, $connid);
list($answers) = mysql_fetch_row($replyResult);
# now reduce by 1 (the opening posting, it's by definition not a reply)
$answers = $answers - 1;
mysql_free_result($replyResult);

return $answers;
} # End: outputGetReplies



/**
 * search the data of last reply
 *
 * @param string $threadID
 * @param string $connid
 * @return array $lastReply
 */
function outputGetLastReply($threadID, $connid) {
global $db_settings;

$la_result = mysql_query("SELECT name, id FROM ". $db_settings['forum_table'] ." WHERE tid = ". $threadID ." ORDER BY time DESC LIMIT 1", $connid);
$last_answer = mysql_fetch_assoc($la_result);
mysql_free_result($la_result);

return $last_answer;
} # End: outputGetLastReply


/**
 * detects the status of $mark dependent of users role
 *
 * @param array $mark
 * @param string $user_type
 * @param string $connid
 * @return array $mark
 */
function outputStatusMark($mark, $user_type = '', $connid) {
global $settings;

$mark['admin'] = ($user_type === "admin" && $settings['admin_mod_highlight'] == 1) ? 1 : 0;
$mark['mod'] = ($user_type === "mod" && $settings['admin_mod_highlight'] == 1) ? 1 : 0;
$mark['user'] = ($user_type === "user" && $settings['user_highlight'] == 1) ? 1 : 0;
return $mark;
}



/**
 * generates the link to the posting form in top- and subnavigation
 *
 * @param integer $category
 * @param string $view (optional)
 * @return string $output
 */
function outputPostingLink($category, $view = '') {
global $lang;

$r = '';

$qs = '';

$q1 = !empty($view) ? 'view='.$view : '';
$q2 = !empty($category) ? 'category='.$category : '';

if (!empty($view) or !empty($category))
	{
	$qs .= '?'.$q1;
	if (!empty($q2))
		{
		$qs .= ($qs != '?') ? '&amp;'.$q2 : $q2;
		}
	}

$r .= '<li><a rel="nofollow" href="posting.php'. $qs .'" title="'. outputLangDebugInAttributes($lang['new_entry_linktitle']);
$r .=  '"><span class="fa fa-bullhorn icon-bullhorn"></span>&nbsp;'. htmlspecialchars($lang['new_entry_linkname']) .'</a></li>'."\n";

return $r;
} # End: outputPostingLink



/**
 * generates posting authr name string
 *
 * @param array $mark
 * @param array $entry
 * @param string $view
 * @return string $r
 */
function outputAuthorInfo($mark, $entry, $view) {
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
$uname = '';

# whole author string template
$authorstring = $lang['forum_author_marking'];
# editors template
$editstring = $lang['forum_edited_marking'];
# generate setting to show contact link if not present
$entry["hide_email"] = empty($entry["hide_email"]) ? 0 : $entry["hide_email"];
# generate string for posting ID
$entryID .= ($settings['show_posting_id'] == 1) ? '<span class="postinginfo">Posting:&nbsp;#&nbsp;'. $entry['id'] .'</span>' : '';
# generate string for name of the answered author
$answer = (!empty($entry['answer'])) ? '<span class="postinginfo">@&nbsp;'. htmlspecialchars($entry['answer']) .'</span>' : '';
# generate HTML cource code for userdata (hp, email, location)
if ($entry["email"] != ""
	&& $entry["hide_email"] != 1
	or $entry["hp"] != "")
	{
	$email_hp .= " ";
	}
if ($entry["hp"] != "")
	{
	$email_hp .= '<a href="'. amendProtocol($entry["hp"]) .'" title="';
	$email_hp .= htmlspecialchars($entry["hp"]) .'"><img src="img/homepage.png" ';
	$email_hp .= 'alt="'. outputLangDebugInAttributes($lang['homepage_alt']) .'" width="13" height="13"></a>';
	}
if (($entry["email"] != ""
	&& $entry["hide_email"] != 1)
	and $entry["hp"] != ""
	and (($settings['entries_by_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_id']))
	or $settings['entries_by_users_only'] == 0))
	{
	$email_hp .= "&nbsp;";
	}
if (($entry["email"] != ""
	&& $entry["hide_email"] != 1)
	and (($settings['entries_by_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_id']))
	or $settings['entries_by_users_only'] == 0))
	{
	$email_hp .= '<a href="contact.php?id='. $entry["posters_id"];
	$email_hp .= '" rel="nofollow" title="';
	$email_hp .= str_replace("[name]", htmlspecialchars($entry['name']), outputLangDebugInAttributes($lang['email_to_user_linktitle'])) .'">';
	$email_hp .= '<img src="img/email.png" alt="'. outputLangDebugInAttributes($lang['email_alt']) .'" width="13" height="10"></a>';
	}
if ($entry["place"] != "")
	{
	$place .= htmlspecialchars($entry['place']) .', ';
	}
# generate HTML source code of authors name
$name = outputAuthorsName($entry['name'], $mark, $entry['user_id']);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $entry['user_id'] > 0)
	{
	$linktitle = str_replace("[name]", htmlspecialchars($entry['name']), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
	$uname .= '<a class="userlink" href="user.php?id='. $entry["user_id"] .'"';
	$uname .= ' rel="nofollow" title="'. $linktitle .'">'. $name .'</a>';
	}
else
	{
	$uname = $name;
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix'].'user_type'] == "admin"
	or $_SESSION[$settings['session_prefix'].'user_type'] == "mod"))
	{
	$entryIP = '<span class="postinginfo">'. $entry['ip_address'] .'</span>';
	}

if ($entry["edited_diff"] > 0
	&& $entry["edited_diff"] > $entry["time"]
	&& $settings['show_if_edited'] == 1)
	{
	$editstring = str_replace("[name]", htmlspecialchars($entry["edited_by"]), $editstring);
	$editstring = str_replace("[time]", strftime($lang['time_format'],$entry["e_time"]), $editstring);
	$entryedit .= '<span class="postinginfo">'. $editstring .'</span>';
	}

if ($view == 'forum')
	{
	$authorstring = str_replace("[name]", $uname, $authorstring);
	$authorstring = str_replace("[email_hp]", $email_hp, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[time]", $entry["posting_time"], $authorstring);
	$entryID = !empty($entryID) ? ' - '. $entryID : '';
	$entryedit = (!empty($entryedit)) ? '<br>'. $entryedit : '';
	$r .= $authorstring .'&nbsp;'. $entryIP.$answer.$entryID.$entryedit;
	}
else if ($view == 'board'
	or $view == 'mix')
	{
	$place = (!empty($place)) ? '<br>'. $place : '';
	$entryedit = (!empty($entryedit)) ? '<br>'. $entryedit : '';
	if (!empty($entryIP)
		or !empty($entryID)
		or !empty($answer))
		{
		$separator = '<br><br>';
		if (!empty($entryIP))
			{
			$entryID = (!empty($entryID)) ? '<br><br>'.$entryID : '';
			$answer = (!empty($answer)) ? '<br>'.$answer : '';
			}
		else
			{
			if (!empty($answer))
				{
				$entryID = (!empty($entryID)) ? '<br>'.$entryID : '';
				}
			}
		}
	$r .= $uname .'<br>'."\n". $email_hp.$place ."\n<br>". $entry["posting_time"];
	$r .= $entryedit.$separator.$entryIP.$answer.$entryID ."\n";
	}
else
	{
	if (!empty($entryID))
		{
		$entryID = ' - '. $entryID;
		}
#	$r .= $name.$entryID;
	$r .= $name;
	}

return $r;
} # End: outputAuthorInfo



/**
 * generates the name part of the authors information
 *
 * @param string $username
 * @param array $mark
 * @param integer $user_id
 * @return string $output
 */
function outputAuthorsName($username, $mark, $user_id = 0) {
global $settings, $lang;

$r = '';
$name = '<span class="{class}"{title}>{username}</span>';
$regimg = '';


if (is_array($mark)
	and $mark['admin'] === 1)
	{
	$class = 'admin-highlight';
	$title = ' title="'. outputLangDebugInAttributes($lang['ud_admin']) .'"';
	}
else if (is_array($mark)
	and $mark['mod'] === 1)
	{
	$class = 'mod-highlight';
	$title = ' title="'. outputLangDebugInAttributes($lang['ud_mod']) .'"';
	}
else if (is_array($mark)
	and $mark['user'] === 1)
	{
	$class = 'user-highlight';
	$title = ' title="'. outputLangDebugInAttributes($lang['ud_user']) .'"';
	}
else
	{
	$class = 'username';
	$title = '';
	}
$name = str_replace("{class}", $class, $name);
$name = str_replace("{title}", $title, $name);
$name = str_replace("{username}", htmlspecialchars($username), $name);

# generate image for registered users
if ($settings['show_registered'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $user_id > 0)
	{
	$regimg .= '<img src="img/registered.png" alt="(R)" width="10" height="10" title="'. outputLangDebugInAttributes($lang['registered_user_title']) .'">';
	}

$r .= $name.$regimg;

return $r;
} # End: outputAuthorsName



/**
 * generates the menu for editing a posting
 *
 * @param array $thread
 * @param string $view
 * @param string $first
 * @return string
 */
function outputPostingEditMenu($thread, $view, $first = '') {
global $settings, $lang;

$r  = '';
$period = false;

if ($settings['user_edit'] == 1
	and $settings['edit_period'] > 0)
	{
	$editPeriodEnd = $thread['time'] + ($settings['edit_period'] * 60);
	$period = ($editPeriodEnd > time()) ? true : false;
	}
else if ($settings['user_edit'] == 1
	and $settings['edit_period'] == 0)
	{
	$period = true;
	}
	
$subscriptPresent = processSearchThreadSubscriptions($thread['tid'], $_SESSION[$settings['session_prefix'].'user_id']);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	or (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
	or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
	{
	$r .= '<ul class="menu">'."\n";
	# edit a posting
	if (($settings['user_edit'] == 1
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		and $period === true))
		or (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?action=edit&amp;id='. $thread["id"] .'&amp;back='. $thread["tid"];
		$r .= '" title="'. outputLangDebugInAttributes($lang['edit_linktitle']) .'" class="buttonize">';
		$r .= '<span class="fa fa-edit icon-pencil-square-o"></span>&nbsp;'. $lang['edit_linkname'] .'</a></li>'."\n";
		}
	# delete a posting
	if (($settings['user_delete'] == 1
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		and $period === true))
		or (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?action=delete&amp;id='. $thread["id"] .'&amp;back='. $thread["tid"];
		$r .= '" title="'. outputLangDebugInAttributes($lang['delete_linktitle']) .'" class="buttonize">';
		$r .= '<span class="fa fa-trash-o icon-trash-o"></span>&nbsp;'. $lang['delete_linkname'] .'</a></li>'."\n";
		}
	# subscribe a thread
	if ((!empty($first)
		and $first === 'opener')
		and isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		if (is_array($subscriptPresent))
			{
			$subAction = 'false';
			$subClass = 'unsubscribe-posting';
			$subTitle = $lang['unsubscribe_linktitle'];
			$subName = $lang['unsubscribe_linkname'];
			$subIcon = 'fa-envelope-o';
			}
		else
			{
			$subAction = 'true';
			$subClass = 'subscribe-posting';
			$subTitle = $lang['subscribe_linktitle'];
			$subName = $lang['subscribe_linkname'];
			$subIcon = 'fa-envelope';
			}
		$r .= '<li><a href="posting.php?subscribe='. $subAction;
		$r .= '&amp;id='. $thread["id"] .'&amp;back='. $thread["tid"];
		$r .= '" class="buttonize" title="';
		$r .= outputLangDebugInAttributes($subTitle) .'">';
		$r .= '<span class="fa '. $subIcon .'"></span>&nbsp;'. $subName .'</a></li>'."\n";
		}
	# lock a thread
	if ((!empty($first) and $first==='opener')
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?lock=true&amp;id='. intval($thread["id"]) .'" title="';
		$r .= ($thread['locked'] == 0) ? outputLangDebugInAttributes($lang['lock_linktitle']) : outputLangDebugInAttributes($lang['unlock_linktitle']);
		$r .= '" class="buttonize">';
		$r .= ($thread['locked'] == 0) ? '<span class="fa fa-lock icon-lock2"></span>&nbsp;'. $lang['lock_linkname'] : '<span class="fa fa-unlock-alt icon-unlock-alt"></span>&nbsp;'. $lang['unlock_linkname'];
		$r .= '</a></li>'."\n";
		}
	# pin a thread
	if ((!empty($first) and $first === 'opener')
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$fixClass = ($thread['fixed'] == 1) ? 'unfix' : 'fix';
		$r .= '<li><a href="posting.php?fix=true&amp;id='. intval($thread["id"]) .'" title="';
		$r .= ($thread['fixed'] == 0) ? outputLangDebugInAttributes($lang['fix_thread_linktitle']) : outputLangDebugInAttributes($lang['unfix_thread_linktitle']);
		$r .= '" class="buttonize">';
		$r .= ($thread['fixed'] == 0) ? '<span class="fa fa-thumb-tack icon-thumb-tack"></span>&nbsp;'. $lang['fix_thread_link'] : '<span class="fa fa-thumb-tack fa-inverse icon-thumb-tack"></span>&nbsp;'. $lang['unfix_thread_link'];
		$r .= '</a></li>'."\n";
		}
	# move a posting
	if (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
		{
		$r .= '<li><span class="inactive-function buttonize">';
		$r .= $lang['move_posting_link'];
		$r .= '</span></li>'."\n";
#		$r .= '<li><a href="posting.php?pin=true&amp;id='. intval($thread["id"]);
#		$r .= '" class="lock-posting" title="';
#		$r .= ($thread['locked'] == 0) ? outputLangDebugInAttributes($lang['lock_linktitle']) : outputLangDebugInAttributes($lang['unlock_linktitle']);
#		$r .= '">';
#		$r .= ($thread['locked'] == 0) ? $lang['lock_linkname'] : $lang['unlock_linkname'];
#		$r .= '</a></li>'."\n";
		}
	$r .= "</ul>\n";
	}

return $r;
} # End: outputPostingEditMenu



/**
 * generates output of thread tree
 *
 * @param array $t threads
 * @param array $childs list of ids of all entries of the thread(s)
 * @param integer $c id of current entry
 * @param string $v information about the current view
 * @param bool $o true for opening posting, false for replies
 * @param integer $d optional depth for HTML source code (cosmetic)
 * @return string $r
 */
function outputThreadTree($t, $childs, $c, $v, $o, $d) {
# return an empty string if $thread is 0
if (count($t) == 0) return '';
# otherwise ...
global $settings, $page, $order, $category, $descasc, $last_visit, $category_accession, $categories, $mark, $connid, $lang;

$r = '';

$z = 1;
# highlighting of admins, mods and users:
if ($v == 'forum'
	and $t[$c]['posters_id'] > 0
	and ($settings['admin_mod_highlight'] == 1
	or $settings['user_highlight'] == 1))
	{
	$mark = outputStatusMark($mark, $t[$c]['user_type'], $connid);
	}
else
	{
	$mark['admin'] = 0;
	$mark['mod'] = 0;
	$mark['user'] = 0;
	}

if ($page != 0
	and $category != 0
	and $order != 'time')
	{
	$urlParam = '&amp;page='. $page .'&amp;category='. intval($category) .'&amp;order='. $order;
	}

if ($v != 'mix'
	&&$t[$c]['pid'] == 0
	&& $category == 0
	&& isset($categories[$t[$c]['category']])
	&& $categories[$t[$c]['category']] != '')
	{
	# Is it a admin/mods-only category?
	if (isset($category_accession[$t[$c]['category']])
		&& $category_accession[$t[$c]['category']] == 2)
		{
		$titleAdd = ' '. outputLangDebugInAttributes($lang['admin_mod_category']);
		$catClassName = 'category-adminmod';
		}
	# Is it a registered users (including admins/mods) category?
	else if (isset($category_accession[$t[$c]['category']])
		&& $category_accession[$t[$c]["category"]] == 1)
		{
		$titleAdd = " ". outputLangDebugInAttributes($lang['registered_users_category']);
		$catClassName = 'category-regusers';
		}
	else
		{
		$titleAdd = '';
		$catClassName = 'category';
		}
	$catLink  = '&nbsp;<a title="'. str_replace('[category]', $categories[$t[$c]['category']], outputLangDebugInAttributes($lang['choose_category_linktitle'])). $titleAdd;
	$catLink .= '" href="'.$v.'.php?category='. intval($t[$c]['category']) .'"><span class="';
	$catLink .= $catClassName .'">('. $categories[$t[$c]['category']] .')</span></a>';
	}
else
	{
	$catLink  = '';
	}

if ($t[$c]["pid"]==0
	and (isset($t[$c]["fixed"])
	and $t[$c]["fixed"] == 1))
	{
	$fixed = ' <img src="img/fixed.png" width="9" height="9" title="'. outputLangDebugInAttributes($lang['fixed']) .'" alt="*">';
	}
else
	{
	$fixed  = '';
	}

if ($t[$c]["pid"] == 0
	&& $settings['all_views_direct'] == 1)
	{
	$otherViews  = '&nbsp;';
	if ($settings['board_view'] == 1)
		{
		$otherViews .= '<a href="board_entry.php?id='. $t[$c]['tid'];
		$otherViews .= ($category > 0) ? '&amp;category='. intval($category) : '';
		$otherViews .= '&amp;view=board"><img src="img/board_d.png" alt="[Board]" title="';
		$otherViews .= outputLangDebugInAttributes($lang['open_in_board_linktitle']) .'" width="12" height="9"></a>';
		}
	if ($settings['mix_view'] == 1
		and $v != 'mix')
		{
		$otherViews .= '<a href="mix_entry.php?id='. $t[$c]['tid'];
		$otherViews .= ($category > 0) ? '&amp;category='. intval($category) : '';
		$otherViews .= '&amp;view=mix"><img src="img/mix_d.png" alt="[Mix]" title="';
		$otherViews .= outputLangDebugInAttributes($lang['open_in_mix_linktitle']) .'" width="12" height="9"></a>';
		}
	if ($settings['thread_view'] == 1
		and $v != 'forum')
		{
		$otherViews .= '<a href="forum_entry.php?id='. $t[$c]['tid'];
		$otherViews .= ($category > 0) ? '&amp;category='. intval($category) : '';
		$otherViews .= '&amp;view=forum"><img src="img/thread_d.png" alt="[Forum]" title="';
		$otherViews .= outputLangDebugInAttributes($lang['open_in_thread_linktitle']) .'" width="12" height="9"></a>';
		}
	}
else
	{
	$otherViews = '';
	}

if ($t[$c]["pid"]==0
	&& isset($_SESSION[$settings['session_prefix'].'user_type'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
	{
	$otherViews .= '<a href="admin.php?mark='. $t[$c]["tid"] .'&amp;refer=';
	$otherViews .= basename($_SERVER["SCRIPT_NAME"]) .'&amp;page='. $page .'&amp;order='. $order;
	$otherViews .= ($category > 0) ? '&amp;category='. intval($category) : '';
	$otherViews .= '"><img src="';
	if ($t[$c]['marked']==1)
		{
		$otherViews .= 'img/marked.png" alt="[x]" title="'. outputLangDebugInAttributes($lang['demark_linktitle']) .'"';
		}
	else
		{
		$otherViews .= 'img/mark.png" alt="[-]" title="'. outputLangDebugInAttributes($lang['mark_linktitle']) .'"';
		}
	$otherViews .= ' width="9" height="9"></a>';
	}

# view
if ($v == 'mix')
	{
	$title = ' title="'.$t[$c]['name'].", ".$t[$c]['Uhrzeit'].' (#'.$t[$c]['id'].')"';
	$append = '';
	$postClass = ($o === true) ? 'thread' : 'reply';
	}
else
	{
	# $v (view) is thread or single thread (forum_entry.php)
	$title = '';
	$name = outputAuthorsName($t[$c]["name"], $mark, $t[$c]['user_id']);
	if (isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $t[$c]["user_id"] > 0
		&& $settings['show_registered'] == 1)
		{
		$sult = str_replace("[name]", htmlspecialchars($t[$c]["name"]), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
		$thread_info_a = str_replace("[name]", '<a href="user.php?id='. $t[$c]["user_id"] .'" title="'. $sult .'">'. $name .'</a>', $lang['thread_info']);
		}
	else $thread_info_a = str_replace("[name]", $name, $lang['thread_info']);
	$append = str_replace("[time]", $t[$c]["Uhrzeit"], $thread_info_a);
	if ((($t[$c]['pid'] == 0)
		&& isset($_SESSION[$settings['session_prefix'].'newtime'])
		&& $_SESSION[$settings['session_prefix'].'newtime'] < $t[$c]['last_answer'])
		|| (($t[$c]['pid'] == 0)
		&& empty($_SESSION[$settings['session_prefix'].'newtime'])
		&& $t[$c]['last_answer'] > $last_visit))
		{
		$postClass = 'threadnew';
		}
	else if ($t[$c]['pid'] == 0)
		{
		$postClass = 'thread';
		}
	else if ((($t[$c]['pid'] != 0)
		&& isset($_SESSION[$settings['session_prefix'].'newtime'])
		&& $_SESSION[$settings['session_prefix'].'newtime'] < $t[$c]['time'])
		|| (($t[$c]['pid'] != 0)
		&& empty($_SESSION[$settings['session_prefix'].'newtime'])
		&& $t[$c]['time'] > $last_visit))
		{
		$postClass = 'replynew';
		}
	else
		{
		$postClass = 'reply';
		}
	}
$r .= str_repeat(" ", $d) .'<li>';
if (isset($_GET['id'])
	and $c == intval($_GET['id'])
	&& $t[$c]["pid"] == 0)
	{
	$r .= '<span class="actthread">'. htmlspecialchars($t[$c]["subject"]) .'</span> '. $append;
	}
else if (isset($_GET['id'])
	and $c == intval($_GET['id'])
	&& $t[$c]["pid"] != 0)
	{
	$r .= '<span class="actreply">'. htmlspecialchars($t[$c]["subject"]) .'</span> '. $append;
	}
else
	{
	$r .= '<a class="'. $postClass .'" href="'. $v .'_entry.php?id='. $t[$c]['id'];
	$r .= ($v == 'mix') ? '#'. $t[$c]['id'] : '';
	$r .= '"'. outputLangDebugInAttributes($title) .'>'. htmlspecialchars($t[$c]['subject']) .'</a> '. $append;
	}
$r .= $catLink.$fixed.$otherViews;

# Eintrag hat Kindelement
if (isset($childs[$c]))
	{
	$dn = $d+1;
	$r .= "\n". str_repeat(" ", $dn) .'<ul class="reply">'."\n";
	foreach ($childs[$c] as $kind)
		{
		$r .= outputThreadTree($t, $childs, $kind, $v, false, $dn+1);
		}
	$r .= str_repeat(" ", $dn) .'</ul>'."\n". str_repeat(" ", $d);
	}

$r .= '</li>'."\n";
return $r;
} # End: outputThreadTree



/**
 * generates tree of threads
 *
 * @param array $t one or all threads
 * @param array $c list of childs
 * @param string $v information about the current view
 * @param integer $d optional depth for HTML source code (cosmetic)
 * @return string
 */
function outputThreads($t, $c, $v = 'thread', $d = 0) {

$r  = "";

if (is_array($c)) {
	foreach ($c[0] as $cid) {
		$dn = $d+1;
		$r .= ($v == 'mix') ? "\n" : "";
		$r .= str_repeat(" ", $d) .'<ul class="thread">'."\n";
		$r .= outputThreadTree($t, $c, $cid, $v, true, $dn);
		$r .= str_repeat(" ", $d) .'</ul>'."\n";
		}
	}

return $r;
} # End: outputThreads



/**
 * generates output for language file debug mode
 *
 * @param array $lang
 * @param string $file
 * @return array $lang
 */
function outputLangDebugOrNot($lang, $file) {
$str = array();
$debug = (!empty($_SESSION['debug']) and $_SESSION['debug'] == 'lang') ? 1 : 0;

foreach ($lang as $key => $val) {
	if (is_string($val)) {
		$hasDebug = strpos($val, '<span title="key: ');
		if ($hasDebug !== false) {
			$str[$key]  = $val;
			}
		else {
			$str[$key]  = $debug == 1 ? '<span title="key: ['. htmlspecialchars($key) .'], file: '. htmlspecialchars($file) .'">' : '';
			$str[$key] .= strval($val);
			$str[$key] .= $debug == 1 ? '</span>' : '';
			}
		}
	else {
		$str[$key] = $val;
		}
	}
return $str;
}



/**
 * reorders the debug output for strings in case of use in HTML-attributes
 *
 * @param string $string
 * @return string $string
 */
function outputLangDebugInAttributes($string) {

$debug = (!empty($_SESSION['debug']) and $_SESSION['debug'] == 'lang') ? 1 : 0;

if ($debug == 1)
	{
	$substring = strstr($string, '"');
	$substring = substr($substring, 1);
	$pos1 = strpos($substring, '"');
	$substring = substr($substring, 0, $pos1);
	$string = strip_tags($string);
	$string = $string.", ".$substring;
	}

return $string;
}



/**
 * temporary function to return a HTML string for a navigation image
 *
 * @param string
 * @return string
 */
function outputImageDescAsc($curr) {
$r = !empty($curr) ? '&nbsp;<img src="img/'. $curr .'.png" alt="['. $curr .']" width="5" height="9" border="0">' : '';
return $r;
} # End outputImageDescAsc



/**
 * Strinps all control characters from output in case of XML output
 *
 * @param string $string
 * @return string $string
 */
function outputXMLclearedString($string) {
$illegalChars = array(array(), array());

$illegalChars["char"][0] = chr(0);
$illegalChars["repl"][0] = "";
$illegalChars["char"][1] = chr(1);
$illegalChars["repl"][1] = "";
$illegalChars["char"][2] = chr(2);
$illegalChars["repl"][2] = "";
$illegalChars["char"][3] = chr(3);
$illegalChars["repl"][3] = "";
$illegalChars["char"][4] = chr(4);
$illegalChars["repl"][4] = "";
$illegalChars["char"][5] = chr(5);
$illegalChars["repl"][5] = "";
$illegalChars["char"][6] = chr(6);
$illegalChars["repl"][6] = "";
$illegalChars["char"][7] = chr(7);
$illegalChars["repl"][7] = "";
$illegalChars["char"][8] = chr(8);
$illegalChars["repl"][8] = "";
$illegalChars["char"][9] = chr(9);
$illegalChars["repl"][9] = " ";
$illegalChars["char"][10] = chr(10);
$illegalChars["repl"][10] = chr(10);
$illegalChars["char"][11] = chr(11);
$illegalChars["repl"][11] = "";
$illegalChars["char"][12] = chr(12);
$illegalChars["repl"][12] = "";
$illegalChars["char"][13] = chr(13);
$illegalChars["repl"][13] = chr(13);
$illegalChars["char"][14] = chr(14);
$illegalChars["repl"][14] = "";
$illegalChars["char"][15] = chr(15);
$illegalChars["repl"][15] = "";
$illegalChars["char"][16] = chr(16);
$illegalChars["repl"][16] = "";
$illegalChars["char"][17] = chr(17);
$illegalChars["repl"][17] = "";
$illegalChars["char"][18] = chr(18);
$illegalChars["repl"][18] = "";
$illegalChars["char"][19] = chr(19);
$illegalChars["repl"][19] = "";
$illegalChars["char"][20] = chr(20);
$illegalChars["repl"][20] = "";
$illegalChars["char"][21] = chr(21);
$illegalChars["repl"][21] = "";
$illegalChars["char"][22] = chr(22);
$illegalChars["repl"][22] = "";
$illegalChars["char"][23] = chr(23);
$illegalChars["repl"][23] = "";
$illegalChars["char"][24] = chr(24);
$illegalChars["repl"][24] = "";
$illegalChars["char"][25] = chr(25);
$illegalChars["repl"][25] = "";
$illegalChars["char"][26] = chr(26);
$illegalChars["repl"][26] = "";
$illegalChars["char"][27] = chr(27);
$illegalChars["repl"][27] = "";
$illegalChars["char"][28] = chr(28);
$illegalChars["repl"][28] = "";
$illegalChars["char"][29] = chr(29);
$illegalChars["repl"][29] = "";
$illegalChars["char"][30] = chr(30);
$illegalChars["repl"][30] = "";
$illegalChars["char"][31] = chr(31);
$illegalChars["repl"][31] = "";

$string = str_replace($illegalChars["char"], $illegalChars["repl"], $string);

return $string;
} # End: outputXMLclearedString



/**
 * Prepares the posting for output
 *
 * @param string $entry
 * @param string $type (optional)
 * @return string $entry
 */
function outputPreparePosting($entry, $type = 'posting') {
global $settings;

if ($settings['autolink'] == 1) $entry = make_link($entry);
if ($settings['bbcode'] == 1) $entry = bbcode($entry);
if ($settings['smilies'] == 1) $entry = smilies($entry);
if ($type == 'posting') $entry = zitat($entry);
if ($type == 'posting') $entry = codeblock($entry);

return $entry;
}

function outputUsersettingsMenu($id, $action = '') {
global $lang, $settings;
$r  = '';

$r .= '<ul class="menulist">';
$r .= (empty($action)) ? '<li><span>'. $lang['user_info'] .'</span></li>' : '<li><a href="user.php?id='. urlencode($id) .'">'. $lang['user_info'] .'</a></li>';
$r .= ($action == 'edit') ? '<li><span>'. $lang['edit_userdata_ln'] .'</span></li>' : '<li><a href="user.php?action=edit">'. $lang['edit_userdata_ln'] .'</a></li>';
if ($settings['user_control_refresh'] == 1
	or $settings['user_control_css'] == 1)
	{
	$r .= ($action == 'usersettings') ? '<li><span>'. $lang['edit_users_settings'] .'</span></li>' : '<li><a href="user.php?action=usersettings">'. $lang['edit_users_settings'] .'</a></li>';
	}
$r .= ($action == 'subscriptions') ? '<li><span>'. $lang['edit_subscription_ln'] .'</span></li>' : '<li><a href="user.php?action=subscriptions">'. $lang['edit_subscription_ln'] .'</a></li>';
$r .= ($action == 'pw') ? '<li><span>'. $lang['edit_pw_ln'] .'</span></li>' : '<li><a href="user.php?action=pw">'. $lang['edit_pw_ln'] .'</a></li>';
$r .= '</ul>';

return $r;
}


/**
 * generates output of the session and cookie values
 *
 * @return string
 */
function outputDebugSession() {
global $settings;
$r  = '';
if ((isset($_SESSION[$settings['session_prefix'].'user_type'])
	and $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
	and $_SESSION[$settings['session_prefix'].'debug'] === 'session')
	{
	$r .= '<h2>SESSION</h2>';
	$r .= '<pre>'. print_r($_SESSION, true) .'</pre>';
	$r .= '<h2>COOKIE</h2>';
	$r .= '<pre>'. print_r($_COOKIE, true) .'</pre>';
	$r .= '<h2>GET</h2>';
	$r .= '<pre>'. print_r($_GET, true) .'</pre>';
	$r .= '<h2>POST</h2>';
	$r .= '<pre>'. print_r($_POST, true) .'</pre>';
	}
return $r;
} # End: outputDebugSession



/**
 * generates a select form control
 *
 * @param array $res
 * @param string $fieldname
 * @param integer $selected (optional)
 * @param integer $size (optional)
 * @return string
 */
function outputMakeSelect($res, $fieldname, $selected = '', $size = 1) {
if (!is_array($res)) return false;
$size = (intval($size) == 0) ? 1 : $size;
$r  = '';
$r .= '<select size="'. intval($size) .'" id="'. htmlspecialchars($fieldname) .'" name="'. htmlspecialchars($fieldname) .'">'. "\n";
foreach ($res as $row) {
	$r .= '  <option value="'. htmlspecialchars($row['id']) .'"';
	$r .= (!empty($selected) and $selected == $row['id']) ? ' selected="selected"' : '';
	$r .= '>'. htmlspecialchars($row['name']) .'</option>'. "\n";
	}
$r .= ' </select>'. "\n";
return $r;
} # End: outputMakeSelect

?>
