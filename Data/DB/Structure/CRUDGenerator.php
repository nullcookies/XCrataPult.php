<?php
//TODO: fix getByKey as there should be not only one!

namespace X\data\DB\Structure;
use \X\C;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Field;
use \X\Debug\Logger;
use \X\Tools\FileSystem;
use \X\AbstractClasses\PrivateInstantiation;
use X\Tools\Strings;


class CRUDGenerator extends PrivateInstantiation{

  private static function t($c){
    return str_repeat("\t", $c);
  }

  private static function gGetByKey(Database &$db, Table &$table, Key &$key){
    $keyName = $key->getType()==Key::KEY_TYPE_PRIMARY ? "PK" : $key->getCamelName();
    $fields = $key->getFields();
    $cacheKey = "\"__getBy".$keyName."Key\".".Strings::smartImplode($fields, ".", function(Field &$value){$value = "var_export(\$".$value->getAlias().", true)";});
    $getByKey = "/**"."\n".
                " * @return null|".ucwords($table->getName())."\n".
                " */"."\n".
                "public static function getBy".$keyName."Key(".Strings::smartImplode($fields, ", ", function(Field &$value){$value = "\$".$value->getAlias();})."){"."\n".
                "\t\$cacheKey = ".$cacheKey.";"."\n".
                "\tif (!Cache::getInstance()->enabled() || !(\$answer = Cache::getInstance()->groupGetItem('DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey))){"."\n".
                "\t\t\$answer=DB::connectionByAlias('".$db->getAlias()."')->getSimple(["."\n".
                "\t\t\t'conditions'=>[\"".Strings::smartImplode($fields, " AND ", function(Field &$value){$value = $value->getName()." = ?:".$value->getName().":";})."\", [".Strings::smartImplode($fields, " , ", function(Field &$value){$value = "'".$value->getName()."' => \$".$value->getAlias();})."]],"."\n".
                "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',"."\n".
                "\t\t\t'className'=>get_called_class(),"."\n".
                "\t\t\t'limit'=>1,"."\n".
                "\t\t]);"."\n".
                "\t\tif (!\$answer->EOF()){"."\n".
                "\t\t\t\$answer=\$answer->First();"."\n".
                "\t\t\tif (Cache::getInstance()->enabled()){"."\n".
                "\t\t\t\tCache::getInstance()->groupSetItem('DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey, \$answer);"."\n".
                "\t\t\t}"."\n".
                "\t\t}else{"."\n".
                "\t\t\t\$answer=null;"."\n".
                "\t\t}"."\n".
                "\t}"."\n".
                "\treturn \$answer;"."\n".
                "}"."\n";
    return $getByKey;
  }

  private static function gGetter(Field $field){
    $getter  =  "/**"."\n".
                " * Getter for field '".$field->getName()."'"."\n".
                " *"."\n".
                " * @return ".$field->getPHPType()."\n".
                " */"."\n".
                "public function get".$field->getCamelName()."(){"."\n".
                "\treturn \$this->".$field->getAlias().";"."\n".
                "}"."\n";
    return $getter;
  }

  private static function gCreateFromRaw(Table $table, $primaryFields){
    $creator  = "public static function createFromRaw(\$raw){"."\n".
                "\t\$className = get_called_class();"."\n".
                "\t\$classObj = new \$className();"."\n";
    $creator .= "\t\$classObj->hook_createFromRaw_before(\$raw);"."\n";
    foreach($table->getFields() as $field){
      $creator.="\tif (array_key_exists('".$field->getName()."', \$raw)){"."\n";
      $creator.="\t\t\$classObj->set".$field->getCamelName()."(\$raw['".$field->getName()."']);"."\n";
      $creator.="\t}"."\n";
    }
    $creator .= "\t\$classObj->hook_createFromRaw_after();"."\n";
    $creator .= "\t\$classObj->pretendReal();"."\n";
    $creator .= "\t\$classObj->cache();"."\n".
                "\treturn \$classObj;"."\n".
                "}"."\n";
    return $creator;
  }

