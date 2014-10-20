<?php
namespace X\AbstractClasses;
if (!defined('__XDIR__')) die();

abstract class Singleton{

  protected function __construct() {;}

  /**
   * @param bool $forceNew
   * @return static
   */
  public static function x($forceNew=false){
    static $classInstance = NULL;
    if (NULL === $classInstance || $forceNew){
      $className = get_called_class();
      if (NULL !== $classInstance){
        unset($classInstance);
      }
      $classInstance = new $className;
    }
    return $classInstance;
  }

  public static function FlushInstance(){
    return self::x(true);
  }

  public function __clone(){
    trigger_error('Cloning '.__CLASS__.' is not allowed.',E_USER_ERROR);
  }

  public function __wakeup(){
    trigger_error('Unserializing '.__CLASS__.' is not allowed.',E_USER_ERROR);
  }
}