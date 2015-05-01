<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 02.04.14
 * Time: 21:10
 */

namespace X\Tools;


class Arrays {

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

  public static function countDimensions(&$array){
    if (!is_array($array)){
      return 0;
    }

    $max_sub_depth = 0;
    foreach ($array as &$subarray) {
      $max_sub_depth = max(
        $max_sub_depth,
        self::countDimensions($subarray)
      );
    }
    return $max_sub_depth+1;
  }

} 