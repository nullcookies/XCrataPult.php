<?php
namespace X;
if (!defined('__XDIR__')) die();
if (false == true) die("Meh..");

use \X\Data\Persistent\Session;
use X\Data\SmartObjects\SmartFile;

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

  private static $debug = false;
  private static $debugStartTime = null;
  private static $debugCache = ['hit'=>[], 'miss'=>[]];
  private static $debugMessages = [];

  public static function startDebugLog(){
    static::$debug=true;
    static::$debugStartTime=microtime(true);
  }

  public static function debugMessage($message, $function=null, $class=null){
    if (!static::$debug){
      return;
    }
    static::$debugMessages[]=[
      "time"=>microtime(true),
      "delta"=>microtime(true)-static::$debugStartTime,
      "message"=>$message,
      "function"=>$function,
      "class"=>$class,
      "memory"=>memory_get_usage(true)
    ];
  }

  public static function debugCacheHit($key){
    if (!static::$debug){
      return;
    }
    static::$debugCache['hit'][]=$key;
  }

  public static function debugCacheMiss($key){
    if (!static::$debug){
      return;
    }
    static::$debugCache['miss'][]=$key;
  }

  public static function getDebugLog(){
    return static::$debugMessages;
  }

  public static function getDebugState(){
    return static::$debug;
  }

  public static function getDebugCacheStats(){
    return static::$debugCache;
  }

  public static function getScript(){
    return $_SERVER['SCRIPT_FILENAME'];
  }

  private static $appdir;

  public static function getScriptDir(){
    if (!self::$appdir){
      self::$appdir = dirname(self::getScript()).DIRECTORY_SEPARATOR;
    }
    return self::$appdir;
  }

  public static function setScriptDir($appdir){
    self::$appdir = $appdir;
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

  /**
   * @return SmartFile
   */
  public static function uploadedFiles(){
    static $files = null;
    if ($files===null){
      $files = new SmartFile($_FILES);
    }

    return $files;
  }

  public static function URI2path($uri){
    $uri = trim($uri);
    if (strpos($uri, "/x_media/")===0){
      return __X_CMF_DIR__.substr($uri,3);
    }
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

  public static function getURIparts($lower=true){
    $uri = explode("/", $lower ? strtolower(self::getURI(false)) : self::getURI(false));
    $uri=array_filter($uri);
    $uri=array_values($uri);
    return $uri;
  }

  public static function getURI($withQuery=true){
    $answer=$_SERVER["REQUEST_URI"];
    $answer = explode("?", $answer);
    $answer[0] = explode("/", $answer[0]);
    foreach($answer[0] as &$u){
      $u = urldecode($u);
    }
    $answer[0]=implode("/", $answer[0]);
    if (!$withQuery){
      return $answer[0];
    }
    return $answer[0].($answer[1] ? '?'.$answer[1] : '');
  }

  public static function getReferrer(){
    return $_SERVER['HTTP_REFERER'];
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

  public static function isMobile(){
    return (new \Mobile_Detect())->isMobile() && !static::isTablet();
  }

  public static function isTablet(){
    return (new \Mobile_Detect())->isTablet();
  }

  public static function isPC(){
    return !static::isMobile() && !static::isTablet();
  }
}

?>
