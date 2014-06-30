<?php
/**
 * Created by mr.xcray in the name of magic
 *
 * Date: 30.06.14
 * Time: 21:28
 */

namespace X\Traits;


trait TFullClassName {

  static public function getFullClassName(){
    return get_called_class();
  }

} 