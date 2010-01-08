<?php
class captcha
 {
  function check_captcha($code,$entered_code)
   {
    if(strtolower($entered_code) == strtolower($code)) return TRUE; else return FALSE;
   }

  function generate_code()
   {
    $letters="abcdefhjkmnpqrstuvwxy34568";
    mt_srand((double)microtime()*1000000);
    $code='';
    for($i=0;$i<5;$i++)
     {
      $code.=substr($letters,mt_rand(0,strlen($letters)-1),1);
     }
    return $code;
   }

  function generate_image($code,$backgrounds_folder='',$fonts_folder='')
   {
    $font_size = 23;
    $font_pos_x = 10;
    $font_pos_y = 30;

    // get background images:
    if($backgrounds_folder!='')
     {
      $handle=opendir($backgrounds_folder);
      while ($file = readdir($handle))
       {
        if(preg_match('/\.jpg$/i', $file)) $backgrounds[] = $file;
       }
      closedir($handle);
     }

    // get fonts:
    if($fonts_folder!='')
     {
      $handle=opendir($fonts_folder);
      while ($file = readdir($handle))
       {
        if(preg_match('/\.ttf$/i', $file)) $fonts[] = $file;
       }
      closedir($handle);
     }

    // split code into chars:
    $code_length = strlen($code);
      for($i=0;$i<$code_length;$i++)
       {
        $code_chars_array[] = substr($code,$i,1);
       }

    // if background images are available, craete image from one of them:
    if(isset($backgrounds))
     {
      $im = @ImageCreateFromJPEG($backgrounds_folder.$backgrounds[mt_rand(0,count($backgrounds)-1)]);
     }
    // if not, create an empty image:
    else
     {
      $im = @ImageCreate(180, 40);
      $background_color = ImageColorAllocate ($im, 234, 234, 234);
     }

    // set text color:
    $text_color = ImageColorAllocate ($im, 0, 0, 0);

    // use fonts, if available:
    if(isset($fonts))
     {
      foreach($code_chars_array as $char)
       {
        $angle = intval(rand((30 * -1), 30));
        ImageTTFText($im, $font_size, $angle, $font_pos_x, $font_pos_y, $text_color, $fonts_folder.$fonts[mt_rand(0,count($fonts)-1)],$char);
        $font_pos_x=$font_pos_x+($font_size+13);
       }
     }
    // if not, use internal font:
    else
     {
      ImageString($im, 5, 7, 4, $code, $text_color);
     }
    header ("Content-type: image/jpeg");
    ImageJPEG($im);
    exit();
   }

  function generate_dummy_image()
   {
    $im = @ImageCreate(180, 40);
    $background_color = ImageColorAllocate ($im, 234, 234, 234);
    header ("Content-type: image/jpeg");
    ImageJPEG($im);
   }

  // for math CAPTCHA:
  function generate_math_captcha()
   {
    $number[0] = rand(0,80);
    $number[1] = rand(0,20);
    $number[2] = $number[0] + $number[1];
    return $number;
   }

  function check_math_captcha($result, $entered_result)
   {
    if(intval($result) == intval($entered_result)) return TRUE; else return FALSE;
   }
 }
?>
