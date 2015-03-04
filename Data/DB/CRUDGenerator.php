<?php
//TODO: fix getByKey as there should be not only one!

namespace X\data\DB;
use \X\C;
use \X\Data\DB\Interfaces\IDB;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Field;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\Structure\Key;
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
                "public static function getBy".$keyName."Key(".Strings::smartImplode($fields, ", ", function(Field &$value){$value = "\$".$value->getAlias();}).", \$ttl=null){"."\n".
                "\tself::mutate();"."\n".
                "\t\$cacheKey = ".$cacheKey.";"."\n".
                "\tif (!Cache::enabled() || !C::getDbCacheTtl() || !(\$answer = Cache::getInstance()->groupGetItem('DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey))){"."\n".
                "\t\tLogger::add('DB_".$db->getAlias()."_".$table->getName().": no key '.\$cacheKey.' in cache. Loading from DB');"."\n".
      (!$key->isUnique() ?
                "\t\t\$answer=[];"."\n".
                "\t\t\$collection=new Collection(static::connection(),'".$table->getName().", ".Strings::smartImplode($fields, " AND ", function(Field &$value){$value = $value->getName()."=::".$value->getName();})."', [".Strings::smartImplode($fields, " , ", function(Field &$value){$value = "'".$value->getName()."' => \$".$value->getAlias();})."]);"."\n".
                "\t\tforeach(\$collection as \$answerData){"."\n".
                "\t\t\t\$answer[]=\$answerData;"."\n".
                "\t\t\tCache::getInstance()->addModifyTrigger(['DB_".$db->getAlias()."_".$table->getName()."', \$answerData->cacheKey()],['DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey]);"."\n".
                "\t\t}"."\n".
                "\t\tCache::getInstance()->addModifyTrigger('DB_".$db->getAlias()."_".$table->getName()."::CHANGE',['DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey]);"."\n".
                "\t\tunset(\$collection);"."\n".
                "\t\tif (Cache::enabled() && C::getDbCacheTtl()){"."\n".
                "\t\t\tCache::getInstance()->groupSetItem('DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey, \$answer, C::getDbCacheTtl());"."\n".
                "\t\t}"."\n"
        :
                "\t\t\$collection=new Collection(static::connection(),'".$table->getName().", ".Strings::smartImplode($fields, " AND ", function(Field &$value){$value = $value->getName()."=::".$value->getName();}).", #1', [".Strings::smartImplode($fields, " , ", function(Field &$value){$value = "'".$value->getName()."' => \$".$value->getAlias();})."]);"."\n".
                "\t\tif (!\$collection->EOF()){"."\n".
                "\t\t\t\$answer=\$collection->First();"."\n".
                "\t\t\tunset(\$collection);"."\n".
                "\t\t\tif (Cache::enabled() && C::getDbCacheTtl()){"."\n".
                "\t\t\t\tCache::getInstance()->groupSetItem('DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey, \$answer, C::getDbCacheTtl());"."\n".
                "\t\t\t\tCache::getInstance()->addModifyTrigger(['DB_".$db->getAlias()."_".$table->getName()."', \$answer->cacheKey()], ['DB_".$db->getAlias()."_".$table->getName()."', \$cacheKey]);"."\n".
                "\t\t\t}"."\n".
                "\t\t}else{"."\n".
                "\t\t\t\$answer=null;"."\n".
                "\t\t}"."\n"
      ).
                "\t}else{"."\n".
      ($key->isUnique() ?
                "\t\t\$answer->setfromCache();"."\n"
                :
                "\t\tforeach(\$answer as &\$item){"."\n".
                "\t\t\t\$item->setfromCache();"."\n".
                "\t\t}"."\n"
      ).
                "\t\tLogger::add('DB_".$db->getAlias()."_".$table->getName().": key '.\$cacheKey.' found in cache. Loading from cache');"."\n".
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
    if ($field->getType()=='timestamp' || $field->getType()=='datetime'  || $field->getType()=='date'  || $field->getType()=='time'){
      $getter  .= "\n/**"."\n".
        " * Getter for field '".$field->getName()."'"."\n".
        " *"."\n".
        " * @return int\n".
        " */"."\n".
        "public function get".$field->getCamelName()."_unixtime(){"."\n".
        ($field->getType()=='timestamp' || $field->getType()=='datetime' ?
        "\tif (\$this->".$field->getAlias()."===null){\n".
        "\t\treturn 0;\n".
        "\t}\n".
        "\tlist(\$date, \$time) = explode(' ', \$this->".$field->getAlias().");\n".
        "\tlist(\$year, \$month, \$day) = explode('-', \$date);\n".
        "\tlist(\$hour, \$minute, \$second) = explode(':', \$time);\n".
        "\treturn (\$year=='0000' ? 0 : mktime(intval(\$hour), intval(\$minute), intval(\$second), intval(\$month), intval(\$day), intval(\$year)));\n" :
        "\treturn strtotime(\$this->".$field->getAlias().");"."\n").
        "}"."\n";
    }elseif($field->getType()=='year'){
      $getter  .= "\n/**"."\n".
        " * Getter for field '".$field->getName()."'"."\n".
        " *"."\n".
        " * @return int\n".
        " */"."\n".
        "public function get".$field->getCamelName()."_unixtime(){"."\n".
        "\treturn mktime(0,0,1,1,1,\$this->".$field->getAlias().");"."\n".
        "}"."\n";
    }
    return $getter;
  }

  private static function gCreateFromRaw(Table $table, $primaryFields){
    $creator  = "public static function &createFromRaw(\$raw, \$prefix=''){"."\n".
                "\t\$className = get_called_class();"."\n".
                "\t\$classObj = \$className::create();"."\n".
                "\t\$classObj->hook_createFromRaw_before(\$raw, \$prefix);"."\n";
    foreach($table->getFields() as $field){
      $creator.="\tif (array_key_exists(\$prefix.'".$field->getName()."', \$raw)){"."\n";
      $creator.="\t\t\$classObj->set".$field->getCamelName()."(\$raw[\$prefix.'".$field->getName()."']);"."\n";
      $creator.="\t}"."\n";
    }
    $creator .= "\t\$classObj->hook_createFromRaw_after();"."\n";
    $creator .= "\t\$classObj->pretendReal();"."\n";
    $creator .= "\t\$classObj->cache(true);"."\n".
                "\treturn \$classObj;"."\n".
                "}"."\n";
    return $creator;
  }

  private static function gSetter(Field $field, $namespaceName, $className){
    $setter   = "/**"."\n".
                " * Setter for field '".$field->getName()."'"."\n".
                " *"."\n".
                " * @param ".$field->getPHPType().($field->getType()=='set' ? "|array" : "")." \$val"."\n".
                " *"."\n".
                " * @return \\".$namespaceName."\\".$className."\n".
                " */"."\n".
                "public function set".$field->getCamelName()."(\$val){"."\n";
    switch($field->getType()){
      case 'boolean':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."!!\$val;"."\n";
        break;
      case 'bit':
      case 'tinyint':
      case 'int':
      case 'smallint':
      case 'bigint':
      case 'serial':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(!is_numeric(\$val)) ? null : ":"")."intval(\$val);"."\n";
        break;
      case 'float':
      case 'double':
      case 'decimal':
      case 'real':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."Strings::doubleval(\$val);"."\n";
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
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."(string)\$val;"."\n";
        break;
      case 'enum':
        $setter.= "\tif (".($field->getNull() ? "\$val!==null && ":"")."!in_array(\$val, ['".implode("','", $field->getRange())."'])){"."\n".
                  "\t\tthrow new \\Exception(\"Value for '".$field->getName()."' should be one of ['".implode("','", $field->getRange())."']\");"."\n".
                  "\t}"."\n".
                  "\t\$this->".$field->getAlias()." = \$val;"."\n";
        break;
      case 'set':
        $setter.= ($field->getNull() ? "\tif (\$val===null || \$val===''){"."\n".
                  "\t\t\$this->".$field->getAlias()." = \$val;"."\n".
                  "\t\treturn \$this;"."\n".
                  "\t}"."\n" : "").
                  "\tif (is_array(\$val)){"."\n".
                  "\t\t\$val=implode(',', \$val);"."\n".
                  "\t}"."\n".
                  "\tforeach(explode(',', \$val) as \$token){"."\n".
                  "\t\tif (!in_array(\$token, ['".implode("','", $field->getRange())."'])){"."\n".
                  "\t\t\tthrow new \\Exception(\"Value for '".$field->getName()."' should be one of ['".implode("','", $field->getRange())."']\");"."\n".
                  "\t\t}"."\n".
                  "\t}"."\n".
                  "\t\$this->".$field->getAlias()." = \$val;"."\n";
        break;
      case 'date':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."is_int(\$val) ? date('Y-m-d', \$val) : \$val;\n";
        break;
      case 'time':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."is_int(\$val) ? date('H:i:s', \$val) : \$val;\n";
        break;
      case 'year':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."(\$val>2155 || \$val<1901) ? date('Y', \$val) : \$val;\n";
        break;
      case 'datetime':
      case 'timestamp':
        $setter.= "\t\$this->".$field->getAlias()." = ".($field->getNull() ? "(\$val===null) ? null : ":"")."is_int(\$val) ? date('Y-m-d H:i:s', \$val) : \$val;\n";
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
                "\tif (Cache::getInstance()->enabled()){"."\n".
                "\t\tCache::getInstance()->fireModifyTrigger(self::tableChangeTriggerKey());"."\n".
                "\t}"."\n".
                "}"."\n";
    return $pretendR;
  }

  private static function gCacheKey($primaryFields, Database &$db, Table &$table){
    $cachekey = "public function cacheKey(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\treturn ".Strings::smartImplode($primaryFields, ".'&&'.", function(Field &$value){$value = "\$this->PRIMARY_".$value->getAlias()."";}).";"."\n" :
                "\treturn false;"."\n").
                "}"."\n";
    $cachekey .="public function triggerKey(){"."\n".
                (is_array($primaryFields) && count($primaryFields) ?
                "\treturn ['DB_".$db->getAlias()."_".$table->getName()."', \$this->cacheKey()];"."\n" :
                "\treturn false;"."\n").
                "}"."\n";
    $cachekey .="static public function tableChangeTriggerKey(){"."\n".
                "\treturn 'DB_".$db->getAlias()."_".$table->getName()."::CHANGE';"."\n".
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
              "\t\$this->hook_asArray_before();"."\n".
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
    $fieldsCnames=[];
    $fieldsCnames[]="protected static \$FieldsCnames = [";
    $fieldNames=[];

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
            $default = Strings::doubleval($field->getDefault());
            break;
          case 'string':
            $default = '"'.str_replace('"', '\"', strval($field->getDefault())).'"';
            break;
          default:
            $default = 0;
        }
      }
      $timeControl='';
      switch($field->getType()){
        case 'timestamp':
        case 'datetime':
          $timeControl = "\t\$val == is_int(\$val) ? date('Y-m-d H:i:s', \$val) : \$val;";
          break;
        case 'date':
          $timeControl = "\t\$val == is_int(\$val) ? date('Y-m-d', \$val) : \$val;";
          break;
        case 'time':
          $timeControl = "\t\$val == is_int(\$val) ? date('H:i:s', \$val) : \$val;";
          break;
        case 'year':
          $timeControl = "\t\$val == (is_int(\$val) && (\$val>2155 || \$val<1901)) ? date('Y', \$val) : \$val;";
          break;
      }

      $properties[]="protected \$".$field->getAlias()."=".$default.";";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."isNull(\$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." is NULL', null, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()."=::val', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_startsWith(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." LIKE \"::val%\"', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_endsWith(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." LIKE \"%::val\"', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_contains(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." LIKE \"%::val%\"', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_greater(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = $timeControl;
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." > ::val', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_less(\$val, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = $timeControl;
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." < ::val', \$val, \$limit, \$groupBy);";
      $selectors[] = "}";

      $selectors[] = "/**";
      $selectors[] = " * @param \$val";
      $selectors[] = " * @return Collection";
      $selectors[] = " */";
      $selectors[] = "public static function getBy".$field->getCamelName()."_between(\$val1, \$val2, \$limit=0, \$groupBy=null, \$ttl=null){";
      $selectors[] = str_replace("\$val", "\$val1", $timeControl);
      $selectors[] = str_replace("\$val", "\$val2", $timeControl);
      $selectors[] = "\treturn static::getByOneField('".$table->getName().".".$field->getName()." BETWEEN ::val1 AND ::val2', ['val1'=>\$val1, 'val2'=>\$val2], \$limit, \$groupBy);";
      $selectors[] = "}";

      if ($field->getPHPType()=='int'){
        $selectors[] = "/**";
        $selectors[] = " * @return \\".$namespaceName."\\".$className."\n";
        $selectors[] = " */";
        $selectors[] = "public function shiftBy".$field->getCamelName()."Down(){";
        $selectors[] = "\t\$obj=self::get('".$table->getName().".".$field->getName().">?, ".$table->getName().".".$field->getName()." asc, #1');";
        $selectors[] = "\tif (\$obj->count()){";
        $selectors[] = "\t\t\$tmp=\$obj->get".$field->getCamelName()."();";
        $selectors[] = "\t\t\$obj->set".$field->getCamelName()."(\$this->get".$field->getCamelName()."())->save();";
        $selectors[] = "\t\t\$this->set".$field->getCamelName()."(\$tmp)->save();";
        $selectors[] = "\t}";
        $selectors[] = "\treturn \$this;";
        $selectors[] = "}";

        $selectors[] = "/**";
        $selectors[] = " * @return \\".$namespaceName."\\".$className."\n";
        $selectors[] = " */";
        $selectors[] = "public function shiftBy".$field->getCamelName()."Up(){";
        $selectors[] = "\t\$obj=self::get('".$table->getName().".".$field->getName()."<?, ".$table->getName().".".$field->getName()." desc, #1');";
        $selectors[] = "\tif (\$obj->count()){";
        $selectors[] = "\t\t\$tmp=\$obj->get".$field->getCamelName()."();";
        $selectors[] = "\t\t\$obj->set".$field->getCamelName()."(\$this->get".$field->getCamelName()."())->save();";
        $selectors[] = "\t\t\$this->set".$field->getCamelName()."(\$tmp)->save();";
        $selectors[] = "\t}";
        $selectors[] = "\treturn \$this;";
        $selectors[] = "}";
      }

      $fieldNames[]="\tconst f_".$field->getName()." = '`".$table->getName()."`.`".$field->getName()."`';";
      $fields[]= "\t'" . $field->getName() . "'=>[";
      $fields[]= "\t\t'camelName'=>'".$field->getCamelName()."',";
      $fieldsCnames[]= "\t'".strtolower($field->getCamelName())."'=>'" . $field->getName() . "',";
      $fields[]= "\t\t'fullName'=>'`".$table->getName()."`.`".$field->getName()."`',";
      $fields[]= "\t\t'getter'=>'get".$field->getCamelName()."',";
      $fields[]= "\t\t'setter'=>'set".$field->getCamelName()."',";
      $fields[]= "\t\t'type'=>'".$field->getType()."',";
      $fields[]= "\t\t'unsigned'=>".($field->getUnsigned() ? "true":"false").",";
      $fields[]= "\t\t'default'=>".(($field->getNull() && $field->getDefault()===null) ? 'null' : "'".$field->getDefault()."'").",";
      $fields[]= "\t\t'autoincrement'=>".($field->getAutoIncrement() ? "true":"false").",";
      $fields[]= "\t\t'null'=>".($field->getNull() ? "true":"false").",";
      if ($field->getType()=='enum' || $field->getType()=='set')
        $fields[]= "\t\t'enum'=>['".implode($field->getRange(), "','")."'],\n";
      //$fields[]= "\t\t'".$prop."'=>".(is_bool($value) ? ($value ? "true" : "false") : ($value===null ? "null" : "'".$value."'")).",\n";
      $fields[]= "\t],";
    }
    $fields[]= "];";
    $fieldsCnames[]= "];";

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

    $glue($fieldNames);
    $glue($fieldsCnames);
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

    $referenceData=[];

    $refTables = [];
    foreach ($table->getKeys() as $key){
      $getters.="\n".self::gGetByKey($db, $table, $key);
      if ($key->getType()==Key::KEY_TYPE_FOREIGN && $key->getRefFields()){
        $refTables[$db->tableByName($key->getRefTable())->getName()][$key->getName()]=$key->getRefFields();
      }
    }

    $referenceData[]="\tpublic static \$refTables=[";
    foreach($refTables as $tableName=>$d){
      $referenceData[]="\t\t'".$tableName."'=>[";
      foreach($d as $keyName=>$keyData){
        $referenceData[]="\t\t\t'".$keyName."'=>[";
        foreach($keyData as $fieldsPair){
          list($field_from, $field_to) = $fieldsPair;
          $referenceData[]="\t\t\t\t'`".$table->getName()."`.`".$field_from->getName()."`'=>'`".$tableName."`.`".$field_to->getName()."`',";
        }
        $referenceData[]="\t\t\t],";
      }
      $referenceData[]="\t\t],";
    }
    $referenceData[]="\t];";

    $glue($referenceData);

    $result = file_get_contents(dirname(__FILE__).'/Structure/CRUDtemplate');
    $result = str_replace("{%NAMESPACE%}",      $namespaceName,       $result);
    $result = str_replace("{%DATABASE_ALIAS%}", $db->getAlias(),      $result);
    $result = str_replace("{%TABLENAME%}",      $table->getName(),    $result);
    $result = str_replace("{%REFS%}",           $referenceData,       $result);
    $result = str_replace("{%CLASSNAME%}",      $className,           $result);
    $result = str_replace("{%FIELDS%}",         $fields,              $result);
    $result = str_replace("{%FIELDSCNAMES%}",   $fieldsCnames,        $result);
    $result = str_replace("{%FIELDNAMES%}",     $fieldNames,          $result);
    $result = str_replace("{%PRIMARYFIELDS%}",  $pFields,             $result);
    $result = str_replace("{%AUTOINCREMENT%}",  self::gAutoincrement($table->getFields()), $result);
    $result = str_replace("{%PRETENDREAL%}",    self::gPretendReal($primaryFields), $result);
    $result = str_replace("{%PROPERTIES%}",     $properties,          $result);
    $result = str_replace("{%GETTERS%}",        $getters,             $result);
    $result = str_replace("{%SETTERS%}",        $setters,             $result);
    $result = str_replace("{%VERSION%}",        date('Y.m.d.H.i.s'),  $result);
    $result = str_replace("{%SELECTORS%}",      $selectors,           $result);
    $result = str_replace("{%CACHEKEY%}",       self::gCacheKey($primaryFields, $db, $table), $result);
    $result = str_replace("{%ISVALID%}",        self::gIsValid($primaryFields), $result);
    $result = str_replace("{%INVALIDATE%}",     self::gInvalidate($primaryFields), $result);
    $result = str_replace("{%ASARRAY%}",        self::gAsArray($table->getFields()), $result);
    $result = str_replace("{%CREATEFROMRAW%}",  self::gCreateFromRaw($table, $primaryFields),    $result);

    return $result;
  }

}
