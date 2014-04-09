<?php

namespace X\Data\DB;

use \X\Data\DB\Iterator;
use \X\Data\DB\Interfaces\IDB;
use X\Validators\Values;

class Collection extends \ArrayObject
{
  private $eof = false;
  private $res = null;
  private $row = 0;
  private $count = 0;
  private $lastRow = null;
  private $instantiator = null;
  private $iterator = null;
  private $driver = null;

  const BAD_CALLBACK = 801;
  const BAD_QUERY_RESOURCE = 802;

  public function __construct($res, IDB &$driver, $instantiator=null){
    if (!is_resource($res)){
      if (trim($res)){
        $res = $driver->query($res);
      }
    }

    if (!is_resource($res) || $driver->errno()){
      throw new \Exception("Resource (or query) provided for collection (".$res.") is not resource (query)!", self::BAD_QUERY_RESOURCE);
    }

    if (Values::isCallback($instantiator)){
      $this->instantiator = $instantiator;
    }elseif($instantiator){
      throw new \Exception("Callback provided (".$instantiator.") for collection is not callable!", self::BAD_CALLBACK);
    }

    $this->driver = &$driver;

    $this->res   = $res;
    $this->count = $this->driver->numRows($res);

    $this->eof = ($this->count == 0);
  }

  public function __destruct(){
    $this->driver->freeResource($this->res);
  }

  public function row($num = null){
    if ($num === null){
      $num = $this->row;
    }

    if ($num === $this->row && $this->lastRow !== null){
      return $this->instantiator!==null ? call_user_func($this->instantiator, $this->lastRow) : $this->lastRow; // todo: codedup
    }

    if ($num >= $this->count) {
      $this->eof = true;
      return $this->lastRow = false;
    }

    if ($num < 0){
      return false;
    }

    $this->driver->dataSeek($this->res, $num);

    $this->lastRow = $this->driver->getNext($this->res);
    $this->row     = $num;

    $this->eof = is_array($this->lastRow) ? false : true;
    return $this->instantiator!==null ? call_user_func($this->instantiator, $this->lastRow) : $this->lastRow;
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
