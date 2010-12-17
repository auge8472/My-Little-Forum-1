<?php



/**
 * counts the chars of the words in a given string
 *
 * @param string
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

?>