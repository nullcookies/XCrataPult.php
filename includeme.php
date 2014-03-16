<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 21.02.14
 * Time: 3:36
 */
namespace X;
define("__XDIR__", dirname(__FILE__) . DIRECTORY_SEPARATOR);

function registerAutoloader($namespaceName, $rootDirectory){

  if (substr($rootDirectory, -1)!=DIRECTORY_SEPARATOR){
    $rootDirectory.=DIRECTORY_SEPARATOR;
  }

  spl_autoload_register( function($className) use(&$namespaceName, &$rootDirectory){
    $namespaceNameLen = strlen($namespaceName);
    if (substr($className, 0, $namespaceNameLen+1) !== $namespaceName."\\"){
      return;
    }

    $classFullName = (string)str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 2));
    $classLastName = array_slice(explode( DIRECTORY_SEPARATOR, $className), -1);
    if (class_exists("\\X\\Debug\\Logger") && class_exists("\\X\\Tools\\Time") && class_exists("\\X\\Tools\\Validators") && class_exists("\\X\\Debug\\Tracer")){
      \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php...');
    }

    if (file_exists( $rootDirectory . $classFullName . '.php')){
      include ( $rootDirectory . $classFullName . '.php');
      \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php... Ok!');
    }else{
      \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php... FAIL!');
    }
  });
}

registerAutoloader("X", __XDIR__);