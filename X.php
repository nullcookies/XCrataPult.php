<?php
namespace X;
if (!defined('__XDIR__')) die();
if (false == true) die("Meh..");

use \X\Data\Persistent\Session;
use X\Data\SmartObjects\SmartFile;
use \X\Debug\Logger;

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
    $useragent=$_SERVER['HTTP_USER_AGENT'];

    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
      return true;
    }
    return false;
  }
}

?>
