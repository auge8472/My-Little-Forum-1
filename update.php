<?php

include_once('functions/include.install.php');
#include_once('functions.php');
include_once("db_settings.php");
include_once("lang/english.php");
include_once("lang/english_add.php");

# initialisation
ini_set('arg_separator.output', '&amp;');
header('Content-Type: text/html; charset=UTF-8');
if (!extension_loaded('mbstring')) include_once('/functions/funcs.mb_replacements.php');
mb_internal_encoding('UTF-8');

# for details see: http://de.php.net/manual/en/security.magicquotes.disabling.php
if (get_magic_quotes_gpc())
	{
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}


# number of the new version
$newVersion = "1.8";
# empty error array
$errors = array();
# status of the script
# 0: initialization status, start of the update procedure
# 1: no connect to the database or no data of an existing forum-DB found
# 2: update complete
$scriptStatus = 0;
$statusOutput[1] = "<p>".$lang_add['db_read_settings_error']."</p>
<p>The forum seems not to be installed. Please control the existence and name of the settings table or switch to <a href=\"install.php\">Installation</a>.</p>";
$statusOutput[2] = "<p>Database tables have been altered, new tables are created.</p>";
$statusOutput[3] = "<p>The given data from the form seems to be wrong.</p>";
$statusOutput[4] = "<p>An error occured</p>\n";

# initialization of the output
$output = "";

# connect the database (return: [status] resource number or false; [errnbr] error number)
$sql = auge_connect_db($db_settings);

if ($sql["status"] === false)
	{
	# no contact to the database server
	# generate error message
	$errors[] = "MySQL error: ".$sql["errnbr"]."<br />".$lang_add['db_'.$sql["errnbr"]];
	# status: no connection to database
	$scriptStatus = 1;
	$output .= "<p>The script can not contact the database server. The username, password or database name seems to be wrong.</p>";
	}
