<?php

//TODO: sort and group by scope PK by default

namespace X\Data\DB;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use X\C;
use X\Data\Persistent\Cache;
use \X\Data\DB\Iterator;
use \X\Data\DB\Interfaces\IDB;
use X\Debug\Logger;
use X\Validators\Values;
use X\X;

class Collection extends \ArrayObject{

  protected $eof = false;
  protected $res = null;
  protected $expr = null;
  protected $exprRestriced=false;
  protected $row = 0;
  protected $count = 0;
  protected $rowCache = [];
  protected $lastRow = null;

  protected $scope = null;

  /**
   * @var null|IDB
   */
  protected $driver = null;

  protected $tables=[];
  protected $tableNames=[];
  protected $fieldNames=[];

  protected $where=[];
  protected $lim=[];
  protected $order=[];
  protected $group=[];
  protected $whereVars=[];
  protected $fields=[];

  const BAD_CALLBACK = 801;
  const BAD_QUERY_RESOURCE = 802;

  public function __construct(&$driver=null, $expr=null){
    if (!$driver){
      $driver = DB::connectionByDatabase();
    }
    $this->driver = &$driver;
    $this->num = 0;
    $this->lastRow= null;
    $this->eof = true;

    $vars = [];

    if (is_array($expr) && count($expr)==1){
      $expr=$expr[0];
    }

    if ($expr instanceof Expr){
      $this->expr = $expr;
      $this->exprRestriced=true;
    }elseif(is_array($expr)){
      $vars = array_slice($expr, 1);
      if (count($vars)==1 && is_array($vars[0])){
        $vars = $vars[0];
      }
      $expr = $expr[0];
    }else{
      if (func_num_args()>2){
        if (func_num_args()==3 && is_array(func_get_arg(2))){
          $vars = func_get_arg(2);
        }else{
          $vars = array_slice(func_get_args(),2);
        }
      }
    }
    $this->whereVars=$vars;
    if (is_string($expr)){
      $from=[];
      $where=[];
      $order=[];
      $group=[];
      $fields=[];
      $limit='';
      foreach($this->magicSploder($expr) as $part){
        $part = trim($part);
        if (Values::isSQLname($part) || ($this->strfind($part, ":")!==false && substr_count($part, ":")==1)){
          $from[]=$part;
        }elseif($this->strfind($part, "by ")!==false || $this->strfind($part." ", "asc ")!==false || $this->strfind($part." ", "desc")!==false){
          $order[]=$part;
        }elseif($this->strfind($part, "#")!==false){
          $limit=$part;
        }elseif($part[0]=='+'){
          $fields[]=substr($part,1);
        }elseif($part[0]=='(' && substr($part, -1)==')'){
          $group[]=$part;
        }elseif($this->strfind($part, "=")!==false){
          if ($this->strfind($part, " ")){
            $constraint=false;
            foreach(explode(" ", $part) as $tmp){
              if (Values::isSQLname($tmp)){
                $constraint=true;
              }
            }
            if ($constraint==false){
              $where[]=$part;
            }else{
              $from[]=$part;
            }
          }else{
            $tmp = explode("=", $part);
            if (count($tmp)>2){
              $where[]=$part;
            }else{
              $constraint=true;
              foreach($tmp as $t){
                if (strpos($t, ".")!=false){
                  $t = explode(".", $t);
                  if (count($t)==2){
                    if (!Values::isSQLname($t[0]) || !Values::isSQLname($t[1])){
                      $constraint=false;
                    }
                  }else{
                    $constraint=false;
                  }
                }elseif(!Values::isSQLname($t)){
                  $constraint=false;
                }
              }
              if ($constraint){
                $where[]=$part;
                $from[]=$part;
              }else{
                $where[]=$part;
              }
            }
          }
        }else{
          $isTable = false;
          if (strpos($part, ".")===false){
            $words = preg_split('/\b/is', $part);
            foreach($words as $word){
              if (Values::isSQLname($word) && CRUD::classByTable($word,$this->driver)){
                $isTable=true;
                break;
              }
            }
          }
          if ($isTable && !preg_match('/[^a-z0-9_\.\ \:\`]/i', $part)){
            $from[]=$part;
          }else{
            $where[]=$part;
          }
        }
      }
      $this->from($from);
      $this->by($where);
      $this->limit($limit);
      $this->order($order);
      $this->group($group);
      $this->fields($fields);
    }
    if (count($this->tableNames)==1){
      reset($this->tableNames);
      $this->scope(key($this->tableNames));
    }
  }

