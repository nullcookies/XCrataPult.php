<?php

namespace X\Data\DB\Drivers;

use X\Data\DB\Expr;
use X\Data\DB\Interfaces\ICRUD;
use X\Tools\Strings;
use \X\X;
use \X\C;
use \X\Debug\Logger;
use \X\Data\DB\DB;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\Structure\Field;
use \X\Data\DB\Structure\Key;
use \X\Data\DB\Collection;


class Mysql implements IDB{

  const ERR_CRUD_GENERATION_ERROR = 301;
  const ERR_UNSUPPORTED_FIELD_TYPE = 302;
  const ERR_UNSUPPORTED_KEY_TYPE = 303;
  const ERR_NO_DATABASE_NAME = 304;
  const ERR_CANNOT_CONNECT = 305;

  const ERR_GET_NO_TABLE = 306;

  private $host =null;
  private $login =null;
  private $pass =null;
  private $charset = null;

  /**
   * @var Database
   */
  private $database = null;

  /**
   * @var string
   */
  private $dbname = '';

  /**
   * @var string
   */
  private $alias = '';
  private $currentDatabase = null;
  private static $connection=null;

  private $dataTypes = Array(
    "boolean"    => "bool",

    "bit"        => "int",
    "tinyint"    => "int",
    "int"        => "int",
    "smallint"   => "int",
    "mediumint"  => "int",
    "bigint"     => "int",
    "serial"     => "int",

    "float"      => "double",
    "double"     => "double",
    "decimal"    => "double",
    "real"       => "double",

    "varchar"    => "string",
    "char"       => "string",
    "tinytext"   => "string",
    "text"       => "string",
    "mediumtext" => "string",
    "longtext"   => "string",

    "tinyblob"   => "string",
    "mediumblob" => "string",
    "blob"       => "string",
    "longblob"   => "string",

    "enum"       => "string",
    "set"        => "string",

    "date"       => "string",
    "time"       => "string",
    "year"       => "string",
    "datetime"   => "string",
    "timestamp"  => "string",
  );

  public function __construct($dbname, $alias=null, $host = null, $login = null, $pass = null) {

    if (!$this->dbname && !$dbname){
      throw new \Exception("There is no database name specified!", self::ERR_NO_DBNAME);
    }
    Logger::add("Constructing instance of ".get_called_class()." for DB ".$dbname);

    $this->dbname = $dbname;
    $this->alias = $alias ?: $this->database;

    $this->host   = $host  ?: $this->host  ?: ini_get("mysql.default_host");
    $this->login  = $login ?: $this->login ?: ini_get("mysql.default_user");
    $this->pass   = $pass  ?: $this->pass  ?: ini_get("mysql.default_password");
  }

  public function getAlias(){
    return $this->alias;
  }

  public function disconnect(){
    if (self::$connection){
      mysql_close(self::$connection);
    }
    self::$connection=null;
  }

  public function reconnect(){
    self::$connection=null;
    $this->lazyConnect();
  }
  public function lazyConnect(){
    if (self::$connection){
      return;
    }
    Logger::add("MySQL: Connecting to ".($this->login ?: "default user")."@".($this->host ?: "default socket")." ...");
    if (self::$connection=mysql_connect($this->host, $this->login, $this->pass, true)){
      Logger::add("MySQL: Connecting to ".($this->login ?: "default user")."@".($this->host ?: "default socket")." ...OK");
      self::chooseDB($this->dbname, $this->alias);
    }else{
      throw new \Exception("Can't connect to MySQL ".($this->login ?: "default user")."@".($this->host ?: "default socket"), self::ERR_CANNOT_CONNECT);
    }
    if($this->charset){
      $this->setCharset($this->charset);
    }
  }

  public function escape($string){
    $this->lazyConnect();
    return mysql_real_escape_string($string, self::$connection);
  }

