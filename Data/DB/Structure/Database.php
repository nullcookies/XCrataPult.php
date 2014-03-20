<?php

namespace X\Data\DB\Structure;

use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\Structure\Field;
use \X\Data\DB\Structure\Key;
use X\Validators\Values;

class Database {

  const ERR_BAD_DATABASE_NAME = 501;
  const ERR_BAD_DATABASE_ALIAS = 502;
  const KEY_DOES_NOT_EXIST = 503;
  const TABLE_DOES_NOT_EXIST = 504;

  private $name;
  private $alias;

  /**
   * @var Table[]
   */
  private $tables = [];
  private $tablesInit_ = false;

  private $driver;

  public function __construct(IDB &$driver, $name, $alias){
    if (!Values::isSuitableForVarName($name)){
      throw new \Exception("Bad database name '".$name."'!", self::ERR_BAD_DATABASE_NAME);
    }
    if (!Values::isSuitableForVarName($alias)){
      throw new \Exception("Bad database alias '".$alias."'!", self::ERR_BAD_DATABASE_ALIAS);
    }
    $this->driver = &$driver;
    $this->name = $name;
    $this->alias = $alias;
  }

  /**
   * LAZY
   */
  private function tablesInit(){
    if ($this->tablesInit_){
      return;
    }
    foreach($this->driver->getTables() as $table){
      $this->tables[$table['name']]= new Table($this->driver, $table['name']);
      $this->tables[$table['name']]->setLastModified($table['time']);
    }
    $this->tablesInit_ = true;
  }

  /**
   * Calls LAZY tablesInit()
   * @param string $name
   * @param bool $existingOnly
   *
   * @return Table
   * @throws \Exception if table doesn't exist AND param $existingOnly is true
   */
  public function &tableByName($name, $existingOnly=false){
    $this->tablesInit();
    if (array_key_exists($name, $this->tables)){
      return $this->tables[$name];
    }else{
      if ($existingOnly){
        throw new \Exception("No such table '".$name."' in database '".$this->getName()."'", self::TABLE_DOES_NOT_EXIST);
      }
      $this->tables[$name]=new Table($this->driver, $name);
      return $this->tables[$name];
    }
  }

  public function getName(){
    return $this->name;
  }

  public function getAlias(){
    return $this->alias;
  }

  /**
   * Calls LAZY tablesInit()
   * @return Table[]
   */
  public function &getTables(){
    $this->tablesInit();
    return $this->tables;
  }

}
