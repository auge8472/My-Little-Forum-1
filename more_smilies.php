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

$templateButton = '   <li><button onclick="opener.insert(\'{$scode}\'); window.close();"><img src="img/smilies/{$filename}" alt="{$scode}"></button></li>';
$buttonItem = '';
$buttonList = array();
$template = file_get_contents($settings['themepath'] .'/templates/more-smileys.html');

$result = mysqli_query($connid, "SELECT file, code_1, title FROM ". $db_settings['smilies_table'] ." ORDER BY order_id ASC");
while ($data = mysqli_fetch_assoc($result)) {
	$buttonItem = $templateButton;
	$buttonItem = str_replace('{$scode}', htmlsc($data['code_1']), $buttonItem);
	$buttonItem = str_replace('{$filename}', htmlsc($data['file']), $buttonItem);
	$buttonList[] = $buttonItem;
}
mysqli_free_result($result);

$template = str_replace('{$language}', htmlsc($lang['language']), $template);
if (!empty($buttonList)) {
	array_unshift($buttonList, "  <ul>");
	$buttonList[] = "  </ul>";
} else  {
	$buttonList[] = "  <p>No smilies</p>";
}

$template = str_replace('{$smileylist}', implode("\n", $buttonList), $template);

echo $template;
