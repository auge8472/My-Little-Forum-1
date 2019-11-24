<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004-2008 Alex                                                #
# http://www.mylittlehomepage.net/                                            #
# Copyright (C) 2009-2019 H. August                                           #
# https://www.projekt-mlf.de/                                                 #
#                                                                             #
# This program is free software; you can redistribute it and/or               #
# modify it under the terms of the GNU General Public License                 #
# as published by the Free Software Foundation; either version 2              #
# of the License, or (at your option) any later version.                      #
#                                                                             #
# This program is distributed in the hope that it will be useful,             #
# but WITHOUT ANY WARRANTY; without even the implied warranty of              #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                #
# GNU General Public License for more details.                                #
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

if($settings['upload_images']!=1) die('This feature is not activated.');

$uploaded_images_path = 'img/uploaded/';
$images_per_page = 30;

function resize($uploaded_file, $file, $new_width, $new_height, $compression=80)
 {
  if(file_exists($file))
   {
    @chmod($file, 0777);
    @unlink($file);
   }

  $image_info = getimagesize($uploaded_file);
  if(!is_array($image_info) || $image_info[2] != 1 && $image_info[2] != 2 && $image_info[2] != 3) $error = true;
  if(empty($error))
  {
  if($image_info[2]==1) // GIF
   {
    $current_image = @ImageCreateFromGIF($uploaded_file) or $error = true;
    if(empty($error)) $new_image = @ImageCreate($new_width,$new_height) or $error = true;
    if(empty($error)) @ImageCopyResized($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error=true;
    if(empty($error)) @ImageGIF($new_image, $file) or $error = true;
   }
  elseif($image_info[2]==2) // JPG
   {
    $current_image = @ImageCreateFromJPEG($uploaded_file) or $error = true;
    if(empty($error)) $new_image=@imagecreatetruecolor($new_width,$new_height) or $error = true;
    if(empty($error)) @ImageCopyResized($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error = true;
    if(empty($error)) @ImageJPEG($new_image, $file, $compression) or $error = true;
   }
  elseif($image_info[2]==3) // PNG
   {
    $current_image=ImageCreateFromPNG($uploaded_file) or $error = true;
    if(empty($error)) $new_image=imagecreatetruecolor($new_width,$new_height) or $error = true;
    if(empty($error)) ImageCopyResized($new_image,$current_image,0,0,0,0,$new_width,$new_height,$image_info[0],$image_info[1]) or $error = true;
    if(empty($error)) ImagePNG($new_image, $file) or $error = $true;
   }
  }
  if(empty($error)) return true;
  else return false;
 }


if(isset($_GET['action'])) $action = $_GET['action']; else $action = 'upload';

$lang['upload_exp'] = str_replace("[width]", $settings['upload_max_img_width'], $lang['upload_exp']);
$lang['upload_exp'] = str_replace("[height]", $settings['upload_max_img_height'], $lang['upload_exp']);
$lang['upload_exp'] = str_replace("[size]", $settings['upload_max_img_size'], $lang['upload_exp']);

if (isset($_FILES['probe']) && $_FILES['probe']['size'] != 0 && !$_FILES['probe']['error'])
 {
  unset($errors);
  $image_info = getimagesize($_FILES['probe']['tmp_name']);

  if(!is_array($image_info) || $image_info[2] != 1 && $image_info[2] != 2 && $image_info[2] != 3) $errors[] = $lang['invalid_file_format'];

  if (empty($errors))
   {
    if($_FILES['probe']['size'] > $settings['upload_max_img_size']*1000 || $image_info[0] > $settings['upload_max_img_width'] || $image_info[1] > $settings['upload_max_img_height'])
     {
      $compression = 10;
      $width=$image_info[0];
      $height=$image_info[1];
      if($width >= $height)
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

      for($compression = 100; $compression>9; $compression=$compression-10)
       {
        if(!resize($_FILES['probe']['tmp_name'], $uploaded_images_path.$img_tmp_name, $new_width, $new_height, $compression)) { $file_size = @filesize($uploaded_images_path.$img_tmp_name); break; }
        $file_size = @filesize($uploaded_images_path.$img_tmp_name);
        if($image_info[2]!=2 && $file_size > $settings['upload_max_img_size']*1000) break;
        if($file_size <= $settings['upload_max_img_size']*1000) break;
       }
      if($file_size > $settings['upload_max_img_size']*1000) { $file_too_large_dump = str_replace("[width]",$image_info[0],$lang['file_too_large']); $file_too_large_dump = str_replace("[height]",$image_info[1],$file_too_large_dump); $file_too_large_dump = str_replace("[size]",number_format($_FILES['probe']['size']/1000,0,",",""),$file_too_large_dump); $errors[] = $file_too_large_dump; }
      if(isset($errors))
       {
        if(file_exists($uploaded_images_path.$img_tmp_name))
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
    if(isset($img_tmp_name))
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
  if(empty($errors))
   {
    @chmod($uploaded_images_path.$filename, 0644);
    $action = 'uploaded';
   }
  else $action = 'upload';
 }

if(empty($errors))
 {
  if(isset($_FILES['probe']['error'])) $errors[] = str_replace('[maximum_file_size]',ini_get('upload_max_filesize'),$lang['upload_error_2']);
 }

if(isset($_GET['uploaded_image_selected']))
 {
  $filename = $_GET['uploaded_image_selected'];
  $action = 'uploaded';
 }

?><html>
<head>
<title><?php echo $lang['upload_image_title']; ?></title>
<style type="text/css">
<!--
body                { font-family: Verdana,Arial,Helvetica,sans-serif; color: #000000; font-size:13px; background: #fff; margin: 0px; padding: 20px; }
h1                  { margin: 0px 0px 20px 0px; font-size:18px; font-weight:bold; }
.caution            { color: red; font-weight: bold; }
a:link              { color: #0000cc; text-decoration: none; }
a:visited           { color: #0000cc; text-decoration: none; }
a:hover             { color: #0000ff; text-decoration: underline; }
a:active            { color: #ff0000; text-decoration: none; }
-->
</style>
</head>
<body>

<?php
switch($action)
 {
  case 'upload':
   ?><h1><?php echo $lang['upload_image_title']; ?></h1><?php
   if(isset($errors))
    {
     ?><p class="caution"><?php echo $lang['error_headline']; ?></p><ul><?php foreach($errors as $f) { ?><li><?php echo $f; ?></li><?php } ?></ul><?php
    }
   ?><p><?php echo $lang['upload_exp']; ?></p>
   <form action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
   <input type="file" name="probe" /><br><br>
   <input type="submit" value="<?php echo $lang['upload_subm_button']; ?>">
   </form>
   <p style="font-size:11px;">[ <a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?action=show_uploaded_images"><?php echo $lang['available_images']; ?></a> ]</p><?php
  break;
  case 'uploaded':
  if (isset($_FILES['probe']))
   {
    ?><h1><?php echo $lang['upload_image_title']; ?></h1><?php
    if(isset($image_manipulated))
     {
      ?><p><?php echo $image_manipulated; ?></p><?php
     }
    else
     {
      ?><p><?php echo $lang['upload_successful']; ?></p><?php
     }
   }
  ?><img src="img/uploaded/<?php echo $filename; ?>" alt="" height="100" border="1">
  <p><?php echo $lang['paste_image']; ?></p>
  <p><button style="width:25px; height:25px;" title="<?php echo $lang['insert_image_normal']; ?>" onclick="opener.insert('[img]<?php echo $uploaded_images_path.$filename; ?>[/img]'); window.close()"><img src="<?php echo $settings['themepath']; ?>/img/img_normal.gif" alt="<?php echo $lang['insert_image_normal']; ?>" wifth="11" height="11" /></button>&nbsp;
  <button style="width:25px; height:25px;" title="<?php echo $lang['insert_image_left']; ?>" onclick="opener.insert('[img|left]<?php echo $uploaded_images_path.$filename; ?>[/img]'); window.close()"><img src="<?php echo $settings['themepath']; ?>/img/img_left.gif" alt="<?php echo $lang['insert_image_left']; ?>" wifth="11" height="11" /></button>&nbsp;
  <button style="width:25px; height:25px;" title="<?php echo $lang['insert_image_right']; ?>" onclick="opener.insert('[img|right]<?php echo $uploaded_images_path.$filename; ?>[/img]'); window.close()"><img src="<?php echo $settings['themepath']; ?>/img/img_right.gif" alt="<?php echo $lang['insert_image_right']; ?>" wifth="11" height="11" /></button></p><?php
  break;
  case 'show_uploaded_images':
   if (isset($_GET['p'])) $p = intval($_GET['p']); else $p = 1;

   $c=0;
   $handle=opendir($uploaded_images_path);
   while ($file = readdir($handle))
    {
     if(preg_match('/\.jpg$/i', $file) || preg_match('/\.png$/i', $file) || preg_match('/\.gif$/i', $file))
      {
       $images[] = $file;
      }
     }
   closedir($handle);
   if(isset($images))
    {
     $images_count = count($images);
     $show_images_from = $p * $images_per_page - $images_per_page;
     $show_images_to =   $p * $images_per_page;
     if($show_images_to>$images_count) $show_images_to = $images_count;
    }
   else $images_count = 0;
   ?><p><?php
   if ($p>1) { ?>[ <a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?action=show_uploaded_images&amp;p=<?php echo intval($p-1); ?>">&laquo;</a> ] <?php } if($p*$images_per_page < $images_count) { ?>[ <a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?action=show_uploaded_images&amp;p=<?php echo intval($p+1); ?>">&raquo;</a> ] <?php } ?>
   [ <a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>"><?php echo $lang['upload_image_title']; ?></a> ]</p>
   <hr /><p><?php
   if($images_count > 0)
    {
     for($i=$show_images_from;$i<$show_images_to;$i++)
      {
       ?><a href="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>?uploaded_image_selected=<?php echo urlencode($images[$i]); ?>"><img style="margin: 0px 15px 15px 0px;" src="<?php echo $uploaded_images_path.$images[$i]; ?>" alt="<?php echo htmlsc($images[$i]); ?>" height="100" border="0"></a><?php
      }
    }
   else
    {
     ?><i><?php echo $lang['no_images']; ?></i><?php
    }
   ?></p><?php
  break;
 }

?></body>
</html>