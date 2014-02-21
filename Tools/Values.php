<?php
namespace x\tools;
if (!defined('__XDIR__')) die();

class Values{

  public static function startsWith($haystack, $needle) {
    $haystack = strtolower($haystack);
    $needle = strtolower($needle);
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public static function endsWith($haystack, $needle) {
    $haystack = strtolower($haystack);
    $needle = strtolower($needle);
    $length = strlen($needle);
    $start  = $length * -1;
    return (substr($haystack, $start) === $needle);
  }

  public static function isAssoc(&$arr){
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  static public function smartImplode($values, $delimiter, $callback){
    if (is_array($values)){
      array_walk($values, $callback);
    }else{
      return $callback($values);
    }

    return implode($delimiter, $values);
  }

  public static function Grades($val, $divider, $names, $dec=2, $prefix=''){
    if (!is_array($names))
      $names = explode(",", $names);
    $names_c = 0;
    while($names_c<count($names)-1 && $val>=$divider)
    {
      $val/=$divider;
      $names_c++;
    }
    return round($val, $dec).$prefix.$names[$names_c];
  }

  public static function clearArray(&$array){
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        self::clearArray($array[$key]);
      }

      if (empty($array[$key])) {
        unset($array[$key]);
      }
    }
  }
}