else
	{
	# List all versions wich can be updated.
	$updateVersions = array("1.7");
	# the database is contacted
	$connid = $sql["status"];
	# read the current settings from the settings table
	$oldSettingsQuery = "SELECT name, value FROM ".$db_settings['settings_table'];
	$ancientSettings = auge_ask_database($oldSettingsQuery,$connid);
	if (!is_array($ancientSettings['status']))
		{
		# status: found no data of an existing installation (empty result or error)
		$output .= $statusOutput[1];
		$scriptStatus = 1;
		}
	else
		{
		# bring the old settings into the needed format
		foreach ($ancientSettings['status'] as $ancientSetting)
			{
			$oldSettings[$ancientSetting['name']] = $ancientSetting['value'];
			}
		# search version number of the old forum version:
		if (empty($oldSettings['version']))
			{
			# the old setting for version string is not present
			$errors[] = $lang_add['no_version_found'];
			}
		else
			{
			# found string for old version
			$versionCompare = false;
			# shorten version string to one digit after first point
			$oldVersionShort = substr($oldSettings['version'],0,3);
			if ($oldVersionShort == $newVersion)
				{
				# identic old and new version
				$errors[] = $lang_add['version_already_installed'];
				}
			else if (!in_array($oldVersionShort,$updateVersions))
				{
				# old version is not supported by the update procedure
				$errors[] = $lang_add['version_not_supported'];
				}
			else
				{
				$oldVersion = $oldSettings['version'];
				}
			} # End: search version number of old forum
		# include files for the language wich is stored in the old settings
		include("lang/".$oldSettings['language_file'] );
		include("lang/".$lang['additional_language_file']);
		# the form was submitted
		if (isset($_POST['form_submitted'])) {
#			$output .= '<pre>';
#			$output .= print_r($_POST, true);
#			$output .= '</pre>';
			# all fields filled out?
			foreach ($_POST as $postKey=>$postVal)
				{
				$postVal = trim($postVal);
				if (empty($postVal)) { $errors[] = $lang['error_form_uncompl']; break; }
				}
			# try to connect the database with posted access data (deprecated):
			if (empty($errors))
				{
				# Umstellung auf meine SQL-Funktionen
				$tempDBConnData = array('host'=>$db_settings['host'],'user'=>$db_settings['user'],'pw'=>$db_settings['pw'],'db'=>$db_settings['db']);
				$tempSQL = auge_connect_db($tempDBConnData);
				if ($tempSQL['status'] === false)
					{
					# wrong given data
					# generate error message
					$errors[] = "MySQL error: ".$tempSQL["errnbr"]."\n".$lang_add['db_'.$tempSQL["errnbr"]];
					# status: no connection to database
					# because wrong given data from the form
					$scriptStatus = 1;
					}
				else
					{
					$connid = $tempSQL['status'];
					}
				} # End: deprecated

			# overwrite database settings file:
			if (empty($errors) and empty($_POST['dont_overwrite_settings']))
				{
				clearstatcache();
				$chmod = decoct(fileperms("db_settings.php"));

				$db_settings['usersettings_table'] = $_POST['table_prefix'].'usersettings';
				$db_settings['us_templates_table'] = $_POST['table_prefix'].'fu_settings';
				$db_settings['banned_ips_table'] = $_POST['table_prefix'].'banned_ips';
				$db_settings['usersubscripts_table'] = $_POST['table_prefix'].'subscripts';
				# content of db_settings.php
				$SetCont  = "<?php\n";
				$SetCont .= "\$db_settings['host'] = \"".$db_settings['host']."\";\n";
				$SetCont .= "\$db_settings['user'] = \"".$db_settings['user']."\";\n";
				$SetCont .= "\$db_settings['pw'] = \"".$db_settings['pw']."\";\n";
				$SetCont .= "\$db_settings['db'] = \"".$db_settings['db']."\";\n";
				$SetCont .= "\$db_settings['settings_table'] = \"".$db_settings['settings_table']."\";\n";
				$SetCont .= "\$db_settings['forum_table'] = \"".$db_settings['forum_table']."\";\n";
				$SetCont .= "\$db_settings['category_table'] = \"".$db_settings['category_table']."\";\n";
				$SetCont .= "\$db_settings['userdata_table'] = \"".$db_settings['userdata_table']."\";\n";
				$SetCont .= "\$db_settings['smilies_table'] = \"".$db_settings['smilies_table']."\";\n";
				$SetCont .= "\$db_settings['banlists_table'] = \"".$db_settings['banlists_table']."\";\n";
				$SetCont .= "\$db_settings['banned_ips_table'] = \"".$db_settings['banned_ips_table']."\";\n";
				$SetCont .= "\$db_settings['useronline_table'] = \"".$db_settings['useronline_table']."\";\n";
				$SetCont .= "\$db_settings['usersettings_table'] = \"".$db_settings['usersettings_table']."\";\n";
				$SetCont .= "\$db_settings['us_templates_table'] = \"".$db_settings['us_templates_table']."\";\n";
				$SetCont .= "\$db_settings['usersubscripts_table'] = \"".$db_settings['usersubscripts_table']."\";\n";
				$SetCont .= "?>";
				# Start: debug output
#				$output .= '<pre>';
#				$output .= htmlspecialchars(print_r($SetCont, true));
#				$output .= '</pre>';
				# End: debug output

				$db_settings_file = @fopen("db_settings.php", "w") or $errors[] = str_replace("CHMOD",$chmod,$lang_add['no_writing_permission']);
				flock($db_settings_file, 2);
				fwrite($db_settings_file, $SetCont);
				flock($db_settings_file, 3);
				fclose($db_settings_file);
				} # End: if (empty($errors) and empty($_POST['dont_overwrite_settings']))
			# update procedure
			if (empty($errors))
				{
				switch($oldVersionShort)
					{
					case 1.7:
						$errors = update17to18($oldSettings, $connid);
						if ($errors === false) {
							$output .= '<p>errors ist <code>false</code>.</p>';
							unset($errors);
							}
						else {
							$output .= '<p>errors ist nicht <code>false</code>.</p>';
							}
					break;
					default:
						$errors[] = $lang_add['version_not_supported'];
					break;
					} # End: switch($oldVersion)
				} # End: structure update procedure
			# structure update was successful, set $scriptStatus to 2
			if (empty($errors))
				{
				# the update was successful
				# the database tables was updated, new settings are saved
				$scriptStatus = 2;
				$output .= $statusOutput[2];
				}
			else
				{
				# an error occured while the update process
				$output .= $statusOutput[4];
				if (!empty($errors) and is_array($errors))
					{
					$output .= errorMessages($errors);
					}
				}
			} # End: if (isset($_POST['form_submitted']))
		else
			{
			# the form was not submitted, standard output with the initial form
			$passLength = strlen($db_settings['pw']);
			$prefix = substr($db_settings['settings_table'], 0, -8);
			$passWord = str_repeat("*", $passLength);
			$output .= '<p>'.$lang_add['update_instructions'].'</p>'."\n";
			$output .= '<h2>'.$lang_add['update_current_dbsettings'].'</h2>'."\n";
			$output .= '<table class="admintab">'."\n";
			$output .= ' <tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add['inst_db_host'].'<br />'."\n";
			$output .= '  <span class="small">'.$lang_add['inst_db_host_d'].'</span></td>'."\n";
			$output .= '  <td class="description">'.$db_settings['host'].'</td>'."\n";
			$output .= ' </tr><tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add['inst_db_name'].'<br />'."\n";
			$output .= '  <span class="small">'.$lang_add['inst_db_name_d'].'</span></td>'."\n";
			$output .= '  <td class="description">'.$db_settings['db'].'</td>'."\n";
			$output .= ' </tr><tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add['inst_db_user'].'<br />'."\n";
			$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>'."\n";
			$output .= '  <td class="description">'.$db_settings['user'].'</td>'."\n";
			$output .= ' </tr><tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add['inst_db_pw'].'<br />'."\n";
			$output .= '  <span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>'."\n";
			$output .= '  <td class="description">'.$passWord.'</td>'."\n";
			$output .= ' </tr><tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add['inst_table_prefix'].'<br />'."\n";
			$output .= '  <span class="small">'.$lang_add['inst_table_prefix_d'].'</span></td>'."\n";
			$output .= '  <td class="description">'.$prefix.'</td>'."\n";
			$output .= ' </tr>'."\n";
			$output .= '</table>'."\n";
			$output .= '<h2>'.$lang_add['update_current_dbtables'].'</h2>'."\n";
			$output .= '<ol>'."\n";
			foreach ($db_settings as $key => $val) {
				$found = strpos($key, '_table');
				if ($found !== false) {
					$output .= ' <li>'.$val.'</li>'."\n";
					}
				}
			$output .= '</ol>'."\n";
			if ($oldVersionShort < $settings['version'])
				{
				$output .= '<h2>'.$lang_add['update_new_dbtables'].'</h2>'."\n";
				if ($settings['version'] == "1.8")
					{
						$output .= '<p>'.$lang_add['update_news_message_1.8'].'</p>';
					}
				}
			$output .= '<form action="update.php" method="post">'."\n";
			$output .= '<table class="admintab">'."\n";
			$output .= ' <tr>'."\n";
			$output .= '  <td class="definition">'.$lang_add["delete_2char_smilies"].'<br />'."\n";
			$output .= '  <span class="small">'.$lang_add['delete_2char_smilies_d'].'</td>'."\n";
			$output .= '  <td class="description">'."\n";
			$output .= '   <input type="radio" id="DeleteSmilies" name="DeleteSmilies" value="delete" checked="checked" /><label for="DeleteSmilies">'.$lang['yes'].'</label>'."\n";
			$output .= '   <input type="radio" id="StoreSmilies" name="DeleteSmilies" value="store" /><label for="StoreSmilies">'.$lang['no'].'</label></td>'."\n";
			$output .= ' </tr>'."\n";
			$output .= '</table>'."\n";
#			$output .= '<ul>';
#			$output .= '<li><input type="checkbox" name="dont_overwrite_settings" value="true"';
#			$output .= isset($_POST['dont_overwrite_settings']) ? ' checked="checked"' : '';
#			$output .= '>'.$lang_add['dont_overwrite_settings'].'</li>';
#			$output .= '</ul>';
			$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_update_ok'].'" /></p>'."\n";
			$output .= '<input type="hidden" name="language" value="'.$lang['language'].'" />'."\n";
			$output .= '<input type="hidden" name="installation_mode" value="update" />'."\n";
			$output .= '<input type="hidden" name="table_prefix" value="'.$prefix.'" />'."\n";
			$output .= '</form>'."\n";
			} # End: if (isset($_POST['form_submitted'])) (else)
		} # End: if (!is_array($oldSettings)) (else)
	} # End: if ($connid === false) (else)

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $settings['forum_name']." - ".$lang_add['install_title']; ?></title>
<style type="text/css">