  public function fields($fields){
    if (!is_array($fields)){
      $fields = $this->magicSploder($fields);
    }
    foreach($fields as $f){
      $tmp = explode(" ", trim($f));
      $tmp = array_pop($tmp);
      if (Values::isSQLname($tmp,true)){
        $this->fields[] = "::".($name="expr_".md5($f));
        $this->whereVars[$name]=new Expr(str_replace(" ".$tmp, " as ".$tmp, $f));
      }
    }
    return $this;
  }

  public function order($order){
    if (!is_array($order)){
      $order = explode(", ", $order);
    }
    foreach($order as $o){
      $o=" ".strtolower($o)." ";
      $o=str_replace(" by ", "", $o);
      $o = " ".$o." ";
      $direction="ASC";
      if (strpos($o, " desc ")!==false){
        $direction="DESC";
      }
      $o=str_replace([" asc "," desc "], "", $o);
      $o = trim($o);
      if ($field = $this->isField($o)){
        $this->order[]=$field.' '.$direction;
      }else{
        $this->order[]=$o.' '.$direction; //we assume that the user can provide us with function inside
      }
    }
    return $this;
  }

  public function resetScope(){
    $this->scope=null;
    return $this;
  }

  public function scope($scope){
    if (!array_key_exists($scope, $this->tableNames)){
      throw new \RuntimeException("There is no table/alias '".$scope."' to scope results");
    }
    $this->scope = $scope;
    return $this;
  }

  public function group($group){
    $this->resetRes();
    if (is_array($group)){
      foreach($group as $g){
        $this->group($g);
      }
      return $this;
    }
    if ($group[0]=='(' && substr($group, -1)==')'){
      $group = substr($group, 1, -1);
    }
    foreach(explode(",", $group) as $g){
      if ($field = $this->isField($g)){
        $this->group[]=$field;
      }else{
        $this->group[]=$g; //we assume that the user can provide us with function inside
      }
    }
    return $this;
  }

  public function limit($lim){
    $this->resetRes();
    if (is_array($lim)){
      if (count($lim)==1){
        $this->lim=[intval($lim[0]), intval($lim[1])];
      }elseif(count($lim)==2){
        $this->lim=[intval($lim[0]), intval($lim[1])];
      }
      return $this;
    }
    $lim = trim($lim);
    if ($lim[0]=='#'){
      $lim = substr($lim, 1);
      $lim = str_replace(["(",")"],'', $lim);
    }

    if (strpos($lim, ",")!==false){
      $lim = explode(",", $lim);
      $this->lim=[intval($lim[0]), intval($lim[1])];
    }else{
      if (strlen($lim)){
        $this->lim=[intval($lim)];
      }
    }
    return $this;
  }

  public function resetConditions(){
    $this->resetRes();
    $this->where=[];
    $this->lim=[];
    $this->order=[];
    $this->group=[];
    $this->whereVars=[];
    return $this;
  }

  public function by($where){
    $this->resetRes();
    if (func_num_args()>1){
      $lastPH='';
      for ($i=0; $i<func_num_args(); $i++){
        $op='=';
        $newPH = 'auto_'.md5(uniqid(true));
        $part = trim(func_get_arg($i));
        if (!$lastPH){
          if ($part[0]=='%'){
            $op=' LIKE "%::'.$newPH.'"';
            $part = substr($part, 1);
          }else{
            switch(substr($part,-1)){
              case '=':
                if (substr($part, -2, 1)=='!'){
                  $op="!=";
                  $part = substr($part, 0, -2);
                  break;
                }
              case '>':
              case '<':
                $op = substr($part,-1).'::'.$newPH;
                $part = substr($part, 0, -1);
                break;
              case '%':
                if (substr($part, -2, 1)=='%'){
                  $op=' LIKE "%::'.$newPH.'%"';
                  $part = substr($part, 0, -2);
                }else{
                  $op=' LIKE "::'.$newPH.'%"';
                  $part = substr($part, 0, -1);
                }
                break;
              default:
                $op=false;
            }
          }
          if ($op===false){
            $this->where[]=$part;
            continue;
          }
          $part = trim($part);
          if ($field = $this->isField($part)){
            $this->where[]=$field.$op;
          }else{
            throw new \RuntimeException("Expected field, got '".$part."' which is either not field or has ambiguous name");
          }
          $lastPH = $newPH;
        }else{
          $this->whereVars[$lastPH]=$part;
          $lastPH='';
        }
      }
    }else{
      if (is_array($where)){
        foreach($where as $w){
          $this->where[]=$w;
        }

        $where = "WHERE ".implode(" and ", array_filter($this->where,'trim'));
        $parsed = (new PHPSQLParser($where))->parsed;
        if ($parsed && array_key_exists("WHERE", $parsed)){
          $this->parseWhere($parsed["WHERE"]);
        }
      }else{
        $this->where[]=$where;
      }
    }
    return $this;
  }

