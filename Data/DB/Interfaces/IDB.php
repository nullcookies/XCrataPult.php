<?php

namespace X\Data\DB\Interfaces;

use X\Data\DB\Collection;
use \X\Data\DB\Structure\Database;
use \X\Data\DB\Structure\Field;
use \X\Data\DB\Structure\Table;
use \X\Data\DB\CRUD;

Interface IDB{
  const SELECT_EQUAL         = '=';
  const SELECT_LESS          = '<';
  const SELECT_EQUAL_LESS    = '<=';
  const SELECT_EQUAL_GREATER = '>=';
  const SELECT_GREATER       = '>';
  const SELECT_STARTS        = '_%';
  const SELECT_CONTAINS      = '%_%';
  const SELECT_ENDS          = '%_';
  const SELECT_IN            = '[]';
  const SELECT_BETWEEN       = '..';
  const SELECT_NOT_EQUAL     = '!=';
  const SELECT_NOT_EQUAL_1   = '<>';

  public function __construct($database, $alias = null, $host = null, $login = null, $pass = null);

  public function setCharset($charset);

  public function chooseDB($dbname=null, $alias=null);
  public function dropDB($dbname);

  public function query($sql);
  public function getNext($resource, $asArray=true, $assoc=true);
  public function numRows($resource);
  public function dataSeek($resource, $position);
  public function freeResource($resource);

  /**
   * @param resource $resource
   *
   * @return int
   */
  public function errno($resource=null);

  /**
   * @param resource $resource
   *
   * @return string
   */
  public function error($resource=null);

  public function generateSQLCreateFromField(Field $field);
  public function generateSQLCreateFromTable(Table $table);

  /**
   * @return Database
   */
  public function getDatabase();
  public function getTables();
  public function getTableKeys($tableName);
  public function getTableFields($tableName);

  /**
   * @param string $type
   *
   * @return string
   */
  public function getPHPType($type);

  public function condition($fieldName, $compare, $value);

  /**
   * @param array $options
   * @return array|Collection
   */
  public function getSimple($options=[]);
}