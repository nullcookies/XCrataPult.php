<?php
namespace X\Data\Persistent;
if (!defined('__XDIR__')) die();

use X\C;
use X\Debug\Logger;

class Cache{
  private $redisObject = null;

  private static $instance=null;
  private static $tried=false;

  /**
   * @param string $host Redis host
   * @return Cache
   */
  public static function &getInstance($host=null){
    if (!self::$instance){
      $called_class = get_called_class();
      if ($host!=null){
        self::$instance = new $called_class($host);
      }elseif (self::$instance==null){
        self::$instance = new $called_class("/var/run/redis/redis.sock"); //default for XCrataPult.server
      }
    }
    return self::$instance;
  }

  private function __construct($host){
    if (!class_exists("\\Redis")){
      throw new \RuntimeException("Please, install 'phpredis'");
    }

    $this->redisObject = new \Redis();

    if ($this->redisObject){
      try{
        $this->redisObject->connect($host);
        if (function_exists('igbinary_serialize') && defined('\\Redis::SERIALIZER_IGBINARY')){
          $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
        }else{
          $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }
      }catch(\RedisException $e){
        $this->redisObject = null;
        throw $e;
      }catch(\Exception $e){
        $this->redisObject = null;
        throw $e;
      }
    }
  }

  public function alive(){
    return $this->redisObject!==null;
  }

  public function addModifyTrigger($trigger, $deleteElement){
    if (is_array($trigger)){
      $trigger=implode(" /g:::", $trigger);
    }
    if (is_array($deleteElement)){
      $deleteElement=implode(" /g:::", $deleteElement);
    }
    $this->stackPush($trigger, $deleteElement);
  }

  public function fireModifyTrigger($trigger){
    if (is_array($trigger)){
      $trigger=implode(" /g:::", $trigger);
    }
    while($element = $this->stackPop($trigger)){
      if (strpos($element, " /g:::")){
        $element=explode(" /g:::", $element);
        $this->groupRemoveItem($element[0], $element[1]);
      }else{
        $this->remove($element);
      }
    }
  }

  public static function enabled(){
    if (self::$tried==false){
      self::getInstance();
      self::$tried=true;
    }
    return self::$instance!==null && self::getInstance()->alive();
  }

  public function __get($name){
    if ($this->exists($name)) {
      return $this->get($name);
    }else{
      return null;
    }
  }

  public function __set($name, $val){
    if ($val === null && ($this->exists($name) !== false)){
      $this->remove($name);
    }elseif($val !== null){
      $this->set($name, $val);
    }
  }

  public function set($name, $val, $ttl = null){
    if ($val === null && ($this->redisObject->exists($name) !== false)){
      $this->remove($name);
    }elseif ($val !== null){
      if ($ttl){
        $this->redisObject->setex($name, $ttl, $val);
        Logger::add("CACHE: set ".$name." as ".print_r($val,1)." for ".$ttl." secs");
      }else{
        $this->redisObject->set($name, $val);
        Logger::add("CACHE: set ".$name." as ".print_r($val,1));
      }
    }
    $this->fireModifyTrigger($name);
  }

  public function get($name){
    return $this->redisObject->get($name);
  }

  public function exists($name){
    return $this->redisObject->exists($name);
  }

  public function remove($name){
    $this->redisObject->del($name);
    $this->fireModifyTrigger($name);
  }

  public function arraySize($arr){
    return $this->redisObject->lSize($arr);
  }

  public function arrayGetItem($arr, $index){
    return $this->redisObject->lGet($arr, $index);
  }

  public function arrayPush($arr, $value){
    return $this->redisObject->lPush($arr, $value);
  }

  public function arraySetItem($arr, $index, $value){
    return $this->redisObject->lSet($arr, $index, $value);
  }

  public function arrayRemoveItem($arr, $value, $count){
    return $this->redisObject->lRem($arr, $value, $count);
  }

  public function arrayDelete($arr){
    $this->redisObject->del($arr);
  }

  public function arrayGet($arr){
    $answer = Array();
    while ($a = $this->redisObject->lPop($arr)){
      $answer[] = $a;
    }
    return $answer;
  }

  public function arrayGetAll($arr){
    if ($len=$this->redisObject->lLen($arr)){
      return $this->redisObject->lRange($arr, 0,$len);
    }else{
      return [];
    }
  }

  public function groupSize($hash){
    return $this->redisObject->hLen($hash);
  }

  public function groupGetItem($hash, $key){
    return $this->redisObject->hGet($hash, $key);
  }

  public function groupSetItem($hash, $key, $value, $ttl=null, $noTrigger=false){
    if (!$noTrigger){
      $this->fireModifyTrigger([$hash, $key]);
    }
    return $this->redisObject->hSet($hash, $key, $value);
  }

  public function groupIncItem($hash, $key, $value=1){
    $this->fireModifyTrigger([$hash, $key]);
    return $this->redisObject->hIncrBy($hash, $key, $value);
  }

  public function groupDecItem($hash, $key, $value=1){
    $this->fireModifyTrigger([$hash, $key]);
    return $this->redisObject->hIncrBy($hash, $key, -$value);
  }

  public function groupRemoveItem($hash, $key){
    $this->fireModifyTrigger([$hash, $key]);
    $this->redisObject->hDel($hash, $key);
  }

  public function groupDelete($hash){
    $this->fireModifyTrigger($hash);
    $this->redisObject->del($hash);
  }

  public function groupGet($hash){
    return $this->redisObject->hGetAll($hash);
  }

  public function queryPush($qry, $value){
    $this->redisObject->rPush($qry, $value);
  }

  public function queryPop($qry, $blocking = false){
    if ($blocking){
      $answer = $this->redisObject->blPop($qry, $blocking);
      return $answer[1];
    }else{
      return $this->redisObject->lPop($qry);
    }
  }

  public function stackPush($stack, $value){
    $this->queryPush($stack, $value);
  }

  public function stackPop($stack, $blocking = false){
    if ($blocking){
      $answer = $this->redisObject->brPop($stack, $blocking);
      return $answer[1];
    } else  {
      return $this->redisObject->rPop($stack);
    }
  }

  public function Status(){
    return $this->redisObject->info();
  }

  public function lock($lockName, $ttl = 1, $timeWait = 0){
    $timeWaitTo = min(10, abs(intval($timeWait))) + time();
    $ttlTo      = $ttl ? (min(10, abs(intval($ttl))) + time()) : 2147483646;
    while (!$this->redisObject->setnx("LOCK_" . $lockName, $ttlTo)) {

      if (intval($this->get("LOCK_" . $lockName)) <= time()) {
        $this->set("LOCK_" . $lockName, $ttlTo);
        return true;
      }
      usleep(100);
      if ($timeWait==0 || $timeWaitTo < time()){
        return false;
      }
    }
    return true;
  }

  public function unlock($lockName){
    $this->redisObject->del("LOCK_" . $lockName);
  }

  public function flushAll(){
    $this->redisObject->flushAll();
  }

  public function __isset($name){
    return $this->exists($name);
  }

  public function __unset($name){
    $this->remove($name);
  }
}