  public function parseWhere($subtree){
    foreach ($subtree as &$item){
      if ($item["expr_type"]=="colref"){
        $item["base_expr"] = trim($item["base_expr"]);
        if (strpos($item["base_expr"], ".")!==false){
          $tmp = explode(".", $item["base_expr"]);
          if (!array_key_exists($tmp[0], $this->tableNames) && !array_key_exists($tmp[0], $this->fieldNames)){
            $this->addTable($tmp[0]);
          }
        }
      }
      if (array_key_exists("sub_tree", $item) && $item["sub_tree"]){
        $this->parseWhere($item["sub_tree"]);
      }
    }
  }

  /**
   * @return Expr
   */
  public function expr(){
    if ($this->expr){
      return $this->expr;
    }
    if (!count($this->tables)){
      return false;
    }
    $fieldsWeNeed=[];
    foreach($this->tables as $tableData){
      $class = $tableData['class'];
      foreach($class::getFields() as $fieldName=>$field){
        if (array_key_exists('fullName', $field) && $field['fullName']){
          $fieldsWeNeed[]="`".$tableData['alias'].'`.`'.$fieldName."` as '".$tableData['alias'].".".$fieldName."'";
        }
      }
    }
    $fieldsWeNeed = array_merge($fieldsWeNeed, $this->fields);
    $fieldsWeNeed=implode(",", $fieldsWeNeed);
    //TODO: additional fields
    $joinedTables='';
    for ($i=0; $i<count($this->tables); $i++){
      if ($i==0){
        $joinedTables.="`".$this->tables[$i]['name']."` ".$this->tables[$i]['alias']." ";
      }else{
        $joinedTables.="LEFT JOIN `".$this->tables[$i]['name']."` ".$this->tables[$i]['alias']." ON(";
        $fields=[];
        foreach($this->tables[$i]['fields'] as $f1=>$f2){
          $fields[]=$f1."=".$f2;
        }
        $joinedTables.=implode(",",$fields);
        $joinedTables.=")";
      }
    }

    $where='';
    if (count($this->where)){
      $where = "WHERE ". implode($this->where, " AND ");
    }

    $groupBy='';
    if (count($this->group)){
      $groupBy="GROUP BY ".implode(",", $this->group);
    }

    $orderBy='';
    if (count($this->order)){
      $orderBy='ORDER BY '.implode(", ", $this->order);
    }

    $limit='';
    if (count($this->lim)==1){
      $limit="LIMIT ".$this->lim[0];
    }elseif (count($this->lim)==2){
      $limit="LIMIT ".$this->lim[0].", ".$this->lim[1];
    }

    $sqlExpr = 'SELECT '.$fieldsWeNeed.' FROM '.$joinedTables.' '.$where.' '.$groupBy.' '.$orderBy.' '.$limit;

    $parsed = (new PHPSQLParser($sqlExpr))->parsed;
    if (count($this->whereVars) && $parsed){
      $this->collapseVars($parsed["WHERE"], $this->whereVars);
    }
    $sqlExpr = (new PHPSQLCreator())->create($parsed);

    foreach($this->whereVars as $name=>$val){
      $sqlExpr = str_replace("::".$name, $val instanceof Expr ? $val->get() : $val, $sqlExpr);
    }

    //echo $sqlExpr;
    //if (($ttl = intval($options["cache_ttl"]))>0){
//      $cacheKey = md5($sqlExpr.$options["instantiator"]);
//    }
//    $collection = new JoinedCollection($sqlExpr, $this, $options['tables'], $ttl ? $cacheKey : null, $ttl);

    return ($this->expr = new Expr($sqlExpr));
  }

