<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 05.11.14
 * Time: 2:28
 */

namespace Afi;


class Utils {

  public static function validatePhone($phone, &$ret=null, $all=false){
    $answer = Base::request("utils/validatephone", urlencode($phone));
    if ($answer["status"]=="ok"){
      foreach($answer['data'] as $data){
        if ($data['valid']){
          if ($all){
            return $answer['data'];
          }else{
            $ret = $data['formatted'];
            return $data;
          }
        }
      }
    }
    return false;
  }

} 