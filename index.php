<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004 Alex                                                     #
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
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

 if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1)
  {
   header("location: login.php?referer=index.php");
   die("<a href=\"login.php?referer=index.php\">further...</a>");
  }

 if (isset($_GET['update']) && isset($_SESSION[$settings['session_prefix'].'newtime']))
  {
   $_SESSION[$settings['session_prefix'].'newtime'] = time();
   $update_result = mysql_query("UPDATE ".$db_settings['userdata_table']." SET last_login=last_login, last_logout=NOW(), registered=registered WHERE user_id='".$_SESSION[$settings['session_prefix'].'user_id']."'", $connid);

   if (isset($_GET['category'])) $qs = "?category=".urlencode(stripslashes($_GET['category'])); else $qs = "";
   if (empty($_GET['view'])) { header("location: forum.php".$qs); die("<a href=\"forum.php".$qs."\">further...</a>"); }
   elseif (isset($_GET['view']) && $_GET['view']=="board") { header("location: board.php".$qs); die("<a href=\"board.php".$qs."\">further...</a>"); }
   elseif (isset($_GET['view']) && $_GET['view']=="mix") { header("location: mix.php".$qs); die("<a href=\"mix.php".$qs."\">further...</a>"); }
  }

 if (isset($_GET['category'])) $qs = "?category=".$_GET['category']; else $qs = "";

 if (isset($_SESSION[$settings['session_prefix'].'user_view']))
  {
   if ($_SESSION[$settings['session_prefix'].'user_view'] == "board") { header("location: board.php".$qs); die("<a href=\"board.php\">further...</a>"); }
   elseif ($_SESSION[$settings['session_prefix'].'user_view'] == "mix") { header("location: mix.php".$qs); die("<a href=\"mix.php\">further...</a>"); }
   else { header("location: forum.php".$qs); die("<a href=\"forum.php\">further...</a>"); }
  }
 elseif (isset($_COOKIE['user_view']))
  {
   if ($_COOKIE['user_view'] == "board") { header("location: board.php".$qs); die("<a href=\"board.php\">further...</a>"); }
   elseif ($_COOKIE['user_view'] == "mix") { header("location: mix.php".$qs); die("<a href=\"mix.php\">further...</a>"); }
   else { header("location: forum.php".$qs); die("<a href=\"forum.php\">further...</a>"); }
  }
 else
  {
   if ($settings['standard'] == "board") { header("location: board.php".$qs); die("<a href=\"board.php\">further...</a>"); }
   elseif ($settings['standard'] == "mix") { header("location: mix.php".$qs); die("<a href=\"mix.php\">further...</a>"); }
   else { header("location: forum.php".$qs); die("<a href=\"forum.php\">further...</a>"); }
  }
?>
