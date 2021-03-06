<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 17.03.14
 * Time: 18:43
 */

namespace X\Validators;
if (!defined('__XDIR__')) die();

use X\AbstractClasses\PrivateInstantiation;
use X\Validators\Data\PublicEmailServers;
use X\Validators\Data\SQLReserved;

class Values extends PrivateInstantiation{

  public static function isIP($ip){
    return filter_var($ip, FILTER_VALIDATE_IP);
  }

  public static function isCallback($var){
    if ($var instanceof \Closure) {
      return true;
    }
    if (is_array($var) && count($var) == 2) {
      $var = array_values($var);
      if ((!is_string($var[0]) && !is_object($var[0])) || (is_string($var[0]) && !class_exists($var[0]))) {
        return false;
      }
      $isObj = is_object($var[0]);
      $class = new \ReflectionClass($isObj ? get_class($var[0]) : $var[0]);
      if ($class->isAbstract()) {
        return false;
      }
      try {
        $method = $class->getMethod($var[1]);
        if (!$method->isPublic() || $method->isAbstract()) {
          return false;
        }
        if (!$isObj && !$method->isStatic()) {
          return false;
        }
      } catch (\ReflectionException $e) {
        return false;
      }
      return true;
    } elseif (is_string($var) && function_exists($var)) {
      return true;
    }
    return false;
  }

  public static function isJSON($data){
    if (!is_string($data)){
      return false;
    }

    json_decode($data);
    return (json_last_error() == JSON_ERROR_NONE);
  }
  public static function isUnsignedFloat($val){
    $val=str_replace(" ","",trim($val));
    return eregi("^([0-9])+([\.|,]([0-9])*)?$",$val);
  }

  public static function isUnsignedInteger($val){
    $val=str_replace(" ","",trim($val));
    return eregi("^([0-9])+$",$val);
  }

  public static function isSignedFloat($val){
    $val=str_replace(" ","",trim($val));
    return eregi("^-?([0-9])+([\.|,]([0-9])*)?$",$val);
  }

  public static function isSignedInteger($val){
    $val=str_replace(" ","",trim($val));
    return preg_match("/^-[1-9][0-9]*$/",$val);
  }

  public static function startsWith($haystack, $needle){
    $haystack = strtolower($haystack);
    $needle = strtolower($needle);
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public static function endsWith($haystack, $needle){
    $haystack = strtolower($haystack);
    $needle = strtolower($needle);
    $length = strlen($needle);
    $start  = $length * -1;
    return (substr($haystack, $start) === $needle);
  }

  public static function isAssoc(&$arr){
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  public static function isSocket($var){
    return (is_resource($var) === true && @get_resource_type($var) === 'Socket');
  }

  public static function isSuitableForVarName($name){
    return !!preg_match("/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/", $name);
  }

  public static function isAlphanum($string){
    return !preg_match('/[^a-z0-9]/i', $string);
  }

  public static function isSQLname($string, $ignoreReserved=false, &$fix=NULL){
    if (strlen($string)>2){
      $string = trim($string);
    }
    if ($string[0]=='`' && substr($string, -1)=='`' && strlen($string)>2){
      $string = substr($string, 1, -1);
      if (!preg_match('/[^a-z0-9_]/i', $string)){
        if ($fix){
          $fix = $string;
        }
        return true;
      }else{
        return false;
      }
    }else{
      if (!preg_match('/[^a-z0-9_]/i', $string) && !preg_match('/[^a-z_]/i', $string[0]) && ($ignoreReserved || !array_key_exists(strtoupper($string), SQLReserved::$list))){
        if ($fix){
          $fix = $string;
        }
        return true;
      }else{
        return false;
      }
    }
  }

  public static function unHTML($string){
    return htmlspecialchars($string);
  }

  public static function isFilename(&$name, $fix=false){
    $disallowedSymbols="\\/?*:;{}\\\\";
    if (preg_match("/^[^".$disallowedSymbols."]+$/", $name)){
      return true;
    }elseif($fix){
      $name = preg_replace("/[".$disallowedSymbols."]/", "_", $name);
      return true;
    }
    return false;
  }

  public static function isEmail($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  public static function isPublicEmail($email){
    if (!self::isEmail($email)){
      return false;
    }
    $email = explode("@", $email);
    $domain = trim(strtolower(array_pop($email)));
    return array_key_exists($domain, PublicEmailServers::$list);
  }

  public static function isURL($url){
    return filter_var($url, FILTER_VALIDATE_URL);
  }
} 