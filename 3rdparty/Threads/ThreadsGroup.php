<?php
namespace Threads;


class ThreadsGroup{
  
  private $resultGroups=[];
  private $ids=[];
  
  public function add($callable, $arguments, $resultsGroup){
    $id = Threads::run($callable, $arguments);;
    $this->ids[]=$id;
    $this->resultGroups[$id]=$resultsGroup;
  }

  public function runAndWait(){
    Threads::waitBlocking($this->ids);
  }

  public function getResults(){
    $results=[];
    foreach($this->ids as $id) {
      $res = Threads::getResults($id);
      if ($res) {
        $results[$this->resultGroups[$id]][] = $res["results"];
      }
    }
    return $results;
  }

}