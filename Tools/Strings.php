<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 18.03.14
 * Time: 13:24
 */

namespace X\Tools;
use PHPSQLParser\utils\PHPSQLParserConstants;
use X\Validators\Values;

if (!defined('__XDIR__')) die();


class Strings {
  public static function smartImplode($values, $delimiter, $callback){
    if (is_array($values)){
      array_walk($values, $callback);
    }else{
      return $callback($values);
    }

    return implode($delimiter, $values);
  }

  public static function smartKImplode($values, $delimiter, $callback){
    $array = [];
    if (is_array($values)){
      foreach($values as $key=>$val){
        $array[]=call_user_func_array($callback, [$key, $val]);
      }
    }else{
      return $callback($values);
    }

    return implode($delimiter, $array);
  }

  public static function Grades($val, $divider, $names, $dec=2, $prefix=''){
    if (!is_array($names)){
      $names = explode(",", $names);
    }
    $names_c = 0;
    while($names_c<count($names)-1 && $val>=$divider){
      $val/=$divider;
      $names_c++;
    }
    return round($val, $dec).$prefix.$names[$names_c];
  }

  public static function Grades_size($val){
    return self::Grades($val, 1024, ['B','KB','MB','GB','TB','PB']);
  }

  public static function translitRuEn($string, $nonLatin="'"){
    $converter = array(
      'а' => 'a',   'б' => 'b',   'в' => 'v',
      'г' => 'g',   'д' => 'd',   'е' => 'e',
      'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
      'и' => 'i',   'й' => 'y',   'к' => 'k',
      'л' => 'l',   'м' => 'm',   'н' => 'n',
      'о' => 'o',   'п' => 'p',   'р' => 'r',
      'с' => 's',   'т' => 't',   'у' => 'u',
      'ф' => 'f',   'х' => 'h',   'ц' => 'c',
      'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
      'ь' => $nonLatin,  'ы' => 'y',   'ъ' => $nonLatin,
      'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

      'А' => 'A',   'Б' => 'B',   'В' => 'V',
      'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
      'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
      'И' => 'I',   'Й' => 'Y',   'К' => 'K',
      'Л' => 'L',   'М' => 'M',   'Н' => 'N',
      'О' => 'O',   'П' => 'P',   'Р' => 'R',
      'С' => 'S',   'Т' => 'T',   'У' => 'U',
      'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
      'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
      'Ь' => $nonLatin,  'Ы' => 'Y',   'Ъ' => $nonLatin,
      'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );

    return strtr($string, $converter);
  }

  public static function mb_ucwords($str){
    return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  }

  public static function offset($val, $minLength=2, $fillChar='0'){
    return str_repeat($fillChar, $minLength-strlen($val)).$val;
  }

  public static function explodeByIndex($string, array $indexes){
    $offset=0;
    $length=strlen($string);
    $answer=[];
    foreach($indexes as $i){
      if ($offset>=$length){
        break;
      }
      if (is_array($i)){
        $answer[]=substr($string, $offset, $i[0]-$offset);
        $offset=$i[0]+$i[1];
      }else{
        $answer[]=substr($string, $offset, $i);
        $offset=$i;
      }
    }
    $answer[]=substr($string, $offset);
    return $answer;
  }

  public static function explodeSelective($string, $delimeters=",", $braces=['()',"''", '""']){
    $answer=[];
    $ignore=false;
    $offset=0;
    $length = strlen($string);

    $cutPoints=[];

    if (!is_array($delimeters)){
      $delimeters = [$delimeters];
    }
    if (!is_array($braces)){
      $braces = [$braces];
    }

    $nearestB=null;
    $nearestBpos=0;
    $bracesStack=[];
    while($offset<$length){
      if (!$ignore){
        $nearestD=null;
        $nearestDpos=$length;
        foreach($delimeters as $d){
          if ( ($pos = strpos($string, $d, $offset))!==false){
            if ($pos<$nearestDpos){
              $nearestDpos=$pos;
              $nearestD=$d;
            }
          }
        }
        if ($nearestD===null){
          $offset=$length;
          break;
        }
        if ($nearestBpos<$nearestDpos){
          $nearestB=null;
          $nearestBpos=$length;
          foreach($braces as $b){
            if ( ($pos = strpos($string, $b[0], $offset))!==false){
              if ($pos<$nearestBpos){
                $nearestBpos=$pos;
                $nearestB=$b[0];
              }
            }
          }
          if ($nearestBpos<$nearestDpos){
            $ignore=true;
            $offset = $nearestBpos+1;
            $bracesStack[]=$nearestB;
          }
        }
        if (!$ignore){
          $cutPoints[]=[$nearestDpos, strlen($nearestD)];
          $offset = $nearestDpos+1;
        }
      }
      while($ignore && $offset<$length){
        $nearestOB=null;
        $nearestOBpos=$length;
        $nearestCB=null;
        $nearestCBpos=$length;
        foreach($braces as $b){
          if ( ($pos = strpos($string, $b[0], $offset))!==false){
            if ($pos<$nearestOBpos){
              $nearestOBpos=$pos;
              $nearestOB=$b[0];
            }
          }
          if ($b[0]===end($bracesStack) && ($pos = strpos($string, $b[1], $offset))!==false){
            if ($pos<$nearestCBpos){
              $nearestCBpos=$pos;
              $nearestCB=$b[1];
            }
          }
        }
        if ($nearestCBpos<$nearestOBpos){
          array_pop($bracesStack);
          $offset = $nearestCBpos+1;
          if (count($bracesStack)==0){
            $ignore=false;
          }
        }else{
          $offset = $nearestOBpos+1;
          $bracesStack[]=$nearestOB;
        }
      }
    }

    return self::explodeByIndex($string, $cutPoints);
  }

  public static function processString($text, $processor, $prefix=null, $postfix=null){

    $process = function ($text, $processor, $default=''){
      $answer='';
      if (Values::isCallback($processor)){
        $answer = call_user_func($processor, $text);
      }elseif(is_array($processor)){
        foreach($processor as $task){
          if (is_string($task)){
            if (strpos($task, ":")){
              $task_ = explode(":", $task, 2);
              if ($task_[0]=="function"){
                $answer.= call_user_func($task_[1], $text);
                continue;
              }
            }
            $answer.=$task;
          }elseif(Values::isCallback($task)){
            $answer.= call_user_func($task, $text);
          }
        }
      }else{
        $answer = $default;
      }
      return $answer;
    };

    return $process($text, $prefix).$process($text, $processor, $text).$process($text, $postfix);
  }

  public static function fillBefore($string, $filler, $length){
    return str_repeat($filler, max(0, $length-strlen($string))).$string;
  }

  public static function doubleval($num){

    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
      ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
      return preg_replace("/[^0-9]/", "", $num) ?: 0;
    }

    return
      preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
      preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)));
  }
} 