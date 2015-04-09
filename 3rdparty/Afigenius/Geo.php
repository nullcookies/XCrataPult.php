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

  public static function countryIdByCityId($id){
    $answer = Base::request("geo/citybyid", urlencode($id), "country_id");
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"]["country_id"];
      }
    }
    return 0;
  }

  public static function countryIdByName($name){
    $answer = Base::request("geo/countrylist", urlencode($name), "id");
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"][0]["id"];
      }
    }
    return 0;
  }

  public static function cityIdByName($cityName, $countryName='', $regionName=''){
    $answer = Base::request("geo/citylist", urlencode($cityName).'/'.urlencode($countryName).'/'.urlencode($regionName), "id");
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"][0]["id"];
      }
    }
    return 0;
  }

  public static function cityById($id){
    $answer = Base::request("geo/citybyid", urlencode($id));
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"];
      }
    }
    return null;
  }

  public static function countryById($id){
    $answer = Base::request("geo/countrybyid", urlencode($id));
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"];
      }
    }
    return null;
  }

  public static function cityByIp($ip=''){
    $answer = Base::request("geo/citybyip", urlencode($ip));
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"];
      }
    }
    return null;
  }

  public static function countryByIp($ip=''){
    $answer = Base::request("geo/countrybyip", urlencode($ip));
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"];
      }
    }
    return null;
  }
} 