<?php
namespace X\Tools;
if (!defined('__XDIR__')) die();

class Values{

  const SIZE_1KB    =       1024;
  const SIZE_10KB   =      10240;
  const SIZE_100KB  =     102400;
  const SIZE_1MB    =    1048576;
  const SIZE_10MB   =   10485760;
  const SIZE_100MB  =  104857600;
  const SIZE_1GB    = 1073741824;

  public static function smartImplode($values, $delimiter, $callback){
    if (is_array($values)){
      array_walk($values, $callback);
    }else{
      return $callback($values);
    }

    return implode($delimiter, $values);
  }

  public static function Grades($val, $divider, $names, $dec=2, $prefix=''){
    if (!is_array($names)){
      $names = explode(",", $names);
    }
    $names_c = 0;
    while($names_c<count($names)-1 && $val>=$divider){
      $val/=$divider;
      $names_c++;
    }
    return round($val, $dec).$prefix.$names[$names_c];
  }

  public static function clearArray(&$array){
    foreach ($array as $key => $value){
      if (is_array($value)){
        self::clearArray($array[$key]);
      }

      if (empty($array[$key])){
        unset($array[$key]);
      }
    }
  }
}