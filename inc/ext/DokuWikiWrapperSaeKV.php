<?php

DokuWikiWrapperSaeKV::register('saekv', true);




class FileSystemSaeKV {
    
    function init(){
      $this->kv = new SaeKV();
    }
    
       private function unpack_stat( $text ) {
        $arr = unpack("L5", substr($text,0,20));

        // check if valid
        if ($arr[0] != 0x8003) return false;
        //if ( !in_array($arr[2], array( 040777, 0100777))) return false;
        if ( $arr[4] > time() ) return false;
        if ( $arr[5] > time() ) return false;

        return array('dev'=>$arr[1], 'mode'=>$arr[2], 'size'=>$arr[3], 'mtime'=>$arr[4], 'ctime'=>$arr[5]);
    }

    private function pack_stat($stat) {
        $text = pack("LLLLL", 0x8003, 0, $stat['size'], $stat['ctime'], $stat['mtime']);
        return $text;
    }
    
    function get($key){
      $value = $this->kv->get($key);
      
      if ( $value !== false &&  ($stat = $this->unpack_stat($value)) !== false ) {
         return array('data'=>substr($value, 20), 'stat'=>$stat);
      } else {
         return false;
      }
    }
   
    function set($key, $data, &$stat=array()){
      $mtime = time();
      //$mtime = empty($stat['mtime'])?$mtime:$stat['mtime'];
      $ctime = $mtime;
      $stat['mtime'] = $mtime;
      $stat['size']  = strlen($data);
      $value = $this->pack_stat($stat).$data;
      $result = $this->kv->set($key, $value);

      return $result;
    }

    function delete($key){
      $result = $this->kv->delete($key);
      return $result;
    }
}



/***************************************************
* Implement WrapperInterface for SaeKV
***************************************************/


class DokuWikiWrapperSaeKV {
   private $dir_mode  =  040777 ; # 16895 ; // 040000 + 0222;
   private $file_mode = 0100777 ; # 33279 ; //0100000 + 0777;

   public $context;
   
   private static $protocol = null; // WRAPPER_NAME
   public static function register($protocol='saekv', $force=false){
     if($force && in_array($protocol, stream_get_wrappers())) {
       stream_wrapper_unregister($protocol);
     }

     self::$protocol = $protocol;
     $classname = get_class();
     stream_wrapper_register($protocol, $classname)
       or die("Failed to register protocol $protocol as $classname"); //STREAM_IS_URL
   }
   
   static $cache = array();
   public function clearstatcache(){
     self::$cache=array(); 
   }
   
   public function __construct() { }

