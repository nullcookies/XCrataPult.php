<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;

class Key {

  const KEY_TYPE_PRIMARY = "PRIMARY KEY";
  const KEY_TYPE_FOREIGN = "FOREIGN KEY";
  const KEY_TYPE_UNIQUE = "UNIQUE KEY";
  const KEY_TYPE_INDEX = "INDEX KEY";

  const ERR_NO_FIELDS_ASSOCIATED = 501;

  private $type;
  private $name;
  private $driver;
  private $refTable;
  private $unique=true;
  /**
   * @var Field[]
   */
  private $fields = [];
  private $fieldsNames=[];
  /**
   * @var Field[]
   */
  private $refFields = [];

  public function __construct(IDB &$driver, $name){
    $this->driver = &$driver;
    $this->name = $name;
  }

  public function getName(){
    return strtolower($this->name);
  }

  public function getCamelName(){
    return Field::s_getCamelName($this->getName());
  }

  public function addField(Field &$field){
    if (!array_key_exists($field->getName(), $this->fieldsNames)){
      $this->fields[] = $field;
      $this->fieldsNames[$field->getName()]=1;
    }
    return $this;
  }

  public function addRefField(Field &$field, Field &$refField=null){
    $this->refFields[] = [$field, $refField];
    return $this;
  }

  public function setRefTable($tableName){
    $this->refTable = $tableName;
    return $this;
  }

  public function getRefTable(){
    return $this->refTable;
  }

  public function getRefFields(){
    return $this->refFields;
  }

  /**
   * @return string
   */
  public function getType(){
    if ($this->getName()=="primary"){
      return self::KEY_TYPE_PRIMARY;
    }elseif(count($this->refFields)){
      return self::KEY_TYPE_FOREIGN;
    }else{
      return $this->unique ? self::KEY_TYPE_UNIQUE : self::KEY_TYPE_INDEX;
    }
  }

  public function unUnique(){
    $this->unique=false;
  }

  public function isUnique(){
    return $this->unique;
  }

  /**
   * @return Field[]
   */
  public function &getFields(){
    return $this->fields;
  }
}
