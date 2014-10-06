<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 21.02.14
 * Time: 3:36
 */
namespace X;
use X\Debug\Logger;

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
    $classFullName = (string)str_replace('\\', DIRECTORY_SEPARATOR, substr($className, $namespaceNameLen+1));
    $classLastName = array_slice(explode( DIRECTORY_SEPARATOR, $className), -1);

    if (class_exists("\\X\\Debug\\Logger") && class_exists("\\X\\Tools\\Time") && class_exists("\\X\\Tools\\Validators") && class_exists("\\X\\Debug\\Tracer")){
      \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php...');
    }

    if (file_exists( $rootDirectory . $classFullName . '.php')){
      include ( $rootDirectory . $classFullName . '.php');
      if (class_exists("\\X\\Debug\\Logger") && class_exists("\\X\\Tools\\Time") && class_exists("\\X\\Tools\\Validators") && class_exists("\\X\\Debug\\Tracer")){
        \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php... Ok!');
      }
    }else{
      if (class_exists("\\X\\Debug\\Logger") && class_exists("\\X\\Tools\\Time") && class_exists("\\X\\Tools\\Validators") && class_exists("\\X\\Debug\\Tracer")){
        \X\Debug\Logger::Add("Autoloader: " . $rootDirectory . $classFullName . '.php... FAIL!');
      }
    }
  });
}

registerAutoloader("X", __XDIR__);
\X\registerAutoloader("Typograf", __XDIR__.'3rdparty/Typograf/');
\X\registerAutoloader("Parsedown", __XDIR__.'3rdparty/Parsedown/');
\X\registerAutoloader("Clickatell", __XDIR__.'3rdparty/Clickatell/');
\X\registerAutoloader("Afi", __XDIR__.'3rdparty/Afigenius/');

Logger::add("Loading 3rdParty libs");

Logger::add("Loading 'Imagine' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/Imagine/Autoloader.php')){
  require_once(__XDIR__ . '3rdparty/Imagine/Autoloader.php');
  if (class_exists("\\Imagine_Autoloader")){
    \Imagine_Autoloader::register();
    Logger::add("'Imagine' Autoloader loaded");
  }else{
    Logger::add("'Imagine' Autoloader FAILED TO LOAD");
  }
}

Logger::add("Loading 'PHPExcel' Autoloader");
if (file_exists(__XDIR__ . '3rdparty/PHPExcel/PHPExcel.php')){
  require_once(__XDIR__ . '3rdparty/PHPExcel/PHPExcel.php');
  Logger::add("'PHPExcel' Autoloader loaded");
}

require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLParser.php');
require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLCreator.php');

require_once(__XDIR__.'3rdparty/PHPMailer/PHPMailerAutoload.php');


X::isOk();