  private function collapseVars(&$subtree, $vars){
    $n=0;
    if (!$subtree || !is_array($subtree)){
      return;
    }
    foreach ($subtree as &$item){
      switch ($item["expr_type"]){
        case "colref":
          $valname = null;
          if (substr($item["no_quotes"]["parts"][0],0,2)=="::"){
            $valname = substr($item["no_quotes"]["parts"][0],2);
          }elseif($item["no_quotes"]["parts"][0]=="?"){
            $valname = $n++;
          }
          if ($valname!==null){
            if (array_key_exists($valname, $vars)){
              $replacement = $vars[$valname];
            }else{
              $replacement=null;
            }

            if (is_array($replacement)){
              throw new \InvalidArgumentException("Replacement in condition shouldn't be an array");
            }

            $item["expr_type"]="colref";
            $item["base_expr"]= ($replacement===null ? "NULL": (is_numeric($replacement) ? $replacement : (is_bool($replacement) ? ($replacement ? "TRUE" : "FALSE") : ($replacement instanceof Expr ? $replacement->get() : "\"".$this->driver->escape($replacement)."\""))));
            //unset($item["no_quotes"]);
          }
          break;
        case "const":
          $p1 = strpos($item["base_expr"], "::");
          if ($p1!==false){
            preg_match("/([a-z0-9_]*)/i", substr($item["base_expr"], $p1+2), $matches);
            if ($matches[0]){
              $p2=strlen($matches[0])+$p1+2;
            }else{
              $p2=false;
            }
          }else{
            $p2 = false;
          }
          if ($p1!==false && $p2!==false){
            $valname = substr($item["base_expr"],$p1+2, $p2-$p1-2);
          }else{
            $valname=null;
          }

          if ($valname && array_key_exists($valname, $vars)){
            $replacement = $vars[$valname];
          }else{
            $replacement=null;
          }

          $item["expr_type"]="const";
          $item["base_expr"]=str_replace("::".$valname, $replacement===null ? "NULL": is_numeric($replacement) ? $replacement : (is_bool($replacement) ? ($replacement ? "TRUE" : "FALSE") : $this->driver->escape($replacement)), $item["base_expr"]);
      }
      if (array_key_exists("sub_tree", $item) && $item["sub_tree"]){
        $this->collapseVars($item["sub_tree"], $vars);
      }
    }
  }

  private function isField($field){
    if (is_numeric($field)){
      return false;
    }
    if (strpos($field, "`")===false && strpos($field, ".")===false){
      if (!Values::isSQLname($field)){
        return false;
      }
    }
    $fieldName=$field;
    $field = str_replace("`", "", $field);
    if (strpos($field, ".")!==false){
      list($table, $field) = explode(".", $field);
      $fieldName=null;
      if (array_key_exists($table, $this->tableNames)){
        $className = $this->tableNames[$table];
        if (array_key_exists($field, $className::getFields())){
          $fieldName = "`".$table."`.`".$field."`";
        }
      }elseif($className = CRUD::classByTable($table, $this->driver->getDatabase()->getName())){
        if (array_key_exists($field, $className::getFields())){
          $fieldName = "`".$table."`.`".$field."`";
          $this->addTable($className);
        }
      }
    }else{
      if (array_key_exists($field, $this->fieldNames)){
        if (count($this->fieldNames[$field])==1){
          $fieldName = $this->fieldNames[$field][0];
        }else{
          return false;
        }
      }
    }

    return $fieldName?:false;
  }

