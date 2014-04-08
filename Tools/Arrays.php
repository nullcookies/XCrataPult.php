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
        $array[$key] = self::clearArray($array[$key]);
      }

      if (empty($array[$key])) {
        unset($array[$key]);
      }
    }
  }

} 