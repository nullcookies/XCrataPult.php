<?php
/**
 * Created by PhpStorm.
 * User: х
 * Date: 13.10.2015
 * Time: 1:28
 */

namespace X\Data\DB;
/**
 * Class DAC - Data Access Controller
 * @package X\Data\DB
 */
class DAC {
  const OP_READ='operation:read';
  const OP_SAVE='operation:save';
  const OP_DELETE='operation:delete';
  const OP_CREATE='operation:create';

  public static function checkEntity($entityClass, $PKs=[], $fieldName=null, $operationType=null){
    return true;
  }

  public static function checkCRUD($CRUDClass, $PKs=[], $fieldName=null, $operationType=null){
    return true;
  }

}