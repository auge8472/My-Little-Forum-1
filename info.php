<?php
include("inc.php");

if(isset($_GET['info'])) $info = intval($_GET['info']);
if(empty($info)) $info = 0;

$topnav = '<img src="img/where.gif" alt="" width="11" height="8" /><b>'.$lang['info'].'</b>';

parse_template();
echo $header;
?>
<p><?php
switch ($info)
	{
	case 0; echo '&nbsp;'; break;
	case 1: echo $lang['info_forum_disabled']; break;
	default: echo '&nbsp;'; break;
	}
?></p>
<?php
echo $footer;
?>
