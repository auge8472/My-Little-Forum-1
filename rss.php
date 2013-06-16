<?php
include("inc.php");

if (isset($_GET['cat'])
	and is_numeric($_GET['cat'])
	and isset($category_ids)
	and in_array($_GET['cat'], $category_ids))
	{
	$wherePart = "
	WHERE category = ". intval($_GET['cat']);
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
$data = array();
if (!$result)
	{
	$timestamp = time();
	$data[0]['id'] = 0;
	$data[0]['pid'] = 0;
	$data[0]['xtime'] = strftime($lang['time_format'], $timestamp);
	$data[0]['rss_time'] = $timestamp;
	$data[0]['name'] = $settings['forum_email'];
	$data[0]['subject'] = $lang['error_headline'];
	$data[0]['text'] = $lang['db_error'];
	}
else
	{
	while ($satz = mysql_fetch_assoc($result))
		{
		$data[] = $satz; 
		}
	}

$rss1 = new DOMDocument('1.0', 'UTF-8');
$rss1->formatOutput = true;
$root = $rss1->createElement('rss');
	$root->setAttribute('version', '2.0');
	$root->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
	$rss1->appendChild($root);
$chan = $rss1->createElement('channel');
	$root->appendChild($chan); 
$head = $rss1->createElement('title', utf8_encode($settings['forum_name']));
	$chan->appendChild($head);
$head = $rss1->createElement('description', utf8_encode($settings['forum_name']));
	$chan->appendChild($head);
$head = $rss1->createElement('language', utf8_encode($lang['language']));
	$chan->appendChild($head);
$head = $rss1->createElement('link', htmlentities($settings['forum_address']));
	$chan->appendChild($head);
$head = $rss1->createElement('lastBuildDate', utf8_encode(date("D, j M Y H:i:s ").'GMT'));
	$chan->appendChild($head);

$result_count = count($data);

/*
$rss  = '';
$rss .= '  <atom:link href="'.$settings['forum_address'].'rss.php" rel="self" type="application/rss+xml" />'."\n";
*/

if ($result_count > 0
&& $settings['provide_rssfeed'] == 1
&& $settings['access_for_users_only'] == 0)
	{
	foreach ($data as $zeile)
		{
		$ftext = outputXMLclearedString($zeile["text"]);
		$ftext = htmlspecialchars($ftext);
		$ftext = make_link($ftext);
		$ftext = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $ftext);
		$ftext = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $ftext);
		$ftext = bbcode($ftext);
		$ftext = rss_quote($ftext);
		$title = outputXMLclearedString($zeile['subject']);
		$title = htmlspecialchars($title);
		$name = outputXMLclearedString($zeile['name']);
		$name = htmlspecialchars($name);
		if ($zeile['pid']==0)
			{
			$rss_author_info = str_replace("[name]", $name, $lang['rss_posting_by']);
			}
		else
			{
			$rss_author_info = str_replace("[name]", $name, $lang['rss_reply_by']);
			}			
		$rssItem = '';
		$rssItem .= str_replace("[time]", $zeile["xtime"], $rss_author_info);
		$rssItem .= "\n\n". $ftext;
		$item = $rss1->createElement('item');
			$chan->appendChild($item);
		$data = $rss1->createElement('title', $title);
			$item->appendChild($data);
		$data = $rss1->createElement('description', $rssItem);
			$item->appendChild($data);
		$data = $rss1->createElement('pubDate', @ date("r", $zeile['rss_time']));
			$item->appendChild($data);
		$data = $rss1->createElement('link', htmlentities($settings['forum_address'].'forum_entry.php?id='.$zeile['id']));
			$item->appendChild($data);
		$data = $rss1->createElement('guid', htmlentities($settings['forum_address'].'forum_entry.php?id='.$zeile['id']));
			$item->appendChild($data);
		$data = $rss1->createElement('dc:creator', $name);
			$item->appendChild($data);
		}
	}

#header("Content-Type: text/html; charset: UTF-8");
#echo '<pre>'. htmlspecialchars($rss) .'</pre>';
header("Content-Type: application/xml; charset: UTF-8");
echo $rss1->saveXML();
?>