body                {
	font-family: sans-serif;
	color: #000000;
	font-size: 100.01%;
	background-color: #fff;
	margin: 0;
	padding: 0;
	}
#content {
	margin: 1em;
	padding: 0;
	}
h1                  {
	margin: 0 0 20px 0;
	font-size: 1.4em;
	font-weight: bold;
	}
h2                  {
	margin: 0 0 20px 0;
	font-size: 1.3em;
	font-weight: bold;
	}
table.admintab      {
	border: 1px solid #bacbdf;
	border-collapse: collapse;
	min-width:600px;
	}
table.admintab td {
	background-color: #f5f5f5;
	width: 40%;
	padding: 4px;
	vertical-align: top;
	border-right: 1px dotted #bacbdf;
	border-bottom: 1px solid #bacbdf;
	}
table.admintab td:nth-child(n+2):nth-child(n+2) {
	background-color: #f8f8f8;
	width: 60%;
	border-right: none;
	}
table.admintab td.definition {
	font-weight: bold;
	}
table.admintab td.definition .small {
	font-weight: normal;
	}
.caution            { color: red; font-weight: bold; }
.small              { font-size: 0.86em; line-height:150%; }
a:link              { color: #0000cc; text-decoration: none; }
a:visited           { color: #0000cc; text-decoration: none; }
a:focus, a:hover    { color: #0000ff; text-decoration: underline; }
a:active            { color: #ff0000; text-decoration: none; }

</style>
</head>
<body>
<div id="content">
<h1><?php echo $lang_add['installation_mode_update']; ?></h1>
<?php
#if (!empty($errors) and is_array($errors)) {
#	echo errorMessages($errors);
#	}
echo $output;
?>
</div>
</body>
</html>
