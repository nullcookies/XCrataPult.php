<?php
namespace X\Data\Persistent;
use X\X;

if (!defined('__XDIR__')) die();


class Cache{
  /**
   * @var \Redis
   */
  private $redisObject = null;

  private static $instances=[];
  private static $tried=false;

  /**
   * @param string $host Redis host
   * @return Cache
   */
  public static function &getInstance($host=null){
    if (!self::$instances['default'] || !self::$instances[$host]){
      $called_class = get_called_class();
      if ($host!=null){
        self::$instances[$host] = new $called_class($host);
        if (!self::$instances['default']){
          self::$instances['default']=self::$instances[$host];
        }
      }elseif (self::$instances['default']==null){
        self::$instances['default'] = new $called_class("/var/run/redis/redis.sock"); //default for XCrataPult.server
      }
    }
    return $host===null ? self::$instances['default'] : self::$instances[$host];
  }

  public static function reset(){
    self::$instances=[];
  }

  private function __construct($host){
    if (!class_exists("\\Redis")){
      throw new \RuntimeException("Please, install 'phpredis'");
    }

    $this->redisObject = new \Redis();

    if ($this->redisObject){
      try{
        if ($this->redisObject->connect($host)){
          if (function_exists('igbinary_serialize') && defined('\\Redis::SERIALIZER_IGBINARY')){
            $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
          }else{
            $this->redisObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
          }
        }else{
          $this->redisObject=null;
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
    if (!$this->redisObject){
      return null;
    }
    if (is_array($trigger)){
      $trigger=implode(" /g:::", $trigger);
    }
    if (is_array($deleteElement)){
      if (count($deleteElement)==1){
        $deleteElement = $deleteElement[0]." /g:::";
      }else{
        $deleteElement=implode(" /g:::", $deleteElement);
      }
    }
    $this->stackPush($trigger, $deleteElement);
  }

  public function fireModifyTrigger($trigger){
    if (!$this->redisObject){
      return null;
    }
    if (is_array($trigger)){
      $trigger=implode(" /g:::", $trigger);
    }
    while($element = $this->stackPop($trigger)){
      if (strpos($element, " /g:::")){
        $element=explode(" /g:::", $element);
        if (!$element[1]){
          $this->groupDelete(trim($element[0]));
        }else{
          $this->groupRemoveItem($element[0], $element[1]);
        }
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
    return self::$instances['default']!==null && self::getInstance()->alive();
  }

  public function __get($name){
    if (!$this->redisObject){
      return null;
    }
    if ($this->exists($name)) {
      return $this->get($name);
    }else{
      return null;
    }
  }

  public function __set($name, $val){
    if (!$this->redisObject){
      return null;
    }
    if ($val === null && ($this->exists($name) !== false)){
      $this->remove($name);
    }elseif($val !== null){
      $this->set($name, $val);
    }
  }

  public function set($name, $val, $ttl = null){
    if (!$this->redisObject){
      return null;
    }
    if ($val === null && ($this->redisObject->exists($name) !== false)){
      $this->remove($name);
    }elseif ($val !== null){
      if ($ttl){
        $this->redisObject->setex($name, $ttl, $val);
      }else{
        $this->redisObject->set($name, $val);
      }
    }
    $this->fireModifyTrigger($name);
  }

  public function get($name){
    if (!$this->redisObject){
      return null;
    }
    $answer=$this->redisObject->get($name);
    if ($answer!==null){
      X::debugCacheHit($name);
    }else{
      X::debugCacheMiss($name);
    }
    return $answer;
  }

  public function exists($name){
    if (!$this->redisObject){
      return false;
    }
    return $this->redisObject->exists($name);
  }

  public function remove($name){
    if (!$this->redisObject){
      return null;
    }
    $this->redisObject->del($name);
    $this->fireModifyTrigger($name);
  }

  public function groupSize($hash){
    if (!$this->redisObject){
      return null;
    }
    return $this->redisObject->hLen($hash);
  }

  public function groupGetItem($hash, $key){
    if (!$this->redisObject){
      return null;
    }
    $ttl = $this->redisObject->hGet($hash, $key."@TTL");
    $name = $hash.'::'.$key;
    $ttl=intval($ttl);
    if ($ttl && $ttl<time()){
      $this->groupRemoveItem($hash, $key);
      X::debugCacheMiss($name);
      return false;
    }
    $answer=$this->redisObject->hGet($hash, $key);
    if ($answer!==null){
      X::debugCacheHit($name);
    }else{
      X::debugCacheMiss($name);
    }
    return $answer;
  }

  public function groupSetItem($hash, $key, $value, $ttl=null, $noTrigger=false){
    if (!$this->redisObject){
      return null;
    }
    if (!$noTrigger){
      $this->fireModifyTrigger([$hash, $key]);
    }
    $ttl=intval($ttl);
    if ($ttl) {
      $this->redisObject->hSet($hash, $key . "@TTL", time() + $ttl);
    }
    return $this->redisObject->hSet($hash, $key, $value);
  }

  public function groupRemoveItem($hash, $key){
    if (!$this->redisObject){
      return null;
    }
    $this->fireModifyTrigger([$hash, $key]);
    $this->redisObject->hDel($hash, $key);
    $this->redisObject->hDel($hash, $key."@TTL");
  }

  public function groupDelete($hash){
    if (!$this->redisObject){
      return null;
    }
    $this->fireModifyTrigger($hash);
    $this->redisObject->del($hash);
  }

  public function groupGet($hash){
    if (!$this->redisObject){
      return null;
    }
    return $this->redisObject->hGetAll($hash);
  }

  public function queuePush($queue, $value){
    if (!$this->redisObject){
      return null;
    }
    return $this->redisObject->rPush($queue, $value);
  }

  public function queuePop($queue, $blocking = false){
    if (!$this->redisObject){
      return null;
    }
    if ($blocking){
      $answer = $this->redisObject->blPop($queue, $blocking);
      return $answer[1];
    }else{
      return $this->redisObject->lPop($queue);
    }
  }

  public function stackPush($stack, $value){
    if (!$this->redisObject){
      return null;
    }
    return $this->queuePush($stack, $value);
  }

  public function stackPop($stack, $blocking = false){
    if (!$this->redisObject){
      return null;
    }
    if ($blocking){
      $answer = $this->redisObject->brPop($stack, $blocking);
      return $answer[1];
    } else  {
      return $this->redisObject->rPop($stack);
    }
  }

  public function Status(){
    if (!$this->redisObject){
      return null;
    }
    return $this->redisObject->info();
  }

  public function lock($lockName, $ttl = 1, $timeWait = 0){
    if (!$this->redisObject){
      return null;
    }
    $timeWaitTo = $timeWait ? intval($timeWait) + time()   : 0;
    $ttlTo      = $ttl ? intval($ttl) + time() : 1;
    if ($this->isLocked($lockName)){
      return false;
    }
    while (!$this->redisObject->setnx("LOCK_".$lockName, $ttlTo)) {
      if ($this->get("LOCK_".$lockName)==1){
        return false;
      }
      if (intval($this->get("LOCK_".$lockName)) <= time()) {
        $this->set("LOCK_".$lockName, $ttlTo);
        return true;
      }
      if ($timeWait==0 || $timeWaitTo<time()){
        return false;
      }
      usleep(100);
    }
    return true;
  }

  public function isLocked($lockName){
    if (!$this->redisObject){
      return null;
    }
    $val = $this->get("LOCK_".$lockName);
    if ($val===1 || $val>=time()){
      return true;
    }else{
      return false;
    }
  }

  public function unlock($lockName){
    if (!$this->redisObject){
      return null;
    }
    $this->redisObject->del("LOCK_" . $lockName);
  }

  public function flushAll(){
    if (!$this->redisObject){
      return null;
    }
    $this->redisObject->flushAll();
  }

  public function keys($pattern='*'){
    if (!$this->redisObject){
      return null;
    }
    return $this->redisObject->keys($pattern);
  }

  public function __isset($name){
    if (!$this->redisObject){
      return null;
    }
    return $this->exists($name);
  }

  public function __unset($name){
    if (!$this->redisObject){
      return null;
    }
    $this->remove($name);
  }
}