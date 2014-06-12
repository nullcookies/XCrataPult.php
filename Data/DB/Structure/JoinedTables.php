<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 08.06.14
 * Time: 13:14
 */

namespace X\Data\DB\Structure;

use X\C;
use X\Data\DB\CRUD;
use X\Data\DB\DB;
use X\Data\DB\JoinedCollection;
use X\Validators\Values;

class JoinedTables {

  private $tables=[];
  private $tableNames=[];
  private $defaultNamespace='';
  private $fieldNames=[];

  public function __construct($tableClass){
    $this->defaultNamespace = "\\".str_replace('/',"\\", C::getDbNamespace().ucfirst(DB::connectionByDatabase()->getAlias()))."\\";
    $alias=null;
    if (is_array($tableClass)){
      list($tableClass, $alias) = $tableClass;
      if ($alias && !Values::isSuitableForVarName($alias)){
        throw new \RuntimeException("Alias '".$alias."' can't be used as alias due to illegal symbols used");
      }
    }
    $tableClass = $this->checkTableClass($tableClass);
    $this->tables[]=[
      "name"=>$tableClass::TABLE_NAME,
      "alias"=>$alias?:($alias=$tableClass::TABLE_NAME),
      "class"=>$tableClass
    ];
    $this->tableNames[$alias]=$tableClass;

    $this->parseFields($tableClass);

    if (func_num_args()>1){
      for($i=1; $i<func_num_args(); $i++){
        if (is_array(func_get_args()[$i])){
          list($name, $condition, $alias) = func_get_args()[$i];
          $this->join($name, $condition, $alias);
        }else{
          $this->join(func_get_args()[$i]);
        }
      }
    }
  }

  private function checkTableClass($tableClass){

    if (!class_exists($tableClass)){
      if (!class_exists($this->defaultNamespace.$tableClass)){
        throw new \RuntimeException("There is no CRUD class '".$tableClass."'");
      }else{
        $tableClass = $this->defaultNamespace.$tableClass;
      }
    }
    $reflection = new \ReflectionClass($tableClass);
    if ($reflection->isAbstract()){
      throw new \RuntimeException("Class '".$tableClass."' is abstract. Shouldn't be");
    }
    if (!$reflection->implementsInterface("\\X\\Data\\DB\\Interfaces\\ICRUD")){
      throw new \RuntimeException("Class '".$tableClass."' doesn't implement \"\\X\\Data\\DB\\Interfaces\\ICRUD\" interface");
    }
    return $tableClass;
  }

