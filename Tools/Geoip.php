<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 18.03.14
 * Time: 11:55
 */

namespace X\Tools;
if (!defined('__XDIR__')) die();

require_once(__XDIR__.'3rdparty/ipgeobase/ipgeobase.php');

class Geoip {

  private static $geoCodes=null;
  private static $locToEn=null;

  public static function registerGeoCodes($codes, $locToEn){
    self::$geoCodes = $codes;
    self::$locToEn = $locToEn;
  }

  public static function getLocationInfo($ip=null, $lang="EN"){
    if ($ip==null){
      $ip=\X\X::getIP();
    }
    if (!array_key_exists($lang, self::$geoCodes)){
      $lang = "EN";
    }

    $answer = [];

    $gb = new \IPGeoBase();

    $data = $gb->getRecord($ip);
    if($data && array_key_exists("cc", $data)){
      $answer["country_code"] = $data['cc'];
      $answer["country"]  = !empty($data['cc']) ? self::$geoCodes[$lang][$data['cc']] : '';
      $answer["region"]   = !empty($data['region']) ? iconv('windows-1251','utf-8',$data['region']) : '';
      $answer["city_local"]=!empty($data['city']) ? iconv('windows-1251', 'utf-8', $data['city']) : '';
      if ($lang=="EN"){
        $answer["city"]     = array_key_exists($answer["city_local"], self::$locToEn) ? self::$locToEn[$answer["city_local"]] : self::rus2translit($answer["city_local"]);
      }else{
        $answer["city"]     = $answer["city_local"];
      }
      $answer["lat"]      = $data["lat"];
      $answer["lng"]      = $data["lng"];
    }

    if(!in_array($data['cc'], array('RU', 'UA'))){
      $record = \geoip_record_by_name($ip);
      if($record){
        $answer["country_code"] = $record["country_code"];
        $answer["country"]  = $record["country_name"];
        $answer["region"]   = $record["region"];
        $answer["city"]     = $record["city"];
        $answer["lat"]      = number_format($record["latitude"],11,'.','');
        $answer["lng"]      = number_format($record["longitude"],11,'.','');
      }
    }

    if(!$answer["country"]){
      return false;
    }else{
      return $answer;
    }
  }

  static private function rus2translit($string) {
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
      'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
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
      'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
      'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
  }
}
require_once('Geoip_codes.php');