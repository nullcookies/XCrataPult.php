<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 28.07.14
 * Time: 3:35
 */

namespace Typograf;

class Typograf {
  public static function process($text){
    $buf = $text;
    $url = 'http://www.typograf.ru/webservice/';
    $data = array('text' => $text, 'chr'=>'UTF-8');

    $options = array(
      'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded",
        'method'  => 'POST',
        'content' => http_build_query($data),
      ),
    );
    $context  = stream_context_create($options);
    $buf = file_get_contents($url, false, $context);
    if (!$buf){
      $buf = $text;
    }
    return $buf;
  }
} 