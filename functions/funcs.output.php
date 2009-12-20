<?php

/**
 *	collection of functions for output control
 * @author: Heiko August
 */



/**
 * generates the list of functions
 * to manipulate marked threads
 *
 * @param 
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
}

?>