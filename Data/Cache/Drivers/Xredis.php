<?php

namespace X\Data\Cache\Drivers;

// TODO: ensure that locks will have TTL short enough to prevent permanent blockage
// TODO: Add Exceptions

use \X\AbstractClasses\Singleton;
use \X\Data\Cache\Interfaces\ICache;
use X\Debug\Logger;
use \X\X;

/**
 * @required  Redis >= 2.0
 */
class Xredis implements ICache{
  private $redisObject = null;
  private $hosts = [];

  public function connect(){
    if ($this->redisObject!==null){
      return;
    }
    if (!class_exists("\\Redis")){
      throw new \RuntimeException("Please, install 'phpredis'");
    }

    $this->redisObject = new \Redis();
    if ($this->redisObject){
      try{
        foreach ($this->hosts as $host){
          $this->redisObject->pconnect($host);
        }
        if (function_exists('igbinary_serialize') && defined('\\Redis::SERIALIZER_IGBINARY')){
          $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
        }else{
          $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }
      }catch(\RedisException $e){
        $this->redisObject = null;
      }catch(\Exception $e){
        $this->redisObject = null;
      }
    }
    return $this->enabled();
  }

  public function setHost($host){
    $this->hosts[]=$host;
    if ($this->enabled()){
      $this->redisObject->connect($host);
    }
  }

  public function enabled(){
    return $this->redisObject!==null;
  }

  public function __get($name){
    $this->connect();
    if ($this->exists($name)) {
      return $this->get($name);
    }else{
      return null;
    }
  }

  public function __set($name, $val){
    $this->connect();
    if ($val === null && ($this->exists($name) !== false)){
      $this->remove($name);
    }elseif($val !== null){
      $this->set($name, $val);
    }
  }

  public function set($name, $val, $ttl = null){
    $this->connect();
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
  }

  public function get($name){
    $this->connect();
    return $this->redisObject->get($name);
  }

  public function exists($name){
    $this->connect();
    return $this->redisObject->exists($name);
  }

  public function remove($name){
    $this->connect();
    $this->redisObject->del($name);
  }

  public function arraySize($arr){
    $this->connect();
    return $this->redisObject->lSize($arr);
  }

  public function arrayGetItem($arr, $index){
    $this->connect();
    return $this->redisObject->lGet($arr, $index);
  }

  public function arraySetItem($arr, $index, $value, $ttl=null){
    $this->connect();
    return $this->redisObject->lSet($arr, $index, $value);
  }

  public function arrayRemoveItem($arr, $value, $count){
    $this->connect();
    return $this->redisObject->lRem($arr, $value, $count);
  }

  public function arrayDelete($arr){
    $this->connect();
    $this->redisObject->del($arr);
  }

  public function arrayGet($arr){
    $this->connect();
    $answer = Array();
    while ($a = $this->redisObject->lPop($arr)){
      $answer[] = $a;
    }
    return $answer;
  }

  public function groupSize($hash){
    $this->connect();
    return $this->redisObject->hLen($hash);
  }

  public function groupGetItem($hash, $key){
    $this->connect();
    return $this->redisObject->hGet($hash, $key);
  }

  public function groupSetItem($hash, $key, $value, $ttl=null){
    $this->connect();
    return $this->redisObject->hSet($hash, $key, $value);
  }

  public function groupRemoveItem($hash, $key){
    $this->connect();
    $this->redisObject->hDel($hash, $key);
  }

  public function groupDelete($hash){
    $this->connect();
    $this->redisObject->del($hash);
  }

  public function groupGet($hash){
    $this->connect();
    $this->redisObject->hGetAll($hash);
  }

  public function queryPush($qry, $value){
    $this->connect();
    $this->redisObject->rPush($qry, $value);
  }

  public function queryPop($qry, $blocking = false){
    $this->connect();
    if ($blocking){
      $answer = $this->redisObject->blPop($qry, $blocking);
      return $answer[1];
    }else{
      return $this->redisObject->lPop($qry);
    }
  }

  public function stackPush($stack, $value){
    $this->connect();
    $this->queryPush($stack, $value);
  }

  public function stackPop($stack, $blocking = false){
    $this->connect();
    if ($blocking){
      $answer = $this->redisObject->brPop($stack, $blocking);
      return $answer[1];
    } else
      return $this->redisObject->rPop($stack);
  }

  public function Status(){
    $this->connect();
    return $this->redisObject->info();
  }

  public function lock($lockName, $ttl = 1, $timeWait = 1){
    $this->connect();
    $timeLeft = min(10, abs(intval($timeWait)));
    $ttl      = min(10, abs(intval($ttl)));
    while (!$this->redisObject->setnx("LOCK_" . $lockName, \x\Tools\Time::microTime() + $ttl)) {
      if (doubleval($this->get("LOCK_" . $lockName)) <= \x\Tools\Time::microTime()) {
        $this->set("LOCK_" . $lockName, \x\Tools\Time::microTime() + $ttl);
        return true;
      }
      usleep(10);
      $timeLeft -= 10;
      if ($timeLeft < 0)
        return false;
    }
    return true;
  }

  public function unlock($lockName){
    $this->connect();
    $this->redisObject->del("LOCK_" . $lockName);
  }

  public function __isset($name) {
    $this->connect();
    return $this->exists($name);
  }

  public function __unset($name) {
    $this->connect();
    $this->remove($name);
  }
}