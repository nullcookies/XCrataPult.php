<?php

namespace X\Data\Cache\Interfaces;

interface ICache
{
  public function __get($name);
  public function __set($name, $val);
  public function __isset($name);
  public function __unset($name);

  public function set($name, $val, $ttl=null);
  public function get($name);
  public function remove($name);
  public function exists($name);

  /**
   * @return bool
   */
  public function connect();

  public function arraySize($arr);
  public function arrayGet($arr);
  public function arrayGetItem($arr, $index);
  public function arraySetItem($arr, $index, $value, $ttl=null);
  public function arrayRemoveItem($arr, $value, $count);
  public function arrayDelete($arr);

  public function groupSize($hash);
  public function groupGet($hash);
  public function groupGetItem($hash, $key);
  public function groupSetItem($hash, $key, $value, $ttl=null);
  public function groupRemoveItem($hash, $key);
  public function groupDelete($hash);

  /**
   * FILO push
   *
   * @param string $qry name of query
   * @param mixed $value new value to add
   *
   * @return bool
   */
  public function queryPush($qry, $value);

  /**
   * FILO pop
   *
   * @param string $qry
   * @param bool $blocking
   *
   * @return mixed|null
   */
  public function queryPop($qry, $blocking=false);

  /**
   * FIFO push
   *
   * @param string $stack
   * @param mixed $value
   *
   * @return bool
   */
  public function stackPush($stack, $value);

  /**
   * FIFO pop
   *
   * @param string $stack
   * @param bool $blocking
   *
   * @return mixed|null
   */
  public function stackPop($stack, $blocking=false);

  /**
   * @return bool
   */
  public function enabled();

  /**
   * Mutex with watchdog-like support.
   *
   * @param string  $lockName name of MUTEX (prefix will be added!)
   * @param int     $ttl      TTL in s, (10 sec max)
   * @param int     $timeWait time in s to wait for MUTEX to be opened (10 sec max)
   *
   * @return bool
   */
  public function lock($lockName, $ttl=1, $timeWait=1);

  /**
   * Mutex reset function
   *
   * @param string  $lockName name of MUTEX (prefix will be added!)
   */
  public function unlock($lockName);
};