  public function from($tables){
    $this->resetRes();
    if (!is_array($tables)){
      $this->magicSploder($tables);
    }else{
      array_walk($tables, function(&$v){$v=strtolower(trim($v));});
    }
    foreach($tables as $table){
      $part=null;
      $tmp='';
      $tableName = null;
      $conditions = [];
      $alias = null;
      $aliasPretender='';
      if (Values::isSQLname($table, false, $table)){
        $tableName = $table;
      }else{
        $inQuotas=false;
        for($i=0; $i<strlen($table); $i++){
          if ($table[$i]=='`'){
            $inQuotas=!$inQuotas;
            if ($i!=strlen($table)-1){
              continue;
            }
          }
          if (Values::isSQLname($table[$i], true) || $inQuotas){
            $tmp.=$table[$i];
          }

          if (!Values::isSQLname($table[$i], true) || $i==strlen($table)-1){
            if ($tableName===null && $tmp){
              $tableName=$tmp;
              $tmp='';
            }
            if ($table[$i]=='.' || $table[$i]=='='){
              if ($tableName && !$tmp){
                $tmp = $tableName;
              }elseif(Values::isSQLname($tmp) && !$alias && $table[$i]=='.'){
                $aliasPretender = $tmp;
              }
              $tmp.=$table[$i];
              $part="conditions";
            }else{
              if ($tableName && $tmp && !$part){
                $alias = $tmp;
                $tmp='';
              }elseif ($part=='conditions'){
                if (is_string($conditions)){
                  throw new \RuntimeException("For table ".$tableName." both FK and fields constrain are specified. Should be only one.");
                }
                $conditions[]=$tmp;
                if ($aliasPretender){;
                  $tmp = explode("=", $tmp)[1];
                  if (strpos($tmp, ".")!==false){
                    $tmp = explode(".", $tmp)[0];
                    if ($tmp===$tableName){
                      $aliasPretender='';
                    }
                  }
                }
                $tmp='';
                $part='';
              }elseif ($part=='key'){
                if (is_array($conditions) && count($conditions)!=0){
                  throw new \RuntimeException("For table ".$tableName." both FK and fields constrain are specified. Should be only one.");
                }
                $conditions=$tmp;
                $tmp='';
                $part='';
              }

              if($table[$i]==':'){
                $part='key';
              }
            }
          }
        }
      }

      if (!$alias && $aliasPretender){
        $alias = $aliasPretender;
      }
      if (is_array($conditions) && count($conditions)==0){
        $conditions=null;
      }elseif(is_array($conditions)){
        $tmp = $conditions;
        $conditions=[];
        foreach($tmp as $t){
          list($from, $to)=explode("=",$t);
          $conditions[trim($from)]=trim($to);
        }
      }
      $this->addTable([$tableName, $alias, $conditions]);
    }

  }

