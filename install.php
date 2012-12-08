<?php

include_once('functions/include.install.php');

$version = false;
$table_prefix = 'forum_';

if (isset($_POST['language']))
	{
	$language = $_POST['language'];
	$settings['language_file'] = $language;
	}

include("lang/".$settings['language_file'] );
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

	if (empty($errors) && $installation_mode=='installation')
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
		flock($db_settings_file, 2);
		fwrite($db_settings_file, $fileSettingsContent);
		flock($db_settings_file, 3);
		fclose($db_settings_file);
		}

	if (empty($errors))
		{
		# create database if desired:
		if (isset($_POST['create_database']))
			{
			@mysql_query("CREATE DATABASE ".$db_settings['db'], $connid) or $errors[] = $lang_add['db_create_db_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		# select database:
		if (empty($errors))
			{
			@mysql_select_db($db_settings['db'], $connid) or $errors[] = $lang_add['db_inexistent_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_errno($connid)."<br />".mysql_error($connid).")";
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
			cat varchar(20) NOT NULL default ''
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create posting table
			$table["postings"]["name"] = $db_settings['forum_table'];
			$table["postings"]["query"] = "CREATE TABLE ".$db_settings['forum_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			pid int(11) unsigned NOT NULL default '0',
			tid int(11) unsigned NOT NULL default '0',
			uniqid varchar(255) NOT NULL default '',
			time datetime NOT NULL,
			last_answer timestamp(14) NOT NULL default '0000-00-00 00:00:00',
			edited timestamp(14) NOT NULL default '0000-00-00 00:00:00',
			edited_by varchar(255) NOT NULL default '',
			user_id int(11) unsigned default '0',
			name varchar(255) NOT NULL default '',
			subject varchar(255) NOT NULL default '',
			category int(11) unsigned NOT NULL default '0',
			email varchar(255) NOT NULL default '',
			hp varchar(255) NOT NULL default '',
			place varchar(255) NOT NULL default '',
			ip varchar(39) NOT NULL default '',
			text text NOT NULL,
			show_signature tinyint(4) unsigned default '0',
			email_notify tinyint(4) unsigned default '0',
			marked tinyint(4) unsigned default '0',
			locked tinyint(4) unsigned default '0',
			fixed tinyint(4) unsigned default '0',
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
			hide_email tinyint(4) unsigned default '0',
			user_hp varchar(255) NOT NULL default '',
			user_place varchar(255) NOT NULL default '',
			signature varchar(255) NOT NULL default '',
			profile text NOT NULL,
			logins int(11) unsigned NOT NULL default '0',
			last_login timestamp(14) NOT NULL,
			last_logout timestamp(14) NOT NULL,
			user_ip varchar(39) NOT NULL default '',
			registered timestamp(14) NOT NULL,
			user_view varchar(255) NOT NULL default '',
			new_posting_notify tinyint(4) unsigned default '0',
			new_user_notify tinyint(4) unsigned default '0',
			personal_messages tinyint(4) unsigned default '0',
			time_difference tinyint(4) unsigned default '0',
			user_lock tinyint(4) unsigned default '0',
			pwf_code varchar(255) NOT NULL default '',
			activate_code varchar(255) NOT NULL default '',
			PRIMARY KEY (user_id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			# create smilies table
			$table["smilies"]["name"] = $db_settings['smilies_table'];
			$table["smilies"]["query"] = "CREATE TABLE ".$db_settings['smilies_table']." (
			id int(11) unsigned NOT NULL auto_increment,
			order_id int(11) unsigned NOT NULL default '0',
			file varchar(100) NOT NULL,
			code_1 varchar(50) NOT NULL default '',
			code_2 varchar(50) NOT NULL default '',
			code_3 varchar(50) NOT NULL default '',
			code_4 varchar(50) NOT NULL default '',
			code_5 varchar(50) NOT NULL default '',
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
			requests smallint(2) unsigned NOT NULL DEFAULT '0',
			UNIQUE KEY ip (ip)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			#create useronline table
			$table["useronline"]["name"] = $db_settings['useronline_table'];
			$table["useronline"]["query"] = "CREATE TABLE ".$db_settings['useronline_table']." (
			ip char(15) NOT NULL default '',
			time int(14) unsigned NOT NULL default '0',
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
				@mysql_query($tbl["query"], $connid) or $errors[] = str_replace("[table]",$tbl['name'],$lang_add['db_create_table_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				}
			}

		# insert admin in userdata table:
		if (empty($errors))
			{
			$fillUserdata = "INSERT INTO ".$db_settings['userdata_table']." SET
			user_type = 'admin',
			user_name = '".mysql_real_escape_string($_POST['admin_name'])."',
			user_real_name = '',
			user_pw = '".md5(trim($_POST['admin_pw']))."',
			user_email = '".mysql_real_escape_string($_POST['admin_email'])."',
			hide_email = '1',
			profile = '',
			registered = NOW(),
			user_view = '".$settings['standard']."',
			personal_messages = '1'";
			@mysql_query($fillUserdata, $connid) or $errors[] = $lang_add['db_insert_admin_error']." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		# insert settings in settings table:
		if (empty($errors))
			{
			# insert default settings:
			while(list($key, $val) = each($settings))
				{
				$fillSetting = "INSERT INTO ".$db_settings['settings_table']." SET
				name = '".mysql_real_escape_string($key)."',
				value = '".mysql_real_escape_string($val)."'";
				@mysql_query($fillSetting, $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				# empty $fillSetting for the next loop
				$fillSetting = "";
				}
			# update posted settings:
			$updateSetting["forum_name"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_name'])."'
			WHERE name='forum_name' LIMIT 1";
			$updateSetting["forum_address"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_address'])."'
			WHERE name='forum_address' LIMIT 1";
			$updateSetting["forum_email"] = "UPDATE ".$db_settings['settings_table']."
			SET value='".mysql_real_escape_string($_POST['forum_email'])."'
			WHERE name='forum_email' LIMIT 1";
			@mysql_query($updateSetting["forum_name"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($updateSetting["forum_address"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			@mysql_query($updateSetting["forum_email"], $connid) or $errors[] = str_replace("[setting]",$setting,$lang_add['db_update_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
			}

		# insert smilies in smilies table:
		if (empty($errors))
			{
			$order_id = 1;
			foreach($smilies as $smiley)
				{
				$fillSmiley = "INSERT INTO ".$db_settings['smilies_table']." SET
				order_id = ".intval($order_id).",
				file = '".mysql_real_escape_string($smiley[0])."',
				code_1 = '".mysql_real_escape_string($smiley[1])."',
				code_2 = '".mysql_real_escape_string($smiley[2])."',
				code_3 = '".mysql_real_escape_string($smiley[3])."',
				code_4 = '".mysql_real_escape_string($smiley[4])."',
				code_5 = '".mysql_real_escape_string($smiley[5])."',
				title = '".mysql_real_escape_string($smiley[6])."'";
				@mysql_query($fillSmiley, $connid) or $errors[] = str_replace("[setting]",$db_settings['smilies_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
				# empty $fillSmiley for the next loop
				$fillSmiley = "";
				$order_id++;
				}
			}

		# insert banlists:
		if (empty($errors))
			{
			$templateBanlist = array("users", "words");
			foreach ($templateBanlist as $val)
				{
				$fillBanlist = "INSERT INTO ". $db_settings['banlists_table'] ." SET
				name = '". mysql_real_escape_string($val) ."',
				list = ''";
				@mysql_query($fillBanlist, $connid) or $errors[] = str_replace("[setting]",$db_settings['banlists_table'],$lang_add['db_insert_settings_error'])." (MySQL: ". mysql_errno($connid) ."<br />". mysql_error($connid) .")";
				# empty $fillBanlist for the next loop
				$fillBanlist = "";
				}
			}

		# insert possible usersettings
		if (empty($errors))
			{
			foreach ($usersettings as $us)
				{
				$fillUserSetting = "INSERT INTO ".$db_settings['us_templates_table']." SET
				name = '".mysql_real_escape_string($us['name'])."',
				value = '".mysql_real_escape_string($us['value'])."',
				type = '".mysql_real_escape_string($us['type'])."'";
				@mysql_query($fillUserSetting, $connid) or $errors[] = str_replace("[setting]",$db_settings['us_templates_table'],$lang_add['db_insert_settings_error'])." (MySQL: ".mysql_errno($connid)."<br />".mysql_error($connid).")";
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
		if (!$connid) $errors[] = $lang_add['db_connection_error']." (MySQL: ".mysql_errno()."<br />".mysql_error().")";
		else $db_selected = mysql_select_db($db_settings['db'], $connid);
		if ($db_selected === true)
			{
			$checkQuery = "SELECT
			value AS installed_version
			FROM ".$db_settings['settings_table']."
			WHERE name = 'version'";
			$versionResult = mysql_query($checkQuery, $connid);
			if ($versionResult === false)
				{
				$errors[] = $lang_add['db_read_settings_error']." [Version] (MySQL: ".mysql_errno()."<br />".mysql_error().")";
				}
			else
				{
				$version = mysql_fetch_row($versionResult, $connid);
				}
			}
		else
			{
			# $db_selected === false
			$errors[] = $lang_add['db_connection_error']." (MySQL: ".mysql_errno()."<br />".mysql_error().")";
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
		$handle=opendir('./lang/');
		while ($file = readdir($handle))
			{
			if (strrchr($file, ".")==".php" && strrchr($file, "_")!="_add.php")
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
			$output .= ($settings['language_file'] ==$langFile) ? ' selected="selected"' : '';
			$output .= '>'.htmlspecialchars(ucfirst(str_replace(".php","",$langFile))).'</option>'."\n";
			}
		$output .= '</select>'."\n";
		$output .= '<input type="submit" value="'.$lang['submit_button_ok'].'" /></p>'."\n";
		$output .= '</form>'."\n";
		}
	else
		{
		$output .= '<h2>'.$lang_add['installation_instructions'].'</h2>';
		if (isset($errors))
			{
			$output .= errorMessages($errors);
			}
		$output .= '<form action="install.php" method="post">';
		$output .= '<fieldset>';
		$output .= '<legend>'.$lang_add['inst_basic_settings'].'</legend>';
		$output .= '<p>'.$lang_add['inst_main_settings_d'].'</p>';
		$output .= '<table class="admintab">';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="forum-name">'.$lang_add['forum_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_name_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="forum_name" value="';
		$output .= (isset($_POST['forum_name'])) ? htmlspecialchars($_POST['forum_name']) : $settings['forum_name'];
		$output .= '" size="40" id="forum-name" /></td>';
		$output .= '</tr><tr>';
		$output .= '<td class="admintab-l"><label for="forum-address">'.$lang_add['forum_address'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_address_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="forum_address" value="';
		if (isset($_POST['forum_address']))
			{
			$output .= htmlspecialchars($_POST['forum_address']);
			}
		else if ($settings['forum_address'] != "")
			{
			$output .= $settings['forum_address'];
			}
		else
			{
			$output .= "http://".$_SERVER['SERVER_NAME'].str_replace("install.php","",$_SERVER['SCRIPT_NAME']);
			}
		$output .= '" size="40" id="forum-address" /></td>';
		$output .= '</tr><tr>';
		$output .= '<td class="admintab-l"><label for="forum-email">'.$lang_add['forum_email'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['forum_email_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="forum_email" value="';
		$output .= (isset($_POST['forum_email'])) ? htmlspecialchars($_POST['forum_email']) : "@";
		$output .= '" size="40" id="forum-email" /></td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= '</fieldset>';
		$output .= '<fieldset>';
		$output .= '<legend>'.$lang_add['inst_admin_settings'].'</legend>';
		$output .= '<p>'.$lang_add['inst_admin_settings_d'].'</p>';
		$output .= '<table class="admintab">';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="admin-name">'.$lang_add['inst_admin_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_name_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="admin_name" value="';
		$output .= (isset($_POST['admin_name'])) ? htmlspecialchars($_POST['admin_name']) : '';
		$output .= '" size="40" id="admin-name" /></td>';
		$output .= '</tr><tr>';
		$output .= '<td class="admintab-l"><label for="admin-email">'.$lang_add['inst_admin_email'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_email_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="admin_email" value="';
		$output .= (isset($_POST['admin_email'])) ? htmlspecialchars($_POST['admin_email']) : "@";
		$output .= '" size="40" id="admin-email" /></td>';
		$output .= '</tr><tr>';
		$output .= '<td class="admintab-l"><label for="admin-pw">'.$lang_add['inst_admin_pw'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_pw_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="password" name="admin_pw" value="" size="40" id="admin-pw" /></td>';
		$output .= '</tr><tr>';
		$output .= '<td class="admintab-l"><label for="admin-pw-confirm">'.$lang_add['inst_admin_pw_conf'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_admin_pw_conf_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="password" name="admin_pw_conf" value="" size="40" id="admin-pw-confirm" /></td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= '</fieldset>';
		$output .= '<fieldset>';
		$output .= '<legend>'.$lang_add['inst_db_settings'].'</legend>';
		$output .= '<p>'.$lang_add['inst_db_settings_d'].'</p>';
		$output .= '<ul>';
		$output .= '<li><input type="checkbox" name="create_database" id="create-db-1" value="true"';
		$output .= (isset($_POST['create_database'])) ? ' checked="checked"' : '';
		$output .= ' /><label for="create-db-1">'.$lang_add['create_database'].'</label></li>';
		$output .= '<li><input type="checkbox" name="dont_overwrite_settings" id="create-db-0" value="true"';
		$output .= (isset($_POST['dont_overwrite_settings'])) ? ' checked="checked"' : '';
		$output .= '><label for="create-db-0">'.$lang_add['dont_overwrite_settings'].'</label></li>';
		$output .= '</ul>';
		$output .= '<table class="admintab">';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="db-host">'.$lang_add['inst_db_host'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_host_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="host" value="';
		$output .= (isset($_POST['host'])) ? htmlspecialchars($_POST['host']) : $db_settings['host'];
		$output .= '" size="40" id="db-host" /></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="db-name">'.$lang_add['inst_db_name'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_name_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="db" value="';
		$output .= (isset($_POST['db'])) ? htmlspecialchars($_POST['db']) : $db_settings['db'];
		$output .= '" size="40" id="db-name" /></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="db-user">'.$lang_add['inst_db_user'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_user_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="user" value="';
		$output .= (isset($_POST['user'])) ? htmlspecialchars($_POST['user']) : $db_settings['user'];
		$output .= '" size="40" id="db-user" /></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="db-pass">'.$lang_add['inst_db_pw'].'</label><br />';
		$output .= '<span class="small">'.$lang_add['inst_db_pw_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="password" name="pw" value="';
		$output .= (isset($_POST['pw'])) ? htmlspecialchars($_POST['pw']) : '';
		$output .= '" size="40" id="db-pass" /></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td class="admintab-l"><label for="db-prefix">'.$lang_add['inst_table_prefix'].'</b><br />';
		$output .= '<span class="small">'.$lang_add['inst_table_prefix_d'].'</span></td>';
		$output .= '<td class="admintab-r"><input type="text" name="table_prefix" value="';
		$output .= (isset($_POST['table_prefix'])) ? htmlspecialchars($_POST['table_prefix']) : $table_prefix;
		$output .= '" size="40" id="db-prefix" /></td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= '</fieldset>';
		$output .= '<p><input type="submit" name="form_submitted" value="'.$lang_add['forum_install_ok'].'" /></p>';
		$output .= '<input type="hidden" name="language" value="'.$language.'" />';
		$output .= '<input type="hidden" name="installation_mode" value="installation" />';
		$output .= '</form>';
		}
	}
else
	{
	$output .= '<p class="caution" style="background-image:url(http://www.mylittlehomepage.net/mylittleforum/install/x.gif);">'.$lang_add['installation_complete'].'</p>
	<p>'.$lang_add['installation_complete_exp'].'</p>
	<p><a href="index.php">'.$lang_add['installation_complete_link'].'</a></p>';
	}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang['language']; ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $settings['forum_name']." - ".$lang_add['install_title']; ?></title>
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
