<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 04.08.14
 * Time: 23:44
 */

namespace Clickatell;

class Clickatell {

  const API_URL = "https://api.clickatell.com/http/";
  const API_AUTH = "auth";
  const API_SEND = "sendmsg";

  private $login, $apiId, $password;
  private $sessid=null;

  public function __construct($login, $api_id, $password){
    $this->login = $login;
    $this->apiId = $api_id;
    $this->password = $password;
  }

  private function connect(){
    $ch =  curl_init(self::API_URL.self::API_AUTH."?user=".$this->login."&password=".$this->password."&api_id=".$this->apiId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $sess = explode(":",$result);
    if ($sess[0] == "OK") {
      $this->sessid = trim($sess[1]);
    }else{
      throw new \RuntimeException("Clickatell authentication failure!");
    }
  }

  private function encode($message){
    $mb_hex = '';
    for($i = 0 ; $i<mb_strlen($message,'UTF-8') ; $i++){
      $c = mb_substr($message,$i,1,'UTF-8');
      $o = unpack('N',mb_convert_encoding($c,'UCS-4BE','UTF-8'));
      $mb_hex .= sprintf('%04X',$o[1]);
    }
    return $mb_hex;
  }

  public function send($to, $message, $from){
    $data = array(
      'text' => $this->encode($message),
      'to'=>$to,
      'from'=>$from,
      '?session_id'=>$this->sessid,
      'unicode'=>1
    );

    $handle = curl_init(self::API_URL.self::API_SEND);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);
    $result = explode(":",$result);
    if ($result[0] == "ID") {
      return trim($result[1]);
    } else {
      if ($result[0]=="ERR"){
        $errCode = intval($result[1]);
        if ($errCode==1){
          $this->connect();
          $this->send($to, $message, $from);
        }else{
          throw new \RuntimeException("Clickatell error: ".$result[1], $errCode);
        }
      }
      throw new \RuntimeException("Clickatell unknown error: ".implode(":", $result));
    }
  }

} 