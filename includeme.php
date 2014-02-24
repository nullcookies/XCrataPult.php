<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 21.02.14
 * Time: 3:36
 */
namespace X;
define("__XDIR__", dirname(__FILE__) . DIRECTORY_SEPARATOR);

function _X_AUTOLOADER($className) {
  if (substr($className, 0, 2) !== "X\\"){
    return;
  }

  $classFullName = (string)str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 2));
  $classLastName = array_slice(explode( DIRECTORY_SEPARATOR, $className), -1);
  if (substr(array_pop($classLastName), 0, 2)=='T_'){ //trait
    $classFullName = str_replace("T_", "", $classFullName);
  }
  if (class_exists("\\X\\Debug\\Logger") && class_exists("\\X\\Tools\\Time") && class_exists("\\X\\Tools\\Validators") && class_exists("\\X\\Debug\\Tracer")){
    \X\Debug\Logger::Add("Autoloader: " . __XDIR__ . $classFullName . '.php');
  }

  if (file_exists(__XDIR__ . $classFullName . '.php')){
    include (__XDIR__ . $classFullName . '.php');
  }
}

spl_autoload_register("\\X\\_X_AUTOLOADER");
\X\Debug\Logger::Add("Autoloader: registered");