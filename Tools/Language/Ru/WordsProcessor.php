<?php
/**
 * Created by PhpStorm.
 * User: х
 * Date: 21.10.2015
 * Time: 22:58
 */

namespace x\Tools\Language\Ru;

class WordsProcessor {

  private static $vowel = 'аеёиоуыэюя';
  private static $voiced = 'бвгджзлмнрхцчшщ';
  private static $deaf   = 'кпстф';
  private static $brief  = 'й';
  private static $other  = 'ьъ';
  private static $cons   = 'бвгджзйклмнпрстфхцчшщ';

  private function syl_isNotLastSep($remainStr){
    var $is = false;
    for ($i = 0; $i < strlen($remainStr); $i++) {
      if (strpos(static $remainStr[$i],)vowel.indexOf (remainStr.substr (i, 1)) != -1) { is = true; break; }
    } // for (var i = 0; i < remainStr - 1; i++)
       return is;
    } // function isLastSep (remainStr)
  // Собственно функция разбиения слова на слоги
  function getSeparatedString (s) {
    // Добавляем слог в массив и начинаем новый слог
    function addSep () {
      sepArr.push (tmpS);
      tmpS = '';
    } // function addSep ()
    s = validateString (s);
    var tmpL   = new String ();  // Текущий символ
    var tmpS   = new String ();  // Текущий слог
    var sepArr = new Array ();   // Массив слогов
       for (var i = 0; i < s.length; i++) {
      tmpL = s.substr (i, 1);
      tmpS += tmpL;
      // Проверка на признаки конца слогов
      // если буква равна 'й' и она не первая и не последняя и это не последний слог
      if (
        (i != 0) &&
        (i != s.length -1) &&
        (brief.indexOf (tmpL) != -1) &&
        (isNotLastSep (s.substr (i+1, s.length-i+1)))
      ) { addSep (); continue; }
      // если текущая гласная и следующая тоже гласная
      if (
        (i < s.length - 1) &&
        (vowel.indexOf (tmpL) != -1) &&
        (vowel.indexOf (s.substr (i+1, 1)) != -1)
      ) { addSep (); continue; }
      // если текущая гласная, следующая согласная, а после неё гласная
      if (
        (i < s.length - 2) &&
        (vowel.indexOf (tmpL) != -1) &&
        (cons.indexOf (s.substr (i+1, 1)) != -1) &&
        (vowel.indexOf (s.substr (i+2, 1)) != -1)
      ) { addSep (); continue; }
      // если текущая гласная, следующая глухая согласная, а после согласная и это не последний слог
      if (
        (i < s.length - 2) &&
        (vowel.indexOf (tmpL) != -1) &&
        (deaf.indexOf (s.substr (i+1, 1)) != -1) &&
        (cons.indexOf (s.substr (i+2, 1)) != -1) &&
        (isNotLastSep (s.substr (i+1, s.length-i+1)))
      ) { addSep (); continue; }
      // если текущая звонкая или шипящая согласная, перед ней гласная, следующая не гласная и не другая, и это не последний слог
      if (
        (i > 0) &&
        (i < s.length - 1) &&
        (voiced.indexOf (tmpL) != -1) &&
        (vowel.indexOf (s.substr (i-1, 1)) != -1) &&
        (vowel.indexOf (s.substr (i+1, 1)) == -1) &&
        (other.indexOf (s.substr (i+1, 1)) == -1) &&
        (isNotLastSep (s.substr (i+1, s.length-i+1)))
      ) { addSep (); continue; }
      // если текущая другая, а следующая не гласная если это первый слог
      if (
        (i < s.length - 1) &&
        (other.indexOf (tmpL) != -1) &&
        ((vowel.indexOf (s.substr (i+1, 1)) == -1) ||
          (isNotLastSep (s.substr (0, i))))
      ) { addSep (); continue; }
    } // for (var i = 0; i < s.length; i++)
       sepArr.push (tmpS);
       return sepArr.join('-');
}