<?php
namespace X\Data;
if (!defined('__XDIR__')) die();

class SharedMemoryBlock{

  private $link;
  private $blockName;

  public function __construct($blockName, $redisConnection=null){
    $this->blockName = $blockName;
    if (!$redisConnection){
      $this->link = &Xredis::getInstance();
    }
  }

  public function getRedisKey(){
    return "SharedMemoryBlock_KEY_".$this->blockName;
  }

  public function get($name){
    return $this->link->groupGetItem($this->getRedisKey(), $name);
  }

  public function set($name, $val){
    $this->link->groupSetItem($this->getRedisKey(), $name, $val);
  }

  public function max($name, $val){
    $mutex = new Mutex("LOCK_".$this->getRedisKey());
    $mutex->lock();
    if ($this->get($name)<$val){
      $this->link->groupSetItem($this->getRedisKey(), $name, $val);
    }
    $mutex->unlock();
  }

  public function min($name, $val){
    $mutex = new Mutex("LOCK_".$this->getRedisKey());
    $mutex->lock();
    if ($this->get($name)>$val){
      $this->link->groupSetItem($this->getRedisKey(), $name, $val);
    }
    $mutex->unlock();
  }

  public function inc($name, $val=1){
    $this->link->groupIncItem($this->getRedisKey(), $name, $val);
  }

  public function dec($name, $val=1){
    $this->link->groupDecItem($this->getRedisKey(), $name, $val);
  }

  public function exists($name){
    return !!$this->link->groupGetItem($this->getRedisKey(), $name);
  }

  public function remove($name){
    $this->link->groupRemoveItem($this->getRedisKey(), $name);
  }

  public function getBlockName(){
    return $this->blockName;
  }

  public function dump(){
    return $this->link->groupGet($this->getRedisKey());
  }

  public function destroy(){
    $this->link->groupDelete($this->getRedisKey());
  }

} 