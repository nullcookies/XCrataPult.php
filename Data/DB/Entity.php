<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 29.06.14
 * Time: 2:01
 */

namespace X\Data\DB;


use X_CMF\Admin\Icons;

abstract class Entity {

  const FIELD_TYPE_AUTO='auto';
  const FIELD_TYPE_STRING='string';
  const FIELD_TYPE_ENUM='enum';
  const FIELD_TYPE_IMAGE='image';
  const FIELD_TYPE_EXTERNAL='external';

  const FIELD_TYPE_PROPERTY='property';

  const FIELDS_GROUP='fieldsgroup';

  protected static $icon = Icons::ICON_list_alt;

  public static function getLocalizationPath(){
    $className = get_called_class();
    return 'entities.'.array_reverse(explode("\\", $className))[0];
  }

  public static function getIcon(){
    return static::$icon;
  }

  public static function isNew(){
    return true;
  }

} 