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



/**
Vorgehen:

$scriptStatus = 0;
Beginn des Updatprozesses
- Prüfung, ob alte Einstellungen verfügbar sind (DB-Verbindung, Settings)
  - nein: $scriptStatus = 1;
  - ja: Update der Tabellenstruktur, einfügen neuer Werte; $scriptStatus = 2;
DONE

$scriptStatus = 1;
Abbruch des Updates (keine DB-Verbindung), keine Settings
- Link zum Installationsskript

$scriptStatus = 2;
Update der vorhandenen Daten (update_content.php)
- neue Tabelle zur Kontrolle des Updates anlegen
  - erste 100 Datensätze in einer Schleife einlesen, anpassen und neu speichern
  - IDs dieser Datensätze in die Updatetabelle eintragen
  - $scriptStatus = 3;
  - Skript mit Anzeige der Anzahl der bereits veränderten Daten und der
    noch zu bearbeitenden Daten erneut aufrufen; $scriptStaus bleibt bei 3
  - Sind alle Daten transformiert: $scriptStatus = 4;

$scriptStatus = 4;
Updateprozess beendet
*/

# number of the new version
$newVersion = "1.8";
# empty error array
$errors = array();
# status of the script
# 0: initialization status, start of the update procedure
# 1: no connect to the database or no data of an existing forum-DB found
# 2: initial data sended, update of database table structure successful
# 3: update process of data (update_content.php)
# 4: update complete (update_content.php)
$scriptStatus = 0;
$statusOutput[1] = "<p>".$lang_add['db_read_settings_error']."</p>
<p>The forum seems not to be installed. Please control the existence and name of the settings table or switch to <a href=\"install.php\">Installation</a>.</p>";
$statusOutput[2] = "<p>Database tables have been altered, new tables are created. Now it is time to actualise the  some data.</p>";
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
#	$error = 'db_'.$sql["errnbr"];
	$errors[] = "MySQL error: ".$sql["errnbr"]."\n".$lang_add['db_'.$sql["errnbr"]];
	# status: no connection to database
	$scriptStatus = 1;
	$output .= "<p>The script can not contact the database server. The username, password or database name seems to be wrong.</p>";
	}
