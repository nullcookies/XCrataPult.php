<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 14.05.14
 * Time: 1:42
 */

namespace X\Data\SmartObjects;

use X\Tools\FileSystem;
use X\Tools\Strings;
use X\Validators\Files;

class SmartFile extends SmartArray{

  public function __construct(array $array=Array()) {
    $this->array = $array;
  }

  public function append($value) {
    return; //no changes allowed;
  }

  function offsetSet($key, $value) {
    return; //no changes allowed;
  }

  private function modernize(&$file, $pos=null){
    $getItem = function (&$file, $item) use ($pos){return $pos===null ? $file[$item] : $file[$item][$pos];};
    $assignItem = function (&$file, $item, $value) use ($pos){ $pos===null ? $file[$item]=$value : $file[$item][$pos]=$value;};

    if ($getItem($file, 'error')){
      return;
    }

    $assignItem($file, 'human_size', Strings::Grades( $getItem($file, 'size'), 1024, 'B,KB,MB,GB,TB', 2, ' ') );

    if (!$getItem($file, 'size')){
      return;
    }

    $ext = explode(".", $getItem($file, 'name'));
    $assignItem($file, 'ext', array_pop($ext));

    // image?
    $imagedata  = getimagesize( $getItem($file, 'tmp_name') );
    if ($imagedata && $imagedata[0] && $imagedata[1]){
      $assignItem($file, 'width', $imagedata[0]);
      $assignItem($file, 'height', $imagedata[1]);
      $assignItem($file, 'is_image', true);
      $assignItem($file, 'real_type', strtolower($imagedata['mime']));
      $assignItem($file, 'bits', $imagedata['bits']);
      $assignItem($file, 'colors', $imagedata['channels']==3 ? 'RGB' : 'CMYK');
      $assignItem($file, 'channels', $imagedata['channels']);
    }

    $assignItem($file, 'is_pdf', Files::isPDF($getItem($file, 'tmp_name')));
  }

  function offsetGet($key) {
    if (!array_key_exists($key, $this->array)){
      return null;
    }
    $multivalue = is_array($this->array[$key]['name']);
    if ( ($multivalue && $this->array[$key]['name'][0]['modernized']) || (!$multivalue && $this->array[$key]['modernized']) ){
      return $this->array[$key];
    }

    if ($multivalue){
      for ($i=0; $i<count($this->array[$key]['name']); $i++){
        $this->modernize($this->array[$key], $i);
      }
    }else{
      $this->modernize($this->array[$key]);
    }

    return $this->array[$key];
  }

  function store($fileData, $path, $filename=null, $preserveExtension=false){
    if (!is_array($fileData)){
      throw new \InvalidArgumentException("File data provided is invalid");
    }
    if ($filename===null){
      $filename = $fileData["name"];
    }elseif($preserveExtension){
      $filename.=".".$fileData["ext"];
    }

    $destination = FileSystem::finalizeDirPath($path).$filename;

    if (file_exists($destination) && is_file($destination)){
      if (!unlink($destination)){
        throw new \RuntimeException("$destination exists and cannot be overwritten");
      }
    }

    if (move_uploaded_file($fileData["tmp_name"], $destination)){
      return $filename;
    }
    throw new \RuntimeException("$destination exists is not writable");
  }

  function offsetUnset($key) {
    return; //no changes allowed;
  }

  function offsetExists($offset) {
    return array_key_exists($offset, $this->array);
  }

  function exists($offset){
    return $this->offsetExists($offset);
  }
}