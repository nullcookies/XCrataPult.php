<?php

namespace X\Data\DB;

use \X\Data\DB\Collection;

class Iterator extends \ArrayIterator{

  /**
   * @var \X\Data\DB\Collection
   */
  private $collection;

  public function offsetExists($index)
  {
    return $this->collection->offsetExists($index);
  }

  public function offsetGet($index)
  {
    return $this->collection->offsetGet($index);
  }

  public function offsetSet($index, $newval)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function offsetUnset($index)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function append($value)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function getArrayCopy()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function count()
  {
    echo 3;
    return $this->collection->count();
  }

  public function getFlags()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function setFlags($flags)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function asort()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function ksort()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function uasort($cmp_function)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function uksort($cmp_function)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function natsort()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function natcasesort()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function unserialize($serialized)
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function serialize()
  {
    throw new \BadMethodCallException("Iterator: This method is not applicable (".__METHOD__.")");
  }

  public function rewind()
  {
    $this->collection->Reset();
  }

  public function current()
  {
    return $this->collection->Current();
  }

  public function key()
  {
    return $this->collection->Position();
  }

  public function next()
  {
    return $this->collection->Next();
  }

  public function valid()
  {
    return !$this->collection->EOF();
  }

  public function seek($position)
  {
    return $this->collection->Position($position);
  }

  public function __construct(Collection &$collection, $flags = 0)
  {
    $this->collection = &$collection;
  }
}