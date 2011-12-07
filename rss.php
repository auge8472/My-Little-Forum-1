<?php
include("inc.php");

if (isset($_GET['cat'])
	and is_numeric($_GET['cat'])
	and isset($category_ids)
	and in_array($_GET['cat'], $category_ids))
	{
	$wherePart = "
	WHERE category = ".intval($_GET['cat']);
	}

# database request
$rssQuery = "SELECT
id,
pid,
DATE_FORMAT(time + INTERVAL ".$time_difference." HOUR, '".$lang['time_format_sql']."') AS xtime,
UNIX_TIMESTAMP(time) AS rss_time,
name,
subject,
text
FROM ".$db_settings['forum_table'];

if (isset($wherePart))
	{
	$rssQuery .= $wherePart;
	}
else if (is_array($categories))
	{
	$rssQuery .= "
	WHERE category IN (".$category_ids_query.")";
	}
$rssQuery .= "
ORDER BY time DESC
LIMIT 15";
$result = mysql_query($rssQuery, $connid);
if (!$result) die($lang['db_error']."<br />".mysql_error());
$result_count = mysql_num_rows($result);

$rss  = '';
$rss .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";

$rss .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
$rss .= ' <channel>'."\n";
$rss .= '  <title>'.$settings['forum_name'].'</title>'."\n";
$rss .= '  <link>'.$settings['forum_address'].'</link>'."\n";
$rss .= '  <description>'.$settings['forum_name'].'</description>'."\n";
$rss .= '  <language>'.$lang['language'].'</language>'."\n";

if ($result_count > 0
&& $settings['provide_rssfeed'] == 1
&& $settings['access_for_users_only'] == 0)
	{
	while ($zeile = mysql_fetch_assoc($result))
		{
		$ftext = outputXMLclearedString($zeile["text"]);
		$ftext = htmlspecialchars($ftext);
		$ftext = make_link($ftext);
		$ftext = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $ftext);
		$ftext = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $ftext);
		$ftext = bbcode($ftext);
		$ftext = nl2br($ftext);
		$ftext = rss_quote($ftext);
		$title = outputXMLclearedString($zeile['subject']);
		$title = htmlspecialchars($title);
		$name = outputXMLclearedString($zeile['name']);
		$name = htmlspecialchars($name);
		$rss .= '  <item>'."\n";
		$rss .= '   <title>'.$title.'</title>'."\n";
		$rss .= '   <description><![CDATA[<i>';
		if ($zeile['pid']==0)
			{
			$rss_author_info = str_replace("[name]", $name, $lang['rss_posting_by']);
			}
		else
			{
			$rss_author_info = str_replace("[name]", $name, $lang['rss_reply_by']);
			}
		$rss .= str_replace("[time]", $zeile["xtime"], $rss_author_info);
		$rss .= '</i><br /><br />'.$ftext.']]></description>'."\n";
		$rss .= '   <link>'.$settings['forum_address'].'forum_entry.php?id='.$zeile['id'].'</link>'."\n";
		$rss .= '   <guid>'.$settings['forum_address'].'forum_entry.php?id='.$zeile['id'].'</guid>'."\n";
		$rss .= '   <author>'.$name.'</author>'."\n";
		$rss .= '   <pubDate>'.date("r", $zeile['rss_time']).'</pubDate>'."\n";
		$rss .= '  </item>'."\n";
		}
	}
$rss .= ' </channel>'."\n";
$rss .= '</rss>'."\n";

#header("Content-Type: text/html; charset: UTF-8");
#echo '<pre>'.htmlspecialchars($rss).'</pre>';
header("Content-Type: text/xml; charset: UTF-8");
echo $rss;
?>
