<?php

namespace X\Data\DB;

use \X\C;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Field;
use \X\Debug\Logger;
use \X\Tools\FileSystem;
use \X\Validators\Values;
use \X\AbstractClasses\PrivateInstantiation;
use \X\Data\DB\Structure\CRUDGenerator;


class DB extends PrivateInstantiation{

  const ERR_BAD_INTERFACE = 100;
  const ERR_NO_SUCH_DRIVER = 101;
  const ERR_BAD_CONNECTION_TYPE = 102;
  const ERR_BAD_ALIAS_NAME = 103;
  const ERR_NO_SUCH_DATABASE = 104;
  const ERR_CANNOT_CREATE_DIR = 105;

  private static $SUPPORTED_CONNECTION_TYPES = ['socket','ip'];

  private static $connections=[];
  private static $aliases=[];

  /**
   * @param string $alias alias name
   * @param string $driver driver name
   *
   * @throws \exception on any error
   */
  public static function setDriver($alias, $driver){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    $driver = strval($driver);

    $driver = ucfirst($driver);

    if (!class_exists($driver)){
      if (!class_exists("\\X\\Data\\DB\\Drivers\\".$driver)){
        throw new \exception("DB Driver '".$driver."' doesn't exist.", self::ERR_NO_SUCH_DRIVER);
      }else{
        $className = "\\X\\Data\\DB\\Drivers\\".$driver;
      }
    }else{
      $className = $driver;
    }
    $interfaces = class_implements($className);
    if (!$interfaces || !in_array("X\\Data\\DB\\Interfaces\\IDB", $interfaces)){
      throw new \exception("Specified driver for db ".$alias." doesn't implement interface \\X\\Data\\DB\\Interfaces\\IDB", self::ERR_BAD_INTERFACE);
    }
    self::$connections[$alias]['driver'] = $className;
  }

  /**
   * @param string $alias alias name
   * @param string $charset charset name
   */
  public static function setCharset($alias, $charset){
    self::$connections[$alias]['charset']=$charset;
  }

  /**
   * @param string $alias alias name
   * @param string $type connection type (supported are in $SUPPORTED_CONNECTION_TYPES)
   *
   * @throws \exception on any error
   */
  public static function setConnectionType($alias, $type){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    if (!in_array($type, self::$SUPPORTED_CONNECTION_TYPES)){
      throw \exception("Bad connection type for ".$alias.". Supported types are: ".implode(", ", self::$SUPPORTED_CONNECTION_TYPES), self::ERR_BAD_CONNECTION_TYPE);
    }
    self::$connections[$alias]['connection_type']=$type;
  }

  /**
   * @param string $alias alias name
   * @param string $socket socket name (will be tested during connection)
   */
  public static function setSocket($alias, $socket){
    self::setConnectionType($alias, 'socket');
    self::setHost($alias, ':'.$socket);
  }

  /**
   * @param string $alias alias name
   * @param string $host host/ip name (will be tested during connection)
   */
  public static function setHost($alias, $host){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    self::$connections[$alias]['host']=$host;
  }

  /**
   * @param string $alias alias name
   * @param string $login username (will be tested during connection)
   */
  public static function setLogin($alias, $login){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    self::$connections[$alias]['login']=$login;
  }

  /**
   * @param string $alias alias name
   * @param string $password password for user (will be tested during connection)
   */
  public static function setPassword($alias, $password){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    self::$connections[$alias]["password"] = $password;
  }

  /**
   * @param string $alias alias name
   * @param string $alias should meet file naming and php php class naming requirements
   */
  public static function setDatabase($alias, $db){
    if (!Values::isSuitableForVarName($alias)){
      throw \exception("Bad alias name for ".$alias, self::ERR_BAD_ALIAS_NAME);
    }
    self::$connections[$alias]['database']=$db;
    self::$aliases[$db]=$alias;
  }

