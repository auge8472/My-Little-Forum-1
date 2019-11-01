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
include("lang/".$lang['additional_language_file']);

if (isset($_POST['user_time_difference']))
 {
  setcookie("user_time_difference",$_POST['user_time_difference'],time()+(3600*24*30));
  header("location: index.php");
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
  <form action="<?php echo basename($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="UTF-8"><div></div>
  <select name="user_time_difference"><?php for ($h = -24; $h <= 24; $h++) { ?><option value="<?php echo $h; ?>"<?php if ($user_time_difference==$h) echo ' selected="selected"'; ?>><?php echo $h; ?></option><?php } ?></select></td>
  <input type="submit" name="ok" value="<?php echo $lang['submit_button_ok']; ?>" />
  </div></form><?php
 }
echo $footer;
?>