  public function join($tableClass, $conditions=null, $alias=null){
    $tClass = $this->checkTableClass($tableClass);
    if ($alias && !Values::isSuitableForVarName($alias)){
      throw new \RuntimeException("Alias '".$alias."' can't be used as alias due to illegal symbols used");
    }elseif($alias===null){
      $alias = $tClass::TABLE_NAME;
    }

    $fields=null;

    $fieldsAssigner = function($fieldsOriginal, $tClass, $dClass, $tAlias, $dAlias){
      $fields=[];
      foreach($fieldsOriginal as $f1=>$f2){
        if ($dClass::TABLE_NAME!=$dAlias){
          $f1 = explode(".", $f1);
          $f1[0]="`".$dAlias."`";
          $f1 = implode(".", $f1);
        }
        if ($tClass::TABLE_NAME!=$tAlias){
          $f2 = explode(".", $f2);
          $f2[0]="`".$tAlias."`";
          $f2 = implode(".", $f2);
        }
        $fields[$f1]=$f2;
      }
      return $fields;
    };

    if ($conditions===null){
      try{
        $checkRef = function($tClass, $dClass, $tAlias, $dAlias)use(&$fields, $fieldsAssigner){
          if (array_key_exists($tClass::TABLE_NAME, $dClass::$refTables)){
            if (count($dClass::$refTables[$tClass::TABLE_NAME])>1){
              throw new \RuntimeException();
            }elseif(count($dClass::$refTables[$tClass::TABLE_NAME])==1){
              if ($fields!==null){
                throw new \RuntimeException();
              }else{
                $fields=$fieldsAssigner(reset($dClass::$refTables[$tClass::TABLE_NAME]), $tClass, $dClass, $tAlias, $dAlias);
              }
            }
          }
        };

        foreach($this->tables as $dTable){
          $dClass = $dTable["class"];

          // dClass -> tClass
          $checkRef($tClass, $dClass, $alias, $dTable["alias"]);
          $checkRef($dClass, $tClass, $dTable["alias"], $alias);
        }

      }catch(\RuntimeException $e){
        $fields = null;
      }

      if ($fields===null){
        throw new \RuntimeException("The table '".$tClass::TABLE_NAME."' cannot be joined without specifying FK since it is ambigous");
      }

    }elseif(is_string($conditions)){ // FK name stated
      $conditions = trim(strtolower($conditions));
      foreach($this->tables as $dTable){
        $dClass = $dTable["class"];

        // dClass -> tClass
        if (
          array_key_exists($tClass::TABLE_NAME, $dClass::$refTables) &&
          array_key_exists($conditions, $dClass::$refTables[$tClass::TABLE_NAME])
        ){
          $fields=$fieldsAssigner($dClass::$refTables[$tClass::TABLE_NAME][$conditions], $tClass, $dClass, $alias, $dTable["alias"]);
        }

        // tClass -> dClass
        if (
          array_key_exists($dClass::TABLE_NAME, $tClass::$refTables) &&
          array_key_exists($conditions, $tClass::$refTables[$dClass::TABLE_NAME])
          ){
            $fields=$fieldsAssigner($tClass::$refTables[$dClass::TABLE_NAME][$conditions], $dClass, $tClass, $dTable["alias"], $alias);
          }
        }
      if ($fields===null){
        throw new \RuntimeException("The table '".$tClass::TABLE_NAME."' cannot be joined with FK '".$conditions."' since it has no fields shared between tables to be connected");
      }
    }elseif (is_array($conditions) && count($conditions)){

      $fieldCheck = function($fieldName)use($tClass){
        $fieldName = strtolower($fieldName);
        if (strpos($fieldName, ".")){
          $fieldName = str_replace("`", "", $fieldName);
          list($table, $field) = explode(".", $fieldName);
          $fieldName=null;
          if (array_key_exists($table, $this->tableNames)){
            $className = $this->tableNames[$table];
            if (array_key_exists($field, $className::getFields())){
              $fieldName = "`".$table."`.`".$field."`";
            }
          }elseif($table == $tClass::TABLE_NAME){
            if (array_key_exists($field, $tClass::getFields())){
              $fieldName = "`".$table."`.`".$field."`";
            }
          }
        }else{
          if (array_key_exists($fieldName, $this->fieldNames) && count($this->fieldNames[$fieldName])==1 && !array_key_exists($fieldName, $tClass::getFields())){
            $fieldName = $this->fieldNames[$fieldName][0];
          }elseif(!array_key_exists($fieldName, $this->fieldNames) && array_key_exists($fieldName, $tClass::getFields()) && array_key_exists("fullName", $tClass::getFields()[$fieldName]) && $tClass::getFields()[$fieldName]["fullName"]){
            $fieldName = $tClass::getFields()[$fieldName]["fullName"];
          }else{
            $fieldName='';
          }
        }
        return $fieldName;
      };

      foreach($conditions as $fromField=>$toField){
        $fromField = $fieldCheck($fromField);
        $toField = $fieldCheck($toField);
        if ($fromField && $toField){
          $fields[$fromField]=$toField;
        }else{
          throw new \RuntimeException("Fields provided in conditions contain non-existing ones or ambiguous. Use {table_name}.{field_name} naming style.");
        }
      }
    }else{
      throw new \RuntimeException("Conditions provided to join tables are not supported. Please, refer to the documentation.");
    }

    $this->tables[]=[
      "name"=>$tClass::TABLE_NAME,
      "alias"=>$alias,
      "class"=>$tClass,
      "fields"=>$fields
    ];
    $this->tableNames[$alias]=$tClass;
    $this->parseFields($tClass);

    return $this;
  }

  /**
   * @param string $where
   * @param string|array $fields
   * @param int $limit
   * @param string|array $order
   * @return JoinedCollection
   */
  public function get($where=null, $fields=null, $limit=0, $order=Array(), $groupBy=null){
    /**
     * @var $tClass CRUD
     */
    $tClass = $this->tables[0]["class"];
    $connection = $tClass::connection();
    return $connection->getJoined([
      'tables'=>$this->tables,
      'conditions'=>[$where, $fields],
      'limit'=>$limit,
      'order'=>$order,
      'groupBy'=>$groupBy
    ]);
  }

  private function parseFields($tableClass){
    $tableClass = $this->checkTableClass($tableClass);
    foreach($tableClass::getFields() as $name=>$fieldData){
      if (array_key_exists("fullName", $fieldData) && $fieldData["fullName"]){
        $this->fieldNames[$name][]=$fieldData["fullName"];
      }
    }
  }

} 