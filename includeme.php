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
    $classFullName = (string)str_replace('\\', DIRECTORY_SEPARATOR, substr($className, $namespaceNameLen+1));

    if (file_exists( $rootDirectory . $classFullName . '.php')){
      include ( $rootDirectory . $classFullName . '.php');
    }
  });
}

registerAutoloader("X", __XDIR__);
\X\registerAutoloader("Typograf", __XDIR__.'3rdparty/Typograf/');
\X\registerAutoloader("Phois", __XDIR__.'3rdparty/Phois/');
\X\registerAutoloader("Parsedown", __XDIR__.'3rdparty/Parsedown/');
\X\registerAutoloader("Clickatell", __XDIR__.'3rdparty/Clickatell/');
\X\registerAutoloader("Afi", __XDIR__.'3rdparty/Afigenius/'); 
\X\registerAutoloader("Snoop", __XDIR__.'3rdparty/Snoop/');
\X\registerAutoloader("Barzo\\Password", __XDIR__.'3rdparty/PasswordGenerator/');
\X\registerAutoloader("Longman\\TelegramBot", __XDIR__.'3rdparty/telegram-bot/');
\X\registerAutoloader("Skype", __XDIR__.'3rdparty/Skype/');
\X\registerAutoloader("ClickMeeting", __XDIR__.'3rdparty/Clickmeeting/');
\X\registerAutoloader("Piwik", __XDIR__.'3rdparty/Piwik/');
\X\registerAutoloader("Skype", __XDIR__.'3rdparty/Skype/');
\X\registerAutoloader("GuzzleHttp", __XDIR__.'3rdparty/GuzzleHttp/');
\X\registerAutoloader("Psr", __XDIR__.'3rdparty/Psr/'); 
\X\registerAutoloader("Symfony", __XDIR__.'3rdparty/Symfony/');
\X\registerAutoloader("Threads", __XDIR__.'3rdparty/Threads/'); 
\X\registerAutoloader("JeroenDesloovere\\VCard", __XDIR__.'3rdparty/VCard/');
\X\registerAutoloader("Behat\\Transliterator", __XDIR__.'3rdparty/Behat/Transliterator/');

require_once(__XDIR__ . '3rdparty/GuzzleHttp/functions.php');
require_once(__XDIR__ . '3rdparty/GuzzleHttp/Promise/functions.php');
require_once(__XDIR__ . '3rdparty/GuzzleHttp/Psr7/functions.php');
require_once(__XDIR__ . '3rdparty/Imagine/Autoloader.php');
\Imagine_Autoloader::register();

require_once(__XDIR__.'3rdparty/dompdf/autoload.inc.php');
require_once(__XDIR__.'3rdparty/PHPExcel/PHPExcel.php');
require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLParser.php');
require_once(__XDIR__.'3rdparty/PHPSQLParser/PHPSQLCreator.php');
require_once(__XDIR__.'3rdparty/PHPMailer/PHPMailerAutoload.php');
require_once(__XDIR__.'3rdparty/Mobile-Detect/Mobile_Detect.php');
require_once(__XDIR__.'3rdparty/Dropbox/autoload.php');


X::isOk();