<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 29.06.14
 * Time: 2:01
 */

namespace X\Data\DB;


use app\model\Main\Users;
use X\Debug\Logger;
use X\Validators\Values;
use X_CMF\Admin\Icons;
use X_CMF\Client\Request;

abstract class Entity {

  const FIELD_TYPE_AUTO='auto';
  const FIELD_TYPE_TEXT='text';
  const FIELD_TYPE_NUMBER='number';
  const FIELD_TYPE_ENUM='enum';
  const FIELD_TYPE_IMAGE='image';
  const FIELD_TYPE_EXTERNAL='external';
  const FIELD_TYPE_PROPERTY='property';
  const FIELD_TYPE_ENTITY_LIST='entity_list';
  const FIELD_TYPE_SORT_POSITION = 'sortpos';

  const ERROR_INCORRECT='incorrect';
  const ERROR_TOOLONG = 'too_long';
  const ERROR_TOOSHORT = 'too_short';
  const ERROR_TOOMUCH = 'too_much';
  const ERROR_TOOFEW = 'too_few';
  const ERROR_REQUIRED = 'required';

  protected static $icon = Icons::ICON_list_alt;

  protected static $fields=[];
  protected static $groups=[];
  protected static $joins=[];
  protected static $pk=[];

  protected static $CRUD='';

  /**
   * @var CRUD[]
   */
  protected $object=null;
  protected $isNew=true;

  protected $saveErrors=[];

  public function __construct($object=null){
    $crud = static::$CRUD;
    if ($object!==null && $object instanceof $crud){
      $this->object=$object;
      $this->isNew=false;
    }else{
      $this->object = $crud::create();
    }
    $this->hook_constructor_after();
  }

  public static function getLocalizationPath(){
    $className = get_called_class();
    return 'entities.'.array_reverse(explode("\\", $className))[0];
  }

  public static function getIcon(){
    return static::$icon;
  }

  public static function setIcon($icon){
    static::$icon = $icon;
  }

  public function isNew(){
    return $this->isNew;
  }

  public static function getCRUD(){
    return static::$CRUD;
  }

  public static function getJoins(){
    return static::$joins;
  }

  public static function getEnum($fieldName){
    $fieldData = static::getFieldInfo($fieldName);
    if ($fieldData['type']==Entity::FIELD_TYPE_ENUM){
      if (array_key_exists('values', $fieldData)){
        return $fieldData['values'];
      }elseif(array_key_exists('origin', $fieldData)){
        if (strpos($fieldData['origin'], '.')){
          list($table, $field)=explode(".", $fieldData['origin']);
          $answer=[];
          $myCRUD = static::$CRUD;
          $targetCRUD = CRUD::classByTable($table, $myCRUD::connection());
          $PKs = array_keys($targetCRUD::getPrimaryFields());
          foreach(
              DB::get(
                $table.
                (array_key_exists('proxy', $fieldData) ? ', '.$fieldData['proxy'] :'').
                (array_key_exists('condition', $fieldData) ? ', '.$fieldData['condition'] :'').
                ',('.$fieldData['origin'].')'
              )->resetScope() as $f){
            $value=[];
            foreach($PKs as $pk){
              $value[]=$f->Raw()[$table.'.'.$pk];
            }
            $answer[implode(",", $value)]=$f->Raw()[array_key_exists('label', $fieldData) ? $fieldData['label'] : $fieldData['origin']];
          }
          return $answer;
        }
      }
    }
    return null;
  }

  public function getList($fieldName){
    $fieldData = static::getFieldInfo($fieldName);
    switch($fieldData['type']){
      case Entity::FIELD_TYPE_ENTITY_LIST:
        if ($this->object===null){
          return [];
        }
        $entityName = implode("\\", array_slice(explode("\\", get_called_class()),0, -1))."\\".ucfirst(strtolower($fieldData['origin']));
        $crudName = $entityName::getCrud();
        $table = $crudName::TABLE_NAME;

        $keyName=null;
        if (array_key_exists($fieldData['origin'], static::getJoins())){
          $keyName = static::getJoins()[$fieldData['origin']];
        }

        if (!is_array($keyName)){
          $crud = static::$CRUD;
          $table = $crud::TABLE_NAME;
          if (array_key_exists($table, $crudName::$refTables)){
            if ($keyName && array_key_exists($keyName, $crudName::$refTables[$table])){
              $conditions = $crudName::$refTables[$table][$keyName];
            }else{
              reset($crudName::$refTables[$table]);
              $conditions = current($crudName::$refTables[$table]);
            }
          }
        }else{
          $conditions=$keyName;
        }

        $FKfields=[];
        foreach($conditions as $to=>$from){
          $to = str_replace("`", "", $to);
          $from = str_replace("`", "", $from);
          list(,$fieldTo) = explode(".", $to);
          list(,$fieldFrom) = explode(".", $from);
          $FKfields[$fieldTo]=$this->object->fieldValue($fieldFrom);
        }

        $object = $this->object;
        $table = $fieldData['origin'];
        $query=[];
        $query[]=$object::TABLE_NAME;
        $query[]=$table;
        if ($conditions = static::getJoinConditions($table)){
          $query[]=$conditions;
        }
        $tableCrud = CRUD::classByTable($table, $object::connection());
        $fields = $tableCrud::getPrimaryFields() ?: $tableCrud::getFields();
        foreach($fields as $field){
          $where[]=$field['fullName']." is not NULL";
        }
        if ($where){
          $query[]=implode(" and ", $where);
        }
        $answer=["fields"=>$FKfields, "entity_name"=>$fieldData['origin'], "entity"=>new $entityName(), "objects"=>[]];
        $expr = DB::get(implode(",", $query))->scope($table);
        //echo $expr->expr();
        foreach($expr as $obj){
          $answer["objects"][]=new $entityName($obj);
        }
        return $answer;
        break;
    }
  }

