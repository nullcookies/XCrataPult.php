<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\Structure\Field;

class Key {

  const KEY_TYPE_PRIMARY = "PRIMARY KEY";
  const KEY_TYPE_FOREIGN = "FOREIGN KEY";
  const KEY_TYPE_UNIQUE = "UNIQUE KEY";

  const ERR_NO_FIELDS_ASSOCIATED = 501;

  private $type;
  private $name;
  private $driver;

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
    return $this->name;
  }

  public function getCamelName(){
    return str_replace(" ", "",ucwords(implode(" ",explode("_",$this->getName()))));
  }

  public function addField(Field &$field){
    $this->fields[] = $field;
    return $this;
  }

  public function addRefField(Field &$field){
    $this->refFields[] = $field;
    return $this;
  }

  /**
   * @return string
   */
  public function getType(){
    if ($this->getName()=="PRIMARY"){
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
