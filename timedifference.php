<?php
include('inc.php');
include('lang/'.$lang['additional_language_file']);

if (isset($_POST['user_time_difference']))
	{
	setcookie('user_time_difference',$_POST['user_time_difference'],time()+(3600*24*30));
	header('location: '.$settings['forum_address'].'index.php');
	die('<a href="forum.php">further...</a>');
	}

if (isset($_COOKIE['user_time_difference']))
	{
	$user_time_difference = $_COOKIE['user_time_difference'];
	}
else
	{
	$user_time_difference = 0;
	}

$wo = $lang_add['td_title'];
$topnav = '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'.$lang_add['td_title'].'</b>';

parse_template();
echo $header;
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	echo '<p class="posting">'.$lang_add['td_user_note'].'</p>'."\n";
	}
else
	{
	echo '<p class="posting">'.$lang_add['td_desc'].'</p>'."\n";
	echo '<form action="'.$_SERVER["SCRIPT_NAME"].'" method="post">'."\n";
	echo '<select name="user_time_difference">'."\n";
	for ($h = -24; $h <= 24; $h++)
		{
		echo '<option value="'.$h.'"';
		if ($user_time_difference==$h) ? ' selected="selected"' : '';
		echo '>'.$h.'</option>'."\n"
		}
	echo '</select>'."\n";
	echo '<input type="submit" name="ok" value="'.$lang['submit_button_ok'].'" />'."\n";
	echo '</form>'."\n";
	}
echo $footer;
?>
