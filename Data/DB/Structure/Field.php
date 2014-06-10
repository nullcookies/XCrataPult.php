<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\Structure\Key;
use X\Validators\Values;

class Field {

  const ERR_BAD_FIELD_NAME = 201;

  private $name = '';
  private $alias = '';
  private $type = 'int';
  private $range = 0;
  private $null = false;
  private $default = null;
  private $unique = false;
  private $autoIncrement = false;
  private $timeOnUpdate = false;
  private $timeOnCreate = false;
  private $unsigned = false;
  private $keys = [];
  private $tableName='';

  private $driver;

  public function __construct(IDB &$driver, $name, $tableName=''){
    if (!Values::isSuitableForVarName($name)){
      throw new \exception("Bad field name '".$name."'!", self::ERR_BAD_FIELD_NAME);
    }
    $this->driver = &$driver;
    $this->tableName = $tableName;
    $this->name = strtolower($name);
    $this->alias = '_'.$this->name;
  }

  public function getTableName(){
    return $this->tableName;
  }

  public function generateSQLCreate(){
    return $this->driver->generateSQLCreateFromField($this);
  }

  public function getName(){
    return strtolower($this->name);
  }

  public function getCamelName(){
    return self::s_getCamelName($this->getName());
  }

  public static function s_getCamelName($name){
    return str_replace(" ", "",ucwords(implode(" ",explode("_",$name))));
  }

  public function getAlias(){
    return $this->alias;
  }

  public function setType($type, $typeRange=null){
    $this->type = $type;
    if (is_array($typeRange)){
      $this->range = $typeRange;
    }else{
      $this->range = intval($typeRange);
    }
    return $this;
  }

  public function getType(){
    return $this->type;
  }

  public function getPHPType(){
    return $this->driver->getPHPType($this->getType());
  }

  public function getRange(){
    return $this->range;
  }

  public function setNull($null){
    $this->null = !!$null;
    return $this;
  }

  public function getNull(){
    return $this->null;
  }

  public function setDefault($default){
    $this->default = $default;
    return $this;
  }

  public function getDefault(){
    return $this->default;
  }

  public function setUnique($unique){
    $this->unique = !!$unique;
    return $this;
  }

  public function getUnique(){
    return $this->unique;
  }

  public function addKey(Key &$key){
    $this->keys[]=&$key;
    return $this;
  }

  public function getKeys(){
    return $this->keys;
  }

  public function setAutoIncrement($autoIncrement){
    $this->autoIncrement = !!$autoIncrement;
    return $this;
  }

  public function getAutoIncrement(){
    return $this->autoIncrement;
  }

  public function setUnsigned($unsigned){
    $this->unsigned = !!$unsigned;
    return $this;
  }

  public function getUnsigned(){
    return $this->unsigned;
  }

  public function setTimeOnUpdate($timeOnUpdate){
    $this->timeOnCreate = !!$timeOnUpdate;
    return $this;
  }

  public function getTimeOnUpdate(){
    return $this->timeOnUpdate;
  }

  public function setTimeOnCreate($timeOnCreate){
    $this->timeOnCreate = !!$timeOnCreate;
    return $this;
  }

  public function getTimeOnCreate(){
    return $this->timeOnCreate;
  }

}
