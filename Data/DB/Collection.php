<?php

//TODO: sort and group by scope PK by default

namespace X\Data\DB;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use X\Tools\Strings;
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

  protected $fieldsToSelect=[];

  const BAD_CALLBACK = 801;
  const BAD_QUERY_RESOURCE = 802;

  public function __construct($driver=null, $expr=null){

    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');

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

    if ($expr instanceof Collection){
      $this->expr = $expr->expr();
      $this->exprRestriced=true;
    }elseif ($expr instanceof Expr){
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

    if (is_array($vars) && count($vars)){
      $this->whereVars=$vars;
    }

    if (is_string($expr)){
      $from=[];
      $where=[];
      $order=[];
      $group=[];
      $fields=[];
      $limit='';
      foreach(Strings::explodeSelective($expr) as $part){
        $part = trim($part);
        if (!strlen($part)){
          continue;
        }
        if($this->strfind(" ".$part, " by ")!==false || $this->strfind(" ".$part." ", " asc ")!==false || $this->strfind(" ".$part." ", " desc ")!==false){
          $order[]=$part;
        }elseif (Values::isSQLname($part) || ($this->strfind($part, ":")!==false && substr_count($part, ":")==1)){
          $from[]=$part;
        }elseif($this->strfind($part, "#")!==false){
          $limit=$part;
        }elseif($part[0]=='+'){
          $fields[]=substr($part,1);
        }elseif($part[0]=='(' && substr($part, -1)==')' && substr_count($part, "(")==1 && substr_count($part, ")")==1){
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
              $word = trim($word);
              if ($word && Values::isSQLname($word) && CRUD::classByTable($word,$this->driver)){
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  public function fields($fields){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (!is_array($fields)){
      $fields = Strings::explodeSelective($fields);
    }
    foreach($fields as $f){
      $tmp = explode(" ", trim($f));
      $tmp = array_pop($tmp);
      if (Values::isSQLname($tmp,true)){
        $this->fields[] = "::".($name="expr_".md5($f));
        $this->whereVars[$name]=new Expr(str_replace(" ".$tmp, " as ".$tmp, $f));
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function order($order){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (!is_array($order)){
      $order = Strings::explodeSelective($order);
    }

    if (func_num_args()>1){
      if (func_num_args()==2 && is_array(func_get_arg(1))){
        $vars = func_get_arg(1);
      }else{
        $vars = array_slice(func_get_args(),1);
      }
      if (!Values::isAssoc($vars)){
        foreach($vars as $v){
          $this->whereVars[]=$v;
        }
      }else{
        $this->whereVars=array_replace_recursive($this->whereVars, $vars);
      }
    }

    $this->resetRes();
    foreach($order as $o){
      $o = trim($o);
      if ($o=="*"){
        $this->order[]="RAND()";
      }else{
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
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function resetScope(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->scope=null;
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function scope($scope){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (!array_key_exists($scope, $this->tableNames)){
      throw new \RuntimeException("There is no table/alias '".$scope."' to scope results");
    }
    $this->scope = $scope;
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function group($group){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->resetRes();
    if (is_array($group)){
      foreach($group as $g){
        $this->group($g);
      }
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function limit($lim){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->resetRes();
    if (is_array($lim)){
      if (count($lim)==1){
        $this->lim=[intval($lim[0]), intval($lim[1])];
      }elseif(count($lim)==2){
        $this->lim=[intval($lim[0]), intval($lim[1])];
      }
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function resetConditions(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->resetRes();
    $this->where=[];
    $this->lim=[];
    $this->order=[];
    $this->group=[];
    $this->whereVars=[];
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function by($where){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (func_num_args()>1){
      if (func_num_args()==2 && is_array(func_get_arg(1))){
        $vars = func_get_arg(1);
      }else{
        $vars = array_slice(func_get_args(),1);
      }
      if (!Values::isAssoc($vars)){
        foreach($vars as $v){
          $this->whereVars[]=$v;
        }
      }else{
        $this->whereVars=array_replace_recursive($this->whereVars, $vars);
      }

    }

    $this->resetRes();
    if (func_num_args()>1){
      $newPH = 'auto_'.md5(uniqid(true));

      $part = func_get_arg(0);
      $part = trim($part);
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
        if ($this->isField($part)){
          $op='=::'.$newPH;
          $this->whereVars[$newPH]=func_get_arg(1);
        }else{
          $this->where[]=$part;
          Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
          return $this;
        }
      }
      $part = trim($part);
      if ($field = $this->isField($part)){
        $this->where[]=$field.$op;
      }else{
        throw new \RuntimeException("Expected field, got '".$part."' which is either not field or has ambiguous name");
      }
    }else{
      if (is_array($where)){
        foreach($where as $w){
          $this->where[]=$w;
        }

        $where = "WHERE ".implode(" and ", array_filter($this->where,'trim'));
        $parser = new PHPSQLParser($where);
        $parsed = $parser->parsed;
        if ($parsed && array_key_exists("WHERE", $parsed)){
          $this->parseWhere($parsed["WHERE"]);
        }
        unset($parser);
      }else{
        $this->where[]=$where;
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function parseWhere(&$subtree){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    foreach ($subtree as &$item){
      if ($item["expr_type"]=="colref"){
        $item["base_expr"] = trim($item["base_expr"]);
        if (strpos($item["base_expr"], ".")!==false){
          $tmp = explode(".", $item["base_expr"]);
          if (!array_key_exists($tmp[0], $this->tableNames) && !array_key_exists($tmp[0], $this->fieldNames)){
            $this->addTable($tmp[0]);
          }
          $item["base_expr"] = '`'.$tmp[0].'`.`'.$tmp[1].'`';
        }
      }
      if (array_key_exists("sub_tree", $item) && $item["sub_tree"]){
        $this->parseWhere($item["sub_tree"]);
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }
  
  public function addFieldsToSelect($field){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $fields = explode(",", $field);
    $fields = array_filter($fields);
    foreach($fields as &$f){
      if (!($tmp = $this->isField($f))){
        throw new \RuntimeException("The field '".$f."' cannot be added to the list of fields to select since it is ambiguous");
      }
      $this->fieldsToSelect[]=$tmp;
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  /**
   * @return Expr
   */
  public function expr(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if ($this->expr){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $this->expr;
    }

    if (!count($this->tables)){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return false;
    }
    $fieldsWeNeed=[];
    if (count($this->fieldsToSelect)){
      $fieldsWeNeed=$this->fieldsToSelect;
    }else{
      foreach($this->tables as $tableData){
        $class = $tableData['class'];
        foreach($class::getFields() as $fieldName=>$field){
          if (array_key_exists('fullName', $field) && $field['fullName']){
            $fieldsWeNeed[]="`".$tableData['alias'].'`.`'.$fieldName."` as '".$tableData['alias'].".".$fieldName."'";
          }
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
      $groupBy = array_unique($this->group);
      $groupBy="GROUP BY ".implode(",", $groupBy);
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

    $parser = new PHPSQLParser($sqlExpr);
    $parsed = $parser->parsed;

    if (count($this->whereVars) && $parsed){
      if (array_key_exists("WHERE", $parsed)){
        $this->collapseVars($parsed["WHERE"], $this->whereVars);
      }
      if (array_key_exists("ORDER", $parsed)){
        $this->collapseVars($parsed["ORDER"], $this->whereVars);
      }
    }
    $creator = new PHPSQLCreator();
    $sqlExpr = $creator->create($parsed);

    foreach($this->whereVars as $name=>$val){
      $sqlExpr = str_replace("::".$name, $val instanceof Expr ? $val->get() : ($val instanceof Collection ? $val->expr()->get() : $val), $sqlExpr);
    }
    unset($parser);
    unset($creator);
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return ($this->expr = new Expr($sqlExpr));
  }

  private function collapseVars(&$subtree, $vars, &$n=0){
    $valname=0;
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (!$subtree || !is_array($subtree)){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $valname;
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

            $item["expr_type"]="colref";

            if (is_array($replacement)){
              $item["base_expr"] = Strings::smartImplode($replacement, ",", function($var){return $this->driver->escape($var);});
            }else{
              $item["base_expr"]= ($replacement===null ? "NULL": (is_numeric($replacement) ? $replacement : (is_bool($replacement) ? ($replacement ? "TRUE" : "FALSE") : ($replacement instanceof Expr ? $replacement->get() : ($replacement instanceof Collection ? $replacement->expr()->get() : "\"".$this->driver->escape($replacement)."\"")))));
            }
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
        $this->collapseVars($item["sub_tree"], $vars, $n);
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $n;
  }

  private function isField($field){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (is_numeric($field)){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return false;
    }
    $exists=array_key_exists($field, $this->fieldNames);
    if (strpos($field, "`")===false && strpos($field, ".")===false){
      if (!Values::isSQLname($field) && !$exists){
        Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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
          Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
          return false;
        }
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $fieldName?:false;
  }

  public function from($tables){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->resetRes();
    if (!is_array($tables)){
      $tables = Strings::explodeSelective($tables);
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function addTable($tableClass, $alias=null, $conditions=null){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');

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
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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
        throw new \RuntimeException("The table '".$tClass::TABLE_NAME."' cannot be joined without specifying FK since it is ambiguous");
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
          throw new \RuntimeException("Fields provided in [".$fromField."=>".$toField."] conditions contain non-existing ones or ambiguous. Use {table_name}.{field_name} naming style.");
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this;
  }

  public function __destruct(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if ($this->res){
      $this->driver->freeResource($this->res);
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  public function prefetch(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $i=0;
    $this->rowCache=[];
    while($a = $this->driver->getNext($this->res)){
      $this->rowCache[$i++]=$a;
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  public function run(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  /**
   * @param null $num
   * @return CollectionMember
   */
  public function row($num = null){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
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
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $this->lastRow;
    }else{
      if (!$this->lastRow){
        Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $answer;
    }
  }

  public function getTable($alias){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $alias = strtolower($alias);
    if (array_key_exists($alias, $this->tableNames)){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $this->tableNames[$alias];
    }else{
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return false;
    }
  }

  public function getTableField($tableField){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $tableField=ucwords($tableField);
    $chunks = preg_split('/(?=[A-Z])/', $tableField);
    array_shift($chunks);
    $name='';
    foreach($chunks as $c){
      $name.=$c;
      $field = strtolower(substr($tableField, strlen($name)));
      if ($className = $this->getTable($name)){
        if (array_key_exists($field, $className::getFields()) || array_key_exists($field, $className::getCFields())){
          Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
          return ['alias'=>$name, 'table'=>$className, 'field'=>$field];
        }
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return false;
  }

  public function resetRes(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->res = null;
    if (!$this->exprRestriced){
      $this->expr = null;
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  public function reset(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $this->row = 0;
    $this->lastRow=null;
    $this->eof = ($this->count()==0);
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  public function next(){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if ($this->lastRow === null){
      $n = $this->row;
    }else{
      $n = $this->row + 1;
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $this->row($n);
  }

  public function position($num=null){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if ($num===null){
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $this->row;
    }

    if ($num >= $this->count()) {
      $this->eof = true;
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return $this->lastRow = false;
    }

    if ($num < 0){
      $this->row = 0;
      Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
      return false;
    }

    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
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

  /**
   * @param mixed $index
   * @return mixed|CollectionMember
   */
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    $tableClass = $this->checkTableClass($tableClass);
    foreach($tableClass::getFields() as $name=>$fieldData){
      if (array_key_exists("fullName", $fieldData) && $fieldData["fullName"]){
        $this->fieldNames[$name][]=$fieldData["fullName"];
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
  }

  private function strfind($haystack, $needle, $offset=0){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
    if (($pos=strpos($haystack, $needle, $offset))!==false){
      $chunk = substr($haystack, 0, $pos);
      if (
        (substr_count($chunk, "(") - substr_count($chunk, "("))!=0 ||
        substr_count($chunk, "'")%2!=0 ||
        substr_count($chunk, "\"")%2!=0 ||
        substr_count($chunk, "`")%2!=0
      ){
        Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
        return $this->strfind($haystack, $needle, $pos+1);
      }else{
        Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
        return $pos;
      }
    }
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return false;
  }

  private function checkTableClass($tableClass){
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.begin');
	  $tableClass=str_replace("`", "", $tableClass);
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
    Logger::add('function '.__CLASS__.'::'.__FUNCTION__.'.end');
    return $tableClass;
  }
}
