<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 18.03.14
 * Time: 13:24
 */

namespace X\Tools;
if (!defined('__XDIR__')) die();


class Strings {
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
} 