<?php

namespace X\Data\DB\Drivers;

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
use \X\Validators\Values;
use \X\Data\DB\Collection;
use \X\Data\DB\CRUD;


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
  private $connection=null;

  private $dataTypes = Array(
    "boolean"    => "bool",

    "bit"        => "int",
    "tinyint"    => "int",
    "int"        => "int",
    "smallint"   => "int",
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

    $this->dbname = $dbname;
    $this->alias = $alias ?: $this->database;

    $this->host   = $host  ?: $this->host  ?: ini_get("mysql.default_host");
    $this->login  = $login ?: $this->login ?: ini_get("mysql.default_user");
    $this->pass   = $pass  ?: $this->pass  ?: ini_get("mysql.default_password");

    Logger::add("MySQL: Connecting to ".($this->login ?: "default user")."@".($this->host ?: "default socket")." ...");

    if ($this->connection=mysql_pconnect($this->host, $this->login, $this->pass)){
      Logger::add("MySQL: Connecting to ".($this->login ?: "default user")."@".($this->host ?: "default socket")." ...OK");
      self::chooseDB($this->dbname, $this->alias);
    }else{
      throw new \Exception("Can't connect to MySQL ".($this->login ?: "default user")."@".($this->host ?: "default socket"), self::ERR_CANNOT_CONNECT);
    }
    return $this;
  }

  public function escape($string){
    return mysql_real_escape_string($string, $this->connection);
  }

  public function getTables(){
    self::chooseDB("information_schema");

    $tables = [];
    foreach (new Collection("SELECT TABLE_NAME as name, UNIX_TIMESTAMP(CREATE_TIME) as time FROM TABLES WHERE TABLE_SCHEMA = '".$this->dbname."'", $this) as $a){
      $tables[]=$a;
    }
    Logger::add("- got Tables");
    self::chooseDB();
    return $tables;
  }

  public function getTableKeys($tableName){
    $keys = [];
    foreach(new Collection("SELECT k.*, t.* FROM information_schema.table_constraints t JOIN information_schema.key_column_usage k USING ( constraint_name, table_schema, table_name ) WHERE k.TABLE_NAME = '".$tableName."' AND t.table_schema =  '".$this->dbname."'", $this) as $key){

      $key_s = new Key($this, $key['CONSTRAINT_NAME']);

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
      $key_s->addField($keyField);
      if ($key['REFERENCED_TABLE_SCHEMA']){
        $keyRefField = &DB::connectionByDatabase($key['REFERENCED_TABLE_SCHEMA'])
          ->getDatabase()
          ->tableByName($key['REFERENCED_TABLE_NAME'])
          ->fieldByName($key['REFERENCED_COLUMN_NAME']);
        $key_s->addRefField($keyRefField);
      }
      $keys[$key['CONSTRAINT_NAME']] = $key_s;
      Logger::add("- - key '".$key['TABLE_NAME']."_".$key['COLUMN_NAME']."'... OK");
    }
    return $keys;
  }

  public function getTableFields($tableName){
    $fields = [];
    foreach(new Collection("SHOW COLUMNS FROM `".$tableName."`", $this) as $field){
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

      $fields[$field['Field']] = (new Field($this, $field['Field']))
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
    $this->query("DROP DATABASE IF EXISTS `".$this->escape($dbname)."`");
    return true;
  }

  public function query($sql) {
    Logger::add("MySQL query: ".$sql);
    return mysql_query($sql, $this->connection);
  }

  public function condition($fieldName, $compare, $value)
  {
    $where = "`".mysql_real_escape_string($fieldName,$this->connection)."` ";

    if (is_array($value))
      if ($compare==IDB::SELECT_IN)
        $val = Strings::smartImplode($value, ",", function(&$val){$val = "'".mysql_real_escape_string($val, $this->connection)."'";});
      elseif ($compare==IDB::SELECT_BETWEEN && count($value)==2)
      {
        $val[0] = mysql_real_escape_string($value[0], $this->connection);
        $val[1] = mysql_real_escape_string($value[1], $this->connection);
      }
      else
        $val = mysql_real_escape_string(implode("",$value), $this->connection);
    else
      $val = mysql_real_escape_string($value, $this->connection);

    switch($compare)
    {
      case IDB::SELECT_LESS:
        $where.="< '".$val."'";
        break;

      case IDB::SELECT_EQUAL_LESS:
        $where.="<= '".$val."'";
        break;

      case IDB::SELECT_EQUAL_GREATER:
        $where.=">= '".$val."'";
        break;

      case IDB::SELECT_GREATER:
        $where.="> '".$val."'";
        break;

      case IDB::SELECT_STARTS:
        $where.=" LIKE '".$val."%'";
        break;

      case IDB::SELECT_CONTAINS:
        $where.=" LIKE '%".$val."%'";
        break;

      case IDB::SELECT_ENDS:
        $where.=" LIKE '%".$val."'";
        break;

      case IDB::SELECT_IN:
        $where.=" IN (".$val.")";
        break;

      case IDB::SELECT_BETWEEN:
        $where.=" BETWEEN ".$val[0]." AND ".$val[1];
        break;

      case IDB::SELECT_NOT_EQUAL:
      case IDB::SELECT_NOT_EQUAL_1:
      default:
        if ($value === null)
          $where.="IS NOT NULL";
        else
          $where.="!= '".$val."'";
        break;

      case IDB::SELECT_EQUAL:
      default:
        if ($value === null)
          $where.="IS NULL";
        else
          $where.="= '".$val."'";
        break;
    }
    return $where;
  }

  public function getNext($resource, $asArray=true, $assoc=true){
    if ($asArray){
      return mysql_fetch_array($resource, $assoc ? MYSQL_ASSOC : MYSQL_NUM);
    }else{
      return mysql_fetch_object($resource);
    }
  }

  public function numRows($resource) {
    return mysql_num_rows($resource);
  }

  public function dataSeek($resource, $position) {
    return mysql_data_seek($resource, $position);
  }

  public function freeResource($resource) {
    return mysql_free_result($resource);
  }

  public function errno($resource = null) {
    if ($resource===null){
      return mysql_errno();
    }else{
      return mysql_errno($resource);
    }
  }

  public function error($resource = null) {
    if ($resource===null){
      return mysql_error();
    }else{
      return mysql_error($resource);
    }
  }

  public function getSimple($options=[]){
    $defaults = [
      'conditions'=>[],
      'limit'=>0,
      'asArray'=>false,
      'order'=>[],
      'table'=>null,
      'instantiator'=>null,
      'fields'=>[]
    ];
    $options = array_merge($defaults, $options);

    if (strpos($options['instantiator'], "::")){
      $options['instantiator'] = explode("::",$options['instantiator']);
    }

    if ($options['instantiator']!==false && !Values::isCallback($options['instantiator'])){
      if ($tableClass = \X\Debug\Tracer::getCallerClass()){
        $interfaces = class_implements($tableClass, true);
        if ($interfaces && !in_array("X\\Data\\DB\\CRUD", $interfaces)){
          if ($options['table']===null){
            $options['table']=$tableClass::TABLE_NAME;
          }
          if ($options['table']===$tableClass::TABLE_NAME){
            $options['instantiator'] = $tableClass.'::createFromRaw';
            $options['fields']=[];
          }
        }
      }
    }elseif($options['instantiator']!==false && $options['table']===null){
      $options['table']=$options['instantiator'][0]::TABLE_NAME;
    }

    if ($options['table']===null){
      throw new \exception("Table wasn't specified for select-query", self::ERR_GET_NO_TABLE);
    }

    $answer=null;
    if (!is_array($options['conditions']) || !count($options['conditions']))
      $options['conditions']=null;

    $options['limit'] = intval($options['limit']);

    $where = Array();
    if ($options['conditions']){
      foreach($options['conditions'] as $field=>$condition){
        if (!is_array($condition)){
          $compare = IDB::SELECT_EQUAL;
          $value = $condition;
        }else if (array_key_exists('value',$condition)){
          $compare = array_key_exists('compare',$condition) ? $condition['compare'] : IDB::SELECT_EQUAL;
          $value = $condition['value'];
        }else if (Values::isAssoc($condition)){
          $compare=[];
          $value=[];
          foreach($condition as $condCompare=>$condValue){
            $compare[] = $condCompare;
            $value[] = $condValue;
          }
        }else{
          $compare = IDB::SELECT_IN;
          $value = array_values($condition);
        }

        if (!is_array($compare)){
          $where[]=$this->condition($field, $compare, $value);
        }else{
          for ($i=0; $i<count($compare); $i++){
            $where[]=$this->condition($field, $compare[$i], $value[$i]);
          }
        }
      }
    }

    if (count($where))
      $where = "WHERE ".implode($where, " AND ");
    else
      $where = '';
    $orderBy = Array();
    foreach ($options['order'] as $key=>$val){
      if ($val=='*'){
        $orderBy[]="RAND()";
      }elseif (is_int($key) && (!isset($tableClass) || (!array_key_exists($key, $tableClass::getFields()) && array_key_exists($val,$tableClass::getFields())))){
        $orderBy[]="`".$val."` ASC";
      }elseif (!isset($tableClass) || array_key_exists($key, $tableClass::getFields())){
        $orderBy[]="`".$key."` ".(($val===false || strtolower($val)==='desc') ? "DESC" : "ASC");
      }
    }
    if (count($orderBy)){
      $orderBy = "ORDER BY ".implode(", ", $orderBy);
    }else{
      $orderBy = '';
    }

    $fieldsWeNeed = (!is_array($options['fields']) || !count($options['fields'])) ? '*' : Strings::smartImplode($options['fields'], ", ", function(&$value){$value = "`".$value."`";});
    $collection = new Collection('SELECT '.$fieldsWeNeed.' FROM `'.$options['table'].'` '.$where.' '.$orderBy.' '.($options['limit'] > 0 ? 'LIMIT '.$options['limit'] : '').';', $this, $options['instantiator']);

    if (!!$options['asArray']){
      $answer = Array();
      while($instance = $collection->Next()){
        $answer[]=$instance->asArray();
      }
      return $answer;
    }

    return $collection;
  }

  public function store(ICRUD &$object){
    foreach($object->getFields() as $fieldName=>$fieldRules){
      $fieldNames[]=$fieldName;
      $data[$fieldName]=$object->{'get'.ucwords($fieldName)}();
      if ($data[$fieldName]===null && !$fieldRules['null']){
        $data[$fieldName]=$fieldRules['default'];
      }
    }

    if (!$object->isValid()){
      $sql = "INSERT INTO `".$object::TABLE_NAME."`";
      $sql.=" (".Strings::smartImplode($fieldNames, ",", function(&$value){$value='`'.$value.'`';}).") ".
            "VALUES".
            " (".Strings::smartImplode($fieldNames, ",", function(&$value)use($data){$value=$data[$value]===null ? "NULL" : "'".mysql_real_escape_string($data[$value])."'";}).");";
    }else{
      $sql = "UPDATE `".$object::TABLE_NAME."` SET ";
      foreach($fieldNames as $name){
        $sql.= '`'.$name.'`'."='".$this->escape($data[$name])."'".($name != end($fieldNames) ? ",":"");
      }
      $sql.= " WHERE ";
      $primaryFields = $object::getPrimaryFields();
      reset($primaryFields);
      foreach($primaryFields as $name=>$alias){
        $sql.= '`'.$name.'`'."='".$object->getPrimaryField($name)."'".($alias != end($primaryFields) ? ",":"");
      }
      $sql.= ";";
    }
    $res = $this->query($sql);
    if (!$res){
      throw new \Exception("SQL FAILED: >>".$sql."<< WITH ERROR ".mysql_error($this->connection));
    }
    if ($res && $id = mysql_insert_id($this->connection)){
      $object->autoincrement($id);
    }
    return $res;
  }

  public function delete(ICRUD &$object){
    if (!$object->isValid()){
      return;
    }

    foreach($object->getFields() as $fieldName=>$fieldRules){
      $fieldNames[]=$fieldName;
      $data[$fieldName]=$object->{'get'.ucwords($fieldName)}();
      if ($data[$fieldName]===null && !$fieldRules['null']){
        $data[$fieldName]=$fieldRules['default'];
      }
    }

    $sql = "DELETE FROM `".$object::TABLE_NAME."` WHERE ";
    $primaryFields = $object::getPrimaryFields();
    reset($primaryFields);
    foreach($primaryFields as $name=>$alias){
      $sql.= '`'.$name.'`'."='".$object->getPrimaryField($name)."'".($alias != end($primaryFields) ? ",":"");
    }
    $sql.= " LIMIT 1;";

    $res = $this->query($sql);
    if (!$res){
      throw new \Exception("SQL FAILED: >>".$sql."<< WITH ERROR ".mysql_error($this->connection));
    }

    return $res;
  }

  public function setCharset($charset){
    $charset = mysql_real_escape_string($charset, $this->connection);
    $this->query("SET NAMES '".$charset."'");
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