  public function getTables(){
    $this->lazyConnect();

    self::chooseDB("information_schema");
    $tables = [];
    foreach (new Collection($this, new Expr("SELECT TABLE_NAME as name, UNIX_TIMESTAMP(CREATE_TIME) as time FROM TABLES WHERE TABLE_SCHEMA = '".$this->dbname."'")) as $a){
      $tables[]=$a;
    }
    Logger::add("- got Tables");
    self::chooseDB();
    return $tables;
  }

  public function getTableKeys($tableName){
    $this->lazyConnect();

    $keys = [];
    foreach(new Collection($this, new Expr("SELECT k.*, t.* FROM information_schema.table_constraints t JOIN information_schema.key_column_usage k USING ( constraint_name, table_schema, table_name ) WHERE k.TABLE_NAME = '".$tableName."' AND t.table_schema =  '".$this->dbname."'")) as $key){

      if (!array_key_exists($key['CONSTRAINT_NAME'], $keys)){
        $keys[$key['CONSTRAINT_NAME']] = new Key($this, $key['CONSTRAINT_NAME']);
      }

      $type = 0;
      switch($key['CONSTRAINT_TYPE']){
        case 'PRIMARY KEY':
          $type = Key::KEY_TYPE_PRIMARY;
          break;
        case 'FOREIGN KEY':
          $type = Key::KEY_TYPE_FOREIGN;
          break;
        case 'UNIQUE':
          $type = Key::KEY_TYPE_UNIQUE;
          break;
      }

      if (!$type){
        throw new \Exception("Unknown key type '".$key['CONSTRAINT_TYPE']."'", self::ERR_UNSUPPORTED_KEY_TYPE);
      }

      $keyField = &DB::connectionByDatabase($key['TABLE_SCHEMA'])
        ->getDatabase()
        ->tableByName($key['TABLE_NAME'])
        ->fieldByName($key['COLUMN_NAME']);
      if ($type==Key::KEY_TYPE_FOREIGN && $key['REFERENCED_TABLE_SCHEMA']){
        $keyRefField = &DB::connectionByDatabase($key['REFERENCED_TABLE_SCHEMA'])
          ->getDatabase()
          ->tableByName($key['REFERENCED_TABLE_NAME'])
          ->fieldByName($key['REFERENCED_COLUMN_NAME']);
        $keys[$key['CONSTRAINT_NAME']]->addRefField($keyField, $keyRefField);
        $keys[$key['CONSTRAINT_NAME']]->setRefTable($key['REFERENCED_TABLE_NAME']);
        $keys[$key['CONSTRAINT_NAME']]->unUnique();
      }//else{
        $keys[$key['CONSTRAINT_NAME']]->addField($keyField);
      //}
      Logger::add("- - key \"".$key['CONSTRAINT_NAME']."\": '".$key['TABLE_NAME']."_".$key['COLUMN_NAME']."'... OK");
    }
    //SHOW INDEX FROM `data`.`object_names`
    foreach(new Collection($this, new Expr("SHOW INDEX FROM `".$this->dbname."`.`".$tableName."`")) as $key){

      if ($key['Key_name']=="PRIMARY"){
        continue;
      }
      if (!array_key_exists($key['Key_name'], $keys)){
        $keys[$key['Key_name']] = new Key($this, $key['Key_name']);
        $keys[$key['Key_name']]->unUnique();
      }

      $type = 0;
      if ($key['Non_unique']){
        $type = Key::KEY_TYPE_INDEX;
      }else{
        $type = Key::KEY_TYPE_UNIQUE;
      }

      $keyField = &DB::connectionByDatabase($key['TABLE_SCHEMA'])
        ->getDatabase()
        ->tableByName($key['Table'])
        ->fieldByName($key['Column_name']);
      $keys[$key['Key_name']]->addField($keyField);
      Logger::add("- - key \"".$key['Key_name']."\": '".$key['Table']."_".$key['Column_name']."'... OK");
    }
    return $keys;
  }

