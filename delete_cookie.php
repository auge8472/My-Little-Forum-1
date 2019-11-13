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

$wo = $lang['del_cookie_title'];

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo htmlsc($lang['charset']); ?>">
<head>
<title><?php echo $lang['del_cookie_title']; ?></title>
<meta http-equiv="content-type" content="text/html; charset=<?php echo htmlsc($lang['charset']); ?>" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>
<body id="deletecookie">
<h1><?php echo $lang['del_cookie_title']; ?></h1>
<?php if ($cookies_set == true) { ?><p><?php echo $lang['del_cookie']; ?></p><?php } else { ?><p><?php echo $lang['no_cookie_set']; ?></p><?php }
?></body>
</html>