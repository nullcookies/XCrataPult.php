<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 10.06.14
 * Time: 22:41
 */

namespace X\Data\DB;


use X\C;
use X\Data\DB\Interfaces\IDB;
use X\Data\Persistent\Cache;
use X\Debug\Logger;
use X\Validators\Values;

class JoinedCollection extends Collection{

  private $oCache=[];
  private $tables=[];

  public function asRaw(){
    return $this->current();
  }

  public function __construct($res, IDB &$driver, $tables, $cacheKey = null, $cacheTTL = 0){
    parent::__construct($res, $driver, null, $cacheKey, $cacheTTL);
    foreach($tables as $tableData){
      $this->tables[$tableData['alias']] = $tableData['class'];
    }
  }

  public function row($num = null){
    if (!array_key_exists($num, $this->oCache)){
      $data = parent::row($num);
      $this->oCache[$num] = new JoinedCRUD($data, $this->tables);
    }
    return $this->oCache[$num];
  }
}