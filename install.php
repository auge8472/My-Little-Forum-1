<?php

include_once('functions/include.install.php');

$version = false;
$table_prefix = 'mlf1_';

if (isset($_POST['language']))
	{
	$language = $_POST['language'];
	$language_file = $language;
	}
else
	{
	$language_file = 'english.php';
	}

include("lang/".$language_file);
include("lang/".$lang['additional_language_file']);
include("db_settings.php");

unset($errors);

if (isset($_POST['form_submitted']))
	{
	// all fields filled out?
	foreach ($_POST as $post)
		{
		if (trim($post) == "")
			{
			$errors[] = $lang['error_form_uncompl'];
			break;
			}
		}

	if (empty($errors)
		and (!empty($installation_mode) and $installation_mode=='installation'))
		{
		if ($_POST['admin_pw'] != $_POST['admin_pw_conf']) $errors[] = $lang_add['inst_pw_conf_error'];
		}

	// try to connect the database with posted access data:
	if (empty($errors))
		{
		$connid = @mysql_connect($_POST['host'], $_POST['user'], $_POST['pw']);
		if (!$connid) $errors[] = $lang_add['db_connection_error']." (MySQL: ".mysql_errno()."<br />".mysql_error().")";
		}
	// overwrite database settings file:
	if (empty($errors) && empty($_POST['dont_overwrite_settings']))
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
		$db_settings['banned_ips_table'] = $_POST['table_prefix'].'banned_ips';
		$db_settings['useronline_table'] = $_POST['table_prefix'].'useronline';
		$db_settings['usersettings_table'] = $_POST['table_prefix'].'usersettings';
		$db_settings['us_templates_table'] = $_POST['table_prefix'].'fu_settings';
		$db_settings['usersubscripts_table'] = $_POST['table_prefix'].'subscripts';
		# content of db_settings.php
		$fileSettingsContent  = "<?php\n";
		$fileSettingsContent .= "\$db_settings['host'] = \"".$db_settings['host']."\";\n";
		$fileSettingsContent .= "\$db_settings['user'] = \"".$db_settings['user']."\";\n";
		$fileSettingsContent .= "\$db_settings['pw'] = \"".$db_settings['pw']."\";\n";
		$fileSettingsContent .= "\$db_settings['db'] = \"".$db_settings['db']."\";\n";
		$fileSettingsContent .= "\$db_settings['settings_table'] = \"".$db_settings['settings_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['forum_table'] = \"".$db_settings['forum_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['category_table'] = \"".$db_settings['category_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['userdata_table'] = \"".$db_settings['userdata_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['smilies_table'] = \"".$db_settings['smilies_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['banlists_table'] = \"".$db_settings['banlists_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['banned_ips_table'] = \"".$db_settings['banned_ips_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['useronline_table'] = \"".$db_settings['useronline_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['usersettings_table'] = \"".$db_settings['usersettings_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['us_templates_table'] = \"".$db_settings['us_templates_table']."\";\n";
		$fileSettingsContent .= "\$db_settings['usersubscripts_table'] = \"".$db_settings['usersubscripts_table']."\";\n";
		$fileSettingsContent .= "?>";

		$db_settings_file = @fopen("db_settings.php", "w") or $errors[] = str_replace("CHMOD",$chmod,$lang_add['no_writing_permission']);
		flock($db_settings_file, LOCK_EX);
		fwrite($db_settings_file, $fileSettingsContent);
		flock($db_settings_file, LOCK_UN);
		fclose($db_settings_file);
		}

	if (empty($errors))
		{
		# create database if desired:
		if (isset($_POST['create_database']))
			{
			@mysql_query("CREATE DATABASE ".$db_settings['db'], $connid) or $errors[] = $lang_add['db_create_db_error'] ." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
			}

		# select database:
		if (empty($errors))
			{
			@mysql_select_db($db_settings['db'], $connid) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
			}

		# create tables:
		if (empty($errors))
			{
			# create settings table
			$table["settings"]["name"] = $db_settings['settings_table'];
			$table["settings"]["query"] = "CREATE TABLE ".$db_settings['settings_table']." (
			name varchar(255) NOT NULL default '',
			value varchar(255) NOT NULL default '',
			type varchar(30) NOT NULL default '',
			poss_values varchar(160) NOT NULL default '',
			standard varchar(80) NOT NULL default '',
			cat varchar(20) NOT NULL default '',
			UNIQUE KEY setting (name)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8";
			# create posting table
			$table["postings"]["name"] = $db_settings['forum_table'];
			$table["postings"]["query"] = "CREATE TABLE ".$db_settings['forum_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			pid int(11) unsigned NOT NULL default '0',
			tid int(11) unsigned NOT NULL default '0',
			uniqid varchar(255) NOT NULL default '',
			time datetime NOT NULL,
			last_answer timestamp NOT NULL default '0000-00-00 00:00:00',
			edited timestamp NOT NULL default '0000-00-00 00:00:00',
			edited_by varchar(255) NOT NULL default '',
			user_id int(11) unsigned default '0',
			name varchar(255) NOT NULL default '',
			subject varchar(255) NOT NULL default '',
			category int(11) unsigned NOT NULL default '0',
			email varchar(255) NOT NULL default '',
			hp varchar(255) NOT NULL default '',
			place varchar(255) NOT NULL default '',
			ip_addr int(10) unsigned NOT NULL default '0', 
			text text NOT NULL,
			show_signature tinyint(1) unsigned default '0',
			email_notify tinyint(1) unsigned default '0',
			marked tinyint(1) unsigned default '0',
			locked tinyint(1) unsigned default '0',
			fixed tinyint(1) unsigned default '0',
			views int(11) unsigned default '0',
			PRIMARY KEY (id),
			KEY tid (tid),
			KEY category (category),
			KEY pid (pid),
			KEY fixed (fixed)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create category table
			$table["category"]["name"] = $db_settings['category_table'];
			$table["category"]["query"] = "CREATE TABLE ".$db_settings['category_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			category_order int(11) unsigned NOT NULL,
			category varchar(255) NOT NULL default '',
			description varchar(255) NOT NULL default '',
			accession tinyint(4) unsigned NOT NULL default '0',
			PRIMARY KEY (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create userdata table
			$table["userdata"]["name"] = $db_settings['userdata_table'];
			$table["userdata"]["query"] = "CREATE TABLE ".$db_settings['userdata_table']." (
			user_id int(11) unsigned NOT NULL auto_increment,
			user_type varchar(255) NOT NULL default '',
			user_name varchar(255) NOT NULL default '',
			user_real_name varchar(255) NOT NULL default '',
			user_pw varchar(255) NOT NULL default '',
			user_email varchar(255) NOT NULL default '',
			hide_email tinyint(1) unsigned default '0',
			user_hp varchar(255) NOT NULL default '',
			user_place varchar(255) NOT NULL default '',
			signature varchar(255) NOT NULL default '',
			profile text NOT NULL,
			logins int(11) unsigned NOT NULL default '0',
			last_login timestamp(14) NOT NULL,
			last_logout timestamp(14) NOT NULL,
			ip_addr int(10) unsigned NOT NULL default '0', 
			registered timestamp(14) NOT NULL,
			user_view varchar(255) NOT NULL default '',
			new_posting_notify tinyint(1) unsigned default '0',
			new_user_notify tinyint(1) unsigned default '0',
			personal_messages tinyint(1) unsigned default '0',
			time_difference tinyint(4) unsigned default '0',
			user_lock tinyint(1) unsigned default '0',
			pwf_code varchar(255) NOT NULL default '',
			activate_code varchar(255) NOT NULL default '',
			PRIMARY KEY (user_id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create smilies table
			$table["smilies"]["name"] = $db_settings['smilies_table'];
			$table["smilies"]["query"] = "CREATE TABLE ".$db_settings['smilies_table']." (
			id smallint(5) unsigned NOT NULL auto_increment,
			order_id smallint(5) unsigned NOT NULL default '0',
			file varchar(100) NOT NULL,
			code_1 varchar(30) NOT NULL default '',
			code_2 varchar(30) NOT NULL default '',
			code_3 varchar(30) NOT NULL default '',
			code_4 varchar(30) NOT NULL default '',
			code_5 varchar(30) NOT NULL default '',
			title varchar(255) NOT NULL,
			PRIMARY KEY (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create banlist table
			$table["banlists"]["name"] = $db_settings['banlists_table'];
			$table["banlists"]["query"] = "CREATE TABLE ".$db_settings['banlists_table']." (
			name varchar(255) NOT NULL default '',
			list text NOT NULL
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create banned IPs table
			$table["banned_ips"]["name"] = $db_settings['banned_ips_table'];
			$table["banned_ips"]["query"] = "CREATE TABLE ".$db_settings['banned_ips_table']." (
			ip int(10) unsigned NOT NULL DEFAULT '0',
			last_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			requests tinyint(1) unsigned NOT NULL DEFAULT '0',
			UNIQUE KEY ip (ip)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			#create useronline table
			$table["useronline"]["name"] = $db_settings['useronline_table'];
			$table["useronline"]["query"] = "CREATE TABLE ".$db_settings['useronline_table']." (
			ip_addr int(10) NOT NULL default '0',
			requestdate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			user_id int(11) unsigned default '0'
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			$table["usersettings"]["name"] = $db_settings['usersettings_table'];
			$table["usersettings"]["query"] = "CREATE TABLE ".$db_settings['usersettings_table']." (
			user_id int(12) unsigned NOT NULL,
			name varchar(60) NOT NULL default '',
			value varchar(40) NOT NULL default '',
			PRIMARY KEY  (user_id,name)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			$table["us_template"]["name"] = $db_settings['us_templates_table'];
			$table["us_template"]["query"] = "CREATE TABLE ".$db_settings['us_templates_table']." (
			name varchar(60) NOT NULL,
			value varchar(40) NOT NULL,
			type enum('string','bool') NOT NULL default 'string'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			$table["usersubscripts"]["name"] = $db_settings['usersubscripts_table'];
			$table["usersubscripts"]["query"] = "CREATE TABLE ".$db_settings['usersubscripts_table']." (
			user_id int(12) unsigned NOT NULL,
			tid int(12) unsigned NOT NULL,
			UNIQUE KEY user_thread (user_id,tid)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			foreach ($table as $tbl)
				{
				$ret = mysql_query($tbl["query"], $connid);
				if ($ret === false)
					{
					$errors[] = str_replace("[table]",$tbl['name'],$lang_add['db_create_table_error']) ." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
					}
				}
			}

		# insert admin in userdata table:
		if (empty($errors))
			{
			$fillUserdata = "INSERT INTO ".$db_settings['userdata_table']." SET
			user_type = 'admin',
			user_name = '". mysql_real_escape_string($_POST['admin_name']) ."',
			user_real_name = '',
			user_pw = '". md5(trim($_POST['admin_pw'])) ."',
			user_email = '". mysql_real_escape_string($_POST['admin_email']) ."',
			hide_email = '1',
			profile = '',
			logins = 1,
			ip_addr = INET_ATON('". mysql_real_escape_string($_SERVER["REMOTE_ADDR"]) ."'),
			registered = NOW(),
			user_view = '". mysql_real_escape_string($settings['standard']['standard']) ."',
			personal_messages = '1'";
			@mysql_query($fillUserdata, $connid) or $errors[] = $lang_add['db_insert_admin_error']." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
			}

		# insert settings in settings table:
		if (empty($errors))
			{
			# insert default settings:
			while(list($key, $val) = each($settings))
				{
				if (!empty($_POST['forum_name']) and $key == 'forum_name')
					{
					$val['value'] = $_POST['forum_name'];
					}
				else if (!empty($_POST['forum_address']) and $key == 'forum_address')
					{
					$val['value'] = $_POST['forum_address'];
					}
				else if (!empty($_POST['forum_email']) and $key == 'forum_email')
					{
					$val['value'] = $_POST['forum_email'];
					}
				else if (!empty($_POST['language']) and $key == 'language_file')
					{
					$val['value'] = $_POST['language'];
					}
				else
					{
					$val['value'] = $val['standard'];
					}
				$loopSettings[] = "('". mysql_real_escape_string($key) ."', '". mysql_real_escape_string($val['value']) ."', '". mysql_real_escape_string($val['type']) ."', '". mysql_real_escape_string($val['poss_values']) ."', '". mysql_real_escape_string($val['standard']) ."', '". mysql_real_escape_string($val['cat']) ."')";
				}
			if (is_array($loopSettings))
				{
#				foreach($loopSettings as $setz) {
#					echo "<pre>". print_r($setz, true) ."</pre>\n";
#					}
				$loopSettings = implode(", ", $loopSettings);
				$fillSettings = "INSERT INTO ". $db_settings['settings_table'] ."
				(name, value, type, poss_values, standard, cat)
				VALUES
				". $loopSettings;
				@mysql_query($fillSettings, $connid) or $errors[] = str_replace("[setting]",$fillSettings,$lang_add['db_insert_settings_error']) ." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
				}
			}

		# insert smilies in smilies table:
		if (empty($errors))
			{
			$order_id = 1;
			foreach($smilies as $smiley)
				{
				$loopSmiley[] = "(". intval($order_id) .", '". mysql_real_escape_string($smiley[0]) ."', '". mysql_real_escape_string($smiley[1]) ."', '". mysql_real_escape_string($smiley[2]) ."', '". mysql_real_escape_string($smiley[3]) ."', '". mysql_real_escape_string($smiley[4]) ."', '". mysql_real_escape_string($smiley[5]) ."', '". mysql_real_escape_string($smiley[6]) ."')";
				$order_id++;
				}
			if (is_array($loopSmiley))
				{
				$loopSmiley = implode(", ", $loopSmiley);
				$fillSmiley = "INSERT INTO ". $db_settings['smilies_table'] ."
				(order_id, file, code_1, code_2, code_3, code_4, code_5, title)
				VALUES
				". $loopSmiley;
				@mysql_query($fillSmiley, $connid) or $errors[] = str_replace("[setting]", $db_settings['smilies_table'], $lang_add['db_insert_settings_error']) ." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
				}
			}

		# insert banlists:
		if (empty($errors))
			{
			$templateBanlist = array("users", "words");
			foreach ($templateBanlist as $val)
				{
				$loopBanlist[] = "('". mysql_real_escape_string($val) ."')";
				}
			if (is_array($loopBanlist))
				{
				$loopBanlist = implode(", ", $loopBanlist);
				$fillBanlist = "INSERT INTO ". $db_settings['banlists_table'] ."
				(name)
				VALUES
				". $loopBanlist;
				@mysql_query($fillBanlist, $connid) or $errors[] = str_replace("[setting]",$db_settings['banlists_table'],$lang_add['db_insert_settings_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
				}
			}

		# insert possible usersettings
		if (empty($errors))
			{
			foreach ($usersettings as $us)
				{
				$fillUserSetting = "INSERT INTO ".$db_settings['us_templates_table']." SET
				name = '". mysql_real_escape_string($us['name']) ."',
				value = '". mysql_real_escape_string($us['value']) ."',
				type = '". mysql_real_escape_string($us['type']) ."'";
				@mysql_query($fillUserSetting, $connid) or $errors[] = str_replace("[setting]",$db_settings['us_templates_table'], $lang_add['db_insert_settings_error']) ." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
				# empty $fillBanlist for the next loop
				$fillUserSetting = "";
				}
			}
		# still no errors, so the installation should have been successful!
		if(empty($errors)) $installed = true;
		}
	} # End: if (isset($_POST['form_submitted']))
else
	{
	if (!empty($db_settings['host'])
		and !empty($db_settings['user'])
		and !empty($db_settings['pw'])
		and !empty($db_settings['db']))
		{
		$connid = @mysql_connect($db_settings['host'], $db_settings['user'], $db_settings['pw']);
		if (!$connid) $errors[] = $lang_add['db_connection_error']." (MySQL: ". mysql_errno() ."<br />". mysql_error() .")";
		else $db_selected = mysql_select_db($db_settings['db'], $connid);
		if ($db_selected === true)
			{
			$test = mysql_query("SHOW TABLES LIKE '%settings'", $connid);
			if (mysql_num_rows($test) > 0)
			$checkQuery = "SELECT
			value AS installed_version
			FROM ".$db_settings['settings_table']."
			WHERE name = 'version'";
			$versionResult = mysql_query($checkQuery, $connid);
			if ($versionResult === false)
				{
				$errors[] = $lang_add['db_read_settings_error'] ." [Version] (MySQL: ". mysql_errno() ."<br />". mysql_error() .")";
				$version = false;
				}
			else
				{
				$version = mysql_fetch_row($versionResult, $connid);
				}
			}
		else
			{
			# $db_selected === false
			$errors[] = $lang_add['db_connection_error'] ." (MySQL: ". mysql_errno() ."<br />". mysql_error() .")";
			}
		}
	}

# Generierung der Ausgabe
$output  = "";

if (empty($installed))
	{
	if ($version !== false and !empty($version))
		{
		# forum is installed, provide the link to update.php
		$output .= '<h2>'.$lang_add['installation_mode_update'].'</h2>'."\n";
		$output .= '<p>'.$lang_add['select_version'].': '.$version['installed_version'].'</p>'."\n";
		if (floatval(substr($version['installed_version'],0,2)) < 1.7)
			{
			$output .= '<p>'.$lang_add['version_not_supported'].'</p>'."\n";
			}
		else
			{
			$output .= '<p><a href="update.php">'.$lang_add['forum_update_ok'].'</a></p>'."\n";
			}
		}
	else if (empty($language))
		{
		$handle = opendir('./lang/');
		while ($file = readdir($handle))
			{
			if (strrchr($file, ".") == ".php" && strrchr($file, "_") != "_add.php")
				{
				$languageFile[] = $file;
				}
			}
		closedir($handle);
		$output .= '<h2>'.$lang_add['language_file_inst'].'</h2>'."\n";
		$output .= '<form action="install.php" method="post">'."\n";
		$output .= '<select name="language" size="1">'."\n";
		foreach ($languageFile as $langFile)
			{
			$output .= '<option value="'.$langFile.'"';
			$output .= ($language_file == $langFile) ? ' selected="selected"' : '';
			$output .= '>'. htmlspecialchars(ucfirst(str_replace(".php", "", $langFile))) .'</option>'."\n";
			}
		$output .= '</select>'."\n";
		$output .= '<input type="submit" value="'. $lang['submit_button_ok'] .'" /></p>'."\n";
		$output .= '</form>'."\n";
		}
	else
		{
		$output .= '<h2>'.$lang_add['installation_instructions'].'</h2>'."\n";
		if (isset($errors))
			{
			$output .= errorMessages($errors);
			}
		$output .= '<form action="install.php" method="post">'."\n";
		$output .= '<fieldset>'."\n";
		$output .= '<legend>'.$lang_add['inst_basic_settings'].'</legend>'."\n";
		$output .= '<p>'.$lang_add['inst_main_settings_d'].'</p>'."\n";
		$output .= '<table class="admintab">'."\n";
		$output .= '<tr>'."\n";
		$output .= '<td class="admintab-l"><label for="forum-name">'.$lang_add['forum_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_name_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="forum_name" value="';
		if (!empty($_POST['forum_name']))
			{
			$output .= htmlspecialchars($_POST['forum_name']);
			}
		else
			{
			$output .= '';
			}
		$output .= '" size="40" id="forum-name" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="forum-address">'.$lang_add['forum_address'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_address_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="forum_address" value="';
		if (!empty($_POST['forum_address']))
			{
			$output .= htmlspecialchars($_POST['forum_address']);
			}
		else
			{
			$output .= "http://".$_SERVER['SERVER_NAME']. str_replace("install.php","",$_SERVER['SCRIPT_NAME']);
			}
		$output .= '" size="40" id="forum-address" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="forum-email">'.$lang_add['forum_email'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_email_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="forum_email" value="';
		$output .= (isset($_POST['forum_email'])) ? htmlspecialchars($_POST['forum_email']) : "@";
		$output .= '" size="40" id="forum-email" /></td>'."\n";
		$output .= '</tr>'."\n";
		$output .= '</table>'."\n";
		$output .= '</fieldset>'."\n";
		$output .= '<fieldset>'."\n";
		$output .= '<legend>'.$lang_add['inst_admin_settings'].'</legend>'."\n";
		$output .= '<p>'.$lang_add['inst_admin_settings_d'].'</p>'."\n";
		$output .= '<table class="admintab">'."\n";
		$output .= '<tr>'."\n";
		$output .= '<td class="admintab-l"><label for="admin-name">'.$lang_add['inst_admin_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_name_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="admin_name" value="';
		$output .= (isset($_POST['admin_name'])) ? htmlspecialchars($_POST['admin_name']) : '';
		$output .= '" size="40" id="admin-name" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="admin-email">'.$lang_add['inst_admin_email'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_email_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="admin_email" value="';
		$output .= (isset($_POST['admin_email'])) ? htmlspecialchars($_POST['admin_email']) : "@";
		$output .= '" size="40" id="admin-email" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="admin-pw">'.$lang_add['inst_admin_pw'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_pw_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="password" name="admin_pw" value="" size="40" id="admin-pw" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="admin-pw-confirm">'.$lang_add['inst_admin_pw_conf'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_pw_conf_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="password" name="admin_pw_conf" value="" size="40" id="admin-pw-confirm" /></td>'."\n";
		$output .= '</tr>'."\n";
		$output .= '</table>'."\n";
		$output .= '</fieldset>'."\n";
		$output .= '<fieldset>'."\n";
		$output .= '<legend>'.$lang_add['inst_db_settings'].'</legend>'."\n";
		$output .= '<p>'.$lang_add['inst_db_settings_d'].'</p>'."\n";
		$output .= '<ul>'."\n";
		$output .= '<li><input type="checkbox" name="create_database" id="create-db-1" value="true"';
		$output .= (isset($_POST['create_database'])) ? ' checked="checked"' : '';
		$output .= ' /><label for="create-db-1">'.$lang_add['create_database'].'</label></li>'."\n";
		$output .= '<li><input type="checkbox" name="dont_overwrite_settings" id="create-db-0" value="true"';
		$output .= (isset($_POST['dont_overwrite_settings'])) ? ' checked="checked"' : '';
		$output .= '><label for="create-db-0">'.$lang_add['dont_overwrite_settings'].'</label></li>'."\n";
		$output .= '</ul>'."\n";
		$output .= '<table class="admintab">'."\n";
		$output .= '<tr>'."\n";
		$output .= '<td class="admintab-l"><label for="db-host">'.$lang_add['inst_db_host'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_host_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="host" value="';
		$output .= (isset($_POST['host'])) ? htmlspecialchars($_POST['host']) : $db_settings['host'];
		$output .= '" size="40" id="db-host" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="db-name">'.$lang_add['inst_db_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_name_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="db" value="';
		$output .= (isset($_POST['db'])) ? htmlspecialchars($_POST['db']) : $db_settings['db'];
		$output .= '" size="40" id="db-name" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="db-user">'.$lang_add['inst_db_user'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="user" value="';
		$output .= (isset($_POST['user'])) ? htmlspecialchars($_POST['user']) : $db_settings['user'];
		$output .= '" size="40" id="db-user" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="db-pass">'.$lang_add['inst_db_pw'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="password" name="pw" value="';
		$output .= (isset($_POST['pw'])) ? htmlspecialchars($_POST['pw']) : '';
		$output .= '" size="40" id="db-pass" /></td>'."\n";
		$output .= '</tr><tr>'."\n";
		$output .= '<td class="admintab-l"><label for="db-prefix">'.$lang_add['inst_table_prefix'].'</b><br />';
		$output .= '<span class="small">'.$lang_add['inst_table_prefix_d'].'</span></td>'."\n";
		$output .= '<td class="admintab-r"><input type="text" name="table_prefix" value="';
		$output .= (isset($_POST['table_prefix'])) ? htmlspecialchars($_POST['table_prefix']) : $table_prefix;
		$output .= '" size="40" id="db-prefix" /></td>'."\n";
		$output .= '</tr>'."\n";
		$output .= '</table>'."\n";
		$output .= '</fieldset>'."\n";
		$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_install_ok'].'" /></p>'."\n";
		$output .= '<input type="hidden" name="language" value="'.$language.'" />'."\n";
		$output .= '<input type="hidden" name="installation_mode" value="installation" />'."\n";
		$output .= '</form>'."\n";
		}
	}
else
	{
	$output .= '<p class="caution" style="background-image:url(http://www.mylittlehomepage.net/mylittleforum/install/x.gif);">'.$lang_add['installation_complete'].'</p>
	<p>'.$lang_add['installation_complete_exp'].'</p>
	<p><a href="index.php">'.$lang_add['installation_complete_link'].'</a></p>'."\n";
	}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $lang_add['install_title']; ?></title>
<style type="text/css">
<!--
body {
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
font-size:13px;
background-color: #fffff3;
margin: 0px;
padding: 20px;
}
h1 {
margin: 0px 0px 20px 0px;
font-size: 18px;
font-weight: bold;
}
table.admintab {
border: 1px solid #bacbdf;
}
td.admintab-hl {
width: 100%;
vertical-align: top;
font-size: 13px;
background: #d2ddea;
}
td.admintab-hl h2 {
margin: 3px 0px;
font-size: 15px;
font-weight: bold;
}
td.admintab-hl p {
font-size: 11px;
line-height: 16px;
margin: 0px 0px 3px 0px;
padding: 0px;
}
td.admintab-l {
width: 50%;
vertical-align: top;
font-size: 13px;
background: #f5f5f5;
}
td.admintab-r {
width: 50%;
vertical-align: top;
font-size: 13px;
background: #f5f5f5;
}
.caution {
color: red;
font-weight: bold;
}
.small {
font-size: 11px;
line-height:16px;
}
a:link, a:visited {
color: #0000cc;
text-decoration: none;
}
a:focus, a:hover {
color: #0000ff;
text-decoration: underline;
}
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
