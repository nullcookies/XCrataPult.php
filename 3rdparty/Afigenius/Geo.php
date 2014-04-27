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

  public static function getCountryDataByName($name){
    $name = trim($name);
    $uri="geo/by-countryname/".urlencode($name);
    return Base::request($uri);
  }

  public static function getCountryDataById($id){
    $id = trim($id);
    $uri="geo/by-countryid/".$id;
    return Base::request($uri);
  }

  public static function getCountryNameById($id, $lang='ru'){
    return self::getCountryDataById($id)["country_".$lang];
  }

  public static function getCountryList(){
    $uri = "geo/countrylist/";
    return Base::request($uri);
  }

  public static function getCityListByCountryId($id){
    $id = intval($id);
    $uri = "geo/citylist-by-countryid/".$id;
    return Base::request($uri)["cities"];
  }
} 