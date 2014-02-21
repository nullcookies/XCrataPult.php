<?php

class Imagine_Autoloader
{
  /**
   * Registers Imagine_Autoloader as an SPL autoloader.
   */
  public static function register()
  {
    ini_set('unserialize_callback_func', 'spl_autoload_call');
    spl_autoload_register(array(new self, 'autoload'));
  }

  /**
   * Handles autoloading of classes.
   *
   * @param string $class A class name.
   */
  public static function autoload($class)
  {
    if (0 !== strpos($class, 'Imagine')) {
      return;
    }
    if (file_exists($file = x\tools\FileSystem::finalizeDirPath(dirname(__FILE__)).'../'.str_replace('\\','/',$class).'.php')) {
      require $file;
    }
  }
}