else
	{
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
		# include files for the language wich is stored in the old settings
		include("lang/".$oldSettings['language_file'] );
		include("lang/".$lang['additional_language_file']);
		# List all versions wich can be updated.
		$updateVersions = array("1.7");
		# the form was submitted
		if (isset($_POST['form_submitted']))
			{
			# all fields filled out?
			foreach ($_POST as $postKey=>$postVal)
				{
				$postVal = trim($postVal);
				if (empty($postVal)) { $errors[] = $lang['error_form_uncompl']; break; }
				}
			# try to connect the database with posted access data:
			if (empty($errors))
				{
				# Umstellung auf meine SQL-Funktionen
				$tempDBConnData = array('host'=>$_POST['host'],'user'=>$_POST['user'],'pw'=>$_POST['pw'],'db'=>$_POST['db']);
				$tempSQL = auge_connect_db($tempDBConnData);
				if ($tempSQL['status'] === false)
					{
					# wrong given data
					# generate error message
#					$error = 'db_'.$tempSQL["errnbr"];
					$errors[] = "MySQL error: ".$tempSQL["errnbr"]."\n".$lang_add['db_'.$tempSQL["errnbr"]];
					# status: no connection to database
					# because wrong given data from the form
					$scriptStatus = 1;
					}
				else
					{
					$connid = $tempSQL['status'];
					}
				}
			# overwrite database settings file:
			if (empty($errors) and empty($_POST['dont_overwrite_settings']))
				{
				clearstatcache();
				$chmod = decoct(fileperms("db_settings.php"));

				$db_settings['host'] = $_POST['host'];
				$db_settings['user'] = $_POST['user'];
				$db_settings['pw'] = $_POST['pw'];
				$db_settings['db'] = $_POST['db'];
				$db_settings['settings_table'] = $_POST['table_prefix'].'settings';
				$db_settings['forum_table'] = $_POST['table_prefix'].'entries';
				$db_settings['category_table'] = $_POST['table_prefix'].'categories';
				$db_settings['userdata_table'] = $_POST['table_prefix'].'userdata';
				$db_settings['smilies_table'] = $_POST['table_prefix'].'smilies';
				$db_settings['banlists_table'] = $_POST['table_prefix'].'banlists';
				$db_settings['useronline_table'] = $_POST['table_prefix'].'useronline';
				$db_settings['usersettings_table'] = $_POST['table_prefix'].'usersettings';
				$db_settings['us_templates_table'] = $_POST['table_prefix'].'fu_settings';
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
				$SetCont .= "\$db_settings['useronline_table'] = \"".$db_settings['useronline_table']."\";\n";
				$SetCont .= "\$db_settings['usersettings_table'] = \"".$db_settings['usersettings_table']."\";\n";
				$SetCont .= "\$db_settings['us_templates_table'] = \"".$db_settings['us_templates_table']."\";\n";
				$SetCont .= "?>";

				$db_settings_file = @fopen("db_settings.php", "w") or $errors[] = str_replace("CHMOD",$chmod,$lang_add['no_writing_permission']);
				flock($db_settings_file, 2);
				fwrite($db_settings_file, $SetCont);
				flock($db_settings_file, 3);
				fclose($db_settings_file);
				} # End: if (empty($errors) and empty($_POST['dont_overwrite_settings']))
			# search version number of the old forum version:
			if (empty($oldSettings['version']))
				{
				# the old setting for version string is not present
				$errors[] = $lang_add['no_version_found'];
#				$select_version = true;
				}
			else
				{
				# found string for old version
				$versionCompare = false;
				# shorten version string to one digit after first point
				$oldVersionShort = substr($oldSettings['version'],0,2);
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
					$oldShort = $oldVersionShort;
					}
				} # End: search version number of old forum
			# update procedure
			if (empty($errors))
				{
				switch($oldShort)
					{
					case 1.7:
						$errors = update17to18($oldSettings, $connid);
						if ($errors === false) unset($errors);
					break;
					default:
						$errors[] = $lang_add['version_not_supported'];
					break;
					} # End: switch($oldVersion)
				} # End: structure update procedure
			# structure update was successful, set $scriptStatus to 2
			if (empty($errors))
				{
				# step1 of the update was successful
				# the database tables was updated, new settings are saved
				$scriptStatus = 2;
				$output .= $statusOutput[2];
				}
			else
				{
				# an error occured while the update process
				$output .= $statusOutput[4];
				if (isset($errors) and is_array($errors))
					{
					$output .= errorMessages($errors);
					}
				}
			} # End: if (isset($_POST['form_submitted']))
		else
			{
			# the form was not submitted, standard output with the initial form
			$output .= '<p>'.$lang_add['update_instructions'].'</p>';
			$output .= '<form action="update.php" method="post">';
			$output .= '<p><input type="checkbox" name="DeleteSmilies" value="delete" selected="selected" />'.$lang_add["delete_2char_smilies"].'<br /><span>'.$lang_add['delete_2char_smilies_d'].'</span></p>';
			$output .= '<fieldset>';
			$output .= '<legend>'.$lang_add['inst_db_settings'].'</legend>';
#			$output .= '<ul>';
#			$output .= '<li><input type="checkbox" name="dont_overwrite_settings" value="true"';
#			$output .= isset($_POST['dont_overwrite_settings']) ? ' checked="checked"' : '';
#			$output .= '>'.$lang_add['dont_overwrite_settings'].'</li>';
#			$output .= '</ul>';
			$output .= '<table class="admintab">';
			$output .= '<tr>';
			$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_host'].'</b><br />';
			$output .= '<span class="small">'.$lang_add['inst_db_host_d'].'</span></td>';
			$output .= '<td class="admintab-r"><input type="text" name="host" value="';
			$output .= isset($_POST['host']) ? htmlspecialchars($_POST['host']) : $db_settings['host'];
			$output .= '" size="40" /></td>';
			$output .= '</tr><tr>';
			$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_name'].'</b><br />';
			$output .= '<span class="small">'.$lang_add['inst_db_name_d'].'</span></td>';
			$output .= '<td class="admintab-r"><input type="text" name="db" value="';
			$output .= isset($_POST['db']) ? htmlspecialchars($_POST['db']) : $db_settings['db'];
			$output .= '" size="40" /></td>';
			$output .= '</tr><tr>';
			$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_user'].'</b><br />';
			$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>';
			$output .= '<td class="admintab-r"><input type="text" name="user" value="';
			$output .= isset($_POST['user']) ? htmlspecialchars($_POST['user']) : $db_settings['user'];
			$output .= '" size="40" /></td>';
			$output .= '</tr><tr>';
			$output .= '<td class="admintab-l"><b>'.$lang_add['inst_db_pw'].'</b><br />';
			$output .= '<span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>';
			$output .= '<td class="admintab-r"><input type="password" name="pw" value="';
			$output .= isset($_POST['pw']) ? htmlspecialchars($_POST['pw']) : $db_settings['pw'];
			$output .= '" size="40" /></td>';
			$output .= '</tr>';
			$output .= '</table>';
			$output .= '</fieldset>';
			$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_update_ok'].'" /></p>';
			$output .= '<input type="hidden" name="language" value="'.$language.'" />';
			$output .= '<input type="hidden" name="installation_mode" value="update" />';
			$output .= '</form>';
			} # End: if (isset($_POST['form_submitted'])) (else)
		# Abfrage des gegenwärtigen $scriptSatus (0 in else)
		if ($scriptStatus == 1)
			{
			# Fehlermeldung ausgeben
			$output .= "";
			}
		else if ($scriptStatus == 2)
			{
			}
		} # End: if (!is_array($oldSettings)) (else)
	} # End: if ($connid === false) (else)

#$table_prefix = 'forum_';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $settings['forum_name']." - ".$lang_add['install_title']; ?></title>
<style type="text/css">
<!--
body                { font-family: sans-serif; color: #000000; font-size:13px; background-color: #fff; margin: 0px; padding: 0px; }
h1                  { margin: 0px 0px 20px 0px; font-size: 18px; font-weight: bold; }
h2                  { margin: 0px 0px 20px 0px; font-size: 15px; font-weight: bold; }
table.admintab      { border: 1px solid #bacbdf; }
td.admintab-hl      { width: 100%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #d2ddea; }
td.admintab-hl h2   { margin: 3px 0px 3px 0px; font-size: 15px; font-weight: bold; }
td.admintab-hl p    { font-size: 11px; line-height: 16px; margin: 0px 0px 3px 0px; padding: 0px; }
td.admintab-l       { width: 50%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #f5f5f5; }
td.admintab-r       { width: 50%; vertical-align: top; font-family: verdana, arial, sans-serif; font-size: 13px; background: #f5f5f5; }
.caution            { color: red; font-weight: bold; }
.small              { font-size: 11px; line-height:16px; }
a:link              { color: #0000cc; text-decoration: none; }
a:visited           { color: #0000cc; text-decoration: none; }
a:hover             { color: #0000ff; text-decoration: underline; }
a:active            { color: #ff0000; text-decoration: none; }
-->
</style>
</head>
<body>
<div>
<h1><?php echo $lang_add['install_title']; ?></h1>
<?php echo $output; ?>
</div>
</body>
</html>
