<?php
namespace X\Debug;
if (!defined('__XDIR__')) die();

class Tracer extends \X\AbstractClasses\PrivateInstantiation{
  public static function getCaller(){
    list(, $caller) = debug_backtrace(false);
    return $caller;
  }

  /**
   * @return string
   */
  public static function getCallerClass(){
    $caller = debug_backtrace(false);
    if (count($caller)>=3 && array_key_exists('class',$caller[2])){
      return '\\'.$caller[2]['class'];
    }else{
      return null;
    }
  }

}
