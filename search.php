<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004-2008 Alex                                                #
# http://www.mylittlehomepage.net/                                            #
# Copyright (C) 2009-2019 H. August                                           #
# https://www.projekt-mlf.de/                                                 #
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

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;
if(count($_POST) > 0)
foreach($_POST as $key => $value)
$$key = $value;

 function snav($page, $suchergebnisse, $count, $search, $ao, $category)  // Seiten-Navigation für suche.php
  {
   global $lang;
   $output = "&nbsp;";
   if ($count > $suchergebnisse) {
   $output .= "&nbsp;&nbsp;";
   $new_index_before = $page - 1;
   $new_index_after = $page + 1;
   $site_count = ceil($count / $suchergebnisse);
   if ($new_index_before >= 0) $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?search='. urlencode($search) .'&amp;category='. intval($category) .'&amp;ao='. urlencode($ao) .'&amp;page='. intval($new_index_before) .'"><b>&laquo;</b></a>&nbsp;';
   if ($page == 3) { $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?search='. urlencode($search) .'&amp;category='. intval($category) .'&amp;ao='. urlencode($ao) .'&amp;page=0"><b>1</b></a>&nbsp;'; }
   elseif ($page > 3) { $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?search='. urlencode($search) .'&amp;category='. intval($category) .'&amp;ao='. urlencode($ao) .'&amp;page=0"><b>1</b></a>&nbsp;<b>...</b>&nbsp;'; }
   for ($i = 0; $i < $site_count; $i++) {
   $pagen_nr = $i;
   if ($page == $pagen_nr or $page == $pagen_nr-1 or $page == $pagen_nr+1 or $page == $pagen_nr-2 or $page == $pagen_nr+2) {
   if ($page != $pagen_nr) {
   $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?search='. urlencode($search) .'&amp;category='. intval($category) .'&amp;ao='. urlencode($ao) .'&amp;page='. intval($pagen_nr) .'"><b>'. ($pagen_nr+1) .'</b></a>&nbsp;'; }
   else {
   $output .= '<span style="color: red; font-weight: bold;"><b>'. ($pagen_nr+1) .'</b></span>&nbsp;';
   } } }
   if ($new_index_after < $site_count) $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?search='. urlencode($search) .'&amp;category='. intval($category) .'&amp;ao='. urlencode($ao) .'&amp;page='. intval($new_index_after) .'"><b>&raquo;</b></a>';
   }
  return $output;
  }

 function pnav($page, $how_many_per_page, $count, $show_postings)
  {
   global $lang;
   $output = "&nbsp;";
   if ($count > $how_many_per_page)
    {
     if (($page-1) >= 0) $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?show_postings='. urlencode($show_postings) .'&amp;page='. intval($page-1) .'" title="'. htmlsc($lang['previous_page_linktitle']) .'"><b>&laquo;</b></a>&nbsp;';
     $page_count = ceil($count/$how_many_per_page);
     if (($page+1) == 1)
      {
       $output .= '<span style="color: red; font-weight: bold;">1</span>&nbsp;';
      }
     else
      {
       $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?show_postings='. urlencode($show_postings) .'&amp;page=0"><b>1</b></a>&nbsp;';
      }

     for($x=$page; $x<$page+4; $x++)
      {
       if ($x > 1 && $x <= $page_count)
        {
         if ($x == $page+1)
          {
           $output .= '<span style="color: red; font-weight: bold;">'. intval($x) .'</span>&nbsp;';
          }
         else
          {
           $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?show_postings='. urlencode($show_postings) .'&amp;page='. intval($x-1) .'"><b>'. intval($x) .'</b></a>&nbsp;';
          }
        }
      }
     if (($page+1) < $page_count) $output .= '<a href="'. basename($_SERVER["SCRIPT_NAME"]) .'?show_postings='. urlencode($show_postings) .'&amp;page='. intval($page+1) .'" title="'. htmlsc($lang['next_page_linktitle']) .'"><b>&raquo;</b></a>';
    }
   return $output;
  }

 if ($settings['access_for_users_only'] == 1 && !isset($_SESSION[$settings['session_prefix'].'user_id']))
  {
   header("location: login.php?msg=noaccess");
   die('<a href="login.php?msg=noaccess">further...</a>');
  }

 if (empty($page)) $page = 0;
 //if (empty($da)) $order="time";
 if (empty($search)) $search = "";
 $category = mysqli_real_escape_string($connid, $category);
 if (empty($ao)) $ao = "and";
 $ul = $page * $settings['search_results_per_page'];

 unset($entrydata);

 if (mb_substr($search, 1, 1) == "\"") $ao="phrase";
 $search = str_replace("\"", "", $search);
 $search = trim($search);
 $search = mysqli_real_escape_string($connid, $search);
 $search_array = explode(" ", $search);
 $search_anz = str_replace(" ", ", ", $search);

 if (isset($category) && $category != 0) $search_category = " AND category='".$category."'"; else $search_category = "";

 if ($ao == "or")
  {
   $search_string = "concat(subject, name, place, text, email, hp) LIKE '%".implode("%' OR concat(subject, name, place, text, email, hp) LIKE '%",$search_array)."%'".$search_category;
  }
 elseif ($ao == "phrase")
  {
   $search_string = "concat(subject, name, place, text, email, hp) LIKE '%".$search."%'".$search_category;
  }
 else
  {
   $search_string = "concat(subject, name, place, text, email, hp) LIKE '%".implode("%' AND concat(subject, name, place, text, email, hp) LIKE '%",$search_array)."%'".$search_category;
  }

 if (empty($search) && isset($show_postings)) $search_string = "user_id='$show_postings'";

 if (is_array($categories))
  {
   $result = mysqli_query($connid, "SELECT id, pid, tid, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit, subject, name, email, hp, place, text, category FROM ". $db_settings['forum_table'] ." WHERE ". $search_string ." AND category IN (". $category_ids_query .") ORDER BY tid DESC, time ASC LIMIT ". intval($ul) .", ". intval($settings['search_results_per_page']));
   $count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE ". $search_string ." AND category IN (". $category_ids_query .")");
   list($count) = mysqli_fetch_row($count_result);
  }
 else
  {
   $result = mysqli_query($connid, "SELECT id, pid, tid, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit, subject, name, email, hp, place, text, category FROM ". $db_settings['forum_table'] ." WHERE ". $search_string ." ORDER BY tid DESC, time ASC LIMIT ". intval($ul) .", ". intval($settings['search_results_per_page']));
   $count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['forum_table'] ." WHERE ". $search_string);
   list($count) = mysqli_fetch_row($count_result);
  }

 if(!$result) die($lang['db_error']);

 // HTML:
$wo = $lang['search_title'];
$subnav_1 = "";
if (isset($search) && empty($show_postings))
 {
  if ($search != "" && $ao=="phrase")
   {
    $subnav_1 .= $lang['phrase']." <b>".htmlsc($search)."</b>";
   }
  elseif ($search != "" && count($search_array) == 1)
   {
    $subnav_1 .= $lang['search_term']." <b>".htmlsc($search_anz)."</b>";
   }
  elseif ($search != "" && count($search_array) > 1)
   {
    $subnav_1 .= $lang['search_term']." <b>".htmlsc($search_anz)."</b>";
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
  elseif ($count > 0 && $search != "" && $count <= $settings['search_results_per_page'])
   {
    $subnav_1 .=  $count;
   }
  else
   {
    $subnav_1 .= "&nbsp;";
    #$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['search_title'].'</b>';
   }
 }
elseif (isset($show_postings) && empty($search))
 {
  $user_name_result = mysqli_query($connid, "SELECT user_name FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($show_postings) ." LIMIT 1");
  if (!$user_name_result) die($lang['db_error']);
  $field = mysqli_fetch_assoc($user_name_result);
  mysqli_free_result($user_name_result);
  $lang['show_userdata_linktitle'] = str_replace("[name]", htmlsc(stripslashes($field["user_name"])), $lang['show_userdata_linktitle']);
  $lang['postings_by_user'] = str_replace("[name]", '<a href="user.php?id='. intval($show_postings) .'">'. htmlsc(stripslashes($field["user_name"])) .'</a>', $lang['postings_by_user']);
  $subnav_1 .= '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'. $lang['postings_by_user'] .'</b>';
 }
if (isset($search) && $search != "") { $subnav_2 = snav($page, $settings['search_results_per_page'], $count, $search, $ao, $category); } elseif (isset($show_postings) && $show_postings !="") { $subnav_2 = pnav($page, $settings['search_results_per_page'], $count, $show_postings); }
parse_template();
echo $header;

$search_match = (isset($search)) ? $search : '';
if (isset($search) && empty($show_postings)) {
	$ao_and = ($ao == "and") ? ' checked="checked"' : '';
	$ao_or = ($ao == "or") ? ' checked="checked"' : '';
	$ao_phrase = ($ao == "phrase") ? ' checked="checked"' : '';
	$templateAdvSearch = file_get_contents($settings['themepath'] .'/templates/form-advanced-search.html');
	$templateAdvSearch = str_replace('{$shd-advanced-search}', htmlsc($lang['search_title']), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$label-search-term}', htmlsc($lang['search_term']), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$search-terms}', htmlsc($search_match), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$chckd-search-and}', $ao_and, $templateAdvSearch);
	$templateAdvSearch = str_replace('{$label-search-and}', htmlsc($lang['search_and']), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$chckd-search-or}', $ao_or, $templateAdvSearch);
	$templateAdvSearch = str_replace('{$label-search-or}', htmlsc($lang['search_or']), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$chckd-search-phrase}', $ao_phrase, $templateAdvSearch);
	$templateAdvSearch = str_replace('{$label-search-phrase}', htmlsc($lang['search_phrase']), $templateAdvSearch);
	$templateAdvSearch = str_replace('{$btn-submit-search}', htmlsc($lang['search_submit']), $templateAdvSearch);
	if ($categories !== false) {
		$xmlSel = simplexml_load_file($settings['themepath'] .'/templates/general-select.xml', null, LIBXML_NOCDATA);
		$selAll = $xmlSel->wholeselect;
		$selBody = $xmlSel->item;
		$r = array();
		$uType = $selBody;
		$uType = str_replace('{$formValue}', 0, $uType);
		$uType = str_replace('{$formText}', htmlsc($lang['show_all_categories']), $uType);
		$checkValue = (isset($category) and $category == 0) ? ' selected="selected"' : '';
		$uType = str_replace('{$checked}', $checkValue, $uType);
		$r[] = $uType;
		foreach ($categories as $key => $val) {
			if ($key > 0) {
				$uType = $selBody;
				$uType = str_replace('{$formValue}', htmlsc($key), $uType);
				$uType = str_replace('{$formText}', htmlsc($val), $uType);
				$checkValue = (isset($category) and $category == $key) ? ' selected="selected"' : '';
				$uType = str_replace('{$checked}', $checkValue, $uType);
				$r[] = $uType;
			}
		}
		$selAll = str_replace('{$options}', join("", $r), $selAll);
		$selAll = str_replace('{$label-select}', $lang['choose_category_formtitle'], $selAll);
		$selAll = str_replace('{$selName}', 'category', $selAll);
		$selAll = str_replace('{$selID}', 'id-category', $selAll);
		$selAll = str_replace('{$selSize}', '1', $selAll);
		$templateAdvSearch = str_replace('{$categories-list}', "   <div>\n". $selAll ."   </div>", $templateAdvSearch);
	} else {
		$templateAdvSearch = str_replace('{$categories-list}', '', $templateAdvSearch);
	}
	echo $templateAdvSearch;
}

if ($count == 0 && $search != "" && count($search_array) > 1 && $ao == "and") { $notification = $lang['no_match_and']; }
elseif ($count == 0 && $search != "" && count($search_array) > 1 && $ao == "or") { $notification = $lang['no_match_or']; }
elseif ($count == 0 && $search != "" && count($search_array) > 1 && $ao == "phrase") { $notification = $lang['no_match_phrase']; }
elseif ($count == 0 && $search != "") { $notification = $lang['search_no_match']; }

if (!empty($notification)) {
	$xmlNote = simplexml_load_file($settings['themepath'] .'/templates/general-notification.xml', null, LIBXML_NOCDATA);
	$noteAll = $xmlNote->wholenote;
	$noteItem = $xmlNote->paragraph;
	$r = array();
	if (is_array($notification)) {
		foreach ($notification as $note) {
			$r[] = str_replace('{$noteText}', htmlsc($note), $noteItem);
		}
	} else {
		$r[] = str_replace('{$noteText}', htmlsc($notification), $noteItem);
	}
	$noteAll = str_replace('{$noteContent}', join("", $r), $noteAll);
	$noteAll = str_replace('{$shd-notification}', htmlsc($lang['caution']), $noteAll);
	$noteAll = str_replace('{$noteClass}', 'caution', $noteAll);
	echo $noteAll;
}

if ((isset($search) && $search != "") || (isset($show_postings) && $show_postings !="")) {
	$xmlResultList = simplexml_load_file($settings['themepath'] .'/templates/general-ul-list.xml', null, LIBXML_NOCDATA);
	$listAll = $xmlResultList->wholelist;
	$listBody = $xmlResultList->item;
	$r = array();
	while ($entrydata = mysqli_fetch_assoc($result)) {
		$search_author_info_x = str_replace("[name]", htmlsc(stripslashes($entrydata["name"])), $lang['search_author_info']);
		$search_author_info_x = str_replace("[time]", strftime($lang['time_format'],$entrydata["Uhrzeit"]), $search_author_info_x);
		$treeClass = ($entrydata["pid"] == 0) ? "thread" : "reply-search";
		if ((isset($_SESSION[$settings['session_prefix'].'user_view']) && $_SESSION[$settings['session_prefix'].'user_view']=="board") || (isset($_COOKIE['user_view']) && $_COOKIE['user_view']=="board") || (isset($standard) && $standard=="board")) {
			$userView = "board_entry.php";
			$itemFragment = "#p". intval($entrydata["id"]);
		} else if ((isset($_SESSION[$settings['session_prefix'].'user_view']) && $_SESSION[$settings['session_prefix'].'user_view']=="mix") || (isset($_COOKIE['user_view']) && $_COOKIE['user_view']=="mix") || (isset($standard) && $standard=="mix")) {
			$userView = "mix_entry.php";
			$itemFragment = "#p". intval($entrydata["id"]);
		} else if (isset($_SESSION[$settings['session_prefix'].'user_view']) && $_SESSION[$settings['session_prefix'].'user_view']=="thread" || (isset($_COOKIE['user_view']) && $_COOKIE['user_view']=="thread")) {
			$userView = "forum_entry.php";
			$itemFragment = "";
		} else {
			$userView = "forum_entry.php";
			$itemFragment = "";
		}
		if (isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='') {
			$catOutput = ' <span class="category">('. $categories[$entrydata["category"]] .')</span>';
		} else {
			$catOutput = '';
		}
		$listItem = $listBody;
		$listItem = str_replace('{$itemClass}', '', $listItem);
		$listItem = str_replace('{$itemID}', '', $listItem);
		$listItem = str_replace('{$itemText}', '<a class="'.  $treeClass .'" href="'. $userView .'?id='. intval($entrydata["tid"]) . $itemFragment .'">'. htmlsc($entrydata["subject"]) .'</a> '. $search_author_info_x . $catOutput, $listItem);
		$r[] = $listItem;
	}
	$listAll = str_replace('{$listitems}', join("", $r), $listAll);
	$listAll = str_replace('{$listClass}', '', $listAll);
	$listAll = str_replace('{$listID}', ' id="searchresults"', $listAll);
	echo $listAll;
}

echo $footer;
?>
