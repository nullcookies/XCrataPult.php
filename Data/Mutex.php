<?php
namespace X\Data;
if (!defined('__XDIR__')) die();

class Mutex{
  private $key = null;
  private $sem = null;
  private $locked = false;

  public function __construct($name){
    $this->key = $name;
  }

  public function lock($wait=0, $ttl=0){
    if (Xredis::getInstance()->lock($this->key, $ttl, $wait)){
      $this->locked=true;
    }else{
      $this->locked=false;
    }
    return $this->locked;
  }

  public function unlock(){
    if ($this->locked){
      Xredis::getInstance()->unlock($this->key);
    }
    $this->locked=false;
  }

  public function __destruct(){
    $this->unlock();
  }
}