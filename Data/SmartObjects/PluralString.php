<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 09.05.14
 * Time: 19:59
 */

namespace X\Data\SmartObjects;
use X\Validators\Values;


/**
 * Class PluralString is for strings which depend on number:
 * '1 dog'
 * '2 dogs'
 *
 * You may set table of rules to make your expression depend on number (only natural numbers are allowed):
 * [
 *  "1"=>"%1 dog",
 *  ">1"=>"%1 dogs"
 * ]
 *
 * Since you have wide variety of possible conditions in rules set, PluralString is suitable for any language.
 *
 * @package X\Data\SmartObjects
 */
class PluralString {

  private $rulesExact=[];
  private $rulesIntervals=[];
  private $rulesEndingExact=[];
  private $rulesEndingIntervals=[];
  private $default='';

  /**
   * Rules are assoc array with conditions as keys and expressions as values.
   *
   * conditions allowed:
   * 1 - exact number
   * 2..4 - interval
   * 20.. - interval with undefined end
   * ..20 - interval with undefined start
   * _3 - ends (N % 10) with 3 (43, 63, 1993)
   * _3..5 - ends with 3, 4, or 5
   * x - default
   *
   * @param array $rules
   */
  public function __construct($rules){
    if (!is_array($rules)){
      throw new \InvalidArgumentException("Rules should be an array!");
    }
    foreach($rules as $condition=>$expression){
      if (Values::isSignedInteger($condition)){
        $this->rulesExact[$condition] = $expression;
      }elseif($condition=='x'){
        $this->default = $expression;
      }elseif($condition[0]=='_'){
        $ncondition=substr($condition,1);
        if (Values::isSignedInteger($ncondition)){
          $this->rulesEndingExact[$ncondition] = $expression;
        }elseif(strpos($ncondition, "..")!==false){

          if(substr($condition, 0, 2)=='..'){
            $ncondition = substr($ncondition, 2);
            if (Values::isSignedInteger($ncondition)){
              $this->rulesEndingIntervals['x'][$ncondition] = $expression;
            }else{
              throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
            }
          }elseif(substr($ncondition, -2)=='..'){
            $ncondition = substr($ncondition, 0, -2);
            if (Values::isSignedInteger($ncondition)){
              $this->rulesEndingIntervals[$ncondition]['x'] = $expression;
            }else{
              throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
            }
          }elseif(count($margins=explode("..",$ncondition))==2){
            list($min, $max) = $margins;
            if (Values::isSignedInteger($min) && Values::isSignedInteger($max)){
              $start = min($min, $max);
              $end = max($min, $max);
              $this->rulesEndingIntervals[$min][$max]=$expression;
            }
          }else{
            throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
          }
        }else{
          throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
        }
      }elseif(strpos($condition, "..")!==false){
        if(substr($condition, 0, 2)=='..'){
          $ncondition = substr($condition, 2);
          if (Values::isSignedInteger($ncondition)){
            $this->rulesIntervals['x'][$ncondition] = $expression;
          }else{
            throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
          }
        }elseif(substr($condition, -2)=='..'){
          $ncondition = substr($condition, 0, -2);
          if (Values::isSignedInteger($ncondition)){
            $this->rulesIntervals[$ncondition]['x'] = $expression;
          }else{
            throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
          }
        }elseif(count($margins=explode("..",$condition))==2){
          list($min, $max) = $margins;
          if (Values::isSignedInteger($min) && Values::isSignedInteger($max)){
            $start = min($min, $max);
            $end = max($min, $max);
            $this->rulesIntervals[$min][$max]=$expression;
          }
        }else{
          throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
        }
      }else{
        throw new \InvalidArgumentException("Condition '".$condition."' cannot be parsed.");
      }
    }
  }

  /**
   * Your number will be checked agains rules in following order (till first match):
   * 1. exact number
   * 2. interval
   * 3. ending exact match
   * 4. ending interval match
   * 5. default
   *
   * @param int $number
   * @return string
   */
  public function render($number){
    if (array_key_exists($number, $this->rulesExact)){
      return (new PlaceholdersString($this->rulesExact[$number]))->render($number);
    }
    foreach($this->rulesIntervals as $start=>$end){
      if ($number>=$start || $start=='x'){
        foreach($end as $max=>$expression){
          if ($number<=$max || $max=='x'){
            return (new PlaceholdersString($expression))->render($number);
          }
        }
      }
    }
    $ending = $number%10;
    if (array_key_exists($ending, $this->rulesEndingExact)){
      return (new PlaceholdersString($this->rulesEndingExact[$ending]))->render($number);
    }
    foreach($this->rulesEndingIntervals as $start=>$end){
      if ($ending>=$start || $start=='x'){
        foreach($end as $max=>$expression){
          if ($ending<=$max || $max=='x'){
            return (new PlaceholdersString($expression))->render($number);
          }
        }
      }
    }
    return (new PlaceholdersString($this->default))->render($number);
  }
} 