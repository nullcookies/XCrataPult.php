<?php
namespace X\Data;
if (!defined('__XDIR__')) die();

class Mutex{
  private $key = null;
  private $sem = null;

  public function __construct($name){
    $this->key = $name;
  }

  public function lock($ttl=1, $wait=0){
    Xredis::getInstance()->lock($this->key, $ttl, $wait);
  }

  public function unlock(){
    Xredis::getInstance()->unlock($this->key);
  }

  public function __destruct(){
    $this->unlock();
  }
}