   public function kv() {
     static $kv=null;
     if (!isset($kv)){
        $kv = new FileSystemSaeKV();
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



    private function open( $key ) {
        $traces = debug_backtrace();
        $file_op        = $traces[2]['function'].'@'.basename($traces[2]['file']).':'.$traces[2]['line'];
        $file_op_caller = $traces[3]['function'].':'.$traces[3]['line'];
        $use_cache = '%   ';

        if(isset(self::$cache[$key])){
          $value = self::$cache[$key];
          $use_cache='  # ';
        }else{
          $value = $this->kv()->get( $key );
          self::$cache[$key] = $value; // TODO: control cache size
        }
        debug_log($use_cache.' '.$key.' '.$file_op_caller.'/'.$file_op." | KVWrapper");

        if ( $value !== false && $this->unpack_stat(substr($value, 0, 20)) === true ) {
            $this->kvcontent = substr($value, 20);
            return true;
        } else {
            return false;
        }
    }

    private function save( $key ) {
      
      
      
      
        $this->stat['mtime'] = $this->stat[9] = time();
        if ( isset($this->kvcontent) ) {
            $this->stat['size'] = $this->stat[7] = strlen($this->kvcontent);
            $value = $this->pack_stat() . $this->kvcontent;
        } else {
            $this->stat['size'] = $this->stat[7] = 0;
            $value = $this->pack_stat();
        }
        //unset(self::$cache[$key]);
        self::$cache[$key] = $value;
        return $this->kv()->set($key, $value);
    }

    private function unpack_stat( $str ) {
        $arr = unpack("L5", $str);

        // check if valid
        if ( $arr[1] < 10000 ) return false;
        if ( !in_array($arr[2], array( $this->dir_mode, $this->file_mode ) ) ) return false;
        if ( $arr[4] > time() ) return false;
        if ( $arr[5] > time() ) return false;

        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = $arr[1];
        $this->stat['mode'] = $this->stat[2] = $arr[2];
        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = $arr[3];
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = $arr[4];
        $this->stat['ctime'] = $this->stat[10] = $arr[5];
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

        return true;
    }

    private function pack_stat( ) {
        $str = pack("LLLLL", $this->stat['ino'], $this->stat['mode'], $this->stat['size'], $this->stat['ctime'], $this->stat['mtime']);
        return $str;
    }

    public function stream_open( $path , $mode , $options , &$opened_path)
    {
        $this->position = 0;
        $this->kvkey = rtrim(trim(substr(trim($path), 8)), '/');  // trim(substr($path,strlen(self::$protocol)),'/');
        $this->mode = $mode;
        $this->options = $options;  // STREAM_USE_PATH | STREAM_REPORT_ERRORS
        
        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                trigger_error("fopen({$path}): No such key in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            if ( $this->open( $this->kvkey ) === true ) {
                $this->position = strlen($this->kvcontent);
            } else {
                $this->kvcontent = '';
                $this->statinfo_init();
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                $this->kvcontent = '';
                $this->statinfo_init();
            } else {
                trigger_error("fopen({$path}): Key exists in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->kvcontent = '';
            $this->statinfo_init();
        } else {
            $this->open( $this->kvkey );
        }

        return true;
    }

    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }

        $ret = substr( $this->kvcontent , $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        $left = substr($this->kvcontent, 0, $this->position);
        $right = substr($this->kvcontent, $this->position + strlen($data));
        $this->kvcontent = $left . $data . $right;

        // TODO:
        $this->position += strlen($data);
        return strlen( $data );

        if ( $this->save( $this->kvkey ) === true ) {
            $this->position += strlen($data);
            return strlen( $data );
        } else return false;
    }

    public function stream_close() {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
          return;
        }
        $this->save( $this->kvkey );
    }


    public function stream_eof()
    {

        return $this->position >= strlen( $this->kvcontent  );
    }

    public function stream_tell()
    {

        return $this->position;
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {

        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->kvcontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->kvcontent ) + $offset >= 0) {
                $this->position = strlen( $this->kvcontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

    public function stream_stat()
    {
        return $this->stat;
    }

   public function stream_metadata($path , $option , $var){
     switch($options){
       case PHP_STREAM_META_ACCESS : return true; // chmod()
       case PHP_STREAM_META_TOUCH:                // touch()
         $path = rtrim(trim(substr(trim($path), 8)), '/');
         if ($this->open($path) === false) $this->statinfo_init(true);
         return $this->save($path);
       case PHP_STREAM_META_OWNER_NAME:           // chown() & chgrp()
       case PHP_STREAM_META_GROUP_NAME:
       case PHP_STREAM_META_OWNER:
       case PHP_STREAM_META_GROUP:
         return true;
       default:
         return true;
     }
   }
    // ============================================
    //-- public function mkdir($path , $mode , $options)
    //-- {
    //--     $path = rtrim(trim(substr(trim($path), 8)), '/');

    //--     if ( $this->open( $path ) === false ) {
    //--         $this->statinfo_init( false );
    //--         return $this->save( $path );
    //--     } else {
    //--         trigger_error("mkdir({$path}): Key exists in KVDB.", E_USER_WARNING);
    //--         return false;
    //--     }
    //-- }

    public function rename($path_from , $path_to)
    {
        $path_from = rtrim(trim(substr(trim($path_from), 8)), '/');
        $path_to = rtrim(trim(substr(trim($path_to), 8)), '/');

        if ( $this->open( $path_from ) === true ) {
            clearstatcache( true );
            return $this->save( $path_to );
        } else {
            trigger_error("rename({$path_from}, {$path_to}): No such key in KVDB.", E_USER_WARNING);
            return false;
        }
    }

    //--public function rmdir($path , $options)
    //--{
    //--    $path = rtrim(trim(substr(trim($path), 8)), '/');

    //--    clearstatcache( true );
    //--    return $this->kv()->delete($path);
    //--}

    public function unlink($path)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        clearstatcache( true );
        return $this->kv()->delete($path);
    }

    public function url_stat($path , $flags){
        // $flags = STREAM_URL_STAT_LINK | STREAM_URL_STAT_QUIET ;
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        $traces = debug_backtrace();
        $file_func = $traces[1]['function'];

        if(in_array($file_func, array('is_dir', 'is_writable'))) { // treat as dir
            debug_log("  direct return true due to $file_func");
            $this->statinfo_init(false);
            return $this->stat;
        }

        if ( $this->open( $path ) !== false ) {
            return $this->stat;
        } else {
            return false;
        }
    }






    // ============================================

    private function statinfo_init( $is_file = true )
    {
        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = crc32(SAE_APPNAME . '/' . $this->kvkey);

        if( $is_file )
            $this->stat['mode'] = $this->stat[2] = $this->file_mode;
        else
            $this->stat['mode'] = $this->stat[2] = $this->dir_mode;

        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = 0;
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = time();
        $this->stat['ctime'] = $this->stat[10] = 0;
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;
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
        return false;
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
