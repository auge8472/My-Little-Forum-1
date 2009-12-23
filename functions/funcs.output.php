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
	$r .= '&nbsp;&nbsp;<form method="get" action="'.$_SERVER['SCRIPT_NAME'].'" title="'.$lang['choose_category_formtitle'].'" style="display: inline;">'."\n";
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
	$r .= '</select>'."\n".'<noscript> <input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></form>'."\n";
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

# whole string template
$authorstring = $lang['forum_author_marking'];
# editors template
$editstring = $lang['forum_edited_marking'];
# generate setting to show contact link if not present
$entry["hide_email"] = empty($entry["hide_email"]) ? 0 : $entry["hide_email"];
# generate string for posting ID
$entryID .= ($settings['show_posting_id'] == 1) ? '<span class="xsmall">Posting:&nbsp;#&nbsp;'.$entry['id'].'</span>' : '';
# generate string for name of the answered author
if (!empty($entry['answer']))
	{
	$answer .= '<br /><span class="xsmall">@&nbsp;'.htmlspecialchars($entry['answer']).'</span>';
	}
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
	$email_hp .= '" title="'.str_replace("[name]", htmlspecialchars($entry["name"]), $lang['email_to_user_linktitle']).'"><img src="img/email.gif" alt="'.$lang['email_alt'].'" width="13" height="10" /></a>';
	}
if ($entrydata["place"] != "")
	{
	$place_c .= htmlspecialchars($entrydata["place"]).", ";
	$place .= htmlspecialchars($entrydata["place"]);
	}
# generate HTML source code of authors name
$name = outputAuthorsName($entry["name"], $mark, $entry["user_id"]);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $entry['user_id'] > 0)
	{
	$linktitle = str_replace("[name]", htmlspecialchars($entry["name"]), $lang['show_userdata_linktitle']);
	$name .= '<a class="userlink" href="user.php?id='.$entry["user_id"].'" title="'.$linktitle.'">'.$name.'</a>';
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
	$entryedit .= '<br /><span class="xsmall">'.$editstring.'</span>';
	}

#$r .= "<pre>".print_r($mark,true)."</pre>";

if ($view=='forum')
	{
	$authorstring = str_replace("[name]", $name, $authorstring);
	$authorstring = str_replace("[email_hp]", $email_hp, $authorstring);
	$authorstring = str_replace("[place, ]", $place_c, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[time]", strftime($lang['time_format'],$entry["p_time"]), $authorstring);
	if (!empty($entryID))
		{
		$entryID = ' - '.$entryID;
		}
	$r .= '<p class="author">'.$authorstring.'&nbsp;'.$entryIP.$entryID.$entryedit.'</p>'."\n";
	}
else if ($view=='board' or $view=='mix')
	{
	if (!empty($place))
		{
		$place = '<br />'.$place;
		}
	if (!empty($entryID))
		{
		$entryID = '<br /><br />'.$entryID;
		}
	$r .= $name.'<br /><br />'.$email_hp.$place.'<br />'.strftime($lang['time_format'],$entry["p_time"]).$entryedit.'<br /><br />'.$entryIP.$entryID.$answer;
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
function outputAuthorsName($name, $mark, $user_id=0) {
global $setting, $lang;

$r = '';
$name = '';
$regimg = '';

if ($mark['admin']===true or $mark['mod']===true or $mark['user']===true)
	{
	$name .= '<span class="';
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
	$name .= '">';
	}
else
	{
	$name .= '<span class="username">';
	}
$name .= htmlspecialchars($entry["name"]).'</span>';

# generate image for registered users
if ($settings['show_registered'] ==1
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $user_id > 0)
	{
	$regimg .= '<img src="img/registered.gif" alt="(R)" width="10" height="10" title="'.$lang['registered_user_title'].'" />';
	}

$r = $name.$regimg;

return $r;
} # End: outputAuthorsName

?>