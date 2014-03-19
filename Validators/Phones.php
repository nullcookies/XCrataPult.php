<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 17.03.14
 * Time: 18:42
 */

namespace X\Validators;
use X\Tools\Geoip;
use X\Tools\Values;

if (!defined('__XDIR__')) die();

class Phones {
  private static $rules;

  public static function registerRules($rules){
    self::$rules = $rules;
    foreach(self::$rules as $ccode=>$cdata){
      if (is_numeric($ccode)){
        self::$rules["maxlen"]=max(self::$rules["maxlen"], strlen($ccode));
        if (is_array(self::$rules[$ccode])){
          foreach(self::$rules[$ccode]["cities"] as $pcode=>$data){
            if (is_numeric($pcode)){
              self::$rules[$ccode]["cities"]["maxlen"]=max(self::$rules[$ccode]["cities"]["maxlen"], strlen($pcode));
            }
          }
        }
      }
    }
  }

  public static function validate($phone){

  }

  /**
   * @param $phone - the phone number to check
   * @param string|null $localizeFor - IP address, or "geoip-country" or "geoip-country/geoip-city" formatted string or null
   */
  public static function getInfo($phone, $localizeFor=null){
    $parts = preg_split( "/\$|\.|\*/", $phone,PREG_SPLIT_NO_EMPTY );
    $ext = count($parts)>1 ? substr($phone, strlen($parts[0])) : '';
    $phone = preg_replace("/[^0-9a-z]/","",strtolower($parts[0]));;

    if ($localizeFor!==null){
      if (Values::isIP($localizeFor)){
        $locationData = Geoip::getLocationInfo($ip);
        $localizeFor=[$locationData["country"],$locationData["city"]];
      }elseif(strpos($localizeFor, "/")){
        list($localizeFor["country"], $localizeFor["city"]) = explode("/", $localizeFor);
      }else{
        $localizeFor["country"] = $localizeFor;
      }
    }

    if (!is_numeric($phone)) {
      $replace = ['2'=>['a','b','c'],
                  '3'=>['d','e','f'],
                  '4'=>['g','h','i'],
                  '5'=>['j','k','l'],
                  '6'=>['m','n','o'],
                  '7'=>['p','q','r','s'],
                  '8'=>['t','u','v'],
                  '9'=>['w','x','y','z']];

      foreach($replace as $digit=>$letters) {
        $phone = str_ireplace($letters, $digit, $phone);
      }
    }

    $data=[
      'input'=>$phone,
      'country'=>null,
      'country_code'=>null,
      'formatted'=>''
    ];
    $local=false;
    $format="+A B C/2,3";

    if (strlen($phone)<=7){
      $data["formatted"]=self::format(["C"=>$phone], "C/2,3");
    }else{

      $useLen=0;
      for ($i=self::$rules["maxlen"]; $i>0; $i--){
        if (array_key_exists(substr($phone, 0, $i), self::$rules)){
          $useLen=$i;
          break;
        }
      }

      $countryCode = $phoneBlocks["A"] = substr($phone, 0, $useLen);

      if (is_string(self::$rules[$countryCode])){
        $countryCode = self::$rules[$countryCode];
      }

      if (!$useLen || strlen($phone)!=($useLen+self::$rules[$countryCode]["length"])){
        $data["formatted"]=self::format(["C"=>$phone], "C/2,3");
        return $data;
      }

      $phone = substr($phone, $useLen);

      $useLen=0;
      for ($i=self::$rules[$countryCode]["cities"]["maxlen"]; $i>0; $i--){
        if (array_key_exists(substr($phone, 0, $i), self::$rules[$countryCode]["cities"])){
          $useLen=$i;
          break;
        }
      }

      $data["country"]=self::$rules[$countryCode]["name"];
      $data["country_code"]=self::$rules[$countryCode]["code"];
      $data["format"]=self::$rules[$countryCode]["format"];

      if (!$useLen){
        if (array_key_exists("unknowns_length", self::$rules[$countryCode])){
          $useLen = self::$rules[$countryCode];
        }
        $data["formatted"]=self::format(["C"=>$phone], "C/2,3");
        return $data;
      }

      $cityCode = $phoneBlocks["B"] = substr($phone, 0, $useLen);
      $subCode = $phoneBlocks["C"] = substr($phone, $useLen);

      if (array_key_exists($cityCode, self::$rules[$countryCode]["cities"])){
        if (is_string(self::$rules[$countryCode]["cities"][$cityCode])){
          $cityCode = self::$rules[$countryCode]["cities"][$cityCode];
        }
        $data["city"]=self::$rules[$countryCode]["cities"][$cityCode]["name"];
        $data["city_local"]=self::$rules[$countryCode]["cities"][$cityCode]["name_local"];
        if (array_key_exists("format", self::$rules[$countryCode]["cities"][$cityCode])){
          $data["format"]=self::$rules[$countryCode]["cities"][$cityCode]["format"];
        }
      }else{
        $data["city"]=false;
      }

      $data["formatted"]=self::format($phoneBlocks, $data["format"], $data["separator"]);
    }
    return $data;
  }

  public static function format($phone, $format="+A B C/2,3", $omit=null){
    if (is_array($format)){
      $answer=[];
      foreach($format as $name=>$rule){
        $answer[$name]=self::format($phone, $rule);
      }
      return $answer;
    }
    $blocks=[];
    $tmpblock='';
    foreach(str_split($format) as $i=>$f){
      switch($f){
        case "A":
        case "B":
          $blocks[$f]=$tmpblock;
          $tmpblock='';
          $blocks[$f].=$f;
          break;
        case "C":
          $blocks[$f]=$tmpblock.substr($format, $i);
          $tmpblock='';
        default:
          $tmpblock.=$f;
      }
    }

    foreach($blocks as $block=>$format){
      if (array_key_exists($block, $phone) && strlen($phone[$block])){
        if (strpos($blocks[$block], '/')){
          list(,$rule)=explode("/",$blocks[$block]);
          $rule = explode(",", $rule);
          $min = intval($rule[0]);
          $max = intval($rule[1]);
          if (count($rule)==3){
            $groupSeparator=$rule[2];
          }else{
            $groupSeparator=' ';
          }
          $prefix = substr($blocks[$block], 0, strpos($blocks[$block], $block));;
          $blocks[$block]='';
          while(strlen($phone[$block])>$max){
            $blocks[$block]=substr($phone[$block],-$min)." ".$blocks[$block];
            $phone[$block] = substr($phone[$block], 0, -$min);
          }
          $blocks[$block]=$phone[$block]." ".$blocks[$block];

          $blocks[$block] = $prefix.preg_replace('/\s+/', $groupSeparator, trim($blocks[$block]));
        }else{
          $blocks[$block] = preg_replace('/_/', $groupSeparator, str_replace($block, $phone[$block], $blocks[$block]));
        }
      }else{
        $blocks[$block] = "";
      }
    }
    return implode('',$blocks);
  }
}

require_once('Phones_dictionary.php');