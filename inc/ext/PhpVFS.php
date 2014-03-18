<?php

/*****************************************************************
 * Stream WrapperInterface
 *****************************************************************/
class PhpVFS {
   public $context;

   private static $protocols = array();
   private static $cache     = array();

   public static function register($protocol, $class, $force=false){
     if($force && in_array($protocol, stream_get_wrappers())) {
       stream_wrapper_unregister($protocol);
     }
     $classname = get_class();
     stream_wrapper_register($protocol, $classname)
       or die("Failed to register protocol $protocol using $class as $classname"); //STREAM_IS_URL
     self::$protocols[$protocol] = $class;
   }


   public static function clearstatcache($file='*'){
     if(PHP_VERSION_ID < 50300 || $file=='*'){
       clearstatcache();
     }else{
       clearstatcache(true, $file); // only since PHP 5.3.0
     }
   }


   public function kv($key) {
      $prop = substr($key,0,strpos($key,':'));
      if(!isset(self::$protocols[$prop])){
         die("Unregisted protocal $prop!");
      }

      $kv = self::$protocols[$prop];
      if(is_string($kv)){
        $kv = new $kv();
        self::$protocols[$prop] = $kv;
      }
      return $kv;
   }
   ######################################################################
   # Wrapper Interface Implementation Below                             #
   ######################################################################

   public function __construct() { }

   // TODO: implement this
   public function mkdir($path , $mode , $options){ return true; }
   public function rmdir($path , $mode , $options){
     //clearstatcache(true);
     return true;
   }

    /************************************************************
     * return false when file not exist
     ************************************************************/
    private function open($key, &$data='', &$stat=array()) {
        $key = $this->tidypath($key);
        $ask_data = !is_null($data); $ask_stat = !is_null($stat);
        $_data = $data; $_stat = $stat;
        if(!($ask_data || $ask_stat)) nice_die('Both of $data and $stat are null!');
        if(isset(self::$cache[$key])){
          //list($data, $stat) = self::$cache[$key];
          $data = self::$cache[$key][0];
          $stat = self::$cache[$key][1];
          if($stat===false) return false;
          if(!($ask_stat && is_null($stat) || $ask_data && is_null($data))) return true;
        }

        $data = $ask_data?(is_null($data)?$_data:$data):null;
        $stat = $ask_stat?(is_null($stat)?$_stat:$stat):null;
        $ok   = $this->kv($key)->get($key, $data, $stat);

        if($ok!==false){
            $stat = $this->statinfo_init($stat);
            self::$cache[$key] = array($data, $stat);
            return true;
        }else{
            $data=null; $stat = false;
            self::$cache[$key] = array(false, false);
            return false;
        }
    }

    private function save($key, $data=null) {
        $key = $this->tidypath($key);
        if(!isset($data)) $data='';
        $mtime = time(); // TODO: refine
        $stat = $this->statinfo_init(array('size'=>strlen($data), 'mtime'=>$mtime));

        $stat_cache = !isset(self::$cache[$key])?null:(self::$cache[$key][1]);
        if($stat_cache){
            $stat['ctime'] = $stat_cache['ctime'];
        }else{
            $stat['ctime'] = $mtime;
        }


        $ok = $this->kv($key)->set($key, $data, $stat);
        if($ok===false) nice_die("Save file FAIL: $key");
        // TODO: check save success or not!!!
        //unset(self::$cache[$key]);
        if($stat_cache){
          self::clearstatcache($key);
        }
        self::$cache[$key]     = array($data, $stat);
        return $ok;
    }


   function tidypath($path){
      $pos = strpos($path,':')+3;
      $scheme = substr($path,0,$pos);
      $file   = substr($path,$pos);
      $file = rtrim(str_replace('//','/',$file),'/.');
      return $scheme.$file;
   }
   /******************* stat related functions ************************/
      public function url_stat($file, $flags){
        // $flags = STREAM_URL_STAT_LINK | STREAM_URL_STAT_QUIET ;

        $file = $this->tidypath($file);

        if(!empty(self::$cache[$file][1])){
          return self::$cache[$file][1];
        }

        $traces = debug_backtrace();
        $file_func = $traces[1]['function'];
        if(in_array($file_func, array('is_dir', 'is_writable'))) { // treat as dir
            $stat = $this->statinfo_init(false);
            return $stat;
        }

        $data=null; $stat=array();
        if( $this->open($file, $data, $stat) !== false ) {
            $stat = $this->statinfo_init($stat);
            return $stat;
        }else{
            return false;
        }
    }


   /******************* stream related functions ************************/

