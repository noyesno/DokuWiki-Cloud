<?php

DokuWikiWrapperMysql::register('mysql', true);

/***************************************************
* Implement WrapperInterface for MySql
***************************************************/

class MysqlKV {
    
    function init(){
      global $conf;
    
      $db_host = $conf['mysql.host']; $db_port = $conf['mysql.port'];
      $db_user = $conf['mysql.user']; $db_pass = $conf['mysql.pass'];
      $db_name = $conf['mysql.db'];   $db_slave = $conf['mysql.slave'];
    
      // TODO: separate read & write ?
      $mysql = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

      if($mysql->connect_error){
        $error = $mysql->connect_error;
        $errno = $mysql->connect_errno;
        nice_die("DB Connect Error: ($errno) $error\n");
      }
      $mysql->set_charset('utf8'); // SET NAMES 'utf8'
      $this->mysql = $mysql;
    }
   
    function get($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');
        
      //TODO: $this->mysql->escape_string($path)
      $sql = "SELECT `data`, UNIX_TIMESTAMP(mtime) as mtime, UNIX_TIMESTAMP(ctime) as ctime FROM `$domain` WHERE path='$path' LIMIT 1";
      $result = $this->mysql->query($sql) or nice_die("DB SELECT FAIL: ".$this->mysql->error);
      $record = $result->fetch_assoc();
      if($record){
        $record['stat']=array('ctime'=>intval($record['ctime']), 'mtime'=>intval($record['mtime']), 'size'=>strlen($record['data']));
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
      $query = $this->mysql->query($sql) or nice_die("DB INSERT FAIL: ".$this->mysql->error);
      debug_log("mysql set $domain/$path: ".strlen($data));
      return $result;
    }

    function delete($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = ltrim($pathinfo['path'], '/');
      
      $sql = "DELETE FROM `$domain` WHERE path='$path' LIMIT 1";
      $query = $this->mysql->query($sql) or die("DB DELETE FAIL: $sql".$this->mysql->error);
      debug_log("delete $domain $path");
      return $query;
    }
}


class DokuWikiWrapperMysql { // implements WrapperInterface


   public $context;

   private static $protocol = null; // WRAPPER_NAME
   public static function register($protocol='mysql', $force=false){
     if($force && in_array($protocol, stream_get_wrappers())) {
       stream_wrapper_unregister($protocol);
     }

     self::$protocol = $protocol;
     $classname = get_class();
     stream_wrapper_register($protocol, $classname)
       or die("Failed to register protocol $protocol as $classname"); //STREAM_IS_URL
   }

   static $cache = array();
   static $statcache = array();
   public static function clearstatcache($file='*'){
     if(PHP_VERSION_ID < 50300 || $file=='*'){
       clearstatcache();
     }else{
       clearstatcache(true, $file); // only since PHP 5.3.0
     }
   }

   public function __construct() { }


   
   public function kv() {
     static $kv=null;
     if (!isset($kv)){
        $kv = new MysqlKV();
        $kv->init();
     }

     $this->kv = &$kv;
     return $this->kv;
   }

   public function mkdir($path , $mode , $options){ return true; }
   public function rmdir($path , $mode , $options){
     //clearstatcache(true);
     return true;
   }

    /************************************************************
     * return false when file not exist
     ************************************************************/
    private function open($key, &$finfo=null) {        
        $traces = debug_backtrace();
        $file_op        = $traces[2]['function'].'@'.basename($traces[2]['file']).':'.$traces[2]['line'];
        $file_op_caller = $traces[3]['function'].':'.$traces[3]['line'];

        if(isset(self::$cache[$key])){
          $value = self::$cache[$key];
          debug_log('  # '.$key.' '.$file_op_caller.'/'.$file_op." | KVWrapper");
          $finfo = $value;
          return ($value===false)?false:true;
        }
        
        $value = $this->kv()->get($key);
        debug_log('%   '.$key.' '.$file_op_caller.'/'.$file_op." | KVWrapper");

        // TODO: control cache size
        if ( $value !== false) {                 // file exist
            debug_log("file $key exist");
            $value['stat'] = $this->statinfo_init($value['stat']);
            self::$cache[$key] = $value;
            $finfo = $value;
            return true;
        } else {                                 // file not exist
            debug_log("file $key not exist");
            self::$cache[$key] = false;
            return false;
        }
    }

