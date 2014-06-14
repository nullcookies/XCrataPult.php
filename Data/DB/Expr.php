<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 14.06.14
 * Time: 22:27
 */

namespace X\Data\DB;

class Expr {
  private $expr = '';

  public function __construct($expr){
    $this->seT($expr);
  }

  public function get(){
    return $this->expr;
  }

  public function set($expr){
    $this->expr = $expr;
  }

  public function run($connectionDBorAlias=null){
    if ($connectionDBorAlias===null){
      return DB::connectionByDatabase()->query($this->get());
    }else{
      if (!($connection = DB::connectionByDatabase($connectionDBorAlias))){
        $connection = DB::connectionByAlias($connectionDBorAlias);
      }
      if ($connection){
        return $connection->query($this->get());
      }else{
        throw new \RuntimeException("There is no connection with name '".$connectionDBorAlias."' available");
      }
    }
  }

} 