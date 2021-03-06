<?php

namespace X\Data\DB;

use X\C;
use X\Data\DB\Structure\Field;
use X\Data\Persistent\Cache;
use X\Traits\TFullClassName;
use \X\Validators\Values;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Interfaces\ICRUD;

abstract class CRUD implements ICRUD{

  use TFullClassName;

  protected static $entityClass = null;
  protected static $DAC = DAC::class;

  const ERR_WRONG_PRIMARY_FIELD=2001;

  protected static $persistent=[];

  protected static $Fields = [];
  protected static $FieldsCnames = [];
  protected static $PrimaryFields = [];
  protected static $UserFields = [];
  protected static $UserFieldsInterface = [];

  static protected $mutated = [];

  public static function mutation(){

  }

  public static function mutate(){
    if (array_key_exists(get_called_class(), static::$mutated)){
      return;
    }
    static::mutation();
    static::$mutated[get_called_class()]=true;
  }

  public static function create(){
    $classname = get_called_class();
    static::mutate();
    static::hook_constructor_before($classname);
    return new $classname();
  }

  protected static function registerProperty($name, $sanitizerFunction=null){
    $name = strtolower($name);
    if (Values::isSuitableForVarName($name) && !array_key_exists($name, self::$Fields)){
      $camelName = Field::s_getCamelName($name);
      static::$Fields[$name]=[
        'name'=>$name,
        'type'=>'string',
        'camelName'=>$camelName,
        'getter'=>'get'.$camelName,
        'setter'=>'set'.$camelName,
        'userfield'=>true
      ];
      static::$UserFields[$name]=null;
      static::$UserFieldsInterface[$camelName]=['sanitizer'=>$sanitizerFunction, 'name'=>$name];
    }
  }

  public function __call($method, $args){
    if (in_array($type=substr($method, 0, 3), ['set','get']) && array_key_exists($camelName=substr($method,3), static::$UserFieldsInterface)){
      if ($type=="set" && count($args)==1){
        static::$UserFields[static::$UserFieldsInterface[$camelName]['name']] = is_callable($sanitizerFunction=static::$UserFieldsInterface[$camelName]['sanitizer']) ? call_user_func($sanitizerFunction, $args[0]) : $args[0];
      }elseif($type=="get"){
        return static::$UserFields[static::$UserFieldsInterface[$camelName]['name']];
      }
    }
  }

  /**
   * @return IDB
   */
  public static function &connection(){
    throw new \Exception("Static method Connection in class \\X\\Data\\DB\\CRUD should be overridden!");
  }

  public static function classByTable($tableName, $database='main'){
    if ($database instanceof IDB){
      $connection = $database;
    }else{
      try{
        $connection = DB::connectionByDatabase($database);
      }catch(\RuntimeException $e){
        try{
          $connection = DB::connectionByAlias($database);
        }catch(\RuntimeException $e){
          return false;
        }
      }
    }
    if (!($alias = $connection->getAlias())){
      return false;
    }
    if (!C::getDbNamespace()){
      return false;
    }
    $path= "\\".str_replace('/',"\\", C::getDbNamespace().ucfirst($alias))."\\";
    $tableName = ucfirst(strtolower($tableName));
    if (class_exists($path.$tableName)){
      return $path.$tableName;
    }
    return false;
  }

  public function fieldValue($fieldName){
    $fieldName=strtolower($fieldName);
    if (array_key_exists($fieldName, static::$Fields)){
      $getter = static::$Fields[$fieldName]["getter"];
      return $this->$getter();
    }
    if (array_key_exists($fieldName, static::$FieldsCnames)){
      $getter = static::$Fields[static::$FieldsCnames[$fieldName]]["getter"];
      return $this->$getter();
    }
    throw new \RuntimeException("There is no field ".$fieldName." in ".static::TABLE_NAME);
  }

  public function setFieldValue($fieldName, $value){
    $fieldName=strtolower($fieldName);
    if (array_key_exists($fieldName, static::$Fields)){
      $setter = static::$Fields[$fieldName]["setter"];
      return $this->$setter($value);
    }
    throw new \RuntimeException("There is no field ".$fieldName." in ".static::TABLE_NAME);
  }


  abstract public function cacheKey($suffix='');
  abstract public function cacheGroup();

  protected function setCache($key, $val, $ttl=null){
    if (!count(static::$PrimaryFields)){
      throw new \RuntimeException("There is no internal cache for objects with no PK");
    }
    if (Cache::enabled()){
      Cache::getInstance()->groupSetItem($this->cacheGroup(), $this->cacheKey($key), $val, $ttl ?: C::getDbCacheTtl());
    }
    return [$this->cacheGroup(), $this->cacheKey($key)];
  }

  protected function getCache($key){
    if (!count(static::$PrimaryFields)){
      throw new \RuntimeException("There is no internal cache for objects with no PK");
    }
    if (Cache::enabled() && $this->cacheKey($key)){
      return Cache::getInstance()->groupGetItem($this->cacheGroup(), $this->cacheKey($key));
    }
    return null;
  }

  public function getPKQuery(){
    $query=[];
    foreach(static::$PrimaryFields as $key=>$alias){
      $query[]=$key.'='.urlencode($this->fieldValue($key));
    }
    return implode("&", $query);
  }

  public static function getPrimaryFields(){
    static::mutate();
    return static::$PrimaryFields;
  }

  public function getEntity(){
    if (static::$entityClass===null){
      return null;
    }
    $class = static::$entityClass;
    return new $class($this);
  }

  public function getCacheTags(){
    return [];
  }

  public function serialize(){
    $answer=['name'=>static::getFullClassName(), 'data'=>[]];
    foreach(static::$Fields as $fieldName=>$data){
      $answer['data'][$fieldName]=$this->fieldValue($fieldName);
    }
    return $answer;
  }

}