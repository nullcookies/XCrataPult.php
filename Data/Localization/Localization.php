<?php
namespace X\Data\Localization;
if (!defined('__XDIR__')) die();

// TODO: add Exceptions
// TODO: add cache support

use X\C;
use \x\tools\FileSystem;
use X\Validators\Values;

class Localization{
  private static $languages = [];
  private static $dictionary = [];
  private static $paths=[];
  private static $currentLanguage=null;
  private static $displayKeys=false;

  private static $fallback = false;

  public static function setFallback($fallback){
    self::$fallback = $fallback;
  }

  public static function setFolder($path){
    static::$paths[] = C::checkDir($path);
    static::$paths = array_unique(static::$paths);
    static::update();
  }

  public static function setLanguages($languages){
    $languages = explode(",", $languages);
    array_walk($languages, function(&$el){$el=trim($el);});
    foreach($languages as $language){
      static::addLanguage($language, Languages::$code2local[$language]);
    }
  }

  public static function addLanguage($code, $name=null){
    if (!array_key_exists($code, static::$languages)){
      static::$languages[$code]  = $name?:Languages::$code2local[$code];
      static::$dictionary[$code] = [];
      if (static::$currentLanguage===null){
        static::$currentLanguage = $code; // first registered language is current by default
      }
    }
    return static::loadFromConfig($code);
  }

  public static function languageExists($code){
    return array_key_exists($code, static::$languages);
  }

  public static function setLanguage($code){
    if (array_key_exists($code, static::$dictionary)){
      static::$currentLanguage = $code;
    }else{
      throw new \InvalidArgumentException("There is no registered language with code '".$code."'");
    }
  }

  public static function displayUnknownKeys($displayKeys=true){
    static::$displayKeys=$displayKeys;
  }

  public static function hasKey($path){
    $language = static::$currentLanguage;
    if (array_key_exists($language, static::$dictionary)){
      $root = &static::$dictionary[$language];
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
    $language = static::$currentLanguage;
    if (array_key_exists($language, static::$dictionary)){
      //TODO: check cache
      $root = &static::$dictionary[$language];
      $path_ = explode(".", $path);
      for($i=0; $i<count($path_); $i++){
        if (is_array($root) && array_key_exists($path_[$i], $root)){
          $root = &$root[$path_[$i]];
          if ($i>=count($path_)-1){ //target reached
            $answer = $root;
            //TODO: cache answer
            if (is_string($answer) || is_array($answer)){
              if (is_string($data[0]) && is_array($answer) && array_key_exists($data[0], $answer)){
                return $answer[$data[0]];
              }
              return $answer;
            }elseif(is_object($answer) && in_array(get_class($answer),["X\\Data\\Localization\\PlaceholdersString","X\\Data\\Localization\\PluralString"])){
              if (!is_array($data)){
                $data = [$data];
              }
              return call_user_func_array([$answer,'render'], $data);
            }
          }
        }else{
          break;
        }
      }
      if (isset(self::$fallback)){
        if (is_bool(self::$fallback)){
          if (self::$fallback){
            return $path;
          }
        }elseif(Values::isCallback(self::$fallback)){
          return call_user_func(self::$fallback, $path);
        }
      }
      return null;
      //TODO: cache null;
    }

    return static::$displayKeys ? $path : null;
  }

  public static function getCurrentLanguageCode(){
    return static::$currentLanguage;
  }

  public static function &getLanguageData($code=null){
    if ($code===null){
      $code = static::$currentLanguage;
    }
    if (array_key_exists($code, static::$dictionary)){
      return static::$dictionary[$code];
    }else{
      return [];
    }
  }

  public static function hasLanguage($code){
    return array_key_exists($code, static::$dictionary);
  }

  public static function gobbleDir($path, $code, $base=null){
    if ($base===null){
      $base = $path;
    }
    $files = FileSystem::dirList($path, '*');
    if (!$files){
      return;
    }
    foreach ($files as $file){
      if (is_dir($file)){
        static::gobbleDir($file, $code, $base);
      }else{
        static::fromFile($code, $file, $base);
      }
    }
  }

  private static function loadFromConfig($code){
    if (!static::$paths){
      throw new \InvalidArgumentException("Localization folder is not configured (l10n.folder). Language ".$code." was not added.");
    }

    foreach(static::$paths as $base){
      $base   = FileSystem::finalizeDirPath($base).$code;

      if (!is_dir($base)){
        continue;
      }

      static::gobbleDir($base, $code);
    }

    return true;
  }
  
  private static function update($erase=false){
    foreach(static::$languages as $code=>$data){
      if ($erase){
        static::$dictionary[$code]=[];
      }
      static::loadFromConfig($code);
    }
  }

  public static function fromFile($code, $filename, $base, $path=null){
    $ext = explode(".", $filename);
    $ext = array_pop($ext);
    if (!$path){
      $path = str_replace($base, '', str_replace('.'.$ext, '', $filename));
    }
    switch($ext){
      case 'ini':$data = parse_ini_file($filename, true);break;
      case 'yml':$data = yaml_parse_file($filename, 0, $ndocs, ["!pl"=>function($data){return new PluralString($data);},"!ps"=>function($data){return new PlaceholdersString($data);}]);break;
      default:   throw new \InvalidArgumentException("Can't parse language file '".$filename."'");
    }
    static::insertData($code, $path, $data);
    return true;
  }

  /**
   * @param $languageCode (e.g. 'en')
   * @param $path (e.g. 'admin.menu'
   * @param $data (e.g. ['menu_1'=>['menu_title'=>'Menu One', 'page_title'=>'Page title'], 'menu_2'...]
   * @return bool
   * @throws \RuntimeException
   */
  public static function insertData($languageCode, $path, $data){
    if (strlen($path)>1){
      if($path[0]==DIRECTORY_SEPARATOR){
        $path = substr($path,1);
      }
      $path = explode(DIRECTORY_SEPARATOR, $path);
      if (!static::hasLanguage($languageCode)){
        static::addLanguage($languageCode);
      }
      $root = &static::$dictionary[$languageCode];
      foreach($path as $part){
        if (!array_key_exists($part, $root)){
          $root[$part]=[];
        }
        $root = &$root[$part];
      }
    }

    if ($data && is_array($data)) {
      $root = array_replace_recursive($root, $data);
      return true;
    }
    return false;
  }
}

?>
