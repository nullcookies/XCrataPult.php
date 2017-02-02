<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 05.11.14
 * Time: 2:28
 */

namespace Afi;


class Phone {

  public static function parse($phone, &$ret=null){
    $answer = Base::v3()->sendRequest("phone/parse", urlencode($phone));
    if ($answer["status"]=="ok"){
      if ($answer['data']['valid']){
        return $answer['data'];
      }
    }
    return false;
  }

} 