<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 14.05.14
 * Time: 1:43
 */

namespace X\Data\SmartObjects;

class SmartArray extends \ArrayObject{
  protected $array;

  public function __construct(array $array=Array()) {
    $this->array = $array;
  }

  public function append($value) {
    $this->array[] = $value;
  }

  function offsetSet($key, $value) {
    if ($key)
      $this->array[$key] = $value;
    else
      $this->array[] = $value;
  }

  function offsetGet($key) {
    if ( array_key_exists($key, $this->array) )
      return $this->array[$key];

    return null;
  }

  function get($key) {
    try{
      return $this->offsetGet($key);
    }catch(\OutOfBoundsException $e){
      return null;
    }
  }

  function &asArray()
  {
    return $this->array;
  }

  function offsetUnset($key) {
    if ( array_key_exists($key, $this->array) )
      unset($this->array[$key]);
  }

  function offsetExists($offset) {
    return array_key_exists($offset, $this->array);
  }

  function exists($offset)
  {
    return $this->offsetExists($offset);
  }

  public function dump() {
    var_dump($this->array);
  }
}