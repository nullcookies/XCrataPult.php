<?php
namespace X;
if (!defined('__XDIR__')) die();
if (false == true) die("Meh..");

use \X\Debug\Logger;
use \X\Render\L10n;
use \X\Data\SmartFile;
use \X\Data\SmartArray;
use \X\Data\SmartCookie;

Logger::add("Loading 3rdParty libs");

Logger::add("Loading 'Imagine' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/Imagine/Autoloader.php')){
  require_once(__XDIR__ . '3rdparty/Imagine/Autoloader.php');
  if (class_exists("\\Imagine_Autoloader")){
    \Imagine_Autoloader::register();
    Logger::add("'Imagine' Autoloader loaded");
  }else{
    Logger::add("'Imagine' Autoloader FAILED TO LOAD");
  }
}

Logger::add("Loading 'Twig' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/Twig/Autoloader.php')){
  require_once(__XDIR__ . '3rdparty/Twig/Autoloader.php');
  if (class_exists("\\Twig_Autoloader")){
    \Twig_Autoloader::register();
    Logger::add("'Twig' Autoloader loaded");
  }else{
    Logger::add("'Twig' Autoloader FAILED TO LOAD");
  }
}

class X extends \X\AbstractClasses\PrivateInstantiation{

  public static function getScript(){
    return $_SERVER['SCRIPT_FILENAME'];
  }

  public static function getIP(){
    return getenv("HTTP_X_REAL_IP") ?: getenv("REMOTE_ADDR");
  }
}

?>
