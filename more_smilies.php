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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<title>Smilies</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>
<body>
<?php
$result = mysqli_query($connid, "SELECT file, code_1, title FROM ". $db_settings['smilies_table'] ." ORDER BY order_id ASC");
while ($data = mysqli_fetch_assoc($result))
 {
  ?><a href="#" onclick="opener.insert('<?php echo htmlsc($data['code_1']); ?> '); window.close();"><img style="margin: 0px 10px 10px 0px; border: 0px;" src="img/smilies/<?php echo rawurlencode($data['file']); ?>" alt="<?php echo htmlsc($data['code_1']); ?>" /></a><?php
 }
mysqli_free_result($result);
?>
</body>
</html>