<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 28.07.14
 * Time: 3:02
 */

namespace Typograph;


class Typograph {
  public static function process($text){
    $host = "www.typograph.ru";
    $script="/webservices/";
    $data = 'text='.urlencode($text);
    $buf = $text;

    $fp = fsockopen($host,80,$errno, $errstr, 30 );

    if ($fp) {
      fputs($fp, "POST $script HTTP/1.1\n");
      fputs($fp, "Host: $host\n");
      fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
      fputs($fp, "Content-length: " . strlen($data) . "\n");
      fputs($fp, "User-Agent: PHP Script\n");
      fputs($fp, "Connection: close\n\n");
      fputs($fp, $data);
      while(fgets($fp,2048) != "\r\n" && !feof($fp));

      while(!feof($fp)){
        $buf .= fread($fp,2048);
      }
      fclose($fp);
    }
    else{
      $buf = $text;
    }

    return $buf;
  }
} 