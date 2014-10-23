<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 27.04.14
 * Time: 2:31
 */

namespace Afi;


use X\Data\Persistent\Cache;
use X\Tools\Strings;

class Base {

  const HOST = "http://data.afigenius.ru/web-api/1.0/";
  const TTL = 60000;

  public static function request($uri, $params='', $fieldsNeeded=[], $limit=10){
    if (!is_array($fieldsNeeded)){
      $fieldsNeeded = explode(",", $fieldsNeeded);
    }
    $fields = urlencode(implode(',', $fieldsNeeded));
    $req = $uri.'/'.$params.'?limit='.intval($limit).($fields ? '&fields='.$fields :'');
    $cacheHash = "AFI:GEO:1:".md5($req);
    if (Cache::enabled() && ($answer=Cache::getInstance()->get($cacheHash))){
      return $answer;
    }
    $answer = file_get_contents(self::HOST.$req);
    $answer = json_decode($answer, JSON_OBJECT_AS_ARRAY);
    if (Cache::enabled()){
      Cache::getInstance()->set($cacheHash, $answer, self::TTL);
    }
    return $answer;
  }
} 