  private static function gSetter(Field $field, $namespaceName, $className){
    $setter   = "/**"."\n".
                " * Setter for field '".$field->getName()."'"."\n".
                " *"."\n".
                " * @param ".$field->getPHPType()." \$val"."\n".
                " *"."\n".
                " * @return \\".$namespaceName."\\".$className."\n".
                " */"."\n".
                "public function set".$field->getCamelName()."(\$val){"."\n";
    switch($field->getType()){
      case 'boolean':
        $setter.= "\t\$this->".$field->getAlias()." = !!\$val;"."\n";
        break;
      case 'bit':
      case 'tinyint':
      case 'int':
      case 'smallint':
      case 'bigint':
      case 'serial':
        $setter.= "\t\$this->".$field->getAlias()." = intval(\$val);"."\n";
        break;
      case 'float':
      case 'double':
      case 'decimal':
      case 'real':
        $setter.= "\t\$this->".$field->getAlias()." = doubleval(\$val);"."\n";
        break;
      case 'varchar':
      case 'char':
      case 'text':
      case 'tinytext':
      case 'mediumtext':
      case 'longtext':
      case 'tinyblob':
      case 'mediumblob':
      case 'blob':
      case 'longblob':
        $setter.= "\t\$this->".$field->getAlias()." = (string)\$val;"."\n";
        break;
      case 'enum':
        $setter.= "\tif (".($field->getNull() ? "\$val!==null && ":"")."!in_array(\$val, ['".implode("','", $field->getRange())."'])){"."\n".
                  "\t\tthrow new \\Exception(\"Value for '".$field->getName()."' should be one of ['".implode("','", $field->getRange())."']\");"."\n".
                  "\t}"."\n".
                  "\t\$this->".$field->getAlias()." = \$val;"."\n";
        break;
      case 'set':
        $setter.= "\tforeach(explode(',', \$val) as \$token){"."\n".
                  "\t\tif (!in_array(\$token, ['".implode("','", $field->getRange())."']){"."\n".
                  "\t\t\tthrow new \\Exception(\"Value for '".$field->getName()."' should be one of ['".implode("','", $field->getRange())."']\");"."\n".
                  "\t\t}"."\n".
                  "\t}"."\n".
                  "\t\$this->".$field->getAlias()." = \$val;"."\n";
        break;
      case 'date':
        $setter.= "\t\$this->".$field->getAlias()." = is_int(\$val) ? date('Y-m-d', \$val) : \$val;";
        break;
      case 'time':
        $setter.= "\t\$this->".$field->getAlias()." = is_int(\$val) ? date('H:i:s', \$val) : \$val;";
        break;
      case 'year':
        $setter.= "\t\$this->".$field->getAlias()." = (\$val>2155 || \$val<1901) ? date('Y', \$val) : \$val;";
        break;
      case 'datetime':
      case 'timestamp':
        $setter.= "\t\$this->".$field->getAlias()." = is_int(\$val) ? date('Y-m-d H:i:s', \$val) : \$val;";
        break;

    }
    $setter.= "\treturn \$this;"."\n".
              "}"."\n".
              "\n";
    return $setter;
  }

