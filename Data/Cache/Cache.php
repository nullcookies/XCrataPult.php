<?php

namespace X\Data\Cache;

use \X\AbstractClasses\PrivateInstantiation;
use \X\Data\Cache\Interfaces\ICache;

/**
 * @method static void connect() tries to connect with specified driver
 */
class Cache extends PrivateInstantiation{

  const ERR_NO_SUCH_DRIVER = 601;
  const ERR_BAD_INTERFACE = 602;
  const ERR_NO_DRIVER = 603;

  private static $driver = null;
  private static $driverInstance = null;

  public static function setDriver($driver){
    $driver = strval($driver);
    $driver = ucfirst($driver);

    if (!class_exists($driver)){
      if (!class_exists("\\X\\Data\\Cache\\Drivers\\".$driver)){
        throw new \exception("Cache Driver '".$driver."' doesn't exist.", self::ERR_NO_SUCH_DRIVER);
      }else{
        $className = "\\X\\Data\\Cache\\Drivers\\".$driver;
      }
    }else{
      $className = $driver;
    }
    $interfaces = class_implements($className);
    if (!$interfaces || !in_array("X\\Data\\Cache\\Interfaces\\ICache", $interfaces)){
      throw new \exception("Specified driver for cache ".$driver." doesn't implement interface \\X\\Data\\Cache\\Interfaces\\ICache", self::ERR_BAD_INTERFACE);
    }

    self::$driver = $className;
  }

  /**
   * @return bool
   */
  public static function enabled(){
    try{
      return self::getDriver()->enabled();
    }catch(\exception $e){
      return false;
    }
  }

  /**
   * @throws \exception
   * @return ICache
   */
  public static function &getDriver(){
    if (self::$driverInstance===null){
      if (self::$driver===null){
        throw new \exception("Driver for cache hasn't been set yet.", self::ERR_NO_DRIVER);
      }
      self::$driverInstance = new self::$driver();
    }
    return self::$driverInstance;
  }

  public static function __callStatic($name, $arguments){
    if (!is_array($arguments) || !count($arguments)){
      return self::getDriver()->$name();
    }else{
      return call_user_func_array([self::getDriver(),$name], $arguments);
    }
  }
}
