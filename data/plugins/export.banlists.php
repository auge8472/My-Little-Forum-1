<?php

/**
 * export the IPs from table *forum_banlists into *forum_banned_ips
 *
 * it's for one-time-use while updating the forum to version 1.8
 *
 * @author Heiko August <post@auge8472.de>
 * @since version 1.8
 */

include_once('../../functions/funcs.db.php');
include_once('../../db_settings.php');

$output = '';
$dataset = array();
$checkDouble = array();

$sql = connect_db($db_settings['host'], $db_settings['user'], $db_settings['pw'], $db_settings['db']);

if ($sql === false) {
	$output .= '<p>An error occured. The database server was not reachable.</p>';
	}
else {
	$queryGetOldData = "SELECT list FROM ". $db_settings['banlists_table'] ." WHERE name = 'ips'";
	$resultOldData = dbaseAskDatabase($queryGetOldData, $sql);
	if ($resultOldData === false) {
		$output .= '<p>An error occured. The query failed.</p>';
		$output .= '<pre>'. print_r($queryGetOldData) .'</pre>';
		$output .= '<pre>'. mysql_errno() .': '. mysql_error() .'</pre>';
		}
	else {
		$oldIPs = explode(',', trim($resultOldData[0]['list']));
		foreach ($oldIPs as $oldIP) {
			$checkIP = ip2long($oldIP);
			if ($checkIP !== false and !in_array($oldIP, $checkDouble)) {
				$dataset[] = "(INET_ATON('". mysql_real_escape_string($oldIP) ."'), NOW(), 1)";
				$checkDouble[] = $oldIP;
				}
			}
		$completeSet = implode(', ', $dataset);
		$querySetNewData = "INSERT INTO ". $db_settings['banned_ips_table'] ." VALUES ". $completeSet;
		$resultNewData = dbaseAskDatabase($querySetNewData, $sql);
		if ($resultNewData === false) {
			$output .= '<p>An error occured. The query failed.</p>';
			$output .= '<pre>'. print_r($querySetNewData) .'</pre>';
			$output .= '<pre>'. mysql_errno() .': '. mysql_error() .'</pre>';
			}
		else
			{
			$output .= '<p>The query was successful. Check the data i.e. via phpMyAdmin.</p>';
			}
		}
	}

echo $output;

?>
