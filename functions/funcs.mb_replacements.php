<?php

function mb_internal_encoding($charset='') {
}

function mb_strlen($string, $encoding='utf-8') {
$encoding = strtolower($encoding);
if (function_exists('mb_strlen'))
	{
	return mb_strlen($string, $encoding);
	}
else if ($encoding=='utf-8')
	{
	$string = utf8_decode($string);
	$string = strlen($string);
	$string = utf8_encode($string);
	return ($string);
	}
else
	{
	return strlen($string);
	}
}
 
function mb_substr($str, $start, $length=0, $encoding='') {
return substr($str, $start, $length);
}

function mb_strpos($haystack, $needle, $offset=0, $encoding='') {
return strpos($haystack, $needle, $offset);
}

function mb_strrpos($haystack, $needle, $offset=0, $encoding='') {
return strrpos($haystack, $needle, $offset);
}

function mb_strtolower($string, $encoding='utf-8') {
$encoding = strtolower($encoding);
if (function_exists('mb_strtolower'))
	{
	return mb_strtolower($string, $encoding);
	}
else if ($encoding=='utf-8')
	{
	$string = utf8_decode($string);
	$string = strtolower($string);
	$string = utf8_encode($string);
	return ($string);
	}
else
	{
	return strtolower($string);
	}
}

function mb_strtoupper($string, $encoding='utf-8') {
$encoding = strtolower($encoding);
if (function_exists('mb_strtolower'))
	{
	return mb_strtoupper($string, $encoding);
	}
else if ($encoding=='utf-8')
	{
	$string = utf8_decode($string);
	$string = strtoupper($string);
	$string = utf8_encode($string);
	return ($string);
	}
else
	{
	return strtoupper($string);
	}
}

function mb_encode_mimeheader($str, $charset='utf-8', $transfer_encoding='', $linefeed='', $indent='') {
return '=?'.$charset.'?B?'.base64_encode($str).'?=';
}

?>