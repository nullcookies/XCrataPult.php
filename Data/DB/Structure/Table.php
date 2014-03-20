<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Field;
use \X\Data\DB\Structure\Key;
use X\Validators\Values;

class Table {

  const ERR_BAD_TABLE_NAME = 401;
  const FIELD_DOES_NOT_EXIST = 402;
  const KEY_DOES_NOT_EXIST = 403;

  private $driver;
  private $name;

  private $fields = [];

  /**
   * @var Key[]
   */
  private $keys = [];

  private $lastModified = 0;

  private $fieldsInit_ = false;
  private $keysInit_ = false;

  public function __construct(IDB &$driver, $name){
    if (!Values::isSuitableForVarName($name)){
      throw new \Exception("Bad table name '".$name."'!", self::ERR_BAD_TABLE_NAME);
    }
    $this->driver = &$driver;
    $this->name = $name;
  }

  public function setLastModified($time){
    $this->lastModified = intval($time);
  }

  public function getLastModified(){
    return $this->lastModified;
  }

  /**
   * @return string table name
   */
  public function getName(){
    return $this->name;
  }

  /**
   * LAZY
   */
  public function fieldsInit(){
    if ($this->fieldsInit_){
      return;
    }
    $this->fields = $this->driver->getTableFields($this->getName());

    $this->fieldsInit_ = true;
  }

  /**
   * Calls LAZY fieldsInit()
   * @param string $name
   * @param bool $existingOnly
   *
   * @return Field
   * @throws \Exception if field doesn't exist AND $existingOnly set to true
   */
  public function &fieldByName($name, $existingOnly=false){
    $this->fieldsInit();
    if (array_key_exists($name, $this->fields)){
      return $this->fields[$name];
    }else{
      if ($existingOnly){
        throw new \Exception("No such field '".$name."' in table '".$this->getName()."'", self::FIELD_DOES_NOT_EXIST);
      }
      $this->fields[$name]=new Field($this->driver, $name);
      return $this->fields[$name];
    }
  }

  /**
   * Calls LAZY fieldsInit()
   * @return Field[]
   */
  public function &getFields(){
    $this->fieldsInit();
    return $this->fields;
  }

  public function keysInit(){
    if ($this->keysInit_){
      return;
    }
    $this->keys = $this->driver->getTableKeys($this->getName());
    $this->keysInit_=true;
  }

  /**
   * Calls LAZY keysInit()
   * @param string $name
   * @param bool $existingOnly
   *
   * @return Key
   * @throws \Exception if key doesn't exist AND param $existingOnly is true
   */
  public function &keyByName($name, $existingOnly=false){
    $this->keysInit();
    if (array_key_exists($name, $this->keys)){
      return $this->keys[$name];
    }else{
      if ($existingOnly){
        throw new \Exception("No such key '".$name."' in table '".$this->getName()."'", self::KEY_DOES_NOT_EXIST);
      }
      $this->keys[$name]=new Key($this->driver, $name);
      return $this->keys[$name];
    }
  }

  /**
   * Calls LAZY keysInit()
   * @return Key[]
   */
  public function &getKeys(){
    $this->keysInit();
    return $this->keys;
  }
}
