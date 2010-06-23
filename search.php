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

include("inc.php");

if (file_exists('install.php')
or file_exists('update.php')
or file_exists('update_content.php'))
	{
	header("location: ".$settings['forum_address']."service.php");
	die("<a href=\"service.php\">further...</a>");
	}

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;
if(count($_POST) > 0)
foreach($_POST as $key => $value)
$$key = $value;

// Seiten-Navigation für suche.php
function snav($page, $suchergebnisse, $count, $search, $ao, $category) {
global $lang;

$output = '';
if ($count > $suchergebnisse)
	{
	$new_index_before = $page - 1;
	$new_index_after = $page + 1;
	$site_count = ceil($count / $suchergebnisse);
	if ($new_index_before >= 0)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?search='.$search;
		$output .= '&amp;category='.$category.'&amp;ao='.$ao.'&amp;page='.$new_index_before;
		$output .= '" title="'.$lang['previous_page_linktitle'].'"><b>&laquo;</b></a>&nbsp;';
		}

	if ($page == 3)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?search='.$search;
		$output .= '&amp;category='.$category.'&amp;ao='.$ao.'&amp;page=0"><b>1</b></a>&nbsp;';
		}
	else if ($page > 3)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?search='.$search;
		$output .= '&amp;category='.$category.'&amp;ao='.$ao.'&amp;page=0">';
		$output .= '<b>1</b></a>&nbsp;<b>...</b>&nbsp;';
		}

	for ($i = 0; $i < $site_count; $i++)
		{
		$pagen_nr = $i;
		if ($page == $pagen_nr or $page == $pagen_nr-1 or $page == $pagen_nr+1 or $page == $pagen_nr-2 or $page == $pagen_nr+2)
			{
			if ($page != $pagen_nr)
				{
				$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?search='.$search;
				$output .= '&amp;category='.$category.'&amp;ao='.$ao.'&amp;page=';
				$output .= $pagen_nr.'"><b>'.($pagen_nr+1).'</b></a>&nbsp;';
				}
			else
				{
				$output .= '<span style="color: red; font-weight: bold;"><b>'.($pagen_nr+1).'</b></span>&nbsp;';
				}
			}
		}

	if ($new_index_after < $site_count)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?search='.$search;
		$output .= '&amp;category='.$category.'&amp;ao='.$ao.'&amp;page='.$new_index_after;
		$output .= '" title="'.$lang['next_page_linktitle'].'"><b>&raquo;</b></a>';
 		}
 	}
return $output;
} # End: snav



function pnav($page, $how_many_per_page, $count, $show_postings) {
global $lang;

$output = '';
if ($count > $how_many_per_page)
	{
	if (($page-1) >= 0)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?show_postings='.$show_postings;
		$output .= '&amp;page='.($page-1).'" title="'.$lang['previous_page_linktitle'];
		$output .= '"><b>&laquo;</b></a>&nbsp;';
		}
	$page_count = ceil($count/$how_many_per_page);

	if (($page+1) == 1)
		{
		$output .= '<span style="color: red; font-weight: bold;">1</span>&nbsp;';
		}
	else
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?show_postings=';
		$output .= $show_postings.'&amp;page=0"><b>1</b></a>&nbsp;';
		}

	for ($x=$page; $x<$page+4; $x++)
		{
		if ($x > 1 && $x <= $page_count)
			{
			if ($x == $page+1)
				{
				$output .= '<span style="color: red; font-weight: bold;">'.$x.'</span>&nbsp;';
				}
			else
				{
				$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?show_postings=';
				$output .= $show_postings.'&amp;page='.($x-1).'"><b>'.$x.'</b></a>&nbsp;';
				}
			}
		}
	if (($page+1) < $page_count)
		{
		$output .= '<a href="'.$_SERVER["SCRIPT_NAME"].'?show_postings=';
		$output .= $show_postings.'&amp;page='.($page+1).'" title="';
		$output .= $lang['next_page_linktitle'].'"><b>&raquo;</b></a>';
		}
	}
return $output;
} # End: pnav

if ($settings['access_for_users_only'] == 1 && !isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}

if (empty($page)) $page = 0;

if (empty($search)) $search = "";

if (empty($ao)) $ao = "and";
$ul = $page * $settings['search_results_per_page'];

unset($entrydata);

if (substr($search, 1, 1) == "\"") $ao="phrase";

$search = str_replace("\"", "", $search);
#$search = stripslashes($search);
$search = trim($search);
$search = mysql_real_escape_string($search);
$search_array = explode(" ", $search);
$search_anz = str_replace(" ", ", ", $search);

