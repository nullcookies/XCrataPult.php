<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 29.06.14
 * Time: 2:01
 */

namespace X\Data\DB;

use app\website\Common;
use Typograf\Typograf;
use X\Tools\FileSystem;
use X\Tools\Strings;
use X\Traits\TFullClassName;
use X\Validators\Values;
use X\X;
use X_CMF\Admin\AdminPanel;
use X_CMF\Admin\EntityPage;
use X_CMF\Admin\Icons;
use X_CMF\Client\Request;
use X_CMF\Client\Response;
use X_CMF\Render\Page;

abstract class Entity {

  use TFullClassName;

  const FIELD_TYPE_CONST='const';

  const FIELD_TYPE_AUTO='auto';
  const FIELD_TYPE_TEXT='text';
  const FIELD_TYPE_URL='url';
  const FIELD_TYPE_EMAIL='url';
  const FIELD_TYPE_NUMBER='number';
  const FIELD_TYPE_ENUM='enum';
  const FIELD_TYPE_SET='set';
  const FIELD_TYPE_IMAGE='image';
  const FIELD_TYPE_EXTERNAL='external';
  const FIELD_TYPE_YESNO = 'yes_no';
  const FIELD_TYPE_SORT_POSITION = 'sortpos';
  const FIELD_TYPE_DATE = 'date';
  const FIELD_TYPE_TIME = 'time';
  const FIELD_TYPE_CONTENT_BLOCK = 'content_block';

  const FIELD_TYPE_GEO_CITY = 'geo_city';
  const FIELD_TYPE_GEO_COUNTRY = 'geo_country';

  const VIEW_TYPE_PROFILE = 'profile';
  const VIEW_TYPE_WIDGET = 'widget';
  const VIEW_TYPE_RECORD = 'record';
  const VIEW_TYPE_EDITOR = 'editor';

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

  protected static $icon = Icons::ICON_list;

  protected static $fields=[];
  protected static $displayFields=[];
  protected static $groups=[];
  protected static $joins=[];
  protected static $pk=[];

  static $CRUD='';
  static $recordTemplate = "admin/pages/entity/record.haml";
  static $editorTemplate = "admin/pages/entity/editor.haml";
  static $profileTemplate = "admin/pages/entity/profile.html";
  static $widgetTemplate = "admin/pages/entity/widget.haml";

  /**
   * @var CRUD[]
   */
  protected $object=null;
  protected $isNew=true;

  public $saveErrors=[];
  public $saved=false;

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
    $tmp = explode("\\", $className);
    return 'entities.'.end($tmp);
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

