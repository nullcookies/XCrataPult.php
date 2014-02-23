<?php
namespace X\Data;
if (!defined('__XDIR__')) die();

use X\Tools\Time;
use X\Tools\Values;

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

  public function exists($name){
    return !!$this->link->groupGetItem($this->getRedisKey(), $name);
  }

  public function remove($name){
    $this->link->groupRemoveItem($this->getRedisKey(), $name);
  }

  public function getBlockName(){
    return $this->blockName;
  }

  public function destroy(){
    $this->link->groupDelete($this->getRedisKey());
  }

} 