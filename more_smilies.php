<?php
include("inc.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<title>Smilies</title>
<meta http-equiv="content-type" content="text/html; charset=<?php echo $lang['charset']; ?>" />
</head>
<body>
<?php
$result = mysql_query("SELECT file, code_1, title FROM ".$db_settings['smilies_table']." ORDER BY order_id ASC", $connid);
while ($data = mysql_fetch_array($result))
	{
	echo '<a href="#" title="'.$lang['smiley_title'].'" onclick="opener.insert(\'';
	echo stripslashes($data['code_1']).'\'); window.close();">';
	echo '<img style="margin: 0px 10px 10px 0px; border: 0px;" src="img/smilies/';
	echo stripslashes($data['file']).'" alt="'.stripslashes($data['code_1']).'" /></a>'."\n";
	}
mysql_free_result($result);
?>
</body>
</html>