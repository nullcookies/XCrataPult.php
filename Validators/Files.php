<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 17.03.14
 * Time: 18:42
 */

namespace X\Validators;
if (!defined('__XDIR__')) die();

use \X\AbstractClasses\PrivateInstantiation;

class Files extends PrivateInstantiation{
  public static function isImage($path){
    $imagedata  = getimagesize( $path );
    return $imagedata && $imagedata[0] && $imagedata[1];
  }

  public static function isPDF($path){
    if ($fd = fopen($path, 'r')){
      if (fread($fd,5)==='%PDF-'){
        fclose($fd);
        return true;
      }
      fclose($fd);
    }
    return false;
  }

  public static function isAnimatedGIF($filename){
    if (!file_exists($filename)){
      return false;
    }

    $filecontents=file_get_contents($filename);

    $str_loc=0;
    $count=0;
    while ($count < 2){
      $where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
      if ($where1 === FALSE){
        break;
      }else{
        $str_loc=$where1+1;
        $where2=strpos($filecontents,"\x00\x2C",$str_loc);
        if ($where2 === FALSE){
          break;
        }else{
          if ($where1+8 == $where2){
            $count++;
          }
          $str_loc=$where2+1;
        }
      }
    }

    if ($count > 1){
      return(true);
    }else{
      return(false);
    }
  }
} 