  public static function getJoinConditions($tableName){
    return false; //TODO
  }

  public static function getFields(){
    return static::$fields;
  }

  public static function getGroups(){
    return static::$groups;
  }

  public static function getFieldInfo($fieldName){
    return static::$fields[$fieldName];
  }

  public static function getPK(){
    return static::$pk;
  }

  public static function create(){
    static::hook_constructor_before();
    $classname = get_called_class();
    return new $classname;
  }

  public static function getByPKKey(){
    $crudName = static::$CRUD;
    $args = [];
    if (func_num_args()==1 && is_array(func_get_arg(0))){
      $args = func_get_arg(0);
    }else{
      $args = func_get_args();
    }
    $object = call_user_func_array([$crudName, 'getByPKKey'], $args);
    if ($object){
      $class = get_called_class();
      $entity = new $class($object);
    }else{
      $entity = null;
    }
    return $entity;
  }

  public function setField($name, $val){
    if (array_key_exists($name, static::$fields)){
      if ((array_key_exists('edit', static::$fields[$name]) && static::$fields[$name]['edit']) || array_key_exists('fk', static::$fields[$name])){

        if (static::$fields[$name]['type']==self::FIELD_TYPE_TEXT){
          $val = trim($val);
        }

        $isOK=true;
        if (array_key_exists('validator', static::$fields[$name])){
          if (!is_array(static::$fields[$name]['validator'])){
            static::$fields[$name]['validator']=[static::$fields[$name]['validator']];
          }
          foreach(static::$fields[$name]['validator'] as $validator){
            if (Values::isCallback($validator)){
              $isOK = $isOK && call_user_func($validator, $val);
            }elseif(is_string($validator) && $validator[0]=='/'){
              $isOK = $isOK && preg_match($validator, $val);
            }
          }
          if (!$isOK){
            $this->saveErrors[$name][]=self::ERROR_INCORRECT;
          }
        }

        if (array_key_exists('min', static::$fields[$name])){
          if (static::$fields[$name]['type']==self::FIELD_TYPE_TEXT){
            if (strlen($val)<static::$fields[$name]['min']){
              $this->saveErrors[$name][]=self::ERROR_TOOSHORT;
              $isOK=false;
            }
          }else{
            if ($val<static::$fields[$name]['min']){
              $this->saveErrors[$name][]=self::ERROR_TOOFEW;
              $isOK=false;
            }
          }
        }

        if (array_key_exists('max', static::$fields[$name])){
          if (static::$fields[$name]['type']==self::FIELD_TYPE_TEXT){
            if (strlen($val)>static::$fields[$name]['max']){
              $this->saveErrors[$name][]=self::ERROR_TOOLONG;
              $isOK=false;
            }
          }else{
            if ($val<static::$fields[$name]['max']){
              $this->saveErrors[$name][]=self::ERROR_TOOMUCH;
              $isOK=false;
            }
          }
        }

        if (array_key_exists('required', static::$fields[$name]) && static::$fields[$name]['required']){
          if (!array_key_exists('keep_if_no_changes', static::$fields[$name]) || $this->isNew()){
            if (!$val){
              $isOK=false;
              $this->saveErrors[$name][]=self::ERROR_REQUIRED;
            }
          }
        }
        if ($isOK){

          if (array_key_exists('sanitizer', static::$fields[$name])){
            if (!is_array(static::$fields[$name]['sanitizer'])){
              static::$fields[$name]['sanitizer'] = [static::$fields[$name]['sanitizer']];
            }
            foreach(static::$fields[$name]['sanitizer'] as $sanitizer){
              $val = call_user_func($sanitizer, $val);
            }
          }
          $this->object->setFieldValue($name, $val);
        }
      }
    }
    return $this;
  }

  public function getField($name){
    if (array_key_exists($name, static::$fields)){
      return $this->object->fieldValue($name);
    }
    return null;
  }

  public function save(){
    if ($this->hook_save_before()){
      $this->object->save();
      $this->isNew=false;
    }
    $this->hook_save_after();
  }

  public function getSaveErrors(){
    return $this->saveErrors;
  }

  public static function processSave(){
    $pk = [];
    if (Request::post("ent_new")){
      $entity = static::create();
    }else{
      foreach(static::getPK() as $key){
        if ($val = Request::post($key)){
          $pk[]=$val;
        }else{
          return null;
        }
      }

      $entity = static::getBYPKKey($pk);
      if (!$entity){
        return null;
      }
    }

    foreach(static::$fields as $field=>$data){
      if (!array_key_exists('proxy', $data)){
        if (($val = Request::post($field))!==null){
          $entity->setField($field, $val);
        }
      }
    }

    if (!$entity->getSaveErrors()){
      $entity->save();
      $proxy=false;
      foreach(static::$fields as $field=>$data){
        if (array_key_exists('proxy', $data)){
          if (($val = Request::post($field))!==null){
            $entity->setField($field, $val);
            $proxy=true;
          }
        }
      }
      if ($proxy && count(static::getPK())){
        $entity->save();
      }
    }
    return $entity;
  }


  public static function hook_constructor_before(){}
  public function hook_constructor_after(){}

  public function hook_getByPKKey_before(){}
  public function hook_getByPKKey_after(){}

  public function hook_save_before(){return true;}
  public function hook_save_after(){}

  public function registerSaveError($field, $message){
    $this->saveErrors[$field][]=$message;
  }

} 