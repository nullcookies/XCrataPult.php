<?php
namespace X\Tools;
if (!defined('__XDIR__')) die();

// TODO: add Exceptions

use \X\X;
use \X\AbstractClasses\PrivateInstantiation;
use \X\Debug\Tracer;

class FileSystem extends PrivateInstantiation{

  public static function writeLocked($fileLink, $content, $mode="w", $permissions=0774)
  {
    if (!is_resource($fileLink)){
      if (is_string($fileLink)){

        if (file_exists($fileLink) && !is_writable($fileLink)){
          ;//Exception
          return;
        }

        $resource = fopen($fileLink, $mode);
        $result = self::writeLocked($resource, $content, $mode, $permissions);
        if ($resource){
          fclose($resource);
          chmod ( $fileLink , $permissions );
        }
        return $result;
      }
      else{
        ;//Exception
        return;
      }
    }
    if (flock($fileLink, LOCK_EX ))
    {
      fwrite($fileLink, $content);
      flock($fileLink, LOCK_UN);
      $meta_data = stream_get_meta_data($fileLink);
      $filename = $meta_data["uri"];
      chmod ( $filename, $permissions );
    }
    else{
      ;//Exception
      return;
    }
  }

  public static function finalizeDirPath($dir)
  {
    if (strlen($dir) && !in_array(substr($dir,-1), Array("/", "\\"))){
      $dir.=DIRECTORY_SEPARATOR;
    }
    return $dir;
  }

  public static function dirList($base=null, $pattern='*', $filesOnly=true, $limit=1000, $globOptions=null)
  {
    if ($globOptions===null){
      $globOptions = GLOB_NOSORT|GLOB_BRACE;
    }

    if ($base===null){
      $caller = Tracer::getCaller();
      $base = dirname($caller['file']);
    }

    $base = substr(self::finalizeDirPath($base), 0, -1);

    $answer = [];

    if (!is_dir($base)){
      return $answer;
    }

    if (!is_readable($base)){
      if (!$filesOnly){
        return $answer[]= $base." [ACCESS DENIED]";
      }
      return $answer;
    }

    foreach(glob($base.DIRECTORY_SEPARATOR.$pattern, $globOptions) as $a){
      if (!is_dir($a) || !$filesOnly){
        $answer[]=$a;
      }
      $limit--;
      if ($limit<=0){
        break;
      }
      if (is_dir($a)){
        $_answer= self::dirList($a, $pattern, $filesOnly, $limit, $globOptions);
        $limit -= count($_answer);
        $answer = array_merge($answer, $_answer);
      }
    }

    return $answer;
  }

  public static function glue($files, $glue){
    $answer='';
    foreach($files as $file){
      $answer.=file_get_contents($file).$glue;
    }
    return $answer;
  }
}