  public function addTable($tableClass, $alias=null, $conditions=null){
    $this->resetRes();
    if (is_array($tableClass)){
      list($tableClass, $alias, $conditions) = $tableClass;
    }

    if (count($this->tables)==0 && $conditions!==null){
      throw new \RuntimeException("Conditions for table ".$tableClass." are not allowed here, since this table is first in a row");
    }

    $tClass = $this->checkTableClass($tableClass);
    if ($alias && !Values::isSuitableForVarName($alias)){
      throw new \RuntimeException("Alias '".$alias."' can't be used as alias due to illegal symbols used");
    }elseif($alias===null){
      $alias = $tClass::TABLE_NAME;
    }

    if (count($this->tables)==0){
      $this->tables[]=[
        "name"=>$tClass::TABLE_NAME,
        "alias"=>$alias?:($alias=$tClass::TABLE_NAME),
        "class"=>$tClass
      ];
      $this->tableNames[$alias]=$tClass;
      $this->parseFields($tClass);
      return $this;
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

      $fieldCheck = function($fieldName)use($tClass, $alias){
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
          }elseif($table == $tClass::TABLE_NAME || $table==$alias){
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

  public function __destruct(){
    if ($this->res){
      $this->driver->freeResource($this->res);
    }
  }

  public function prefetch(){
    $i=0;
    $this->rowCache=[];
    while($a = $this->driver->getNext($this->res)){
      $this->rowCache[$i++]=$a;
    }
  }

  public function run(){
    if ($this->res===null && $this->expr()){
      //echo $this->expr()->get()."<br><br>";
      $this->res = $this->expr()->run($this->driver);
      if ($this->res){
        $this->count = $this->driver->numRows($this->res);
      }else{
        $this->count = 0;
      }
      if ($this->count && $this->count<C::getDbCacheMaxrows()){
        $this->prefetch();
      }
      $this->eof= $this->count===0;
    }
  }

  /**
   * @param null $num
   * @return CollectionMember
   */
  public function row($num = null){
    if ($num === null){
      $num = $this->row;
    }

    $this->run();

    if ($num<0 || $num >= $this->count) {
      $this->lastRow = false;
    }elseif (array_key_exists($num, $this->rowCache)){
      $this->lastRow = $this->rowCache[$num];
    }else{
      $this->driver->dataSeek($this->res, $num);
      $data = $this->driver->getNext($this->res);
      $this->lastRow = $this->rowCache[$num] = $data;
    }

    $this->row = $num;
    $this->eof = $this->lastRow===false;

    if ($this->exprRestriced){
      return $this->lastRow;
    }else{
      if (!$this->lastRow){
        return false;
      }
      if ($this->lastRow instanceof CollectionMember){
        $answer = $this->lastRow;
      }else{
        $this->rowCache[$num] = $answer = new CollectionMember($this, $this->lastRow);
      }

      if ($this->scope){
        $tableName = ucwords($this->scope);
        $answer = $answer->$tableName();
      }
      return $answer;
    }
  }

  public function getTable($alias){
    $alias = strtolower($alias);
    if (array_key_exists($alias, $this->tableNames)){
      return $this->tableNames[$alias];
    }else{
      return false;
    }
  }

  public function getTableField($tableField){
    $tableField=ucwords($tableField);
    $chunks = preg_split('/(?=[A-Z])/', $tableField);
    array_shift($chunks);
    $name='';
    foreach($chunks as $c){
      $name.=$c;
      $field = strtolower(substr($tableField, strlen($name)));
      if ($className = $this->getTable($name)){
        if (array_key_exists($field, $className::getFields())){
          return ['table'=>$className, 'field'=>$field];
        }
      }
    }
    return false;
  }

  public function resetRes(){
    $this->res = null;
    if (!$this->exprRestriced){
      $this->expr = null;
    }
  }

  public function reset(){
    $this->row = 0;
    $this->lastRow=null;
    $this->eof = ($this->count()==0);
  }

  public function next(){
    if ($this->lastRow === null){
      $n = $this->row;
    }else{
      $n = $this->row + 1;
    }
    return $this->row($n);
  }

  public function position($num=null){
    if ($num===null){
      return $this->row;
    }

    if ($num >= $this->count()) {
      $this->eof = true;
      return $this->lastRow = false;
    }

    if ($num < 0){
      $this->row = 0;
      return false;
    }

    return $this->row=$num;
  }

  public function current(){
    return $this->row($this->row);
  }

  public function prev(){
    return $this->row($this->row - 1);
  }

  public function first(){
    return $this->row(0);
  }

  public function last(){
    return $this->row($this->count() - 1);
  }

  public function EOF(){
    $this->run();
    return $this->eof;
  }

  public function size(){
    return $this->count();
  }

  public function offsetExists($index){
    if (is_string($index)){
      return false;
    }
    $index = intval($index);
    return $index >= 0 && $index < $this->count();
  }

  public function offsetGet($index){
    return $this->Row($index);
  }

  public function offsetSet($index, $newval){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function offsetUnset($index){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function append($value){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getArrayCopy(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function count(){
    $this->run();
    return $this->count;
  }

  public function getFlags(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function setFlags($flags){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function asort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function ksort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function uasort($cmp_function){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function uksort($cmp_function){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function natsort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function natcasesort(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function unserialize($serialized){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function serialize(){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getIterator(){
    if (!$this->iterator){
      $this->iterator = new Iterator($this);
    }
    return $this->iterator;
  }

  public function exchangeArray($input){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function setIteratorClass($iterator_class){
    throw new \BadMethodCallException("Collection: This method is not allowed (".__METHOD__.")");
  }

  public function getIteratorClass(){
    return "\\X\\Data\\DB\\Iterator";
  }


  private function parseFields($tableClass){
    $tableClass = $this->checkTableClass($tableClass);
    foreach($tableClass::getFields() as $name=>$fieldData){
      if (array_key_exists("fullName", $fieldData) && $fieldData["fullName"]){
        $this->fieldNames[$name][]=$fieldData["fullName"];
      }
    }
  }

  private function magicSploder($string){
    $string = strtolower($string);
    $string = preg_replace('/\s\s+/', ' ', trim($string));
    $string = str_replace([" =", "= "], "=", $string);
    $reg = '/[^(,]*(?:\([^)]+\))?[^),]*[\)]*/';
    preg_match_all($reg, $string, $matches);
    $answer=array_filter($matches[0]);
    array_walk($answer, function(&$v){$v=trim($v);});
    return $answer;
  }

  private function strfind($haystack, $needle, $offset=0){
    if (($pos=strpos($haystack, $needle, $offset))!==false){
      $chunk = substr($haystack, 0, $pos);
      if (
        (substr_count($chunk, "(") - substr_count($chunk, "("))!=0 ||
        substr_count($chunk, "'")%2!=0 ||
        substr_count($chunk, "\"")%2!=0 ||
        substr_count($chunk, "`")%2!=0
      ){
        return $this->strfind($haystack, $needle, $pos+1);
      }else{
        return $pos;
      }
    }
    return false;
  }

  private function checkTableClass($tableClass){

    if (!class_exists($tableClass)){
      $tmp = CRUD::classByTable($tableClass, $this->driver->getDatabase()->getName());
      if (!class_exists($tmp)){
        throw new \RuntimeException("There is no CRUD class '".$tableClass."'");
      }else{
        $tableClass = $tmp;
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
}
