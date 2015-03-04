<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 05.11.14
 * Time: 2:28
 */

namespace Afi;


class Utils {

  public static function validatePhone($phone, &$ret=null){
    $answer = Base::request("utils/validatephone", urlencode($phone));
    if ($answer["status"]=="ok"){
      if ($answer['data']['valid']){
        $ret = $answer['data']['formatted'];
        return $answer['data'];
      }
    }
    return false;
  }

} 