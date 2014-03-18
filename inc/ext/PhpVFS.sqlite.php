<?php

class PhpVFS_Sqlite {
    function __construct(){
       $this->init();
    }

    function init(){
        global $conf;

        $dbfile = $conf['vfs.sqlite.db']; 
        $this->dbh = new PDO('sqlite:'.$dbfile, null,null,array(PDO::ATTR_PERSISTENT => false)); // success
    }

    function get($key, &$data='', &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      $table = $domain;
      $sql = "SELECT * FROM `$table` WHERE `path`='$path' LIMIT 1";

      $result = $this->dbh->query($sql);
      if(!$result) print_r($this->dbh->errorInfo());
      $record = $result->fetch(PDO::FETCH_ASSOC);

      if($record){
        $record['stat']=array('ctime'=>intval($record['ctime']), 'mtime'=>intval($record['mtime']), 'size'=>strlen($record['data']));
        $data = $record['data'];
        $stat = $record['stat'];
        // unset($record['mtime']); unset($record['ctime']);
        debug_log("mysql get $domain/$path: ".strlen($record['data']));
      }

      return is_null($record)?false:$record;
    }

    function set($key, $data, &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      $mtime = time();
      //$mtime = empty($stat['mtime'])?$mtime:$stat['mtime'];
      $ctime = $mtime;
      $stat['mtime'] = $mtime;
      $table = $domain;
      $sql = "INSERT INTO `$table` (path,mtime,ctime,data) VALUES ('$path',$mtime,$ctime,"
            .$this->dbh->quote($data).")";

      $result = $this->dbh->query($sql);
      if(!$result) print_r($this->dbh->errorInfo());
      $id = $this->dbh->lastInsertId();
      
      debug_log("mysql set $domain/$path: ".strlen($data));
      return $result;
   }

    function del($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      $table = $domain;
      $sql = "DELETE FROM `$table` WHERE path='$path'";
      $result = $this->dbh->query($sql);
      if(!$result) print_r($this->dbh->errorInfo());
      debug_log("delete $domain $path");
      return $query;
   }
}
