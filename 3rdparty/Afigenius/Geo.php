<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * For documentation please refer to http://data.afigenius.ru/
 * Thank you!
 *
 * Date: 26.04.14
 * Time: 1:05
 */

namespace Afi;

class Geo {
  /**
   * Returns array of Cities which has $name as part of their names in any language
   *
   * @param $name - part or full name of cities
   * @param array $fields - fields you need to receive (leave empty to receive everything)
   * @return array - list of cities
   */
  public static function citylistByCityname($name, $fields=[], $limit=10){
    $uri="geo/citylist-by-cityname/".urlencode($name);
    return Base::request($uri, [], $fields, $limit);
  }
} 