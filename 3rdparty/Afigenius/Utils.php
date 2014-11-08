<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 05.11.14
 * Time: 2:28
 */

namespace Afi;


class Utils {

  public static function validatePhone($phone){
    $answer = Base::request("utils/validatephone", $phone);
    if ($answer["status"]=="ok"){
      return true;
    }
    return false;
  }

} 