<?php
namespace X\Tools;
if (!defined('__XDIR__')) die();

use \X\AbstractClasses\PrivateInstantiation;

final class Time extends PrivateInstantiation{
  /**
   * @return int milliseconds
   */
  static public function microTime(){
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    return $time;
  }

  static public function microDelta($base){
    return bcsub(self::microTime(), $base, 4);
  }

  /**
   * Finds a timestamp of 0 seconds in latest Monday of specified week.
   *
   * @param null $weekTime timestamp anywhere inside a week Monday of which you want to find. null means current week.
   * @return int 00:00:00 of Monday
   */
  static public function mondayToTime($weekTime=null){
    return strtotime('last monday', strtotime('tomorrow', $weekTime?:time()));
  }
}
?>
