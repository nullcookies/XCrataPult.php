<?php
/**
 * Created by PhpStorm.
 * User: х
 * Date: 04.11.2015
 * Time: 19:39
 */

namespace X\Data;


use X\Data\Persistent\Cache;
use X\Validators\Values;

class MessageBus {

  static $subscriptionHandlers=[];
  static protected $prefix='x-messagebus:';

  public static function addSubscriptionsHandler($handler){
    if (!Values::isCallback($handler)){
      throw new \Exception("Bad subscription handler");
    }else{
      static::$subscriptionHandlers[]=$handler;
    }
  }

  public static function send($to, $message){
    if (!Cache::enabled()){
      return false;
    }

    $list=[$to];
    foreach(static::$subscriptionHandlers as $handler) {
      if ($list = call_user_func($handler,$to)){
        break;
      }
    }
    foreach($list as $to){
      if (Cache::getInstance()->queuePush(static::$prefix.$to, $message)===false) {
        return false;
      }
    }
    return true;
  }

  public static function listen($rooms, $timeout=10, $periodms=200){
    if (!Cache::enabled()){
      return [];
    }
    if (!is_array($rooms)) {
      $rooms = [$rooms];
    }
    $answer=[];
    $timeLimit = time()+$timeout;
    while(count($answer)==0 && time()<$timeLimit) {
      foreach ($rooms as $room) {
        while($message = Cache::getInstance()->queuePop(static::$prefix.$room)) {
          $answer[$room][] = $message;
        }
      }
      usleep($periodms*1000);
    }
    return $answer;
  }

  /**
   * @return array
   */
  public static function getChannels(){
    if (!Cache::enabled()){
      return [];
    }
    $channels=[];
    $prefixLen=strlen(static::$prefix);
    foreach(Cache::getInstance()->keys(static::$prefix.'*') as $key){
      $channels[]=substr($key, $prefixLen);
    }
    return $channels;
  }

}