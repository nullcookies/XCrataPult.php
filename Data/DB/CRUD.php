<?php

namespace X\Data\DB;

use \X\Tools\Values;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Interfaces\ICRUD;

abstract class CRUD implements ICRUD{

  const ERR_WRONG_PRIMARY_FIELD=2001;

  protected static $Fields = [];
  protected static $PrimaryFields = [];

  public static function create(){
    $classname = get_called_class();
    return new $classname();
  }

  /**
   * @return IDB
   */
  public static function &connection(){
    throw new \Exception("Static method Connection in class \\X\\Data\\DB\\CRUD should be overridden!");
  }

}