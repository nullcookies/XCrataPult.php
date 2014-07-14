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
use X\X;
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
  const FIELD_TYPE_YESNO = 'yes_no';
  const FIELD_TYPE_SORT_POSITION = 'sortpos';

  const ERROR_INCORRECT='incorrect';
  const ERROR_TOOLONG = 'too_long';
  const ERROR_TOOSHORT = 'too_short';
  const ERROR_TOOMUCH = 'too_much';
  const ERROR_TOOFEW = 'too_few';
  const ERROR_REQUIRED = 'required';
  const ERROR_FILE_TYPE_UNSUPPORTED = 'filetype_unsupported';
  const ERROR_FILE_TOO_BIG = 'filesize_too_big';
  const ERROR_FILE_NOT_IMAGE = 'not_image';
  const ERROR_IMAGE_WIDTH_BIG = 'image_width_exeeds';
  const ERROR_IMAGE_HEIGHT_BIG = 'image_height_exeeds';
  const ERROR_IMAGE_WIDTH_SMALL = 'image_width_small';
  const ERROR_IMAGE_HEIGHT_SMALL = 'image_height_small';

  protected static $icon = Icons::ICON_list_alt;

  protected static $fields=[];
  protected static $groups=[];
  protected static $joins=[];
  protected static $pk=[];

  protected static $CRUD='';
  protected static $listTemplate = 'admin/pages/entity/list.html';

  /**
   * @var CRUD[]
   */
  protected $object=null;
  protected $isNew=true;

  public $saveErrors=[];

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

  public static function getListTemplate(){
    return static::$listTemplate;
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
        $subEntityName = implode("\\", array_slice(explode("\\", get_called_class()),0, -1))."\\".ucfirst(strtolower($fieldData['origin']));
        if (!class_exists($subEntityName)){
          throw new \RuntimeException("sub entity ".$fieldData['origin']." doesn't exist");
        }
        $subCrudName = $subEntityName::getCrud();
        $subTable = $subCrudName::TABLE_NAME;

        $keyName=null;
        if (array_key_exists($fieldData['origin'], static::getJoins())){
          $keyName = static::getJoins()[$fieldData['origin']];
        }

        if (!is_array($keyName)){
          $crud = static::$CRUD;
          $table = $crud::TABLE_NAME;
          if (array_key_exists($table, $subCrudName::$refTables)){
            if ($keyName && array_key_exists($keyName, $subCrudName::$refTables[$table])){
              $conditions = $subCrudName::$refTables[$table][$keyName];
            }else{
              reset($subCrudName::$refTables[$table]);
              $conditions = current($subCrudName::$refTables[$table]);
            }
          }else{
            throw new \RuntimeException("there is no FK to join '".$crud."' with '".$subCrudName."'");
          }
        }else{
          $conditions=$keyName;
        }

        $FKfields=[];
        $where=[];
        foreach($conditions as $to=>$from){
          $to = str_replace("`", "", $to);
          $from = str_replace("`", "", $from);
          list(,$fieldTo) = explode(".", $to);
          list(,$fieldFrom) = explode(".", $from);
          $FKfields[$fieldTo]=$this->object->fieldValue($fieldFrom);
          $where[]=$to."=::".$fieldTo;
        }

        $object = $this->object;
        $table = $fieldData['origin'];
        $query=[];
        $query[]=$object::TABLE_NAME;
        $query[]=$table;

        if ($where){
          $query[]=implode(" and ", $where);
        }

        foreach($subEntityName::getSortBy() as $sortBy){
          $query[]=$sortBy;
        }

        $answer=["fields"=>$FKfields, "entity_name"=>$fieldData['origin'], "entity"=>new $subEntityName(), "objects"=>[]];
        $expr = DB::get(implode(",", $query), $FKfields)->scope($table);
        //echo $expr->expr()."<bR><br>";
        foreach($expr as $obj){
          $answer["objects"][]=new $subEntityName($obj);
        }
        return $answer;
        break;
    }
  }

  public static function getSortBy(){
    $sortBy=[];
    $crud = static::$CRUD;
    foreach(static::$fields as $name=>$field){
      if ($field['type']==self::FIELD_TYPE_SORT_POSITION){
        $sortBy[]=$crud::getFields()[$name]['fullName'].(strtolower($field['direction'])=='desc' ? " desc" : " asc");
      }
    }
    return $sortBy;
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

  public function setField($name, $val=null){
    if (array_key_exists($name, static::$fields)){

      $fieldData = static::$fields[$name];
      $fieldType = $fieldData['type'];

      if (in_array($fieldType, [self::FIELD_TYPE_ENTITY_LIST])){
        return;
      }
      
      if ((array_key_exists('edit', $fieldData) && $fieldData['edit']) || array_key_exists('fk', $fieldData)){

        if ($fieldType==self::FIELD_TYPE_YESNO){
          $val = $val ? $fieldData['yes'] : $fieldData['no'];
        }

        if ($fieldType==self::FIELD_TYPE_TEXT){
          $val = trim($val);
        }

        if ($fieldType==self::FIELD_TYPE_NUMBER){
          if ($fieldData['decimal']){
            $val = doubleval($val);
          }else{
            $val = intval($val);
          }
        }

        $isOK=true;

        // independent check for files
        if ($fieldType==self::FIELD_TYPE_IMAGE){
          $files = X::uploadedFiles();
          $isOK=false;
          if ($file = $files[$name]){
            if (!$file['is_image']){
              $this->saveErrors[$name][]=self::ERROR_FILE_NOT_IMAGE;
            }elseif (array_key_exists('width_min', $fieldData) && $file['width']<$fieldData['width_min']){
              $this->saveErrors[$name][]=self::ERROR_IMAGE_WIDTH_SMALL;
            }elseif (array_key_exists('width_max', $fieldData) && $file['width']>$fieldData['width_max']){
              $this->saveErrors[$name][]=self::ERROR_IMAGE_WIDTH_BIG;
            }elseif (array_key_exists('height_min', $fieldData) && $file['height']<$fieldData['height_min']){
              $this->saveErrors[$name][]=self::ERROR_IMAGE_HEIGHT_SMALL;
            }elseif (array_key_exists('height_max', $fieldData) && $file['height']>$fieldData['height_max']){
              $this->saveErrors[$name][]=self::ERROR_IMAGE_HEIGHT_BIG;
            }elseif (array_key_exists('mime_types', $fieldData) && !in_array($file['real_type'], $fieldData['mime_types'])){
              $this->saveErrors[$name][]=self::ERROR_FILE_TYPE_UNSUPPORTED;
            }elseif (array_key_exists('max_size', $fieldData) && $file['size']>$fieldData['max_size']){
              $this->saveErrors[$name][]=self::ERROR_FILE_TOO_BIG;
            }else{
              $isOK=true;
            }
            if ($isOK){
              $val = $files->store($file, $fieldData['upload_path']);
            }
          }
        }else{

          if (array_key_exists('validator', $fieldData) && $isOK){
            if (!is_array($fieldData['validator'])){
              $fieldData['validator']=[$fieldData['validator']];
            }
            foreach($fieldData['validator'] as $validator){
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

          if (array_key_exists('min', $fieldData) && $isOK){
            if ($fieldType==self::FIELD_TYPE_TEXT){
              if (strlen($val)<$fieldData['min']){
                $this->saveErrors[$name][]=self::ERROR_TOOSHORT;
                $isOK=false;
              }
            }else{
              if ($val<$fieldData['min']){
                $this->saveErrors[$name][]=self::ERROR_TOOFEW;
                $isOK=false;
              }
            }
          }

          if (array_key_exists('max', $fieldData) && $isOK){
            if ($fieldType==self::FIELD_TYPE_TEXT){
              if (strlen($val)>$fieldData['max']){
                $this->saveErrors[$name][]=self::ERROR_TOOLONG;
                $isOK=false;
              }
            }else{
              if ($val>$fieldData['max']){
                $this->saveErrors[$name][]=self::ERROR_TOOMUCH;
                $isOK=false;
              }
            }
          }
        }

        if (array_key_exists('required', $fieldData) && $fieldData['required'] && $isOK){
          if (!array_key_exists('keep_if_no_changes', $fieldData) || $this->isNew()){
            if (!$val){
              $isOK=false;
              $this->saveErrors[$name][]=self::ERROR_REQUIRED;
            }
          }
        }
        if ($isOK){
          if (array_key_exists('sanitizer', $fieldData)){
            if (!is_array($fieldData['sanitizer'])){
              $fieldData['sanitizer'] = [$fieldData['sanitizer']];
            }
            foreach($fieldData['sanitizer'] as $sanitizer){
              $val = call_user_func($sanitizer, $val);
            }
          }
          if ($val || !array_key_exists('keep_if_no_changes', $fieldData) || $this->isNew()){
            $this->object->setFieldValue($name, $val);
          }
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
        if ($data['type']==self::FIELD_TYPE_IMAGE && X::uploadedFiles()->exists($field)){
          $entity->setField($field);
        }else{
          $entity->setField($field, Request::post($field));
        }
      }
    }

    if (!$entity->saveErrors){
      $entity->save();
      $proxy=false;
      foreach(static::$fields as $field=>$data){
        if (array_key_exists('proxy', $data)){
          $entity->setField($field, Request::post($field));
          $proxy=true;
        }
      }
      if ($proxy && count(static::getPK())){
        $entity->save();
      }
    }
    return $entity;
  }

  public function delete(){
    if (!$this->isNew()){
      $this->object->delete();
      $this->object=null;
      $this->isNew=true;
    }
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