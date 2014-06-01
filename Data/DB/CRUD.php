<?php

namespace X\Data\DB;

use X\Data\DB\Structure\Field;
use \X\Validators\Values;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Interfaces\ICRUD;

abstract class CRUD implements ICRUD{

  const ERR_WRONG_PRIMARY_FIELD=2001;

  protected static $Fields = [];
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
        'userfield'=>true
      ];
      static::$UserFields[$name]=null;
      static::$UserFieldsInterface[$camelName]=['sanitizer'=>$sanitizerFunction, 'name'=>$name];
    }
  }

  public function __call($method, $args){
    if (in_array($type=substr($method, 0, 3), ['set','get']) && array_key_exists($camelName=substr($method,3), static::$UserFieldsInterface)){
      if ($type=="set" && count($args)==1){
        static::$UserFields[static::$UserFieldsInterface[$camelName]['name']] = is_callable($sanitizerFunction=static::$UserFieldsInterface[$camelName]['sanitizer']) ? call_user_func($sanitizerFunction, $val) : $args[0];
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

}