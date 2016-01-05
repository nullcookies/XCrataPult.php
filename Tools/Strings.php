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
    return self::Grades($val, 1024, ['B','KB','MB','GB','TB','PB'], 2, ' ');
  }

  /**
   * Ru->En translit is ok, but
   * En->Ru translit is corrected for best contact names transliteration.
   *
   * @param $string
   * @param string $nonLatin
   * @param bool $back
   * @return string
   */
  public static function translitRuEn($string, $nonLatin="'", $back=false){
    $ru_en= array(
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
    $en_ru= array(
      'a'=>'а',
      'b'=>'б',
      'c'=>'ц',
      'ct'=>'кт',
      'd'=>'д',
      'e'=>'е',
      'ei'=>'ей',
      'eld'=>'эльд',
      'f'=>'ф',
      'g'=>'г',
      'orge'=>'оргий',
      'gene'=>'гений',
      'h'=>'х',
      'i'=>'и',
      'ij'=>'ий',
      'ia'=>'ия',
      'lyuk'=>'люк',
      'ichae'=>'ихаи',
      'lga'=>'льга',
      'rya'=>'рия',
      'nia'=>'ния',
      'odko'=>'одько',
      'tian'=>'тьян',
      'iye'=>'ие',
      'aye'=>'ае',
      'j'=>'дж',
      'iya'=>'ия',
      'jo'=>'ё',
      'je'=>'ье',
      'tsk'=>'цк',
      'lts'=>'льц',
      'mtso'=>'мцо',
      'mtse'=>'мце',
      'k'=>'к',
      'kh'=>'х',
      'l'=>'л',
      'm'=>'м',
      'n'=>'н',
      'ander'=>'андр',
      'o'=>'о',
      'p'=>'п',
      'q'=>'ку',
      'r'=>'р',
      's'=>'с',
      'sh'=>'ш',
      'shch'=>'щ',
      't'=>'т',
      'u'=>'у',
      'yu'=>'ю',
      'lu'=>'лю',
      'v'=>'в',
      'w'=>'в',
      'x'=>'кс',
      'y'=>'й',
      'try'=>'трий',
      'ry'=>'ры',
      'eny'=>'ений',
      'eug'=>'евг',
      'ky'=>'кы',
      'yy'=>'ый',
      'yoev'=>'ёев',
      'ly'=>'лы',
      'eln'=>'ельн',
      'iln'=>'ильн',
      'elt'=>'ельт',
      'oly'=>'олий',
      'aly'=>'алий',
      'lj'=>'ль',
      'igor'=>'игорь',
      'Igor'=>'Игорь',
      'slja'=>'сля',
      'tlja'=>'тля',
      'mlja'=>'мля',
      'klja'=>'кля',
      'ely'=>'елый',
      'sky'=>'ский',
      'tsky'=>'цкий',
      'cky'=>'цкий',
      'sy'=>'сы',
      'dy'=>'ды',
      'z'=>'з',
      'zh'=>'ж',
      'jh'=>'ж',
      'aya'=>'ая',
      'ya'=>'я',
      'ja'=>'я',
      'ija'=>'ия',
      'aj'=>'ай',
      'ay'=>'ай',
      'mja'=>'мья',
      'dja'=>'дья',
      'nja'=>'нья',
      'lja'=>'лья',
      'mya'=>'мья',
      'dya'=>'дья',
      'nya'=>'нья',
      'lya'=>'лья',
      'iy'=>'ий',
      'kiy'=>'кий',
      'ch'=>'ч',
      'ej'=>'ей',

      'A'=>'А',
      'B'=>'Б',
      'C'=>'С',
      'D'=>'Д',
      'E'=>'Е',
      'EI'=>'ЕЙ',
      'Ei'=>'Эй',
      'ELD'=>'ЭЛЬД',
      'Eld'=>'Эльд',
      'Eu'=>'Ев',
      'EU'=>'ЕВ',
      'F'=>'Ф',
      'G'=>'Г',
      'ORGE'=>'ОРГИЙ',
      'GENE'=>'ГЕНИЙ',
      'H'=>'Х',
      'I'=>'И',
      'IJ'=>'ИЙ',
      'J'=>'Дж',
      'JO'=>'Ё',
      'Jo'=>'Ё',
      'K'=>'К',
      'KH'=>'Х',
      'Kh'=>'Х',
      'L'=>'Л',
      'LU'=>'ЛЮ',
      'Lu'=>'Лю',
      'LY'=>'ЛЫЙ',
      'M'=>'М',
      'N'=>'Н',
      'ANDER'=>'АНДР',
      'O'=>'О',
      'P'=>'П',
      'Q'=>'Ку',
      'R'=>'Р',
      'S'=>'С',
      'SH'=>'Ш',
      'SHCH'=>'Щ',
      'Sh'=>'Ш',
      'T'=>'Т',
      'U'=>'У',
      'UR'=>'ЮР',
      'Ur'=>'Юр',
      'YU'=>'Ю',
      'IYE'=>'ИЕ',
      'Yu'=>'Ю',
      'V'=>'В',
      'W'=>'В',
      'X'=>'Кс',
      'Y'=>'Й',
      'RY'=>'РЫ',
      'Ry'=>'Ры',
      'KY'=>'КЫ',
      'Ky'=>'Кы',
      'SY'=>'СЫ',
      'Sy'=>'Сы',
      'Z'=>'З',
      'ZH'=>'Ж',
      'JH'=>'Ж',
      'Zh'=>'Ж',
      'Jh'=>'Ж',
      'YA'=>'Я',
      'JA'=>'Я',
      'AY'=>'Ай',
      'Ay'=>'Ай',
      'AJ'=>'Ай',
      'Aj'=>'Ай',
      'Ya'=>'Я',
      'Ja'=>'Я',
      'Ju'=>'Ю',
      'JU'=>'Ю',
      'Mja'=>'Мья',
      'Dja'=>'Дья',
      'Nja'=>'Нья',
      'Lja'=>'Лья',
      'MJA'=>'МЬЯ',
      'DJA'=>'ДЬЯ',
      'NJA'=>'НЬЯ',
      'LJA'=>'ЛЬЯ',
      'Mya'=>'Мья',
      'Dya'=>'Дья',
      'Nya'=>'Нья',
      'Lya'=>'Лья',
      'MYA'=>'МЬЯ',
      'DYA'=>'ДЬЯ',
      'NYA'=>'НЬЯ',
      'LYA'=>'ЛЬЯ',
      'AYA'=>'АЯ',
      'Aya'=>'Ая',
      'IY'=>'ИЙ',
      'Iy'=>'Ий',
      'SKY'=>'СКИ',
      'Sky'=>'Cки',
      'KIY'=>'Кий',
      'CH'=>'Ч','Ch'=>'Ч',
      'EJ'=>'ЕЙ',
      'Ej'=>'Ей',

      "'"=>'ь',
      "''"=>'Ъ'
    );

    $answer = strtr($string, $back ? $en_ru : $ru_en);
    if ($back){
      $answer=str_replace("ьь", "ь", $answer);
      $answer=str_replace("йй", "й", $answer);
      $answer=str_replace("ъъ", "ъ", $answer);
    }
    return $answer;
  }

  public static function translitEnRu($string){
    return static::translitRuEn($string, "'", true);
  }

  public static function mb_ucwords($str){
    return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  }

  public static function mb_str_split( $string ) {
    $split = preg_split('/\b([\(\).,\-\'\-,\:!\?;"\{\}\[\]„“»«‘\r\n\/%\.\|]*)/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    return $split;
  }

  public static function mb_trim( $string ){
    $string = preg_replace("/(^\s+)|(\s+$)/us", "", $string);
    return $string;
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

  public static function fillAfter($string, $filler, $length){
    return $string.str_repeat($filler, max(0, $length-strlen($string)));
  }

  public static function doubleval($num){

    $negative = trim(strval($num))[0]=='-';
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
      ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
      return ($negative ? '-' : '').preg_replace("/[^0-9]/", "", $num) ?: 0;
    }

    return
      ($negative ? '-' : '').
      preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
      preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)));
  }
} 