  /**
   * @param $alias
   *
   * @return IDB
   * @throws \Exception
   */
  public static function &connectionByAlias($alias){
    if (!array_key_exists($alias, self::$connections) || !array_key_exists('database',self::$connections[$alias])){
      throw new \Exception("There is no database with alias '".$alias."' registered in DB::connections", self::ERR_NO_SUCH_DATABASE);
    }
    $connection = &self::$connections[$alias];
    if (!array_key_exists("connection", $connection)){
      if (!class_exists($connection['driver'])){
        throw new \Exception("Driver for DB '".$alias."' wasn't set or invalid");
      }
      $connection['connection']=
        (
        new $connection['driver'](
          $connection['database'],
          $alias,
          array_key_exists('host',$connection) ? $connection['host'] : null,
          array_key_exists('login',$connection) ? $connection['login'] : null,
          array_key_exists('password',$connection) ? $connection['password'] : null
        )
        );
      if (array_key_exists("charset", $connection)){
        $connection['connection']->setCharset($connection['charset']);
      }
    }
    return $connection['connection'];
  }

  /**
   * @param $db
   *
   * @return IDB
   * @throws \Exception
   */
  public static function connectionByDatabase($db=null){
    if ($db===null && count(self::$aliases)){
      reset(self::$aliases);
      $db = key(self::$aliases);
    }
    if (!array_key_exists($db, self::$aliases)){
      throw new \Exception("There is no database '".$db."' registered in DB::connections", self::ERR_NO_SUCH_DATABASE);
    }
    return self::connectionByAlias(self::$aliases[$db]);
  }

  /**
   * Generates CRUDs for all specified DBs
   */
  public static function generateCRUDs($forceAbstracts=false, $forceInheritors=false){
    foreach(self::$connections as $database=>$connection){
      Logger::add("Generating CRUDs for '".$database."'...");

      Logger::add(" - getting database object...");
      $db = &self::connectionByAlias($database)->getDatabase();
      Logger::add(" - getting database object...OK");
      Logger::add(" - creating CRUD files...");
      foreach ($db->getTables() as $table){
        Logger::add("Processing table ".$table->getName());
        $className = ucfirst($table->getName());
        $folderName = ucfirst($db->getAlias());

        $classPath = C::getDbDir().$folderName;
        $classPathAbstracts = C::getDbDir().$folderName."/abstracts";
        $classFile = $classPath."/".$className.".php";
        $classFileAbstracts = $classPathAbstracts."/".$className.".php";

        if ($forceInheritors || !file_exists($classFile)){
          if ((!file_exists($classPath) || !is_dir($classPath)) && !mkdir($classPath, 0774, true)){
            throw new \Exception("Cannot create directory '".$classPath."' for CRUD '".$className."'", self::ERR_CANNOT_CREATE_DIR);
          }
          $namespaceName = str_replace('/',"\\", C::getDbNamespace().$folderName);
          $classFileContents = file_get_contents(dirname(__FILE__).'/Structure/CRUDinheritorTemplate');
          $classFileContents = str_replace("{%NAMESPACE%}",      $namespaceName,       $classFileContents);
          $classFileContents = str_replace("{%DATABASE_ALIAS%}", $db->getAlias(),      $classFileContents);
          $classFileContents = str_replace("{%TABLENAME%}",      $table->getName(),    $classFileContents);
          $classFileContents = str_replace("{%CLASSNAME%}",      $className,           $classFileContents);

          file_put_contents($classFile, $classFileContents);
        }

        if ($forceAbstracts || !file_exists($classFileAbstracts) || filemtime($classFileAbstracts)<$table->getLastModified()){
          if ((!file_exists($classPathAbstracts) || !is_dir($classPathAbstracts)) && !mkdir($classPathAbstracts, 0774, true)){
            throw new \Exception("Cannot create directory '".$classPathAbstracts."' for CRUD '".$className."'", self::ERR_CANNOT_CREATE_DIR);
          }
          file_put_contents($classFileAbstracts, CRUDGenerator::generateClass($db, $table));
        }
      }
      Logger::add(" - creating CRUD files...OK");

      Logger::add("Generating CRUDs for '".$database."'...OK");
    }
  }

}
