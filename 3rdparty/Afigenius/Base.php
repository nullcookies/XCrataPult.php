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
  const TTL = 86400;

  public static function request($uri, $params='', $fieldsNeeded=[], $limit=10){
    if (!is_array($fieldsNeeded)){
      $fieldsNeeded = explode(",", $fieldsNeeded);
    }
    $avc = date('d.m.Y');
    $fields = urlencode(implode(',', $fieldsNeeded));
    $req = $uri.'/'.$params.'?acv='.$avc.'111&limit='.intval($limit).($fields ? '&fields='.$fields :'');
    $cacheHash = "AFI:2:".md5($req);
    if (0 && Cache::enabled() && ($answer=Cache::getInstance()->get($cacheHash))){
      return $answer;
    }
    $answer = file_get_contents(self::HOST.$req);
    $answer = json_decode($answer, JSON_OBJECT_AS_ARRAY);
    if (Cache::enabled()){
      Cache::getInstance()->set($cacheHash, $answer, self::TTL);
    }
    return $answer;
  }

  private $version = null;
  private $host = null;
  private $cacheHash = null;

  private static $instances=[];

  public function __construct($version, $host='https://data.afigenius.ru/web-api/'){
    $this->version = intval($version).'.0';
    $this->host = $host;
  }

  public function sendRequest($uri, $params='', $fieldsNeeded=[], $limit=10){
    if (!is_array($fieldsNeeded)){
      $fieldsNeeded = explode(",", $fieldsNeeded);
    }
    $ac = date('d.m.Y');
    $fields = urlencode(implode(',', $fieldsNeeded));
    $req = '/'.$uri.'/'.$params.'?ac='.$ac.'&limit='.intval($limit).($fields ? '&fields='.$fields :'');
    $cacheHash = "AFI:".$ac.":".md5($req);
    if (Cache::enabled() && ($answer=Cache::getInstance()->get($cacheHash))){
      return $answer;
    }
    $request = $this->host.$this->version.$req;
    $answer = file_get_contents($request);
    $answer = json_decode($answer, JSON_OBJECT_AS_ARRAY);
    if (Cache::enabled()){
      Cache::getInstance()->set($cacheHash, $answer, self::TTL);
    }
    return $answer;
  }

  /**
   * @param $name
   * @return Base
   */
  public static function __callStatic($name, $arguments){
    if (strlen($name)==2 && $name[0]=='v' && $version = intval($name[1])){
      if (!static::$instances[$version]){
        $classname = get_called_class();
        static::$instances[$version] = new $classname($version);
      }
      return static::$instances[$version];
    }
    return null;
  }
} 