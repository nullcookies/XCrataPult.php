<?php
namespace Snoop;

use X\Validators\Values;

class Avatars {

  public static function gravatar($email){
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $email ) ) );
    list($proto, $code, $message) = explode(" ", get_headers($url."?d=404")[0]);
    if ($code!='404'){
      return $url;
    }else{
      return null;
    }
  }

  public static function mailru($account){
    $url = 'http://my.mail.ru/mail/'.$account.'/';
    $contents = file_get_contents($url);
    if (($pos=strpos($contents, 'mm-profile_not-found_avatar'))!==false){
      $pos1=strpos($contents, 'url(', $pos);
      $pos2=strpos($contents, ')', $pos1);
      if ($pos1!==false && $pos2!==false){
        $url = substr($contents, $pos1+4, $pos2-$pos1-4);
        return $url;
      }
    }elseif (($pos=strpos($contents, 'profile__avatar'))!==false) {
      $pos1_1 = strpos($contents, 'url(', $pos);
      $pos1_2 = strpos($contents, ')', $pos1_1);

      $pos2_1 = strpos($contents, 'src="', $pos);
      $pos2_2 = strpos($contents, '"', $pos2_1+5);

      if ($pos1_1 !== false && $pos1_2 != false && $pos2_1 != false && $pos2_2 != false) {
        if ($pos1_2 < $pos2_2) {
          $url = substr($contents, $pos1_1 + 4, $pos1_2 - $pos1_1 - 4);
          if (strpos($url, 'avatar') !== false) {
            return $url;
          }
        } else {
          $url = substr($contents, $pos2_1 + 5, $pos2_2 - $pos2_1 - 5);
          if (strpos($url, 'avatar') !== false) {
            return $url;
          }
        }
      } elseif ($pos1_1 !== false && $pos1_2 != false) {
        $url = substr($contents, $pos1_1 + 4, $pos1_2 - $pos1_1 - 4);
        if (strpos($url, 'avatar') !== false) {
          return $url;
        }
      } elseif ($pos2_1 !== false && $pos2_2 != false) {
        $url = substr($contents, $pos2_1 + 5, $pos2_2 - $pos2_1 - 5);
        if (strpos($url, 'avatar') !== false) {
          return $url;
        }
      }
    }
    return null;
  }

  public static function getByEmail($email){
    $urls=[];
    if (Values::isEmail($email)){
      $email = strtolower($email);
      if ($url = static::gravatar($email)){
        $urls['gravatar']=$url;
      }
      $parts = explode("@", $email);
      if ($parts[1]=='mail.ru' && $url = static::mailru($parts[0])){
        $urls['mailru']=$url;
      }
    }
    return $urls;
  }
}
