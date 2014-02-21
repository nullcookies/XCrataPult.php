<?php
namespace X\Tools;
if (!defined('__XDIR__')) die();

use \X\AbstractClasses\PrivateInstantiation;

final class Time extends PrivateInstantiation{
  /**
   * @return int milliseconds
   */
  static public function microTime()
  {
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    return $time;
  }

  static public function microDelta($base)
  {
    return bcsub(self::microTime(), $base, 4);
  }
}
?>
