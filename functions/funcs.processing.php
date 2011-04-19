<?php



/**
 * counts the chars of the words in a given string
 *
 * @param string $string
 * @param string $setting
 * @param string $message
 * @return array $error
 */
function processCountCharsInWords($string, $setting, $message) {
$error = array();
$text_arr = explode(" ",$string);
$countWords = count($text_arr);

for ($i=0; $i<$countWords; $i++)
	{
	trim($text_arr[$i]);
	$laenge = mb_strlen($text_arr[$i]);
	if ($laenge > $setting)
		{
		$error[] = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$setting))."...", $message);
		}
	}

return $error;
} # End: processCountCharsInWords



/**
 * splits URL in parts and encodes the parts
 *
 * @param string $url
 * @return string $url
 */
function processUrlEncode($url, $uri= true) {
$url = trim($url);
$temp = parse_url($url);
$nurl .= (!empty($temp['scheme'])) ? $temp['scheme'].'://' : '';
$nurl .= (!empty($temp['user']) and !empty($temp['pass'])) ? $temp['user'].':'.$temp['pass'].'@' : '';
$nurl .= (!empty($temp['host'])) ? $temp['host'] : '';
$nurl .= (!empty($temp['port'])) ? ':'.$temp['port'] : '';
if (!empty($temp['path']))
	{
	$temp['path'] = explode("/", $temp['path']);
	for ($i = 0; $i < count($temp['path']); $i ++)
		{
		if (!empty($temp['path'][$i]))
			{
			if ($i==0 and $uri===false)
				{
				$nurl .= rawurlencode($temp['path'][$i]);
				}
			else
				{
				$nurl .= '/'.rawurlencode($temp['path'][$i]);
				}
			}
		}
	}
if (!empty($temp['query']))
	{
	$nurl .= '?';
	if (strpos($temp['query'], ';'))
		{
		$queryParts = explode(';', $temp['query']);
		}
	else if (strpos($temp['query'], '&amp;'))
		{
		$queryParts = explode('&amp;', $temp['query']);
		}
	else if (strpos($temp['query'], '&'))
		{
		$queryParts = explode('&', $temp['query']);
		}
	else
		{
		$queryParts = array($temp['query']);
		}
	for ($i = 0; $i < count($queryParts); $i++)
		{
		$splitter[$i] = explode('=', $queryParts[$i]);
		if ($i == 0)
			{
			$nurl .= $splitter[$i][0].'='.urlencode($splitter[$i][1]);
			}
		else
			{
			$nurl .= '&amp;'.$splitter[$i][0].'='.urlencode($splitter[$i][1]);
			}
		}
	}
else
	{
	$nurl .= '';
	}
$nurl .= (!empty($temp['fragment'])) ? '#'.$temp['fragment'] : '';

return $nurl;
} # End: processUrlEncode



/**
 * filters subscriptions of postings if there is a subscription for the whole thread 
 *
 * @param array $subscriptions
 * @return array $subscriptions
 * @return bool false
 */
function processSubscriptFilter($a) {
global $db_settings, $connid, $lang;

if (is_array($a) === false) return false;

$i = 0;

foreach ($a as $sub)
	{
	if ($sub['thread_notify'] == 1)
		{
		$temp[$i]['tid'] = $sub['tid'];
		$temp[$i]['id'] = $sub['id'];
		}
	$i++;
	}

for ($i = 0; $i < count($a); $i++)
	{
	foreach ($temp as $tmp)
		{
		if ($tmp['tid'] == $a[$i]['tid']
		and $a[$i]['id'] != $tmp['id'])
			{
			$queryDel[] = $a[$i]['id'];
			$a[$i]['delete'] = 1;
			break;
			}
		}
	}

if (!empty($queryDel))
	{
	if (count($queryDel) > 1)
		{
		$queryDel = join(", ", $queryDel);
		$matches = "IN (".$queryDel.")";
		}
	else
		{
		$matches = "= ".$queryDel[0];
		}

	$queryUnsubscribe = "UPDATE ".$db_settings['forum_table']." SET 
		email_notify = 0
		WHERE id ".$matches;
	$result = mysql_query($queryUnsubscribe, $connid);
	if (!$result) die($lang['db_error']);
	}

return $a;
} # End: processSubscriptFilter($a)



/**
 * unifies all possible line breaks into unixoid break
 *
 * @param string $string
 * @return string $string
 */
function convertLineBreaks($string) {
return preg_replace("/\015\012|\015|\012/", "\n", $string);
} # End: convertLineBreaks



/**
 * extracts all line breaks from a string
 *
 * @param string $string
 * @return string $string
 */
function bbcodeStripContents($string) {
return preg_replace("/[^\n]/", '', $string);
} # End: bbcodeStripContents



/**
 * returns a string of a link from a given link-bbcode
 *
 * @param string $action
 * @param array $attributes
 * @param string $content
 * @param array $params
 * @param int $node_object
 * @return string
 */
function bbcodeDoURL($action, $attributes, $content, $params, $node_object) {

/**
 * Origin of code inside "if ($action == 'validate')" is jlog 1.1.3
 * see: http://jeenaparadies.net/webdesign/jlog/
 */
if ($action == 'validate')
	{
	if (preg_match('#^(http://|ftp://|news:|mailto:|/)#i', $url)) return true; 
	# Some people just write www.example.org, skipping the http://
	# We're going to be gentle a prefix this link with the protocoll.
	# However, example.org (without www) will not be recognized
	else if (substr($url, 0, 4) == 'www.') return true;
	# all other links will be ignored
	return true;
	}
if (!isset ($attributes['default']))
	{
	return '<a rel="nofollow" href="'.htmlspecialchars($content).'">'.htmlspecialchars(shorten_link($content)).'</a>';
	}
return '<a rel="nofollow" href="'.htmlspecialchars($attributes['default']).'">'.$content.'</a>';
} #End: bbcodeDoURL

?>