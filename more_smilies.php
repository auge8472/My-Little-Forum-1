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

$result = mysqli_query($connid, "SELECT file, code_1, title FROM ". $db_settings['smilies_table'] ." ORDER BY order_id ASC");

?><!DOCTYPE html>
<html lang="<?php echo $lang['language']; ?>">
 <head>
  <meta charset="utf-8">
  <title>Smilies</title>
 </head>
 <body>
<?php
while ($data = mysqli_fetch_assoc($result)) {
?><a href="#" title="<?php echo $lang['smiley_title']; ?>" onclick="opener.insert('<?php echo $data['code_1']; ?> '); window.close();"><img style="margin: 0px 10px 10px 0px; border: 0px;" src="img/smilies/<?php echo $data['file']; ?>" alt="<?php echo $data['code_1']; ?>" /></a><?php
}
mysqli_free_result($result);
?>
 </body>
</html>
