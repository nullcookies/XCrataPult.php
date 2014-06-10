<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 11.06.14
 * Time: 0:00
 */

namespace X\Data\DB;


class JoinedCRUD {
  private $rawData=[];

  public function __construct($rawData, $tables){
    $this->rawData = $rawData;
    $this->tables = $tables;
  }

  public function asRaw(){
    return $this->rawData;
  }

  public function __call($method, $args){
    if (substr($method, 0, 2)=='as'){
      $method = strtolower(substr($method, 2));
      if (array_key_exists($method, $this->tables)){
        $className = $this->tables[$method];
        return $className::createFromRaw($this->rawData, $method.'.');
      }
    }
  }

} 