<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;

class Key {

  const KEY_TYPE_PRIMARY = "PRIMARY KEY";
  const KEY_TYPE_FOREIGN = "FOREIGN KEY";
  const KEY_TYPE_UNIQUE = "UNIQUE KEY";

  const ERR_NO_FIELDS_ASSOCIATED = 501;

  private $type;
  private $name;
  private $driver;
  private $refTable;

  /**
   * @var Field[]
   */
  private $fields = [];
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
    $this->fields[] = $field;
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
      return self::KEY_TYPE_UNIQUE;
    }
  }

  /**
   * @return Field[]
   */
  public function &getFields(){
    return $this->fields;
  }
}
