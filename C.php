<?php

namespace X;

use \X\Render\L10n;
use \X\Render\Page;
use \X\Data\DB\DB;
use \X\Data\Cache\Cache;
use \X\Debug\Logger;
use \X\Tools\FileSystem;

class C extends \X\AbstractClasses\PrivateInstantiation{

  private static $config=[
    'db_dir' =>'.app/db/',
    'db_namespace'=>'db',
    'db_abstract'=>true
  ];

  public static function setDbAbstract($abstract){
    self::$config["db_abstract"] = !!$abstract;
  }

  public static function getDbAbstract(){
    return self::$config["db_abstract"];
  }

  public static function setDbDir($path){
    self::helperCheckDir($path);
    self::$config["db_dir"] = FileSystem::finalizeDirPath($path);
  }

  public static function getDbDir(){
    self::checkDir(self::$config["db_dir"]);
    return self::$config["db_dir"];
  }

  public static function setDbNamespace($name){
    self::$config["db_namespace"] = FileSystem::finalizeDirPath($name);
  }

  public static function getDbNamespace(){
    return self::$config["db_namespace"];
  }

  public static function setDb($db, $options){
    foreach($options as $var=>$val){
      $var = "set".str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $var))));
      DB::$var($db, $val);
    }
  }

  public static function set($options){
    if (!is_array($options)){
      throw new \exception("Options should be an array.");
    }

    foreach($options as $var=>$val){
      $var = "set".str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $var))));
      if (strpos($var, ".")!==false){
        list($var, $subvar) = explode(".", $var);
        self::$var($subvar, $val);
      }else{
        self::$var($val);
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

  public static function checkDir(&$path){
    if ($path[0]!='/'){
      $path = X::getScriptDir().$path;
    }
    $path = FileSystem::finalizeDirPath(str_replace("\\", DIRECTORY_SEPARATOR, $path));
    if (!file_exists($path)){
      throw new \exception("No such path [".$path."]");
    }elseif (!is_dir($path)){
      throw new \exception("Path specified is not a directory [".$path."]");
    }
  }
}
