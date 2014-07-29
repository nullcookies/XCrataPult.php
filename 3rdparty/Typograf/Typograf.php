<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 28.07.14
 * Time: 3:35
 */

namespace Typograf;

class Typograf {
  public static function process($text){
    $remoteTypograf = new RemoteTypograf();

    $remoteTypograf->htmlEntities();
    $remoteTypograf->br (false);
    $remoteTypograf->p (true);
    $remoteTypograf->nobr (3);
    $remoteTypograf->quotA ('laquo raquo');
    $remoteTypograf->quotB ('bdquo ldquo');

    return $remoteTypograf->processText ($text);
  }
}
