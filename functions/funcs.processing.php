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


?>