$search_category = (isset($category) and $category != 0) ? " AND category='".mysql_real_escape_string($category)."'" : "";

if ($ao == "or")
	{
	$search_string = "concat(subject, name, place, text, email, hp) LIKE '%".implode("%' OR concat(subject, name, place, text, email, hp) LIKE '%",$search_array)."%'".$search_category;
	}
else if ($ao == "phrase")
	{
	$search_string = "concat(subject, name, place, text, email, hp) LIKE '%".$search."%'".$search_category;
	}
else
	{
	$search_string = "concat(subject, name, place, text, email, hp) LIKE '%".implode("%' AND concat(subject, name, place, text, email, hp) LIKE '%",$search_array)."%'".$search_category;
	}

if (empty($search) && isset($show_postings)) $search_string = "user_id='".$show_postings."'";

$searchQuery = "SELECT
id,
pid,
tid,
UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
subject,
name,
category
FROM ".$db_settings['forum_table']."
WHERE ".$search_string;

$countQuery = "SELECT
COUNT(*)
FROM ".$db_settings['forum_table']."
WHERE ".$search_string;

if (is_array($categories))
	{
	$searchQuery .= "
	AND category IN (".$category_ids_query.")";
	$countQuery .= "
	AND category IN (".$category_ids_query.")";
	}
$searchQuery .= "
ORDER BY tid DESC,
time ASC
LIMIT ".$ul.", ".$settings['search_results_per_page'];

$result = mysql_query($searchQuery, $connid);
if(!$result) die($lang['db_error']);

$count_result = mysql_query($countQuery, $connid);
list($count) = mysql_fetch_row($count_result);


// HTML:
$wo = $lang['search_title'];
$subnav_1 = "";
if (isset($search) && empty($show_postings))
	{
	if ($search != "" && $ao=="phrase")
		{
		$subnav_1 .= $lang['phrase']." <b>".htmlspecialchars($search)."</b>";
		}
	else if ($search != "" && count($search_array) == 1)
		{
		$subnav_1 .= $lang['search_term']." <b>".htmlspecialchars($search_anz)."</b>";
		}
	else if ($search != "" && count($search_array) > 1)
		{
		$subnav_1 .= $lang['search_term']." <b>".htmlspecialchars($search_anz)."</b>";
		}
	else
		{
		$subnav_1 .= "&nbsp;";
		$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['search_title'].'</b>';
		}

	if ($count > 0 && $search != "")
		{
		$subnav_1 .= " - ".$lang['search_result']." ";
		}
	if ($count > 0 && $search != "" && $count > $settings['search_results_per_page'])
		{
		$lang['search_result_range'] = str_replace("[from]", ($page*$settings['search_results_per_page'])+1, $lang['search_result_range']);
		$lang['search_result_range'] = str_replace("[to]", ((1+$page)*$settings['search_results_per_page']), $lang['search_result_range']);
		$lang['search_result_range'] = str_replace("[total]", $count, $lang['search_result_range']);
		$subnav_1 .= $lang['search_result_range'];
		}
	else if ($count > 0 && $search != "" && $count <= $settings['search_results_per_page'])
		{
		$subnav_1 .=  $count;
		}
	else
		{
		$subnav_1 .= "&nbsp;";
		#$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['search_title'].'</b>';
		}
	}
else if (isset($show_postings) && empty($search))
	{
	$user_name_result = mysql_query("SELECT user_name FROM ".$db_settings['userdata_table']." WHERE user_id = '".$show_postings."' LIMIT 1", $connid);
	if (!$user_name_result) die($lang['db_error']);
	$field = mysql_fetch_assoc($user_name_result);
	mysql_free_result($user_name_result);
	$lang['show_userdata_linktitle'] = str_replace("[name]", htmlspecialchars($field["user_name"]), $lang['show_userdata_linktitle']);
	$lang['postings_by_user'] = str_replace('[name]', '<a href="user.php?id='.$show_postings.'" title="'.$lang['show_userdata_linktitle'].'">'.htmlspecialchars($field["user_name"]).'</a>', $lang['postings_by_user']);
	$subnav_1 .= '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'.$lang['postings_by_user'].'</b>';
	}

if (isset($search) && $search != "")
	{
	$subnav_2 = snav($page, $settings['search_results_per_page'], $count, $search, $ao, $category);
	}
else if (isset($show_postings) && $show_postings !="")
	{
	$subnav_2 = pnav($page, $settings['search_results_per_page'], $count, $show_postings);
	}
parse_template();
echo $header;

if (isset($search))
	{
	$search_match = htmlspecialchars($search);
	}
else
	{
	$search_match = "";
	}