  public function getTableFields($tableName){
    $this->lazyConnect();

    $fields = [];
    foreach(new Collection($this, new Expr("SHOW COLUMNS FROM `".$tableName."`")) as $field){
      Logger::add("- - table '".$tableName."'... field '".$field['Field']."'");
      $extra = strtolower($field['Extra']);
      $typeParams = explode("(",$field['Type']);
      $type = strtolower($typeParams[0]);
      $typeRange = count($typeParams)>1 ? str_replace(')','',$typeParams[1]) : null;

      if (!array_key_exists($type, $this->dataTypes)){
        throw new \Exception("Type '".$type."' is not yet supported", self::ERR_UNSUPPORTED_FIELD_TYPE);
      }

      if ($type=='enum' || $type=='set'){
        $typeRange = array_map("trim", explode(",", str_replace(["'",'"'],'',$typeRange)));
      }

      $fields[$field['Field']] = (new Field($this, $field['Field'], $tableName))
        ->setType($type, $typeRange)
        ->setNull($field['Null']!='NO')
        ->setDefault($field['Default']!="CURRENT_TIMESTAMP" ? $field['Default'] : null)
        ->setAutoIncrement($extra == "auto_increment")
        ->setUnsigned(strpos(strtolower($field['Type']), "unsigned"))
        ->setTimeOnUpdate($extra == "on update current_timestamp")
        ->setTimeOnCreate($field['Default'] == "CURRENT_TIMESTAMP")
      ;
      Logger::add("- - table '".$tableName."'... field '".$field['Field']."'... OK");
    }
    return $fields;
  }

  /**
   * LAZY.
   * @return Database
   */
  public function &getDatabase(){

    if (!($this->database instanceof Database)){
      $this->database = new Database($this, $this->dbname, $this->alias);
    }
    return $this->database;
  }

  public function chooseDB($dbname=null, $alias=null){
    if ($dbname===null){
      $dbname = $this->dbname;
    }else{
      $dbname = strtolower($dbname);
    }

    Logger::add("MySQL: DB changing to " . $dbname." ...");

    if ($this->currentDatabase == strtolower($dbname)){
      Logger::add("MySQL: DB is already set to " . $dbname);
      return;
    }

    if (mysql_select_db($dbname)){
      $this->currentDatabase = strtolower($dbname);
    }else{
      throw new \Exception("Can't choose DB ".$dbname." (".$this->error().")");
    }

    Logger::add("MySQL: DB changing to " . $dbname." ...OK");
  }

  public function dropDB($dbname) {
    $this->lazyConnect();
    $this->query("DROP DATABASE IF EXISTS `".$this->escape($dbname)."`");
    return true;
  }

  public function query($sql) {
    $this->lazyConnect();
    Logger::add("MySQL query: ".$sql);
    return mysql_query($sql, self::$connection);
  }

  public function getNext($resource, $asArray=true, $assoc=true){
    $this->lazyConnect();
    if ($asArray){
      return mysql_fetch_array($resource, $assoc ? MYSQL_ASSOC : MYSQL_NUM);
    }else{
      return mysql_fetch_object($resource);
    }
  }

  public function numRows($resource) {
    $this->lazyConnect();
    return mysql_num_rows($resource);
  }

  public function dataSeek($resource, $position) {
    $this->lazyConnect();
    return mysql_data_seek($resource, $position);
  }

  public function freeResource($resource) {
    $this->lazyConnect();
    return mysql_free_result($resource);
  }

  public function errno($resource = null) {
    $this->lazyConnect();
    if ($resource===null){
      return mysql_errno();
    }else{
      return mysql_errno($resource);
    }
  }

  public function error($resource = null) {
    $this->lazyConnect();
    if ($resource===null){
      return mysql_error();
    }else{
      return mysql_error($resource);
    }
  }

  private function parseOrderBy($condition){
    $orderBy = Array();
    if (is_array($condition)){
      foreach ($condition as $key=>$val){
        $key = strtolower($key);
        if ($val=='*'){
          $orderBy[]="RAND()";
        }elseif (is_int($key)){
          $orderBy[]="`".$val."` ASC";
        }else{
          $orderBy[]="`".$key."` ".(($val===false || strtolower($val)==='desc') ? "DESC" : "ASC");
        }
      }
    }elseif(is_string($condition) && strlen(trim($condition))>0){
      $orderBy=[trim($condition)];
    }

    if (count($orderBy)){
      $orderBy = "ORDER BY ".implode(", ", $orderBy);
    }else{
      $orderBy = '';
    }

    return $orderBy;
  }

