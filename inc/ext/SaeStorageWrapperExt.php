<?php

SaeStorageWrapperExt::register('saestor', true);

class SaeStorageWrapperExt extends SaeStorageWrapper {
   private $dir_mode  =  040777 ; # 16895 ; // 040000 + 0222;
   private $file_mode = 0100777 ; # 33279 ; //0100000 + 0777;
   
   public $context;

   private static $protocol = null; // WRAPPER_NAME
   public static function register($protocol='saestor', $force=false){
     if($force && in_array($protocol, stream_get_wrappers())) {
       stream_wrapper_unregister($protocol);
     }

     self::$protocol = $protocol;
     $classname = get_class();
     stream_wrapper_register($protocol, $classname)
       or die("Failed to register protocol $protocol as $classname"); //STREAM_IS_URL
   }

    public function __construct(){
        $this->stor();
    }

    public function stor() {
      static $stor=null;
      if (!isset($stor)) $stor = new SaeStorage();
      $this->stor = $stor;
    }

    public function url_stat($path, $flags) {
        $traces = debug_backtrace();
        $file_func = $traces[1]['function'];

        if(in_array($file_func, array('is_dir', 'is_writable'))) { // treat as dir
            debug_log("  direct return true due to $file_func");
            $this->statinfo_init(false);
            return $this->stat;
        }
    
    
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = rtrim(ltrim(strstr($path, $pathinfo['path']), '/\\'),'/.');

        debug_log("StoreWrapper open $path");
        if ( $attr = $this->stor->getAttr( $this->domain , $this->file ) ) {
            $this->statinfo_init(array('mtime'=>$attr['datetime'], 'ctime'=>$attr['datetime'], 'size'=>$attr['length']));
            return $this->stat;
        //} elseif(($files = $this->stor->getListByPath($this->domain, $this->file, 1, 0, false)) && count($files)){
        } elseif(0 && $this->stor->getFilesNum($this->domain, $this->file) > 0){
            $this->statinfo_init(false);
            return $this->stat;
        } else {
            return false;
        }
    }
    
    private function statinfo_init($is_file=true){

        $this->stat = array();
        $this->stat['dev']   = $this->stat[0]  = 0x8003;
        $this->stat['ino']   = $this->stat[1]  = 0;
        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid']   = $this->stat[4] = 0;
        $this->stat['gid']   = $this->stat[5] = 0;
        $this->stat['rdev']  = $this->stat[6] = 0;
        $this->stat['size']  = $this->stat[7] = 0;
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = 0;
        $this->stat['ctime'] = $this->stat[10] = 0;
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks']  = $this->stat[12] = 0;
                
        $this->stat['dev']   = $this->stat[0] = 0x8003;
        $this->stat['ino']   = $this->stat[1] = crc32(SAE_APPNAME . '/' . $this->kvkey);
        $this->stat['mode']  = $this->stat[2] = $is_file?($this->file_mode):($this->dir_mode);
        $this->stat['mtime'] = $this->stat[9] = time();
        if(is_array($is_file)){
            if(isset($is_file['size']))  $this->stat['size']  = $this->stat[7] = $is_file['size'];
            if(isset($is_file['mtime'])) $this->stat['mtime'] = $this->stat[9] = $is_file['mtime'];
            if(isset($is_file['ctime'])) $this->stat['ctime'] = $this->stat[10]= $is_file['ctime'];
        }
    }
    
    public function mkdir($path, $mode, $options) {
        return true;
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = rtrim(ltrim(strstr($path, $pathinfo['path']), '/\\'),'/.');
        // TODO
        return true;
    }
    public function rmdir($path, $options) {
        return true;
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = rtrim(ltrim(strstr($path, $pathinfo['path']), '/\\'),'/.');
        $this->stor->deleteFolder($this->domain, $this->file);
        return true;
    }
}
