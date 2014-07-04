<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 09.05.14
 * Time: 14:35
 */

namespace X\Data\Localization;

class PlaceholdersString {
  private $expression;

  public function __construct($expression){
    $this->expression = $expression;
  }

  public function render(){
    $numargs = func_num_args();
    $string = $this->expression;
    if ($numargs == 1 && is_array($fields = func_get_arg(0))) {
      foreach($fields as $key=>$val){
        $string = str_replace("%".$key, $val, $string);
      }
    }else{
      $arg_list = func_get_args();
      for ($i = 0; $i < $numargs; $i++) {
        $string = str_replace("%".($i+1), $arg_list[$i], $string);
      }
    }
    return $string;
  }
} 