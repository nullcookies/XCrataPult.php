<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 14.04.14
 * Time: 16:03
 */

namespace X\Data\Persistent;


use X\C;

class Session {

  public function __construct($forseNew=false){
    session_set_cookie_params(C::getSessionTtl(), C::getSessionPath(), C::getSessionDomain(), C::getSessionHttps(), C::getSessionHttpOnly());
    session_start();
    if ($forseNew || ($this->get("xSESSTTL", 0) && $this->get("xSESSTTL")<time())){
      $this->invalidate();
    }
    $this->set("xSESSTTL", time()+C::getSessionTtl());
  }

  public function newID($invalidatePrevious=true){
    session_regenerate_id($invalidatePrevious);
    return $this;
  }

  public function invalidate(){
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 86400,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
      );
    }
    session_destroy();
    session_start();
  }

  public function set($var, $val, $ttl=null){
    $_SESSION[$var]=$val;
    if ($ttl!==null){
      $_SESSION[$var."_TTL"]=time()+$ttl;
    }else{
      unset($_SESSION[$var."_TTL"]);
    }
    return $this;
  }

  public function get($var, $default=null){
    if (array_key_exists($var."_TTL", $_SESSION)){
      if (intval($_SESSION[$var."_TTL"])>time()){
        unset($_SESSION[$var]);
        unset($_SESSION[$var."_TTL"]);
      }
    }
    if (array_key_exists($var, $_SESSION)){
      return $_SESSION[$var];
    }else{
      return $default;
    }
  }

  public function getID(){
    return session_id();
  }

} 