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

// database request
 if ($categories == false)
  {
   $result=mysqli_query($connid, "SELECT id, pid, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS xtime, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS rss_time, name, subject, text FROM ". $db_settings['forum_table'] ." ORDER BY time DESC LIMIT 15");
   if(!$result) die($lang['db_error']);
   }
 elseif (is_array($categories))
  {
   $result=mysqli_query($connid, "SELECT id, pid, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS xtime, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS rss_time, name, subject, text FROM ". $db_settings['forum_table'] ." WHERE category IN (". $category_ids_query .") ORDER BY time DESC LIMIT 15");
   if(!$result) die($lang['db_error']);
  }
$result_count = mysqli_num_rows($result);
header("Content-Type: text/xml; charset:  utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title><?php echo $settings['forum_name']; ?></title>
<link><?php echo $settings['forum_address']; ?></link>
<description><?php echo $settings['forum_name']; ?></description>
<language><?php echo $lang['language']; ?></language>
<?php
if ($result_count > 0 && $settings['provide_rssfeed'] == 1 && $settings['access_for_users_only'] == 0)
{
while ($zeile = mysqli_fetch_assoc($result))
{
$ftext = $zeile["text"];
$ftext = htmlsc($ftext);
$ftext = make_link($ftext);
$ftext = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $ftext);
$ftext = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $ftext);
$ftext = bbcode($ftext, true);
$ftext = nl2br($ftext);
#$ftext = str_replace("&raquo;", "&gt;", $ftext);
#$ftext = str_replace("&laquo;", "&lt;", $ftext);
$ftext = rss_quote($ftext);
#$ftext = str_replace("&", "&amp;", $ftext);
#$ftext = str_replace("<", "&lt;", $ftext);
#$ftext = str_replace(">", "&gt;", $ftext);
$title = $zeile['subject'];
$title = htmlsc($title);
#$title = str_replace("&raquo;", "&gt;", $title);
#$title = str_replace("&laquo;", "&lt;", $title);
#$title = str_replace("&", "&amp;", $title);
$name = $zeile['name'];
$name = htmlsc($name);
#$name = str_replace("&raquo;", "&gt;", $name);
#$name = str_replace("&laquo;", "&lt;", $name);
#$name = str_replace("&", "&amp;", $name);
?><item>
<title><?php echo $title; ?></title>
<content:encoded><![CDATA[<i><?php if ($zeile['pid']==0) { $rss_author_info = str_replace("[name]", $name, $lang['rss_posting_by']); echo str_replace("[time]", strftime($lang['time_format'],$zeile["xtime"]), $rss_author_info); } else { $rss_author_info = str_replace("[name]", $name, $lang['rss_reply_by']); echo str_replace("[time]", strftime($lang['time_format'],$zeile["xtime"]), $rss_author_info); } ?></i><br /><br /><?php echo $ftext; ?>]]></content:encoded>
<link><?php echo $settings['forum_address']."forum_entry.php?id=".$zeile['id']; ?></link>
<pubDate><?php setlocale(LC_TIME, "C"); echo strftime($lang['rss_time'],$zeile['rss_time']); setlocale(LC_TIME, $lang['locale']); ?></pubDate>
</item>
<?php
}
}
?>
</channel>
</rss>