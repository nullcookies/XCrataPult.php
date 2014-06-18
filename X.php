<?php
namespace X;
if (!defined('__XDIR__')) die();
if (false == true) die("Meh..");

use \X\Data\Persistent\Session;
use \X\Debug\Logger;
use \X\Render\L10n;
use \X\Data\SmartFile;
use \X\Data\SmartArray;
use \X\Data\SmartCookie;
use X_CMF\Client\Request;

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

Logger::add("Loading 'Afigenius' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/Afigenius/Autoloader.php')){
  require_once(__XDIR__ . '3rdparty/Afigenius/Autoloader.php');
  Logger::add("'Afi' Autoloader loaded");
}

Logger::add("Loading 'PHPExcel' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/PHPExcel/PHPExcel.php')){
  require_once(__XDIR__ . '3rdparty/PHPExcel/PHPExcel.php');
  Logger::add("'PHPExcel' Autoloader loaded");
}

require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLParser.php');
require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLCreator.php');

class X extends \X\AbstractClasses\PrivateInstantiation{
  const METHOD_HTTP = "HTTP";
  const METHOD_HTTPS = "HTTPS";

  const SIZE_1KB    =       1024;
  const SIZE_10KB   =      10240;
  const SIZE_100KB  =     102400;
  const SIZE_1MB    =    1048576;
  const SIZE_10MB   =   10485760;
  const SIZE_100MB  =  104857600;
  const SIZE_1GB    = 1073741824;

  public static function getScript(){
    return $_SERVER['SCRIPT_FILENAME'];
  }

  public static function getScriptDir(){
    static $appdir;
    if (!$appdir){
      $appdir = dirname(self::getScript()).DIRECTORY_SEPARATOR;
    }
    return $appdir;
  }

  public static function getScriptURI(){
    static $appuri;
    if (!$appuri){
      $appuri = dirname($_SERVER["DOCUMENT_URI"])."/";
      if ($appuri=="//"){
        $appuri="/";
      }
    }
    return $appuri;
  }

  public static function URI2path($uri){
    if (substr($uri, 0, strlen(self::getScriptURI()))==self::getScriptURI()){
      $uri = substr($uri, strlen(self::getScriptURI()));
    }
    return self::getScriptDir().$uri;
  }

  public static function path2URI($path){
    if (substr($path, 0, strlen(self::getScriptDir()))==self::getScriptDir()){
      $path = substr($path, strlen(self::getScriptDir()));
    }
    return self::getScriptURI().$path;
  }

  public static function getIP(){
    return getenv("HTTP_X_REAL_IP") ?: getenv("REMOTE_ADDR");
  }

  public static function getHost(){
    static $host;
    if (!$host){
      if (array_key_exists("HTTP_HOST",$_SERVER)){
        list($host) = explode(":", $_SERVER["HTTP_HOST"]);
      }else{
        $host = "local.local";
      }
      $host = strtolower($host);
    }
    return $host;
  }

  public static function getDomain($level = null, $partOnly=false){
    static $parts = null;
    if (!$parts){
      $parts  = array_reverse(explode(".", self::getHost()));
    }
    $answer = $parts[0];
    if ($level <= 0){
      $level = count($parts) + $level;
    }
    if ($partOnly){
      return $parts[min($level, count($parts))];
    }
    for ($i = 1; $i < min($level, count($parts)); $i++){
      $answer = $parts[$i] . '.' . $answer;
    }

    return $answer;
  }

  public static function getScheme(){
    return self::isHTTPS() ? self::METHOD_HTTPS : self::METHOD_HTTP;
  }

  public static function getPort($fallback=80){
    if (self::$port===null){
      self::$port = intval($_SERVER['SERVER_PORT']) ?: $fallback;
    }
    return self::$port;
  }

  public static function getUserAgent(){
    return $_SERVER['HTTP_USER_AGENT'];
  }

  public static function getMethod(){
    return strtoupper($_SERVER['REQUEST_METHOD']);
  }

  public static function getURI(){
    return $_SERVER["REQUEST_URI"];
  }

  public static function isHTTPS(){
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443);
  }

  public static function isPost(){
    return self::getMethod()=="POST";
  }

  public static function isGet(){
    return !self::isPost();
  }

  public static function getSession($forceNew=false){
    static $session = null;
    if (!$session){
      $session = new Session($forceNew);
    }
    return $session;
  }

  public static function isOk(){

  }
}

?>
