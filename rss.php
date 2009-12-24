<?php
include("inc.php");

# database request
$rssQuery = "SELECT
id,
pid,
UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS xtime,
UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS rss_time,
name,
subject,
text
FROM ".$db_settings['forum_table'];
if (is_array($categories))
	{
	$rssQuery .= "
	WHERE category IN (".$category_ids_query.")";
	}
$rssQuery .= "
ORDER BY time DESC
LIMIT 15";
$result = mysql_query($rssQuery, $connid);
if (!$result) die($lang['db_error']);
$result_count = mysql_num_rows($result);

header("Content-Type: text/xml; charset: UTF-8";
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
 <channel>
  <title><?php echo $settings['forum_name']; ?></title>
  <link><?php echo $settings['forum_address']; ?></link>
  <description><?php echo $settings['forum_name']; ?></description>
  <language><?php echo $lang['language']; ?></language>
<?php
if ($result_count > 0
&& $settings['provide_rssfeed'] == 1
&& $settings['access_for_users_only'] == 0)
	{
	while ($zeile = mysql_fetch_assoc($result))
		{
		$ftext = $zeile["text"];
		$ftext = htmlspecialchars($ftext);
		$ftext = make_link($ftext);
		$ftext = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $ftext);
		$ftext = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $ftext);
		$ftext = bbcode($ftext);
		$ftext = nl2br($ftext);
		$ftext = rss_quote($ftext);
		$title = $zeile['subject'];
		$title = htmlspecialchars($title);
		$name = $zeile['name'];
		$name = htmlspecialchars($name);
?>
  <item>
   <title><?php echo $title; ?></title>
   <content:encoded><![CDATA[<i><?php
    if ($zeile['pid']==0)
     {
     $rss_author_info = str_replace("[name]", $name, $lang['rss_posting_by']);
     echo str_replace("[time]", strftime($lang['time_format'],$zeile["xtime"]), $rss_author_info);
     }
    else
     {
     $rss_author_info = str_replace("[name]", $name, $lang['rss_reply_by']);
     echo str_replace("[time]", strftime($lang['time_format'],$zeile["xtime"]), $rss_author_info);
     }
?></i><br /><br /><?php echo $ftext; ?>]]></content:encoded>
   <link><?php echo $settings['forum_address']."forum_entry.php?id=".$zeile['id']; ?></link>
   <pubDate><?php setlocale(LC_TIME, "C"); echo strftime($lang['rss_time'],$zeile['rss_time']); setlocale(LC_TIME, $lang['locale']); ?></pubDate>
  </item>
<?php
		}
	}
?>
 </channel>
</rss>