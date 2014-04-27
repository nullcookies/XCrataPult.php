<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 26.04.14
 * Time: 1:05
 */

namespace Afi;

class Geo {
  public static function getCityNameById($id, $lang='ru'){
    $id = intval($id);
    $uri="geo/by-cityid/".$id;
    $fields=["city_".$lang];
    return Base::request($uri, [], $fields)["city_".$lang];
  }
} 