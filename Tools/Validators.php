<?php
namespace X\Tools;
if (!defined('__XDIR__')) die();

use \X\AbstractClasses\PrivateInstantiation;

final class Validators extends PrivateInstantiation{

  static public function isJSON($data){
    if (!is_string($data)){
      return false;
    }

    json_decode($data);
    return (json_last_error() == JSON_ERROR_NONE);
  }

  static function isCallback($var){
    if ($var instanceof \Closure) {
      return true;
    }
    if (is_array($var) && count($var) == 2) {
      $var = array_values($var);
      if ((!is_string($var[0]) && !is_object($var[0])) || (is_string($var[0]) && !class_exists($var[0]))) {
        return false;
      }
      $isObj = is_object($var[0]);
      $class = new \ReflectionClass($isObj ? get_class($var[0]) : $var[0]);
      if ($class->isAbstract()) {
        return false;
      }
      try {
        $method = $class->getMethod($var[1]);
        if (!$method->isPublic() || $method->isAbstract()) {
          return false;
        }
        if (!$isObj && !$method->isStatic()) {
          return false;
        }
      } catch (\ReflectionException $e) {
        return false;
      }
      return true;
    } elseif (is_string($var) && function_exists($var)) {
      return true;
    }
    return false;
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

  public static function isImage($path){
    $imagedata  = getimagesize( $path );
    return $imagedata && $imagedata[0] && $imagedata[1];
  }
}


?>
