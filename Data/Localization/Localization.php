<?php
namespace X\Data\Localization;
if (!defined('__XDIR__')) die();

// TODO: add Exceptions
// TODO: add cache support

use X\C;
use X\Data\Localization\Languages;
use X\Data\SmartObjects\PlaceholdersString;
use X\Data\SmartObjects\PluralString;
use \x\debug\Logger;
use \x\abstractClasses\PrivateInstantiation;
use \x\tools\Validators;
use \x\tools\FileSystem;
use \x\x;

class Localization{
  private static $languages = [];
  private static $dictionary = [];
  private static $paths=[];
  private static $currentLanguage=null;
  private static $displayKeys=false;

  public static function setFolder($path){
    self::$paths[] = C::checkDir($path);
  }

  public static function setLanguages($languages){
    $languages = explode(",", $languages);
    array_walk($languages, function(&$el){$el=trim($el);});
    foreach($languages as $language){
      self::addLanguage($language, Languages::$code2local[$language]);
    }
  }

  public static function addLanguage($code, $name=null){
    self::$languages[$code]  = $name?:Languages::$code2local[$code];
    self::$dictionary[$code] = [];
    if (self::$currentLanguage===null){
      self::$currentLanguage = $code; // first registered language is current by default
    }
    return self::loadFromConfig($code);
  }

  public static function languageExists($code){
    return array_key_exists($code, self::$languages);
  }

  public static function setLanguage($code){
    if (array_key_exists($code, self::$dictionary)){
      self::$currentLanguage = $code;
    }else{
      throw new \InvalidArgumentException("There is no registered language with code '".$code."'");
    }
  }

  public static function displayUnknownKeys($displayKeys=true){
    self::$displayKeys=$displayKeys;
  }

  public static function hasKey($path){
    $language = $language ?: self::$currentLanguage;
    if (array_key_exists($language, self::$dictionary)){
      $root = &self::$dictionary[$language];
      $path_ = explode(".", $path);
      for($i=0; $i<count($path_); $i++){
        if (is_array($root) && array_key_exists($path_[$i], $root)){
          $root = &$root[$path_[$i]];
          if ($i>=count($path_)-1){
            return true;
          }
        }else{
          break;
        }
      }
    }
    return false;
  }

  public static function get($path, $data=[]){
    $language = $language ?: self::$currentLanguage;
    if (array_key_exists($language, self::$dictionary)){
      //TODO: check cache
      $root = &self::$dictionary[$language];
      $path_ = explode(".", $path);
      for($i=0; $i<count($path_); $i++){
        if (is_array($root) && array_key_exists($path_[$i], $root)){
          $root = &$root[$path_[$i]];
          if ($i>=count($path_)-1){ //target reached
            $answer = $root;
            //TODO: cache answer
            if (is_string($answer) || is_array($answer)){
              return $answer;
            }elseif (is_object($answer) && in_array(get_class($answer),["X\\Data\\SmartObjects\\PlaceholdersString","X\\Data\\SmartObjects\\PluralString"])){
              return call_user_func_array([$answer,'render'], $data);
            }
          }
        }else{
          break;
        }
      }
      //TODO: cache null;
    }

    return self::$displayKeys ? $path : null;
  }

  public static function getCurrentLanguageCode(){
    return self::$currentLanguage;
  }

  public static function &getLanguageData($code=null){
    if ($code===null){
      $code = self::$currentLanguage;
    }
    if (array_key_exists($code, self::$dictionary)){
      return self::$dictionary[$code];
    }else{
      return [];
    }
  }

  public static function hasLanguage($code){
    return array_key_exists($code, self::$dictionary);
  }

  private static function gobbleDir($path, $code, $base=null){
    if ($base===null){
      $base = $path;
    }
    $files = FileSystem::dirList($path, '*');
    if (!$files){
      return;
    }
    foreach ($files as $file){
      Logger::add("Language file: " . $file . " (loading)");
      if (is_dir($file)){
        self::gobbleDir($file, $code, $base);
      }else{
        if (self::fromFile($code, $file, $base)){
          Logger::add("Language file: " . $file . " (OK)");
        }else{
          Logger::add("Language file: " . $file . " (ERROR)");
        }
      }
    }
  }

  private static function loadFromConfig($code){
    if (!self::$paths){
      throw new \InvalidArgumentException("Localization folder is not configured (l10n.folder). Language ".$code." was not added.");
    }

    foreach(self::$paths as $base){
      $base   = FileSystem::finalizeDirPath($base).$code;
      $files  = Array();

      if (!is_dir($base)){
        Logger::add("Language folder: ".$base. " doesn't exists.");
        continue;
      }

      self::gobbleDir($base, $code);
    }

    return true;
  }

  private static function fromFile($code, $filename, $base){
    $ext = explode(".", $filename);
    $ext = array_pop($ext);
    $path = str_replace($base, '', str_replace('.'.$ext, '', $filename));
    switch($ext){
      case 'ini':$data = parse_ini_file($filename, true);break;
      case 'yml':$data = yaml_parse_file($filename, 0, $ndocs, ["!pl"=>function($data){return new PluralString($data);},"!ps"=>function($data){return new PlaceholdersString($data);}]);break;
      default:   throw new \InvalidArgumentException("Can't parse language file '".$filename."'");
    }
    self::insertData($code, $path, $data);
  }

  private static function insertData($code, $path, $data){
    if (strlen($path)>1){
      if($path[0]==DIRECTORY_SEPARATOR){
        $path = substr($path,1);
      }
      $path = explode(DIRECTORY_SEPARATOR, $path);
      $root = &self::$dictionary[$code];
      foreach($path as $part){
        if (!array_key_exists($part, $root)){
          $root[$part]=[];
        }
        $root = &$root[$part];
      }
    }

    if ($data && is_array($data)) {
      $root = array_merge($root, $data);
      return true;
    }
  }
}

?>
