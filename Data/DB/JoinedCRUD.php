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

  public function __construct($rawData){
    $this->rawData = $rawData;
  }

  public function asRaw(){
    return $this->rawData;
  }
} 