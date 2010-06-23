<?php
/**
 * service.php
 *
 */

include("inc.php");

parse_template();
echo $header;
echo '<h2>'.$lang['info'].'</h2>'."\n";
echo '<p>'.$lang['info_forum_servicing'].'</p>'."\n";
echo $footer;
?>