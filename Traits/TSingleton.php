<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 02.08.14
 * Time: 12:01
 */

namespace X\Traits;

trait TSingleton{
  protected static $instance;

  final public static function getInstance(){
    return isset(static::$instance)
      ? static::$instance
      : static::$instance = new static;
  }

  final private function __construct(){
    $this->init();
  }

  protected function init() {}
  final private function __wakeup() {}
  final private function __clone() {}
}