  public static function getEditorTemplate(){
    return static::$editorTemplate;
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
      }else{
        $object = static::getCRUD();
        if (array_key_exists($fieldName, $object::getFields())){
          $field = $object::getFields()[$fieldName];
          if ($field['type']=='enum'){
            $answer=[];
            foreach($field['enum'] as $v){
              $answer[$v]=$v;
            }
            return $answer;
          }
        }
      }
    }elseif ($fieldData['type']==Entity::FIELD_TYPE_SET){
      if (array_key_exists('values', $fieldData)){
        return $fieldData['values'];
      }
      $object = static::getCRUD();
      if (array_key_exists($fieldName, $object::getFields())){
        $field = $object::getFields()[$fieldName];
        if ($field['type']=='set'){
          $answer=[];
          foreach($field['enum'] as $v){
            $answer[$v]=$v;
          }
          return $answer;
        }
      }
      //TODO: add m:n proxy
    }
    return null;
  }

  public static function getSortBy(){
    $sortBy=[];
    $crud = static::$CRUD;
    foreach(static::getFields() as $name=>$field){
      if ($field['type']==self::FIELD_TYPE_SORT_POSITION){
        $sortBy[]=$crud::getFields()[$name]['fullName'].(strtolower($field['direction'])=='desc' ? " desc" : " asc");
      }
    }
    return $sortBy;
  }

  public static function getFields(){
    return static::$fields;
  }

  public static function getDisplayFields(){
    return static::$displayFields ?: static::$fields;
  }

  public static function getGroups(){
    return static::$groups;
  }

  public static function getFieldInfo($fieldName){
    return static::getFields()[$fieldName];
  }

  public static function getPK(){
    $crudClass = static::$CRUD;
    return static::$pk ?: array_keys($crudClass::getPrimaryFields());
  }

  public function getPKparams(){
    $crudClass = static::$CRUD;
    $vars = static::$pk ?: array_keys($crudClass::getPrimaryFields());
    $vars = (array)$vars;
    $params=[];
    foreach($vars as $var){
      $params[]=$var.'='.urlencode($this->getField($var));
    }
    return implode("&",$params);
  }

  public static function create(){
    static::hook_constructor_before();
    $classname = get_called_class();
    return new $classname;
  }

  public static function getByPKKey(){
    $crudName = static::$CRUD;
    $args = [];
    if (func_num_args()==0){
      $pks=static::getPK();
      if (count($pks)==0){
        throw new \Exception("There is no applicable PK in this entity");
      }
      $args=[];
      foreach($pks as $field){
        $args[]=Request::getpost($field);
      }
    }elseif (func_num_args()==1 && is_array(func_get_arg(0))){
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

  public function setField($name, $val=null, $force=false){
    if (array_key_exists($name, static::getFields())){

      $fieldData = static::getFields()[$name];
      $fieldType = $fieldData['type'];

      if ((array_key_exists('edit', $fieldData) && $fieldData['edit']) || $force || array_key_exists('fk', $fieldData)){
        if (array_key_exists('fk', $fieldData) && $val===null && !$this->isNew()){
          return;
        }
        if ($fieldType==self::FIELD_TYPE_YESNO){
          $val = $val ? $fieldData['yes'] : $fieldData['no'];
        }

        if ($fieldType==self::FIELD_TYPE_DATE || $fieldType==self::FIELD_TYPE_TIME){
          $val = strtotime($val);
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
          if (($file = $files[$name]) && $file['name']){
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
              $filename = explode(".", $file['name']);
              $ext = array_pop($filename);
              $filename = Strings::processString(implode(".", $filename), $fieldData['filename'], $fieldData['prefix'], $fieldData['postfix']).'.'.$ext;

              $val = $files->store($file, $fieldData['upload_path'], $filename);
            }elseif ( !array_key_exists('required', $fieldData) || !$fieldData['required'] || array_key_exists('keep_if_no_changes', $fieldData) && $fieldData['keep_if_no_changes'] && !$this->isNew()){
              $this->saveErrors[$name]=[];
              unset($this->saveErrors[$name]);
              $isOK=true;
            }
          }elseif(array_key_exists('required', $fieldData) && $fieldData['required']){
            if (array_key_exists('keep_if_no_changes', $fieldData) && $fieldData['keep_if_no_changes'] && !$this->isNew()){
              $isOK=true;
            }else{
              $this->saveErrors[$name][]=self::ERROR_REQUIRED;
            }
          }else{
            $isOK=true;
          }
        }elseif($fieldType===self::FIELD_TYPE_CONTENT_BLOCK){
          $val = json_decode($val, true);
          $result=[];

          if ($val && is_array($val)) {
            foreach ($val as $part) {
              switch($part['type']){
                case 'image':
                  $files = X::uploadedFiles();
                  $imagename = $part['image'];
                  if (($file = $files[$imagename]) && $file['name']){
                    if ($file['is_image']){
                      $filename = explode(".", $file['name']);
                      $ext = array_pop($filename);
                      $filename = Strings::processString(implode(".", $filename), $fieldData['filename'], $fieldData['prefix'], $fieldData['postfix']) . '.' . $ext;
                      $newName = $files->store($file, $fieldData['upload_path'], $filename);
                      $part['image']=X::path2URI(FileSystem::finalizeDirPath($fieldData['upload_path']).$newName);
                    }
                  }
                  break;
                case 'slideshow':
                  $files = X::uploadedFiles();
                  $images=[];
                  foreach($part['images'] as $imagename) {
                    $image = null;
                    $index = null;
                    list($imagename, $index) = explode(":", $imagename);
                    if ($files[$imagename] && ( ($index===null && $files[$imagename]['name']) || ($index!==null && $files[$imagename][$index]['name']))){
                      if ($index!==null){
                        $file = $files[$imagename][$index];
                      }else{
                        $file = $files[$imagename];
                      }
                      if ($file['is_image']) {
                        $filename = explode(".", $file['name']);
                        $ext = array_pop($filename);
                        $filename = Strings::processString(implode(".", $filename), $fieldData['filename'], $fieldData['prefix'], $fieldData['postfix']) . '.' . $ext;
                        $newName = $files->store($file, $fieldData['upload_path'], $filename);
                        $image = X::path2URI(FileSystem::finalizeDirPath($fieldData['upload_path']) . $newName);
                      }
                    }
                    if ($image === null) {
                      $image = $imagename;
                    }
                    $images[] = $image;
                  }
                  $part['images']=$images;
                  break;
              }
              $result[]=$part;
            }
          }
          $val = json_encode($result);
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
              if (strlen($val)<$fieldData['min'] && !(!strlen($val) && $fieldData['keep_if_no_changes'] && !$this->isNew())){
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
              if (Values::isCallback($sanitizer)){
                $val = call_user_func($sanitizer, $val);
              }
            }
          }
          if ($val || !array_key_exists('keep_if_no_changes', $fieldData) || $this->isNew()){
            if (!is_array($fieldData['processor'])){
              $fieldData['processor'] = [$fieldData['processor']];
            }
            foreach($fieldData['processor'] as $processor){
              if (Values::isCallback($processor)){
                $val = call_user_func($processor, $val);
              }
            }

            $this->object->setFieldValue($name, $val);
          }
        }
      }elseif($fieldType==self::FIELD_TYPE_CONST){
        $this->object->setFieldValue($name, $fieldData['value']);
      }
    }
    return $this;
  }

  public function getField($name, $enumText=false){
    if (array_key_exists($name, static::getFields())){
      $fieldData = static::getFieldInfo($name);
      if ($enumText && $fieldData['type']==Entity::FIELD_TYPE_ENUM && $fieldData['text']){
        return $this->object->fieldValue($fieldData['text']);
      }else {
        return $this->object->fieldValue($name);
      }
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

  public static function processSave($addData=[]){
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

    foreach(static::getFields() as $field=>$data){
      if (!array_key_exists('proxy', $data)){
        if ($data['type']==self::FIELD_TYPE_IMAGE && X::uploadedFiles()->exists($field)){
          $entity->setField($field);
        }else{
          $entity->setField($field, Request::post($field));
        }
      }
    }
    foreach($addData as $field=>$value){
      $entity->setField($field, $value, true);
    }

    if (!$entity->saveErrors){
      $entity->save();
      $entity->saved=true;
      $proxy=false;
      foreach(static::getFields() as $field=>$data){
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

  public function getObject(){
    return $this->object;
  }

  public function entityDisplayName(){
    return null;
  }

  public function profileLink(){
    $classname = array_reverse(explode("\\", static::class))[0];
    return AdminPanel::getBase().'_x/entity/'.$classname.'/profile/?'.$this->getPKparams();
  }

  public function editorLink(){
    $classname = array_reverse(explode("\\", static::class))[0];
    return AdminPanel::getBase().'_x/entity/'.$classname.'/edit/?'.$this->getPKparams();
  }

  public function fieldLink($name){
    $classname = array_reverse(explode("\\", static::class))[0];
    return AdminPanel::getBase().'_x/entity/'.$classname.'/field/?'.$this->getPKparams().'&_x_fieldname='.urlencode($name);
  }

  private static function closeModal($update=null){
    (new \X_CMF\Admin\Page(null, null, false))->addData(["update"=>$update])->show("admin/pages/entity/close_modal.haml");
  }

  public function uid(){
    $className = array_slice(explode("\\", get_called_class()), -1)[0];
    $pk = $this->getPKparams();
    return $className.':'.$pk;
  }

  public function view($type=null, $return=false, $addData=[]){
    $page = AdminPanel::getPage();

    $classname = array_reverse(explode("\\", static::class))[0];

    switch($type){
      case null:
      case static::VIEW_TYPE_PROFILE:
        $firstGuess= static::$profileTemplate!=self::$profileTemplate ? static::$profileTemplate : null;
        $template = [$firstGuess, 'admin/entities/'.$classname.'/profile.haml', self::$profileTemplate];
        break;
      case static::VIEW_TYPE_EDITOR:
        $firstGuess= static::$editorTemplate!=self::$editorTemplate ? static::$editorTemplate : null;
        $template = [$firstGuess, 'admin/entities/'.$classname.'/editor.haml', self::$editorTemplate];
        break;
      case static::VIEW_TYPE_RECORD:
        $return=true;
        $firstGuess= static::$recordTemplate!=self::$recordTemplate ? static::$recordTemplate : null;
        $template = [$firstGuess, 'admin/entities/'.$classname.'/record.haml', self::$recordTemplate];
        break;
      case static::VIEW_TYPE_WIDGET:
        $return=true;
        $firstGuess= static::$widgetTemplate!=self::$widgetTemplate ? static::$widgetTemplate : null;
        $template = [$firstGuess, 'admin/entities/'.$classname.'/widget.haml', self::$widgetTemplate];
        break;
      default:
        $template = 'admin/entities/'.$classname.'/'.$type.'.haml';
        break;
    }

    $page->setInsidePanel(!Request::getpost('integrated') && !$return);
    if (!Request::getpost('integrated') && !$return) {
      $title = $this->entityDisplayName() ?: 'entities.' . $classname . '.editor.page_title';
      $page->setTitle($title);
      $page->setDescription('entities.' . $classname . '.editor.page_description');
      $page->clearHistory();
    }
    $page->addData([
      "entity"=>$this,
      "integrated"=>Request::getpost('integrated'),
      "URI_back"=>Request::getpost("backurl"),
    ]);
    if(count($addData)) {
      $page->addData($addData);
    }

    return $page->show($template, $return);
  }

  public function record(){
    return $this->view(self::VIEW_TYPE_RECORD);
  }
  public function profile(){
    return $this->view(self::VIEW_TYPE_PROFILE);
  }
  public function widget(){
    return $this->view(self::VIEW_TYPE_WIDGET);
  }
  public function editor(){
    return $this->view(self::VIEW_TYPE_EDITOR);
  }

  public static function externalListener($section=['']){
    $section=(array)$section;
    $classname = array_reverse(explode("\\", static::class))[0];
    $entity=null;
    switch($section[0]){
      case 'changer':

        $pk = static::getPK();
        if (!$pk){
          $pk=[];
        }
        $consistent=(count($pk)>0);
        $keyInfo=[];
        foreach($pk as $key){
          if (!Request::param($key)){
            $consistent=false;
            break;
          }else{
            $keyInfo[]=Request::get($key);
          }
        }

        if($consistent){
          /**
           * @var Entity $object
           */
          $object= call_user_func_array([static::class,'getByPKKey'], $keyInfo);
          $object->setField(Request::post("field"), Request::post("value"));
          $object->save();
          die();
        }

        break;
      case 'add':
        $entity = static::create();
      case 'edit':
        $data=[];
        foreach(static::$fields as $fieldName=>$fieldData){
          if ($fieldData['type']==self::FIELD_TYPE_EXTERNAL){
            $newValue = Request::getpost($fieldName);
            if ($newValue!==null || !array_key_exists('keep_if_no_changes', $fieldData)) {
              $data[$fieldName] = $newValue;
            }
          }
        }

        /**
         * @var $object static
         */
        if (is_object($entity)){
          $object=$entity;
        }else{
          $object=null;
        }

        $pk = static::getPK();
        if (!$pk){
          $pk=[];
        }
        $consistent=(count($pk)>0);
        $keyInfo=[];
        foreach($pk as $key){
          if (!Request::param($key)){
            $consistent=false;
            break;
          }else{
            $keyInfo[]=Request::get($key);
          }
        }

        if (X::isPost()){
          $object=static::processSave($data);
          if (Request::param("integrated") && !$object->isNew()){
            self::closeModal(Request::getpost("_x_update_entity"));
            die();
          }
        }elseif($consistent){
          $object=$object ?: call_user_func_array([static::class,'getByPKKey'], $keyInfo);
        }

        if (!$object && $consistent){
          AdminPanel::error404();
          return false;
        }else{
          $page = AdminPanel::getPage();
          $history = $page->getHistory();
          $page->clearHistory();
          $page->addHistory('entities.'.$classname.'.name', '#');
          $page->addHistory('admin.pages.entity.view', $object->profileLink());
          $page->addHistory($history[0]);

          $page->addData([
            "integrated"=>Request::param("integrated"),
            "Entity"=>$object,
            "form_data"=>$_POST,
          ]);

          $object->view(static::VIEW_TYPE_EDITOR);
        }
        break;
      case 'json':
        $object=null;
        $pk = static::getPK();
        if (!$pk){
          $pk=[];
        }
        $consistent=(count($pk)>0);
        $keyInfo=[];
        foreach($pk as $key){
          if (!Request::param($key)){
            $consistent=false;
            break;
          }else{
            $keyInfo[]=Request::get($key);
          }
        }

        if($consistent){
          $object= call_user_func_array([static::class,'getByPKKey'], $keyInfo);
        }
        if ($object){
          Response::json($object->asArray());
          die();
        }
      break;

      case 'profile':
        if ($entity=static::getByPKKey()){
          $entity->profile();
        }else{
          AdminPanel::error404();
        }
      break;

      case 'record':
        if ($entity=static::getByPKKey()){
          echo $entity->record();
        }else{
          AdminPanel::error404();
        }
      break;

      case 'field':
        if ($entity=static::getByPKKey()){
          $entity->getField(Request::param("_x_fieldname"));
        }else{
          AdminPanel::error404();
        }
      break;
      case '':
        Response::redirect(FileSystem::finalizeDirPath(explode("?", X::getURI())[0]).'view/');
        break;
    }
  }

  public function __call($name, $args=[]){
    if (!method_exists($this->object, $name) && method_exists($this->object, "get".$name)){
      $name = "get".$name;
    }
    return call_user_func_array([$this->object, $name], $args);
  }

  static public function __callStatic($name, $args=[]){
    if (!method_exists(static::$CRUD, $name) && method_exists(static::$CRUD, "get".$name)){
      $name = "get".$name;
    }
    return forward_static_call_array([static::$CRUD, $name], $args);
  }

} 