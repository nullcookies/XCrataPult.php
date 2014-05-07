<?php

namespace X\Data\DB;

use X\C;
use X\Data\Persistent\Cache;
use \X\Data\DB\Iterator;
use \X\Data\DB\Interfaces\IDB;
use X\Debug\Logger;
use X\Validators\Values;
use X\X;

class Collection extends \ArrayObject{
  protected $eof = false;
  protected $res = null;
  protected $row = 0;
  protected $count = 0;
  protected $rowCache = [];
  protected $lastRow = null;
  protected $instantiator = null;
  protected $iterator = null;
  protected $driver = null;

  const BAD_CALLBACK = 801;
  const BAD_QUERY_RESOURCE = 802;

  public function __construct($res, IDB &$driver, $instantiator=null, $cacheKey=null, $cacheTTL=0){

    Logger::add("new Collection created for ".$res);

    if ($cacheKey && $cacheTTL && Cache::enabled()){
      Logger::add("Collection is cacheable");
      if ((Cache::getInstance()->get(C::getCacheTechPrefix()."ARR_".$cacheKey))<time()){
        Logger::add("NO VALID cache was found for Collection");
        Cache::getInstance()->arrayDelete($cacheKey);
      }else{
        Logger::add("FOUND cache for Collection");
        $this->rowCache = Cache::getInstance()->arrayGetAll($cacheKey);
        Logger::add("Collection was fetched from the cache");
      }
    }

    if (Values::isCallback($instantiator)){
      $this->instantiator = $instantiator;
    }elseif($instantiator){
      throw new \Exception("Callback provided (".$instantiator.") for collection is not callable!", self::BAD_CALLBACK);
    }

    if (count($this->rowCache)){
      Logger::add(count($this->rowCache)." element(s) in cache (".$cacheKey.") of Collection");
      $this->count = count($this->rowCache);
    }else{
      if (!is_resource($res)){
        if (trim($res)){
          $res = $driver->query($res);
        }
      }

      if (!is_resource($res) || $driver->errno()){
        throw new \Exception("Resource (or query) provided for collection (".$res.") is not resource (query)!", self::BAD_QUERY_RESOURCE);
      }

      $this->driver = &$driver;
      $this->res   = $res;
      $this->count = $this->driver->numRows($res);
      $this->num = 0;
      $this->lastRow= null;
      $this->eof = ($this->count == 0);

      Logger::add($this->count." element(s) were fetched from DB");
      if ($cacheKey && $cacheTTL && Cache::enabled() && ($this->count <= C::getDbCacheMaxrows())){
        Logger::add("Caching Collection");
        $i=0;
        $rowCacheTmp=[];
        foreach($this as $answer){
          $rowCacheTmp[]=$answer;
          Cache::getInstance()->arrayPush($cacheKey, $answer);
          Logger::add((++$i)." element(s) cached");
        }
        $this->rowCache=$rowCacheTmp;
        Cache::getInstance()->set(C::getCacheTechPrefix()."ARR_".$cacheKey, time()+$cacheTTL);
      }
    }
    Logger::add("Collection is ready!");
    $this->num = 0;
    $this->lastRow= null;
    $this->eof = ($this->count == 0);
  }

  public function __destruct(){
    if ($this->res){
      $this->driver->freeResource($this->res);
    }
  }

  public function row($num = null){
    if ($num === null){
      $num = $this->row;
    }

    if ($num<0 || $num >= $this->count) {
      $this->lastRow = false;
    }elseif (array_key_exists($num, $this->rowCache)){
      $this->lastRow = $this->rowCache[$num];
    }else{
      $this->driver->dataSeek($this->res, $num);
      $data = $this->driver->getNext($this->res);
      $this->lastRow = $this->rowCache[$num] = $this->instantiator!==null ? call_user_func($this->instantiator, $data) : $data;
    }

    $this->row = $num;
    $this->eof = $this->lastRow===false;

    return $this->lastRow;
  }

  public function reset(){
    $this->row = 0;
    $this->lastRow=null;
    $this->eof = ($this->count==0);
  }

  public function next(){
    if ($this->lastRow === null){
      $n = $this->row;
    }else{
      $n = $this->row + 1;
    }
    return $this->row($n);
  }

  public function position($num=null){
    if ($num===null){
      return $this->row;
    }

    if ($num >= $this->count) {
      $this->eof = true;
      return $this->lastRow = false;
    }

    if ($num < 0){
      $this->row = 0;
      return false;
    }

    return $this->row=$num;
  }

  public function current(){
    return $this->row($this->row);
  }

  public function prev(){
    return $this->row($this->row - 1);
  }

  public function first(){
    return $this->row(0);
  }

  public function last(){
    return $this->row($this->count - 1);
  }

  public function EOF(){
    return $this->eof;
  }

  public function size(){
    return $this->count;
  }

  public function offsetExists($index){
    if (is_string($index)){
      return false;
    }
    $index = intval($index);
    return $index >= 0 && $index < $this->count;
  }

  public function offsetGet($index){
    return $this->Row($index);
  }

  public function offsetSet($index, $newval){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function offsetUnset($index){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function append($value){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getArrayCopy(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function count(){
    return $this->count;
  }

  public function getFlags(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function setFlags($flags){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function asort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function ksort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function uasort($cmp_function){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function uksort($cmp_function){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function natsort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function natcasesort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function unserialize($serialized){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function serialize(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getIterator(){
    if (!$this->iterator){
      $this->iterator = new Iterator($this);
    }
    return $this->iterator;
  }

  public function exchangeArray($input){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function setIteratorClass($iterator_class){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getIteratorClass(){
    return "\\X\\Data\\DB\\Iterator";
  }
}
