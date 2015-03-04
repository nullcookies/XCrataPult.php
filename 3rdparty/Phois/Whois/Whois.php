<?php

namespace Phois\Whois;

use X\C;
use X\Data\Persistent\Cache;

class Whois{
  private static $servers = null;
  private $domain;
  private $subDomain;
  private $TLDs;
  private $info;
  private $cache=false;

  /**
   * @param string $domain full domain name (without trailing dot)
   */
  public function __construct($domain, $cache=false){
    $this->domain = mb_strtolower(trim($domain),'utf-8');
    $this->cache = !!$cache;
    if(
      preg_match('/^([\p{L}\d\-]+)\.((?:[\p{L}\-]+\.?)+)$/ui', $this->domain, $matches)
      || preg_match('/^(xn\-\-[\p{L}\d\-]+)\.(xn\-\-(?:[a-z\d-]+\.?1?)+)$/ui', $this->domain, $matches)
    ){
      $this->subDomain = $matches[1];
      $this->TLDs = $matches[2];
      $this->info = $this->parsedInfo();
    }else{
      throw new \InvalidArgumentException("Invalid $domain syntax");
    }
  }

  public function getInfo(){
    return $this->info;
  }

  public function getExpirationDate(){
    $keywords=[
      'paid-till',
      'expire',
      'expiration',
    ];
    return strtotime($this->_findProperty($keywords));
  }

  public function getRegisteredDate(){
    $keywords=[
      'created',
      'creation',
      'registered',
    ];
    return strtotime($this->_findProperty($keywords));
  }

  private function _findProperty($guesses){
    foreach($this->getInfo() as $var=>$val){
      foreach($guesses as $guess){
        if (strpos($var, $guess)!==false){
          return $val;
        }
      }
    }
    return null;
  }

  private static function _getServers(){
    if(self::$servers === null){
      self::$servers = json_decode(file_get_contents(__DIR__ . '/whois.servers.json'), true);
    }
    return self::$servers;
  }

  private function info(){
    if($this->_isValid()){
      $whois_server = self::_getServers()[$this->TLDs][0];

      // If TLDs have been found
      if($whois_server != ''){

        // if whois server serve replay over HTTP protocol instead of WHOIS protocol
        if(preg_match("/^https?:\/\//i", $whois_server)){

          // curl session to get whois reposnse
          $ch = curl_init();
          $url = $whois_server . $this->subDomain . '.' . $this->TLDs;
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
          curl_setopt($ch, CURLOPT_TIMEOUT, 60);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

          $data = curl_exec($ch);

          if(curl_error($ch)){
            return "Connection error!";
          }else{
            $string = strip_tags($data);
          }
          curl_close($ch);

        }else{

          // Getting whois information
          $fp = fsockopen($whois_server, 43);
          if(!$fp){
            return false;
          }

          $dom = $this->subDomain . '.' . $this->TLDs;
          fputs($fp, "$dom\r\n");

          // Getting string
          $string = '';

          // Checking whois server for .com and .net
          if($this->TLDs == 'com' || $this->TLDs == 'net'){
            while(!feof($fp)){
              $line = trim(fgets($fp, 128));

              $string .= $line;

              $lineArr = explode(":", $line);

              if(strtolower($lineArr[0]) == 'whois server'){
                $whois_server = trim($lineArr[1]);
              }
            }
            // Getting whois information
            $fp = fsockopen($whois_server, 43);
            if(!$fp){
              return false;
            }

            $dom = $this->subDomain . '.' . $this->TLDs;
            fputs($fp, "$dom\r\n");

            // Getting string
            $string = '';

            while(!feof($fp)){
              $string .= fgets($fp, 128);
            }

            // Checking for other tld's
          }else{
            while(!feof($fp)){
              $string .= fgets($fp, 128);
            }
          }
          fclose($fp);
        }

        $string_encoding = mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true);
        $string_utf8 = mb_convert_encoding($string, "UTF-8", $string_encoding);

        return htmlspecialchars($string_utf8, ENT_COMPAT, "UTF-8", true);
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  private function parsedInfo(){
    $parts=[];
    if (!$this->cache || !Cache::enabled() || !($parts=Cache::getInstance()->groupGetItem('_X_WHOIS', $this->domain))){
      if ($info = $this->info()){
        $info = str_replace("\r\n", "\n", $info);
        $info = explode("\n", $info);
        foreach($info as &$line){
          if ($line[0]=='%' || $line[0]=='#'){
            continue;
          }
          if (substr_count($line,':')>0){
            list($name,$val) = explode(":", $line,2);
            $parts[trim(strtolower($name))]=trim(strtolower($val));
          }
        }
      }
      if ($parts && $this->cache && Cache::enabled()){
        Cache::getInstance()->groupSetItem('_X_WHOIS', $this->domain, $parts);
      }
    }
    return $parts;
  }

  private function _isValid(){
    if(
      isset(self::_getServers()[$this->TLDs][0])
      && strlen(self::_getServers()[$this->TLDs][0]) > 6
    ){
      $tmp_domain = strtolower($this->subDomain);
      if(
        preg_match("/^[a-z0-9\-]{3,}$/", $tmp_domain)
        && !preg_match("/^-|-$/", $tmp_domain) //&& !preg_match("/--/", $tmp_domain)
      ){
        return true;
      }
    }

    return false;
  }

}