    public function stream_open( $path , $mode , $options , &$opened_path){

        //$this->kvkey = rtrim(trim(substr(trim($path), 8)), '/');  // trim(substr($path,strlen(self::$protocol)),'/');
        $this->path    = $path;
        $this->mode    = $mode;
        $this->options = $options;  // STREAM_USE_PATH | STREAM_REPORT_ERRORS
        $this->offset = -1;
        $this->buffer = '';
        $this->stat   = false;
        $this->is_read  = ($mode[0]=='r' || strpos($mode,'+')>0);
        $this->is_write = ($mode[0]!='r' || strpos($mode,'+')>0);
        if ($mode[0]=='r'){         // 'r', 'r+', 'rb', 'rt'   # read. r+ for read and write
            $data = ''; $stat = array();
            if ( $this->open($path, $data, $stat) !== false ) {
                $this->offset = 0; $this->buffer = $data;
                $this->stat = $stat;
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
            $data = ''; $stat = array();
            if ( $this->open($path, $data, $stat) !== false ) {
                $this->buffer = $data; $this->offset = strlen($this->buffer);
                $this->stat = $stat;
            } else {
                $this->offset = 0; $this->buffer = '';
                $this->stat = $this->statinfo_init();
            }
            return true;
        } elseif( $mode[0]=='x' ){    // 'x', 'x+'               # only create if not exist
            $data = null; $stat = array();
            if ($this->open($path, $data, $stat) !== false ) { # existing file, fail to open
                $this->stat = $stat;
                trigger_error("File $path already exist!", E_USER_WARNING);
                return false;
            } else {                                              # non-existing file, create
                $this->buffer = ''; $this->offset = 0;
                $this->stat = $this->statinfo_init();
                return true;
            }
        } elseif( $mode[0]=='c' ){    // 'c', 'c+'               # no truncate, create anyway
            $data = ''; $stat = array();
            if ( $this->open($path, $data, $stat) !== false ) {          # existing file
                $this->buffer = $data; $this->offset = 0;
                $this->stat   = $stat;
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
        return $this->stat;
    }

    public function stream_read($count){
        if(!$this->is_read){
            return false;
        }

        $ret = substr( $this->buffer , $this->offset, $count);
        $this->offset += strlen($ret);

        return $ret;
    }

    public function stream_write($data){
        if(!$this->is_write){
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
     switch($options){
       case PHP_STREAM_META_ACCESS : return true; // chmod()
       case PHP_STREAM_META_TOUCH:                // touch()
         $data=''; $stat=array();
         if ($this->open($path, $data, $stat) !== false){
           $this->save($path, $data); // TODO: save stat
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
        $key = $this->tidypath($key);
        unset(self::$cache[$key]);
        self::clearstatcache($key);
        return $this->kv($key)->del($key);
    }


    // ============================================

    private function statinfo_init($is_file=true){
        $dir_mode  =  040777 ; # 16895
        $file_mode = 0100777 ; # 33279

        $stat = array();
        $stat['dev']     = $stat[0]  = 0x8003;
        $stat['ino']     = $stat[1]  = 0;
        $stat['mode']    = $stat[2]  = $is_file?($file_mode):($dir_mode);
        $stat['nlink']   = $stat[3]  = 0;
        $stat['uid']     = $stat[4]  = 0;  $stat['gid']     = $stat[5]  = 0;
        $stat['rdev']    = $stat[6]  = 0;
        $stat['size']    = $stat[7]  = 0;  $stat['atime']   = $stat[8]  = 0;
        $stat['mtime']   = $stat[9]  = 0;  $stat['ctime']   = $stat[10] = 0;
        $stat['blksize'] = $stat[11] = 0;  $stat['blocks']  = $stat[12] = 0;

        if(is_array($is_file)){
            if(isset($is_file['mode']))  $stat['mode']  = $stat[2] = $is_file['mode'];
            if(isset($is_file['size']))  $stat['size']  = $stat[7] = $is_file['size'];
            if(isset($is_file['mtime'])) $stat['mtime'] = $stat[9] = $is_file['mtime'];
            if(isset($is_file['ctime'])) $stat['ctime'] = $stat[10]= $is_file['ctime'];
        }
        return $stat;
    }


    public function stream_cast($cast_as) {
      if($cast_as == STREAM_CAST_FOR_SELECT){          //  stream_select()
        return false;
      }elseif($cast_as == STREAM_CAST_AS_STREAM){      //  stream_cast()
        return false;
      }else{
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

   /******************* dir scan functions ************************/
   public function dir_opendir($dir, $options) {
      $dir = $this->tidypath($dir);
      $this->dir_files = $this->kv($dir)->scandir($dir);
      if($this->dir_files===false) return false;
      reset($this->dir_files);
      $this->dir = $dir;
      return true;
   }

   public function dir_closedir() {
      $this->dir_files = null;
      return false;
   }

   public function dir_readdir(){
      list(,$file) = each($this->dir_files);
      if($file==false) return false;

      $name=$file['name'];
      $fullname = $this->dir.'/'.$name;
      $stat = $this->statinfo_init($file);
      self::$cache[$fullname] = array(null, $stat);
      return $name;
   }

   public function dir_rewinddir() {
      reset($this->dir_files);
      return true;
   }
} // end class DokuWikiFileSystemWrapper
