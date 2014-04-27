<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 27.04.14
 * Time: 2:31
 */

namespace Afi;


use X\Data\Cache;
use X\Tools\Strings;

class Base {

  const HOST = "http://data.afigenius.ru/";

  public static function request($uri, $params=[], $fieldsNeeded=[]){
    if (!is_array($params) || !is_array($fieldsNeeded)){
      throw new \InvalidArgumentException();
    }
    ksort($params);
    ksort($fieldsNeeded);
    if (count($params)){
      $params = Strings::smartKImplode($params, "&", function($key, $val){return $key.'='.urlencode($val);});
    }else{
      $params = '1';
    }
    $fields = urlencode(implode(',', $fieldsNeeded));
    $req = $uri.'?'.$params.'&fields='.$fields;
    $cacheHash = "AFI:GEO:".md5($req);

    if (Cache::enabled() && ($answer=Cache::getInstance()->get($cacheHash))){
      return $answer;
    }

    $answer = file_get_contents(self::HOST.$req);
    $answer = json_decode($answer, JSON_OBJECT_AS_ARRAY);
    if (Cache::enabled()){
      Cache::getInstance()->set($cacheHash, $answer, 3600*24);
    }
    return $answer;
  }
} 