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
} 