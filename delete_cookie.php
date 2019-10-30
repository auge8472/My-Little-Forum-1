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

$cookies_set = false;

if (isset($_COOKIE['user_name'])) { setcookie("user_name","",0); $cookies_set = true; }
if (isset($_COOKIE['user_email'])) { setcookie("user_email","",0); $cookies_set = true; }
if (isset($_COOKIE['user_hp'])) { setcookie("user_hp","",0); $cookies_set = true; }
if (isset($_COOKIE['user_place'])) { setcookie("user_place","",0); $cookies_set = true; }

$page_title = htmlsc($lang['del_cookie_title']);
$page_info = ($cookies_set === true) ? htmlsc($lang['del_cookie']) : htmlsc($lang['no_cookie_set']);

?><!DOCTYPE html>
<html lang="<?php echo htmlsc($lang['language']); ?>">
 <head>
  <meta charset="utf-8">
  <title><?php echo $page_title; ?></title>
  <link rel="stylesheet" type="text/css" href="style.css" media="all">
 </head>
 <body id="deletecookie">
  <h1><?php echo $page_title; ?></h1>
  <p><?php echo $page_info; ?></p>
 </body>
</html>
