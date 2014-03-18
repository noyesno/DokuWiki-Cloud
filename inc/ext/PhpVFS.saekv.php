<?php

/***************************************************
* Implement WrapperInterface for SaeKV
***************************************************/
class PhpVFS_SaeKV {

   public function __construct(){
     $this->init();
   }

   function init(){
      $this->kv = new SaeKV();
      $this->kv->init();
   }

   private function unpack_stat( $text ) {
        $arr = unpack("L5", substr($text,0,20));
        // check if valid
        if ($arr[1] != 0x8003) return false;
        if ( !in_array($arr[2], array( 040777, 0100777))) return false;
        // if ( $arr[4] > time() ) return false;
        // if ( $arr[5] > time() ) return false;

        return array('dev'=>$arr[1], 'mode'=>$arr[2], 'size'=>$arr[3], 'mtime'=>$arr[4], 'ctime'=>$arr[5]);
    }

    private function pack_stat($stat) {
        $text = pack("LLLLL", 0x8003, $stat['mode'], $stat['size'], $stat['mtime'], $stat['ctime']);
        return $text;
    }

    function get($key, &$data='', &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');
      $key = trim("$domain/$path",'/');
      //TODO: $key = trim(substr($key, strpos($key,':')+2),'/.');
      $value = $this->kv->get($key);

      if ( $value !== false &&  ($stat = $this->unpack_stat($value)) !== false ) {
         $data = substr($value,20); $stat = $stat;
         return $data;
      } else {
         return false;
      }
    }

    function set($key, $data, &$stat=array()){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');
      $key = trim("$domain/$path",'/');
      $value = $this->pack_stat($stat).$data;
      $ok = $this->kv->set($key, $value);
      if(!$ok){
        nice_die("KV set FAIL");
      }
      return $ok;
    }

    function del($key){
      $pathinfo = parse_url($key); $domain = $pathinfo['host']; $path = trim($pathinfo['path'], '/');
      $key = trim("$domain/$path",'/');
      $ok = $this->kv->delete($key);
      return $ok;
    }

    function scandir($dir){
      return false;
    }
}