  public function store(ICRUD &$object){

    foreach($object->getFields() as $fieldName=>$fieldRules){
      if (!array_key_exists('userfield',$fieldRules)){
        $fieldNames[]=$fieldName;
        $data[$fieldName]=$object->{$fieldRules["getter"]}();
        if ($data[$fieldName]===null && !$fieldRules['null']){
          $data[$fieldName]=$fieldRules['default'];
        }
      }
    }

    if (!$object->isValid()){
      $sql = "INSERT INTO `".$object::TABLE_NAME."`";

      $sql.=" (".Strings::smartImplode($fieldNames, ",", function(&$value){$value='`'.$value.'`';}).") ".
            "VALUES".
            " (".Strings::smartImplode($fieldNames, ",", function(&$value)use($data){$value=$data[$value]===null ? "NULL" : "'".$this->escape($data[$value])."'";}).");";
    }else{
      $sql = "UPDATE `".$object::TABLE_NAME."` SET ";
      foreach($fieldNames as $name){
        $null = ($data[$name]===null) && ($object->getFields()[$name]["null"]);

        $sql.= '`'.$name.'`'."= ".($null ? "NULL" : "'".$this->escape($data[$name])."'").($name != end($fieldNames) ? ",":"");
      }
      $sql.= " WHERE ";
      $primaryFields = $object::getPrimaryFields();
      reset($primaryFields);
      foreach($primaryFields as $name=>$alias){
        $sql.= '`'.$name.'`'."='".$object->getPrimaryField($name)."'".($alias != end($primaryFields) ? " AND ":"");
      }
      $sql.= ";";
    }
    $res = $this->query($sql);

    if (!$res){
      throw new \Exception("SQL FAILED: >>".$sql."<< WITH ERROR ".mysql_error(self::$connection));
    }
    if ($res && ($id = mysql_insert_id(self::$connection))){
      $object->autoincrement($id);
    }
    return $res;
  }

  public function delete(ICRUD &$object){
    foreach($object->getFields() as $fieldName=>$fieldRules){
      $fieldNames[]=$fieldName;
      $data[$fieldName]=$object->{'get'.ucwords($fieldName)}();
      if ($data[$fieldName]===null && !$fieldRules['null']){
        $data[$fieldName]=$fieldRules['default'];
      }
    }

    $sql = "DELETE FROM `".$object::TABLE_NAME."` WHERE ";
    $primaryFields = $object::getPrimaryFields();
    if (count($primaryFields)){
      reset($primaryFields);
      foreach($primaryFields as $name=>$alias){
        $sql.= '`'.$name.'`'."='".$object->getPrimaryField($name)."'".($alias != end($primaryFields) ? " AND ":"");
      }
      $sql.= " LIMIT 1;";
    }else{
      $fields = $object::getFields();
      reset($fields);
      foreach($fields as $name=>$fdata){
        $sql.= '`'.$name.'`'."='".$object->getFieldData($name)."'".($fdata != end($fields) ? " AND ":"");
      }
      $sql.= " LIMIT 1;";
    }

    $res = $this->query($sql);
    if (!$res){
      throw new \Exception("SQL FAILED: >>".$sql."<< WITH ERROR ".mysql_error(self::$connection));
    }

    return $res;
  }

  public function setCharset($charset){
    if (self::$connection){
      $charset = $this->escape($charset);
      $this->query("SET NAMES '".$charset."'");
    }else{
      $this->charset = $charset;
    }
  }

  public function generateSQLCreateFromField(Field $field){
    // TODO:
    return '';
  }

  public function generateSQLCreateFromTable(Table $table){
    // TODO:
    return '';
  }

  public function getPHPType($type){
    return $this->dataTypes[$type];
  }
}
