<?php
include("inc.php");

$cookies_set = false;
if (isset($_COOKIE['user_name'])) { setcookie("user_name","",0); $cookies_set = true; }
if (isset($_COOKIE['user_email'])) { setcookie("user_email","",0); $cookies_set = true; }
if (isset($_COOKIE['user_hp'])) { setcookie("user_hp","",0); $cookies_set = true; }
if (isset($_COOKIE['user_place'])) { setcookie("user_place","",0); $cookies_set = true; }

$wo = $lang['del_cookie_title'];

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<title><?php echo $lang['del_cookie_title']; ?></title>
<meta http-equiv="content-type" content="text/html; charset=<?php echo $lang['charset']; ?>" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>
<body id="deletecookie">
<h1><?php echo $lang['del_cookie_title']; ?></h1>
<p>
<?php echo ($cookies_set == true) ? $lang['del_cookie'] : $lang['no_cookie_set']; ?>
</p>
</body>
</html>