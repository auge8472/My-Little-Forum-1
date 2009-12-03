<?php
include("inc.php");
include("lang/".$lang['additional_language_file']);

if (isset($_POST['user_time_difference']))
 {
  setcookie("user_time_difference",$_POST['user_time_difference'],time()+(3600*24*30));
  header("location: ".$settings['forum_address']."index.php");
  die("<a href=\"forum.php\">further...</a>");
 }
if (isset($_COOKIE['user_time_difference'])) $user_time_difference = $_COOKIE['user_time_difference'];
else $user_time_difference = 0;
$wo = $lang_add['td_title'];
$topnav = '<img src="img/where.gif" alt="" width="11" height="8" border="0"><b>'.$lang_add['td_title'].'</b>';
parse_template();
echo $header;
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
 {
  ?><p class="posting"><?php echo $lang_add['td_user_note']; ?></p><?php
 }
else
 {
  ?><p class="posting"><?php echo $lang_add['td_desc']; ?></p>
  <form action="<?php echo basename($_SERVER["PHP_SELF"]); ?>" method="post"><div></div>
  <select name="user_time_difference"><?php for ($h = -24; $h <= 24; $h++) { ?><option value="<?php echo $h; ?>"<?php if ($user_time_difference==$h) echo ' selected="selected"'; ?>><?php echo $h; ?></option><?php } ?></select></td>
  <input type="submit" name="ok" value="<?php echo $lang['submit_button_ok']; ?>" />
  </div></form><?php
 }
echo $footer;
?>
