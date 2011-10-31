<?php
include("inc.php");

if (file_exists('install.php')
or file_exists('update.php')
or file_exists('update_content.php'))
	{
	header("location: ".$settings['forum_address']."service.php");
	die("<a href=\"service.php\">further...</a>");
	}

if ($settings['upload_images']!=1) die('This feature is not activated.');

$uploaded_images_path = 'img/uploaded/';
$images_per_page = 30;

function resize($uploaded_file, $file, $new_width, $new_height, $compression=80) {

if (file_exists($file))
	{
	@chmod($file, 0777);
	@unlink($file);
	}

$image_info = getimagesize($uploaded_file);

if (!is_array($image_info) || $image_info[2] != 1 && $image_info[2] != 2 && $image_info[2] != 3) $error = true;
if (empty($error))
	{
	if($image_info[2]==1) // GIF
		{
		$current_image = @ImageCreateFromGIF($uploaded_file) or $error = true;
		if (empty($error)) $new_image = @ImageCreate($new_width,$new_height) or $error = true;
		if (empty($error)) @ImageCopyResampled($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error=true;
		if (empty($error)) @ImageGIF($new_image, $file) or $error = true;
		}
	else if ($image_info[2]==2) // JPG
		{
		$current_image = @ImageCreateFromJPEG($uploaded_file) or $error = true;
		if (empty($error)) $new_image=@imagecreatetruecolor($new_width,$new_height) or $error = true;
		if (empty($error)) @ImageCopyResampled($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error = true;
		if (empty($error)) @ImageJPEG($new_image, $file, $compression) or $error = true;
		}
	else if($image_info[2]==3) // PNG
		{
		$current_image=ImageCreateFromPNG($uploaded_file) or $error = true;
		if (empty($error)) $new_image=imagecreatetruecolor($new_width,$new_height) or $error = true;
		if (empty($error)) ImageCopyResampled($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error = true;
		if (empty($error)) ImagePNG($new_image, $file) or $error = $true;
		}
	}

if (empty($error)) return true;
else return false;
}

$action = (isset($_GET['action'])) ? $_GET['action'] : 'upload';

$lang['upload_exp'] = str_replace("[width]", $settings['upload_max_img_width'], $lang['upload_exp']);
$lang['upload_exp'] = str_replace("[height]", $settings['upload_max_img_height'], $lang['upload_exp']);
$lang['upload_exp'] = str_replace("[size]", $settings['upload_max_img_size'], $lang['upload_exp']);

if (isset($_FILES['probe']) && $_FILES['probe']['size'] != 0 && !$_FILES['probe']['error'])
	{
	unset($errors);
	$image_info = getimagesize($_FILES['probe']['tmp_name']);
	if (!is_array($image_info)
		|| ($image_info[2] != 1
		&& $image_info[2] != 2
		&& $image_info[2] != 3))
		{
		$errors[] = $lang['invalid_file_format'];
		}
	if (empty($errors))
		{
		if ($_FILES['probe']['size'] > $settings['upload_max_img_size']*1000
			|| $image_info[0] > $settings['upload_max_img_width']
			|| $image_info[1] > $settings['upload_max_img_height'])
			{
			$compression = 10;
			$width=$image_info[0];
			$height=$image_info[1];
			if ($width >= $height)
				{
				$new_width = $settings['upload_max_img_width'];
				$new_height = intval($height*$new_width/$width);
				}
			else
				{
				$new_height = $settings['upload_max_img_height'];
				$new_width = intval($width*$new_height/$height);
				}
			$img_tmp_name = uniqid(rand()).'.tmp';

			for ($compression = 100; $compression>9; $compression=$compression-10)
				{
				if (!resize($_FILES['probe']['tmp_name'], $uploaded_images_path.$img_tmp_name, $new_width, $new_height, $compression))
					{
					$file_size = @filesize($uploaded_images_path.$img_tmp_name);
					break;
					}
				$file_size = @filesize($uploaded_images_path.$img_tmp_name);
				if ($image_info[2]!=2 && $file_size > $settings['upload_max_img_size']*1000) break;
				if ($file_size <= $settings['upload_max_img_size']*1000) break;
				}
			if ($file_size > $settings['upload_max_img_size']*1000)
				{
				$file_too_large_dump = str_replace("[width]",$image_info[0],$lang['file_too_large']);
				$file_too_large_dump = str_replace("[height]",$image_info[1],$file_too_large_dump);
				$file_too_large_dump = str_replace("[size]",number_format($_FILES['probe']['size']/1000,0,",",""),$file_too_large_dump);
				$errors[] = $file_too_large_dump;
				}
			if (isset($errors))
				{
				if (file_exists($uploaded_images_path.$img_tmp_name))
					{
					@chmod($uploaded_images_path.$img_tmp_name, 0777);
					@unlink($uploaded_images_path.$img_tmp_name);
					}
				}
			}
		}

	if (empty($errors))
		{
		$nr = 0;
		switch($image_info[2])
			{
			case 1:
				for(;;) { $nr++; if (!file_exists($uploaded_images_path."image".$nr.".gif")) break; }
				$filename = "image".$nr.".gif";
			break;
			case 2:
				for(;;) { $nr++; if (!file_exists($uploaded_images_path."image".$nr.".jpg")) break; }
				$filename = "image".$nr.".jpg";
			break;
			case 3:
				for(;;) { $nr++; if (!file_exists($uploaded_images_path."image".$nr.".png")) break; }
				$filename = "image".$nr.".png";
			break;
			}
		if (isset($img_tmp_name))
			{
			@rename($uploaded_images_path.$img_tmp_name, $uploaded_images_path.$filename) or $errors[] = $lang['upload_error'];
			$image_manipulated = str_replace('[width]',$new_width,$lang['image_manipulated']);
			$image_manipulated = str_replace('[height]',$new_height,$image_manipulated);
			$image_manipulated = str_replace("[size]",number_format($file_size/1000,0,",",""),$image_manipulated);
			}
		else
			{
			@move_uploaded_file($_FILES['probe']['tmp_name'], $uploaded_images_path.$filename) or $errors[] = $lang['upload_error'];
			}
		}
	if (empty($errors))
		{
		@chmod($uploaded_images_path.$filename, 0644);
		$action = 'uploaded';
		}
	else $action = 'upload';
	}

if (empty($errors))
	{
	if (isset($_FILES['probe']['error']))
		{
		$errors[] = str_replace('[maximum_file_size]',ini_get('upload_max_filesize'),$lang['upload_error_2']);
		}
	}

if(isset($_GET['uploaded_image_selected']))
	{
	$filename = $_GET['uploaded_image_selected'];
	$action = 'uploaded';
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo strip_tags($lang['upload_image_title']); ?></title>
<style type="text/css">
<!--
body {
font-family:Verdana,Helvetica,sans-serif;
color:#000;
font-size:13px;
background:#fff;
margin:0;
padding:20px;
}
h1 {
margin:0 0 20px 0;
font-size:18px;
font-weight:bold;
}
.caution {
color:red;
font-weight:bold;
}
a:link {
color:#00c;
text-decoration:none;
}
a:visited {
color:#00c;
text-decoration:none;
}
a:focus, a:hover {
color:#00f;
text-decoration:underline;
}
-->
</style>
<script src="data/prototype.js" type="text/javascript"></script>
<script type="text/javascript">/* <![CDATA[ */
function insertCode(imageCode) {
if (opener) {
/*	var txtArea = opener.document.getElementById("text"); */
	var txtArea = opener.$("text");
/*	var txtCont = txtArea.value; */
	var txtCont = $F(txtArea);
  var selLength = txtCont.length;
	txtArea.value = txtCont + " " + imageCode;
	self.close();
	}
}
/* ]]> */
</script>
</head>
<body>
<h1><?php $lang['upload_image_title']; ?></h1>
<?php
switch($action)
	{
	case 'upload':
		if(isset($errors))
			{
			echo errorMessages($errors);
			}
		echo '<p>'.$lang['upload_exp'].'</p>'."\n";
		echo '<form action="'.$_SERVER['SCRIPT_NAME'].'" method="post" '."\n";
		echo 'enctype="multipart/form-data"><input type="file" name="probe" />'."\n";
		echo '<input type="submit" value="'.outputLangDebugInAttributes($lang['upload_subm_button']).'">'."\n";
		echo '</form>'."\n";
		echo '<p>[ <a href="'.$_SERVER['PHP_SELF'].'?action=show_uploaded_images">';
		echo $lang['available_images'].'</a> ]</p>'."\n";
	break;
	case 'uploaded':
		if (isset($_FILES['probe']))
			{
			if(isset($image_manipulated))
				{
				echo '<p>'.$image_manipulated.'</p>'."\n";
				}
			else
				{
				echo '<p>'.$lang['upload_successful'].'</p>'."\n";
				}
			}
		echo '<img src="img/uploaded/'.$filename.'" alt="" height="100" border="1">'."\n";
		echo '<p>'.$lang['paste_image'].'</p>'."\n";
		echo '<p><button style="width:25px; height:25px;" title="'.outputLangDebugInAttributes($lang['insert_image_normal']);
		echo '" onclick="insertCode(\'[img]'.$uploaded_images_path.$filename.'[/img]\');';
		echo '"><img src="img/img_normal.gif" alt="'.outputLangDebugInAttributes($lang['insert_image_normal']);
		echo '" width="11" height="11" /></button>&nbsp;<button style="width:25px; height:25px;"';
		echo ' title="'.outputLangDebugInAttributes($lang['insert_image_left']).'" onclick="insertCode(\'[img|left]';
		echo $uploaded_images_path.$filename.'[/img]\');"><img';
		echo ' src="img/img_left.gif" alt="'.outputLangDebugInAttributes($lang['insert_image_left']).'" width="11" height="11"';
		echo ' /></button>&nbsp;<button style="width:25px; height:25px;" title="';
		echo outputLangDebugInAttributes($lang['insert_image_right']).'" onclick="insertCode(\'[img|right]';
		echo $uploaded_images_path.$filename.'[/img]\');"><img';
		echo ' src="img/img_right.gif" alt="'.outputLangDebugInAttributes($lang['insert_image_right']).'" width="11"';
		echo ' height="11" /></button></p>'."\n";
	break;
	case 'show_uploaded_images':
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1;
		$c=0;
		$handle = opendir($uploaded_images_path);
		while ($file = readdir($handle))
			{
			if (preg_match('/\.jpg$/i', $file)
				|| preg_match('/\.png$/i', $file)
				|| preg_match('/\.gif$/i', $file))
				{
				$images[] = $file;
				}
			}
		closedir($handle);
		if (isset($images))
			{
			$images_count = count($images);
			$show_images_from = $p * $images_per_page - $images_per_page;
			$show_images_to =   $p * $images_per_page;
			if ($show_images_to>$images_count) $show_images_to = $images_count;
			}
		else
			{
			$images_count = 0;
			}
		echo '<p>';
		if ($p>1)
			{
			$pageDown = $p - 1;
			echo '[ <a href="'.$_SERVER['SCRIPT_NAME'].'?action=show_uploaded_images';
			echo '&amp;p='.$pageDown.'">&laquo;</a> ] ';
			}
		if ($p*$images_per_page < $images_count)
			{
			$pageUp = $p + 1;
			echo '[ <a href="'.$_SERVER['SCRIPT_NAME'].'?action=show_uploaded_images';
			echo '&amp;p='.$pageUp.'">&raquo;</a> ] ';
			}
		echo '<hr /><p>';
		if ($images_count > 0)
			{
			for ($i=$show_images_from; $i<$show_images_to; $i++)
				{
				echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?uploaded_image_selected=';
				echo $images[$i].'"><img style="margin: 0px 15px 15px 0px;" src="';
				echo $uploaded_images_path.$images[$i].'" alt="'.$images[$i];
				echo '" height="100" border="0"></a>'."\n";
				}
			}
		else
			{
			echo '<i>'.$lang['no_images'].'</i>';
			}
		echo '</p>'."\n";
	break;
	}
?></body>
</html>
