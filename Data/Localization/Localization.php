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
  private static $path='';
  private static $currentLanguage=null;

  public static function setFolder($path){
    self::$path = C::checkDir($path);
  }

  public static function setLanguages($languages){
    $languages = explode(",", $languages);
    array_walk($languages, function(&$el){$el=trim($el);});
    foreach($languages as $language){
      self::addLanguage($language, Languages::$code2local[$language]);
    }
  }

  public static function addLanguage($code, $name){
    self::$languages[$code]  = $name;
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

  public static function get($path, $data=[]){
    $language = $language ?: self::$currentLanguage;
    if (array_key_exists($language, self::$dictionary)){
      //TODO: check cache
      $root = &self::$dictionary[$language];
      $path = explode(".", $path);
      for($i=0; $i<count($path); $i++){
        if (is_array($root) && array_key_exists($path[$i], $root)){
          $root = &$root[$path[$i]];
          if ($i>=count($path)-1){ //target reached
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

    return $default;
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

  private static function gobbleDir($path, $code){
    $files = FileSystem::dirList($path, '*');
    if (!$files){
      return;
    }
    foreach ($files as $file){
      Logger::add("Language file: " . $file . " (loading)");
      if (is_dir($file)){
        self::gobbleDir($file, $code);
      }else{
        if (self::fromFile($code, $file)){
          Logger::add("Language file: " . $file . " (OK)");
        }else{
          Logger::add("Language file: " . $file . " (ERROR)");
        }
      }
    }
  }

  private static function loadFromConfig($code){
    if (!self::$path){
      throw new \InvalidArgumentException("Localization folder is not configured (l10n.folder). Language ".$code." was not added.");
    }

    $base   = FileSystem::finalizeDirPath(self::$path);
    $files  = Array();

    if (!is_dir($base.$code)){
      throw new \InvalidArgumentException("Language folder: ".$base.$code. " doesn't exists.");
    }

    self::gobbleDir($base . $folder, $code);

    return true;
  }

  private static function fromFile($code, $filename){
    $ext = explode(".", $filename);
    $ext = array_pop($ext);
    $path = str_replace(self::$path.$code, '', str_replace('.'.$ext, '', $filename));
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
