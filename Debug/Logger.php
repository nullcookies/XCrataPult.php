<?php
namespace X\Debug;
if (!defined('__XDIR__')) die();

use \X\AbstractClasses\Singleton;
use X\Tools\Strings;
use \X\Tools\Values;
use \X\Tools\Time;

final class Logger extends Singleton{

  public $debugTime;
  public $lastTime;
  public $debugLog;

  public static function add($message){
    if (!self::x()->debugTime){
      self::x()->debugTime = Time::microTime();
      self::x()->lastTime = Time::microTime();
      self::add("Log started");
    }

    $caller = Tracer::getCaller();
    $file   = $caller['file'];
    $line   = $caller['line'];
    $object = isset($caller['object']) ? $caller['object'] : '';

    if (is_object($object)){
      $object = get_class($object);
    }
    $arr=explode(DIRECTORY_SEPARATOR, $file);
    $file = array_pop($arr);

    self::x()->debugLog[] =
      bcmul(Time::microdelta(self::x()->debugTime),1000,2).
      "ms\t<b>".
      bcmul(Time::microdelta(self::x()->lastTime),1000000).
      "</b> ns\t[".$file.":".$line.($object ? ", ".$object:"")."]\t".
      $message=htmlspecialchars($message);

    self::x()->lastTime = Time::microTime();
  }

  static public function get($html=false){
    Logger::Add("Memory used (peak): " . Strings::Grades(memory_get_peak_usage(true), 1024, 'B,KB,MB,GB'));
    Logger::Add("Memory used (now): " . Strings::Grades(memory_get_usage(true), 1024, 'B,KB,MB,GB'));
    Logger::Add("Sending logs");
    if (!$html){
      return implode("\n", self::x()->debugLog);
    }else{
      $condensed = Array();
      foreach(self::x()->debugLog as $dl)
        $condensed[]=preg_replace("/\t/", "</td><td>", $dl, 3);
      return "<table class='__DEBUG__' style='background:white !important; color:black !important;'><tr><td>".implode("</td></tr><tr><td>", $condensed)."</td></tr></table>";
    }
  }
}