    private function save($key, $data=null) {
        if(!isset($data)) $data='';
        $stat = $this->statinfo_init(array('size'=>strlen($data), 'mtime'=>time()));
        
        $rv = $this->kv()->set($key, $data, $stat);
        if($rv===false) nice_die("Save file FAIL: $key");
        // TODO: check save success or not!!!
        //unset(self::$cache[$key]);
        if(self::$cache[$key]['stat']){
          debug_log("clearstatcache $key");
          self::clearstatcache($key);
        }
        self::$cache[$key] = array('data'=>$data, 'stat'=>$stat);
        return $rv;
    }
  
  
      public function url_stat($path, $flags){
        // $flags = STREAM_URL_STAT_LINK | STREAM_URL_STAT_QUIET ;
        $traces = debug_backtrace();
        $file_func = $traces[1]['function'];
        debug_log("Debug: url_stat: $file_func ".$traces[2]['function'].' '.$traces[3]['function']);
        
        if(in_array($file_func, array('is_dir', 'is_writable'))) { // treat as dir       
            $stat = $this->statinfo_init(false);
            debug_log("  direct return true $path $file_func");
            return $stat;
        }elseif(!empty(self::$cache[$path])){
          debug_log("  # use cache[$path]['stat'] $file_func");
          return self::$cache[$path]['stat'];
        }elseif( $this->open($path, $finfo) !== false ) {
            debug_log("  url_stat exist $path $file_func");
            return $finfo['stat'];
        }else{
            debug_log("  return false $path $file_func");
            return false;
        }
    }
   
    

    public function stream_open( $path , $mode , $options , &$opened_path){
        
        //$this->kvkey = rtrim(trim(substr(trim($path), 8)), '/');  // trim(substr($path,strlen(self::$protocol)),'/');
        $this->path    = $path;        
        $this->mode    = $mode;
        $this->options = $options;  // STREAM_USE_PATH | STREAM_REPORT_ERRORS
        debug_log("open file $path $mode $options ".sprintf("%02x %02x %02x", $options, STREAM_USE_PATH, STREAM_REPORT_ERRORS));
        
        $this->offset = -1;
        $this->buffer = '';
        $this->stat   = false;
        
        if ($mode[0]=='r'){         // 'r', 'r+', 'rb', 'rt'   # read. r+ for read and write
            if ( $this->open($path, $finfo) !== false ) {
                $this->offset = 0; $this->buffer = $finfo['data'];
                $this->stat = $finfo['stat'];
                return true;
            }else{
                $this->stat = false;
                trigger_error("File $path does not exist!", E_USER_WARNING);
                return false;
            }
        }elseif( $mode[0]=='w' ){    // 'w', 'w+',              # truncate, create anyway
            $this->buffer = ''; $this->offset = 0;
            $this->stat = $this->statinfo_init();
            return true;
        }elseif( $mode[0]=='a' ){     // 'a', 'a+', 'ab'
            if ( $this->open($path, $finfo) !== false ) {
                $this->buffer = $finfo['data']; $this->offset = strlen($this->buffer);
                $this->stat = $finfo['stat'];
            } else {
                $this->offset = 0; $this->buffer = '';
                $this->stat = $this->statinfo_init();
            }
            return true;
        } elseif( $mode[0]=='x' ){    // 'x', 'x+'               # only create if not exist
            if ($this->open($path, $finfo) !== false ) {          # existing file, fail to open
                $this->stat = $finfo['stat'];
                trigger_error("File $path already exist!", E_USER_WARNING);
                return false;
            } else {                                              # non-existing file, create
                $this->buffer = ''; $this->offset = 0;
                $this->stat = $this->statinfo_init();
                return true;
            }
        } elseif( $mode[0]=='c' ){    // 'c', 'c+'               # no truncate, create anyway
            if ( $this->open($path, $finfo) !== false ) {          # existing file
                $this->buffer = $finfo['data']; $this->offset = 0;
                $this->stat = $finfo['stat'];
                return true;
            } else {
                $this->buffer = ''; $this->offset = 0;             # create file
                $this->stat = $this->statinfo_init();
                return true;
            }
        } else {
          trigger_error("Not supported fopen mode $mode!", E_USER_WARNING);
          return false;
        }

        return false;
    }
    
    public function stream_stat(){
        debug_log("stream_stat()");
        return $this->stat;
    }
    
