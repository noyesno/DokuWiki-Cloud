<?php

define('IS_SAE', function_exists('sae_debug'));
define('IS_DEBUG', !empty($_GET['_debug']));

include(DOKU_INC.'inc/ext/PhpVFS.php');

/*
include(DOKU_INC.'inc/ext/PhpVFS.saekv.php');
include(DOKU_INC.'inc/ext/PhpVFS.saestor.php');
DokuWikiFileSystemWrapper::register('saekv',   'PhpVFS_SaeKV',    true);
DokuWikiFileSystemWrapper::register('saestor', 'PhpVFS_SaeStore', true);

include(DOKU_INC.'inc/ext/PhpVFS.mysql.php');
DokuWikiFileSystemWrapper::register('mysql',   'PhpVFS_Mysql', true);
*/

include(DOKU_INC.'inc/ext/PhpVFS.sqlite.php');
PhpVFS::register('sqlite',   'PhpVFS_Sqlite', true);

function debug_log($msg=null, $force=false, $force_print=false){
  static $fout=null;
  #print $msg;return;
  if(!($force || IS_DEBUG)) return;
  if(is_null($msg)){
     if(!is_null($fout)) fclose($fout);
     return;
  }
  if($force_print){
    echo '<p>',$msg,"</p>\n";
    return;
  }
  if(IS_SAE){
    sae_set_display_errors(false);
    sae_debug($msg);
    sae_set_display_errors(true);
  }else{
    global $conf;
    if(is_null($fout)) $fout = fopen($conf['tmpdir'],'a');
    fputs($fout, "$msg\n");
  }
  if(!empty($_GET['_html'])){
    global $debug_log;
    $debug_log[] = $msg;
  }
}


// removes files and non-empty directories
function rrmdir($dir) {
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file)
    if ($file != "." && $file != "..") rrmdir("$dir/$file");
    rmdir($dir);
  } else if (is_file($dir)) unlink($dir);
}

// copies files and non-empty directories
function rcopy($src, $dst, $rmdir=false) {
  if ($rmdir && file_exists($dst)) rrmdir($dst);
  if (is_dir($src)) {
    mkdir($dst);
    $files = scandir($src);
    foreach ($files as $file)
    if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file");
  } else if (is_file($src)) copy($src, $dst);
}
