<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 27.04.14
 * Time: 0:24
 *
 * Made to be used with XCrataPult.php
 */

if (class_exists("X\\X")){
  define('__SNOOP_DIR__', dirname(__FILE__).'/' );
  \X\registerAutoloader("Snoop", __SNOOP_DIR__);
}