if (isset($search) && empty($show_postings))
	{
	echo '<form action="search.php" method="get" title="';
	echo $lang['search_formtitle'].'"><div class="search">'."\n";
	echo '<input type="text" name="search" value="'.htmlspecialchars($search_match).'" size="30" />'."\n";
	if ($categories!=false)
		{
		echo '<select size="1" name="category">'."\n";
  		echo '<option value="0"';
		echo (isset($category) && $category==0) ? ' selected="selected"' : '';
		echo '>'.$lang['show_all_categories'].'</option>'."\n";
		while (list($key, $val) = each($categories))
			{
			if ($key!=0)
				{
				echo '<option value="'.$key.'"';
				echo ($key==$category) ? ' selected="selected"' : '';
				echo '>'.$val.'</option>'."\n";
				}
			}
		echo '</select>'."\n";
		}
	echo '<input type="submit" name="" value="'.$lang['search_submit'].'" /><br />'."\n";
	echo '<input type="radio" name="ao" value="and"';
	echo ($ao == "and") ? ' checked="checked"' : '';
	echo ' />'.$lang['search_and'].'&nbsp;<input type="radio" class="search-radio"';
	echo ' name="ao" value="or"';
	echo ($ao == "or") ? ' checked="checked"' : '';
	echo ' />'.$lang['search_or'].'&nbsp;<input type="radio" class="search-radio"';
	echo ' name="ao" value="phrase"';
	echo ($ao == "phrase") ? ' checked="checked"' : '';
	echo ' />'.$lang['search_phrase'].'</div></form>'."\n";
	}
/*
if (!empty($result))
	{
	while ($res = mysql_fetch_assoc($result))
		{
		echo "<pre>".print_r($res,true)."</pre>\n";
		}
	}
*/
if ($count == 0 && $search != "")
	{
	echo '<p class="caution">';
	if (count($search_array) > 1 && $ao == "and")
		{
		echo $lang['no_match_and'];
		}
	else if (count($search_array) > 1 && $ao == "or")
		{
		echo $lang['no_match_or'];
		}
	else if (count($search_array) > 1 && $ao == "phrase")
		{
		echo $lang['no_match_phrase'];
		}
	else
		{
		echo $lang['search_no_match'];
		}
	echo '</p>'."\n";
	}

if (isset($search) && $search != "" || isset($show_postings) && $show_postings !="")
	{
	echo "<ul id=\"searchresults\">\n";
	$i=0;
	while ($entrydata = mysql_fetch_assoc($result))
		{
		$search_author_info_x = str_replace("[name]", htmlspecialchars($entrydata["name"]), $lang['search_author_info']);
		$search_author_info_x = str_replace("[time]", strftime($lang['time_format'],$entrydata["Uhrzeit"]), $search_author_info_x);
		echo '<li><a class="';
		echo ($entrydata['pid'] == 0) ? 'thread' : 'reply-search';
		echo '" href="';
		if (isset($_SESSION[$settings['session_prefix'].'user_view'])
			&& $_SESSION[$settings['session_prefix'].'user_view']=='board')
			{
			echo 'board_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else if (isset($_SESSION[$settings['session_prefix'].'user_view'])
			&& $_SESSION[$settings['session_prefix'].'user_view']=='thread')
			{
			echo 'forum_entry.php?id='.$entrydata['id'];
			}
		else if (isset($_SESSION[$settings['session_prefix'].'user_view'])
			&& $_SESSION[$settings['session_prefix'].'user_view']=='mix')
			{
			echo 'mix_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else if (isset($_COOKIE['user_view'])
			&& $_COOKIE['user_view']=='board')
			{
			echo 'board_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else if (isset($_COOKIE['user_view']) && $_COOKIE['user_view']=='thread')
			{
			echo 'forum_entry.php?id='.$entrydata['id'];
			}
		else if (isset($_COOKIE['user_view']) && $_COOKIE['user_view']=='mix')
			{
			echo 'mix_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else if (isset($standard) && $standard=='board')
			{
			echo 'board_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else if (isset($standard) && $standard=='mix')
			{
			echo 'mix_entry.php?id='.$entrydata['tid'].'#p'.$entrydata['id'];
			}
		else
			{
			echo 'forum_entry.php?id='.$entrydata['id'];
			}
		echo '">'.htmlspecialchars($entrydata['subject']).'</a> ';
		echo $search_author_info_x;
		if (isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='')
			{
			echo ' <span class="category">('.$categories[$entrydata["category"]].')</span>';
			}
		echo '</li>'."\n";
		}
	echo "</ul>\n";
	}
echo $footer;
?>
