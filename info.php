<?php
include("inc.php");

if(isset($_GET['info'])) $info = intval($_GET['info']);
if(empty($info)) $info = 0;

$topnav  = '<li><span class="current"><span class="fa fa-exclamation-triangle icon-warning">';
$topnav .= '</span>&nbsp;'. htmlspecialchars($lang['info']) .'</span></li>'."\n";

parse_template();
echo $header;
?>
<h2><?php echo $lang['info']; ?></h2>
<p><?php
switch ($info)
	{
	case 0; echo '&nbsp;'; break;
	case 1: echo $lang['info_forum_disabled']; break;
	case 2: echo $lang['info_forum_servicing']; break;
	default: echo '&nbsp;'; break;
	}
?></p>
<?php
echo $footer;
?>
