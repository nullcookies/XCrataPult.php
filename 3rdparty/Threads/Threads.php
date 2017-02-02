<?php
namespace Threads;

use X\Validators\Values;
use X\X;

class Threads{

  const STATE_QUEUED="queued";
  const STATE_RUNNING = "running";
  const STATE_STOPPED = "stopped";

  private static $MAX_THREADS=0;
  private static $queue = [];
  private static $running = 0;
  private static $queued = 0;

  public static function getMaxThreads(){
    if (!static::$MAX_THREADS){
      static::$MAX_THREADS = X::cpuCount();
    }
    return static::$MAX_THREADS;
  }

  public static function run($callable, $arguments=[], $callback=null){
    if (!Values::isCallback($callable)){
      throw new \Exception("Callable is not callable");
    }
    if (!is_array($arguments)){
      $arguments=[$arguments];
    }
    if ($callback!==null && !Values::isCallback($callback)){
      throw new \Exception("Callback is not callable");
    }

    static::$queue[] = [
      "thread"=>new Thread($callable, $arguments),
      "state"=>self::STATE_QUEUED,
      "results"=>null,
      "callback"=>$callback
      ];
    Threads::tick();
    return count(static::$queue)-1; //ID
  }

  public static function tick(){
    static::updateStats();

    while (static::$queued>0 && static::$running<static::getMaxThreads()){
      static::runOne();
      static::updateStats();
    }
  }

  private static function updateStats(){
    static::$queued=0;
    static::$running=0;
    foreach(static::$queue as &$threadInfo){
      switch ($threadInfo["state"]) {
        case (self::STATE_QUEUED):
          static::$queued++;
          break;
        case (self::STATE_RUNNING):
          /**
           * @var $thread Thread
           */
          $thread = $threadInfo['thread'];
          if ($thread->isAlive()){
            static::$running++;
          }else{
            $threadInfo["state"]=self::STATE_STOPPED;
            $threadInfo["results"] = $thread->getResults();
            if ($threadInfo["callback"]){
              call_user_func($threadInfo["callback"], $threadInfo["results"]);
            }
          }
          break;
      }
    }
  }

  private static function runOne(){
    foreach (static::$queue as &$threadInfo) {
      if ($threadInfo["state"]===self::STATE_QUEUED){
        $threadInfo["thread"]->start();
        $threadInfo["state"] = self::STATE_RUNNING;
        break;
      }
    }
  }

  public static function getThreadsCount(){
    return count(static::$queue);
  }

  public static function getResults($tid){
    $tid = abs(intval($tid));
    if (count(static::$queue)>$tid){
      return [
        "state"=>static::$queue[$tid]['state'],
        "results"=>static::$queue[$tid]['results'],
      ];
    }else{
      return null;
    }
  }

  public static function waitBlocking($ids=null){
    if (!is_array($ids)) {
      while (static::$running > 0) {
        usleep(100);
        static::tick();
      }
    }else{
      do {
        $waiting = 0;
        foreach ($ids as $id) {
          if (count(self::$queue)>$id && self::$queue[$id]["state"]!=self::STATE_STOPPED){
            $waiting++;
          }
        }
        usleep(100);
        static::tick();
      }while($waiting>0);
    }
  }
  
  private static $groups=[];
  public static function createGroup($name){
    if (array_key_exists($name, self::$groups)){
      throw new \Exception("Group ".$name." already exists");
    }
    return $groups[$name]=new ThreadsGroup();
  }
  
  public static function getGroup($name){
    if (array_key_exists($name, self::$groups)){
      return self::$groups[$name];
    }
    return null;
  }

}