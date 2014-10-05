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

  public static function countryIdByName($name){
    $answer = Base::request("geo/countrylist", $name, "id");
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"][0]["id"];
      }
    }
    return 0;
  }

  public static function cityIdByName($cityName, $countryName='', $regionName=''){
    $answer = Base::request("geo/citylist", $cityName.'/'.$countryName.'/'.$regionName, "id");
    if ($answer["status"]=="ok"){
      if (count($answer["data"])){
        return $answer["data"][0]["id"];
      }
    }
    return 0;
  }
} 