    public function stream_read($count){
        debug_log("stream_read");
        if (!( $this->mode[0]=='r' || (strlen($this->mode)>1 && $this->mode[1]=='+') )){
            return false;
        }

        $ret = substr( $this->buffer , $this->offset, $count);
        $this->offset += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
        debug_log("stream_write");
        if ($this->mode[0]=='r' && (strlen($this->mode)>1 && $this->mode[1]!='+') ) {
            return false;
        }

        $left  = substr($this->buffer, 0, $this->offset);
        $right = substr($this->buffer, $this->offset + strlen($data));
        $this->buffer = $left . $data . $right;
        $this->offset += strlen($data);
        // TODO:
        return strlen($data);
    }

    public function stream_close() {
        debug_log("stream_close");
        if ($this->mode[0]=='r'){
          return;
        }else{
          $this->save($this->path, $this->buffer);
        }
    }


    public function stream_eof(){
        return $this->offset >= strlen($this->buffer );
    }

    public function stream_tell(){
        return $this->offset;
    }

    public function stream_seek($offset , $whence = SEEK_SET){

        switch ($whence) {
        case SEEK_SET:
            if ($offset < strlen($this->buffer) && $offset >= 0) {
                $this->offset = $offset;
                return true;
            }else{
                return false;
            }
            break;
        case SEEK_CUR:
            if ($offset >= 0) {
                $this->offset += $offset;
                return true;
            }
            else
                return false;

            break;
        case SEEK_END:
            if (strlen($this->buffer)+$offset >= 0) {
                $this->offset = strlen($this->buffer)+$offset;
                return true;
            }else{
                return false;
            }
            break;
        default:
            return false;
        }
    }


    
   public function stream_metadata($path , $option , $var){
    debug_log("open file metadata $path");

     switch($options){
       case PHP_STREAM_META_ACCESS : return true; // chmod()
       case PHP_STREAM_META_TOUCH:                // touch()
         if ($this->open($path, $finfo) !== false){
           $this->save($path, $finfo['data']);
         }else{
           $this->save($path, '');
         }
         return true;
       case PHP_STREAM_META_OWNER_NAME:           // chown() & chgrp()
       case PHP_STREAM_META_GROUP_NAME:
       case PHP_STREAM_META_OWNER:
       case PHP_STREAM_META_GROUP:
         return true;
       default:
         return true;
     }
   }

    public function rename($path_from , $path_to){
        // TODO:
        return false;
    }


    public function unlink($key){
        unset(self::$cache[$key]);
        self::clearstatcache($key);
        return $this->kv()->delete($key);
    }


    // ============================================

    private function statinfo_init($is_file=true){
        $dir_mode  =  040777 ;
        $file_mode = 0100777 ;
        
        $stat = array();
        $stat['dev']     = $stat[0]  = 0x8003;
        $stat['ino']     = $stat[1]  = 0;
        $stat['mode']    = $stat[2]  = $is_file?($file_mode):($dir_mode);
        $stat['nlink']   = $stat[3]  = 0;
        $stat['uid']     = $stat[4]  = 0;
        $stat['gid']     = $stat[5]  = 0;
        $stat['rdev']    = $stat[6]  = 0;
        $stat['size']    = $stat[7]  = 0;
        $stat['atime']   = $stat[8]  = 0;
        $stat['mtime']   = $stat[9]  = 0;
        $stat['ctime']   = $stat[10] = 0;
        $stat['blksize'] = $stat[11] = 0;
        $stat['blocks']  = $stat[12] = 0;
                
        if(is_array($is_file)){
            if(isset($is_file['size']))  $stat['size']  = $stat[7] = $is_file['size'];
            if(isset($is_file['mtime'])) $stat['mtime'] = $stat[9] = $is_file['mtime'];
            if(isset($is_file['ctime'])) $stat['ctime'] = $stat[10]= $is_file['ctime'];
        }
        return $stat;
    }

    public function dir_closedir() {
        return false;
    }

    public function dir_opendir($path, $options) {
        return false;
    }
    public function dir_readdir() {
        return false;
    }

    public function dir_rewinddir() {
        return false;
    }

    public function stream_cast($cast_as) {
      if($cast_as == STREAM_CAST_FOR_SELECT){          //  stream_select()
        debug_log("stream_select()");
        return false;
      }elseif($cast_as == STREAM_CAST_AS_STREAM){      //  stream_cast()
        debug_log("stream_cast()");
        return false;
      }else{
        debug_log("stream_cast unknown");
        return false;
      }
    }

    public function stream_flush() {
        return false;
    }

    public function stream_lock($operation) {
        return false;
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

}