  private static function gPretendReal($primaryFields){
    $pretendR = "public function pretendReal(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\t".Strings::smartImplode($primaryFields, "\n\t", function(Field &$value){$value = "\$this->PRIMARY_".$value->getAlias()."=\$this->".$value->getAlias().";";})."\n":"").
                "}"."\n";
    return $pretendR;
  }

  private static function gInvalidate($primaryFields){
    $pretendR = "private function invalidate(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\t".Strings::smartImplode($primaryFields, "\n\t", function(Field &$value){$value = "\$this->PRIMARY_".$value->getAlias()."=null;";})."\n":"").
                "}"."\n";
    return $pretendR;
  }

  private static function gCacheKey($primaryFields){
    $cachekey = "public function cacheKey(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\treturn ".Strings::smartImplode($primaryFields, ".'&&'.", function(Field &$value){$value = "\$this->PRIMARY_".$value->getAlias()."";}).";"."\n" :
                "\treturn false;"."\n").
                "}"."\n";
    return $cachekey;
  }

  private static function gIsValid($primaryFields){
    $isvalid = "public function isValid(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\treturn ".Strings::smartImplode($primaryFields, " && ", function(Field &$value){$value = "\$this->PRIMARY_".$value->getAlias()."!==null";}).";"."\n" :
                "\treturn false;"."\n").
                "}"."\n";
    return $isvalid;
  }

  private static function gAsArray($fields){
    $asArray= "public function asArray(\$fieldsNeeded=[]){"."\n".
              "\t\$answer=[];"."\n".
              "\tif(is_array(\$fieldsNeeded) && count(\$fieldsNeeded)){"."\n".
              "\t\tforeach(\$fieldsNeeded as \$field){"."\n".
              "\t\t\t\$field = strtolower(\$field);"."\n".
              "\t\t\tif(array_key_exists(\$field, self::\$Fields)){"."\n".
              "\t\t\t\t\$answer[\$field]=call_user_func([\$this, self::\$Fields[\$field]['getter']]);"."\n".
              "\t\t\t}"."\n".
              "\t\t}"."\n".
              "\t}else{"."\n".
              "\t\tforeach(self::\$Fields as \$fieldName=>\$fieldData){"."\n".
              "\t\t\t\$answer[\$fieldName]=call_user_func([\$this, \$fieldData['getter']]);"."\n".
              "\t\t}"."\n".
              "\t}"."\n".
              "\t\$this->hook_asArray_after(\$answer);"."\n".
              "\treturn \$answer;"."\n".
              "}";
    return $asArray;
  }

  private static function gAutoincrement($fields){
    $autoincrement = "public function autoincrement(\$id){"."\n";
    foreach($fields as $field){
      if ($field->getAutoIncrement()){
        $autoincrement .= "\t\$this->".$field->getAlias()." = intval(\$id);"."\n";
      }
    }
    $autoincrement .= "}"."\n";
    return $autoincrement;
  }

  public static function generateClass(Database $db, Table $table){

    $className = ucfirst($table->getName());
    $namespaceName = str_replace('/',"\\", C::getDbNamespace().ucfirst($db->getAlias()));

    $properties = [];
    $selectors=[];

    $fields=[];
    $fields[]="protected static \$Fields = [";

    $pFields=[];
    $pFields[]="protected static \$PrimaryFields = [";


    foreach($table->getFields() as $field){

      if ($field->getDefault()===null && $field->getNull()){
        $default='null';
      }else{
        switch( $field->getPHPType()){
          case 'bool':
            $default= !!$field->getDefault() ? 'true' : 'false';
            break;
          case 'int':
            $default = intval($field->getDefault());
            break;
          case 'double':
            $default = doubleval($field->getDefault());
            break;
          case 'string':
            $default = '"'.str_replace('"', '\"', strval($field->getDefault())).'"';
            break;
          default:
            $default = 0;
        }
      }
      $properties[]="protected \$".$field->getAlias()."=".$default.";";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()."=?:val:',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_startsWith(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." LIKE \"%?:val:\"',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_endsWith(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." LIKE \"?:val:%\"',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_contains(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." LIKE \"%?:val:%\"',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_greater(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." > \"%?:val:%\"',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_less(\$val, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." < \"%?:val:%\"',['val'=>\$val]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_between(\$val1, \$val1, \$limit=0, \$asArray=false, \$fields=[]){";
      $selectors[] = "\treturn DB::connectionByAlias('".$db->getAlias()."')->getSimple([";
      $selectors[] = "\t\t\t'table'=>'".$table->getName()."',";
      $selectors[] = "\t\t\t'instantiator'=>get_called_class().'::createFromRaw',";
      $selectors[] = "\t\t\t'className'=>get_called_class(),";
      $selectors[] = "\t\t\t'conditions'=>['".$field->getName()." BETWEEN \"%?:val1:%\" AND \"%?:val2:%\"',['val1'=>\$val1, 'val2'=>\$val2]],";
      $selectors[] = "\t\t\t'limit'=>\$limit,";
      $selectors[] = "\t\t\t'asArray'=>\$asArray,";
      $selectors[] = "\t\t\t'fields'=>\$fields,";
      $selectors[] = "\t\t]);";
      $selectors[] = "}";

      $fields[]= "\t'" . $field->getName() . "'=>[";
      $fields[]= "\t\t'camelName'=>'".$field->getCamelName()."',";
      $fields[]= "\t\t'getter'=>'get".$field->getCamelName()."',";
      $fields[]= "\t\t'type'=>'".$field->getType()."',";
      $fields[]= "\t\t'unsigned'=>'".$field->getType()."',";
      $fields[]= "\t\t'default'=>".(($field->getNull() && $field->getDefault()===null) ? 'null' : "'".$field->getDefault()."'").",";
      $fields[]= "\t\t'autoincrement'=>".($field->getAutoIncrement() ? "true":"false").",";
      $fields[]= "\t\t'null'=>".($field->getNull() ? "true":"false").",";
      if ($field->getType()=='enum' || $field->getType()=='set')
        $fields[]= "\t\t'enum'=>['".implode($field->getRange(), "','")."'],\n";
      //$fields[]= "\t\t'".$prop."'=>".(is_bool($value) ? ($value ? "true" : "false") : ($value===null ? "null" : "'".$value."'")).",\n";
      $fields[]= "\t],";
    }
    $fields[]= "];";

    $primaryFields = [];
    try{
      foreach($table->keyByName("PRIMARY", true)->getFields() as $field){
        $primaryFields[]=$field;
      }
    }catch(\Exception $e){}

    if (count($primaryFields)){
      foreach($primaryFields as $field){
        $properties[]="protected \$PRIMARY_".$field->getAlias()."=null;";
        $pFields[]= "\t'".$field->getName()."'=>'".$field->getAlias()."',";
      }
    }else{
      //put
    }

    $pFields[]= "];";

    $glue = function (&$value){$value = implode("\n", $value);};

    $glue($fields);
    $glue($pFields);
    $glue($properties);
    $glue($selectors);

    //selectors
    // by any field
    // composer
    // joiner


    $setters = "";
    foreach ($table->getFields() as $field){
      $setters.= self::gSetter($field, $namespaceName, $className);
    }
    $getters = "";
    foreach ($table->getFields() as $field){
      $getters.= self::gGetter($field);
    }
    foreach ($table->getKeys() as $key){
      $getters.="\n".self::gGetByKey($db, $table, $key);
    }

    $result = file_get_contents(dirname(__FILE__).'/CRUDtemplate');
    $result = str_replace("{%NAMESPACE%}",      $namespaceName,       $result);
    $result = str_replace("{%DATABASE_ALIAS%}", $db->getAlias(),      $result);
    $result = str_replace("{%TABLENAME%}",      $table->getName(),    $result);
    $result = str_replace("{%CLASSNAME%}",      $className,           $result);
    $result = str_replace("{%FIELDS%}",         $fields,              $result);
    $result = str_replace("{%PRIMARYFIELDS%}",  $pFields,             $result);
    $result = str_replace("{%AUTOINCREMENT%}",  self::gAutoincrement($table->getFields()), $result);
    $result = str_replace("{%PRETENDREAL%}",    self::gPretendReal($primaryFields), $result);
    $result = str_replace("{%PROPERTIES%}",     $properties,          $result);
    $result = str_replace("{%GETTERS%}",        $getters,             $result);
    $result = str_replace("{%SETTERS%}",        $setters,             $result);
    $result = str_replace("{%VERSION%}",        date('Y.m.d.H.i.s'),  $result);
    $result = str_replace("{%SELECTORS%}",      $selectors,           $result);
    $result = str_replace("{%CACHEKEY%}",       self::gCacheKey($primaryFields), $result);
    $result = str_replace("{%ISVALID%}",        self::gIsValid($primaryFields), $result);
    $result = str_replace("{%INVALIDATE%}",     self::gInvalidate($primaryFields), $result);
    $result = str_replace("{%ASARRAY%}",        self::gAsArray($table->getFields()), $result);
    $result = str_replace("{%CREATEFROMRAW%}",  self::gCreateFromRaw($table, $primaryFields),    $result);

    return $result;
  }

}
