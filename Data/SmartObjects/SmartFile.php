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

  private $modernized=[];

  public function __construct(array $array=Array()) {
    parent::__construct($array);
  }

  public function append($value) {
    return; //no changes allowed;
  }

  function offsetSet($key, $value) {
    return; //no changes allowed;
  }

  private function modernize($file, $pos=null){

    $getItem = function (&$file, $item) use($pos) {return $pos===null ? $file[$item] : $file[$item][$pos];};

    if (!$file || !is_array($file)){
      return [];
    }

    if (!array_key_exists('name', $file)){
      return [];
    }
    $answer=[];

    if (is_array($file['name']) && $pos===null){
      for($i=0; $i<count($file['name']); $i++){
        $answer_=$this->modernize($file, $i);
        if ($answer_){
          $answer[]=$answer_;
        }
      }
      return $answer;
    }

    foreach($file as $key=>$val){
      $answer[$key] = $getItem($file, $key);
    }

    $answer['human_size']=Strings::Grades_size($getItem($file, 'size'));

    if (!$getItem($file, 'size')){
      return $answer;
    }

    $ext = explode(".", $getItem($file, 'name'));
    $answer['ext']=array_pop($ext);

    // image?
    $imagedata  = getimagesize( $getItem($file, 'tmp_name') );
    if ($imagedata && $imagedata[0] && $imagedata[1]){
      $answer['width']=$imagedata[0];
      $answer['height']= $imagedata[1];
      $answer['is_image']= true;
      $answer['real_type']= strtolower($imagedata['mime']);
      $answer['bits']= $imagedata['bits'];
      $answer['colors']= $imagedata['channels']==3 ? 'RGB' : 'CMYK';
      $answer['channels']= $imagedata['channels'];
    }

    $answer['modernized']=1;
    $answer['is_pdf']=Files::isPDF($getItem($file, 'tmp_name'));
    return $answer;
  }

  function getContents($file){

  }

  function offsetGet($key) {
    if (!array_key_exists($key, $this->array)){
      return null;
    }
    if (!array_key_exists($key, $this->modernized)) {
      $this->array[$key] = $this->modernize($this->array[$key]);
      $this->modernized[$key]=true;
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