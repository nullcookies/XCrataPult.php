<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 15.06.14
 * Time: 20:43
 */

namespace X\Data\DB;


class CollectionMember {

  private $collection;
  private $rawData;
  private $objectsCache=[];

  public function __construct(Collection &$collection, $rawData){
    $this->collection = $collection;
    $this->rawData = $rawData;
  }

  public function Raw(){
    return $this->rawData;
  }

  public function __call($method, $args){
    $original = $method;
    $method = strtolower($method);
    if (array_key_exists($method, $this->objectsCache)){
      return $this->objectsCache[$method];
    }
    if ($className = $this->collection->getTable($method)){
      return $this->objectsCache[$method]=$className::createFromRaw($this->rawData, $method.'.');
    }elseif ($tableField = $this->collection->getTableField($original)){
      $tableName = $tableField['table']::TABLE_NAME;
      return $this->$tableName()->fieldValue($tableField['field']);
    }
  }

} 