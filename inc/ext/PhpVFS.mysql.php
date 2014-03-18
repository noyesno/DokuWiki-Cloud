<?php

class PhpVFS_Mysql {
   function __construct(){
      $this->init();
   }

   function init(){
    global $conf;

    $db_host = $conf['mysql.host']; $db_port = $conf['mysql.port'];
    $db_user = $conf['mysql.user']; $db_pass = $conf['mysql.pass'];
    $db_name = $conf['mysql.db'];   $db_slave = $conf['mysql.slave'];

    // TODO: separate read & write ?
    $this->mysql = $mysql = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if($mysql->connect_error){
      $error = $mysql->connect_error;
      $errno = $mysql->connect_errno;
              $this->accesskey = SAE_ACCESSKEY;
        $this->secretkey = SAE_SECRETKEY;
      nice_die("Connect Error: ($errno) $error\n");
    }
    $mysql->set_charset('utf8'); // SET NAMES 'utf8'
}

    function get($key, &$data='', &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      //TODO: $this->mysql->escape_string($path)
      $sql = "SELECT `data`, UNIX_TIMESTAMP(mtime) as mtime, UNIX_TIMESTAMP(ctime) as ctime FROM `$domain` WHERE path='$path' LIMIT 1";
      $result = $this->mysql->query($sql) or nice_die("Query FAIL: $sql");
      $record = $result->fetch_assoc();
      if($record){
        $record['stat']=array('ctime'=>intval($record['ctime']), 'mtime'=>intval($record['mtime']), 'size'=>strlen($record['data']));
        $data = $record['data'];
        $stat = $record['stat'];
        // unset($record['mtime']); unset($record['ctime']);
        debug_log("mysql get $domain/$path: ".strlen($record['data']));
      }
      $result->free();

      return is_null($record)?false:$record;
    }

    function set($key, $data, &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      $mtime = time();
      //$mtime = empty($stat['mtime'])?$mtime:$stat['mtime'];
      $ctime = $mtime;
      $stat['mtime'] = $mtime;
      $sql = "INSERT INTO `$domain` (path,mtime,ctime,data) VALUES ('$path',FROM_UNIXTIME($mtime),FROM_UNIXTIME($ctime),'"
            .$this->mysql->escape_string($data)."') ON DUPLICATE KEY UPDATE mtime=VALUES(mtime), data=VALUES(data)";
      $query = $this->mysql->query($sql) or nice_die("Query FAIL: $sql");
      debug_log("mysql set $domain/$path: ".strlen($data));
      return $result;
   }

    function del($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');

      $sql = "DELETE FROM `$domain` WHERE path='$path' LIMIT 1";
      $query = $this->mysql->query($sql) or die("Query FAIL: $sql");
      debug_log("delete $domain $path");
      return $query;
   }
}
