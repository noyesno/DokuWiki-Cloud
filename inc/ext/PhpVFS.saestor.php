<?php

/***************************************************
* Implement WrapperInterface for SaeStore
***************************************************/
class PhpVFS_SaeStore {

   public function __construct(){
     $this->init();
   }

   function init(){
      $this->kv = new SaeStorage();
      //$this->kv->init();
   }

    function get($key, &$data='', &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');

      if(!is_null($stat)){
         $stat = $this->kv->getAttr($domain, $path);
         if($stat===false) return false;
      }
      if(!is_null($data)){
         $data = $this->kv->read($domain, $path);
         if($data===false) return false;
      }
      return true;
    }

    function set($key, $data, &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');

      $value = $this->kv->write($domain,$path,$data);
      if($value===false) return false;
      return true;
    }

    function del($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');
      $key = trim("$domain/$path",'/');
      $ok = $this->kv->delete($key);
      return $ok;
    }

    function scandir($dir){
      $pathinfo = parse_url($dir); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');

      $dir_files = $this->kv->getListByPath($domain, $path, $limit=300, $offset=0, $fold=true);
      if($dir_files===false) return false;
      $files = array_merge($dir_files['dirs'],$dir_files['files']);
      array_walk($files,array($this,'scandir_fix'));
      return $files;
    }

    function scandir_fix(&$stat){
      if(isset($stat['length'])){
          $stat['name']  = $stat['Name'];
          $stat['mtime'] = $stat['uploadTime'];
          $stat['size']  = $stat['length'];
          $stat['mode']  = 0100777;
      }else{
          $stat['mtime'] = 0;
          $stat['mode']  = 040777;
      }
      unset($stat['fullname']);
    }
}

