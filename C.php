<?php

namespace X;

use \X\Render\Page;
use \X\Data\DB\DB;
use \X\Data\Cache\Cache;
use \X\Debug\Logger;
use \X\Tools\FileSystem;

class C extends \X\AbstractClasses\PrivateInstantiation{

  private static $config=[
    'db_namespace' =>'db',
    'db_abstract'=>true,
    'db_cache_ttl'=>3600,
    'db_cache_maxrows'=>10000,
    'cache_tech_prefix'=>"_X_:"
  ];

  public static function setCacheTechPrefix($prefix){
    self::$config["cache_tech_prefix"] = $prefix;
  }

  public static function getCacheTechPrefix(){
    return self::$config["cache_tech_prefix"];
  }

  public static function setDbAbstract($abstract){
    self::$config["db_abstract"] = !!$abstract;
  }

  public static function getDbAbstract(){
    return self::$config["db_abstract"];
  }

  public static function getDbDir(){
    return self::checkDir(self::getAppDir().self::$config["db_namespace"]);
  }

  public static function setDbNamespace($name){
    self::$config["db_namespace"] = $name;
  }

  public static function getDbNamespace(){
    return "app\\".FileSystem::finalizeDirPath(self::$config["db_namespace"]);
  }

  public static function setDb($db, $options){
    foreach($options as $var=>$val){
      $var = "set".str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $var))));
      DB::$var($db, $val);
    }
  }

  public static function setDbCacheTtl($ttl){
    if (($ttl=intval($ttl))>=0){
      self::$config["db_cache_ttl"]=$ttl;
    }
  }

  public static function getDbCacheTtl(){
    return intval(self::$config["db_cache_ttl"]);
  }

  public static function setDbCacheMaxrows($maxrows){
    if (($maxrows = intval($maxrows))>=0){
      self::$config["db_cache_maxrows"]=$maxrows;
    }
  }

  public static function getDbCacheMaxrows(){
    return intval(self::$config["db_cache_maxrows"]);
  }

  public static function getAppDir(){
    return self::checkDir(self::$config["app_dir"]);
  }

  public static function setAppDir($path){
    self::$config["app_dir"] = self::checkDir($path);

    registerAutoloader("app", self::$config["app_dir"]);
  }

  public static function setTemplateFolder($path){
    self::$config["template_folder"] = self::checkDir($path);
  }

  public static function setTemplateCacheFolder($path){
    self::$config["template_cache_folder"] = self::checkDir($path);
  }

  public static function getTemplateFolder(){
    return self::checkDir(self::$config["template_folder"]);
  }

  public static function getTemplateCacheFolder(){
    return array_key_exists("template_cache_folder", self::$config) ? self::checkDir(self::$config["template_cache_folder"]) : null;
  }

  public static function setCacheHost($host){
    \X\Data\Cache::getInstance($host);
  }

  public static function set($options){
    if (!is_array($options)){
      throw new \exception("Options should be an array.");
    }

    foreach($options as $var=>$val){
      $var = "set".str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $var))));
      if (strpos($var, ".")!==false){
        list($var, $subvar) = explode(".", $var);
        if (method_exists(get_called_class(),$var)){
          self::$var($subvar, $val);
        }
      }else{
        if (method_exists(get_called_class(),$var)){
          self::$var($val);
        }
      }
    }
  }

  public static function get($option, $default=null){
    return array_key_exists($option, self::$config) ? self::$config[$option] : $default;
  }

  public static function loadINI($path){
    Logger::add("Loading config from ".$path);
    if ($path[0]!='/'){
      $path = X::getScriptDir().$path;
    }
    if (!file_exists($path)){
      throw new \exception("No such path [".$path."]");
    }
    $res = parse_ini_file($path);
    if (!$res){
      throw new \exception("Can't read specified file (but file exists) [".$path."]");
    }
    Logger::add("Loading config from ".$path." ... loaded");
    self::set($res);
    Logger::add("Loading config from ".$path." ... set");
  }

  public static function checkDir($path){
    if ($path[0]!='/'){
      $path = X::getScriptDir().$path;
    }
    $path = FileSystem::finalizeDirPath(str_replace("\\", DIRECTORY_SEPARATOR, $path));
    if (!file_exists($path)){
      throw new \exception("No such path [".$path."]");
    }elseif (!is_dir($path)){
      throw new \exception("Path specified is not a directory [".$path."]");
    }
    return $path;
  }
}
