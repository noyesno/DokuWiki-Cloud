<!DOCTYPE html>
<html>
<head>
<title>SAE Service Admin</title>
</head>
<body>
<h1>SAE Service Admin</h1>

<h2>SAE Storage</h2>
<form method="post" action="">
    <select name="cmd">
        <option value="getfileslist">getfileslist</option>
        <option value="getdomfilelist">getdomfilelist</option>
        <option value="getdomcapacity">getdomcapacity</option>
        <option value="getfilesnum">getfilesnum</option>
    </select>
   Path/Prefix: <input type="text" name="path" value="<? echo empty($_POST['path'])?'data/media':$_POST['path']; ?>"/>
   Limit: <input type="number" name="limit" value="7" style="width:4em;text-align:right"/>
   Skip: <input type="number" name="skip" value="0" style="width:3em;text-align:right"/>
   Fold: <input type="checkbox" name="fold" value="1"/>
   Debug: <input type="checkbox" name="debug" value="1"/>
    <input type="hidden" name=".saestor" value="1"/>
    <button type="submit">Query Store</button>
</form>
<?php html_saestor(); ?>

<?php
$kv = new SaeKV();
$kv->init();
html_saekv();
?>

<h2>SAE KVDB</h2>
<form method="post" action="">
    <?php sae_kvdb_usage();?>
    <input type="text" name="prefix" value="dokuwiki/"/>
    <input type="submit" name=".submit" value="Empty"/>
    <input type="submit" name=".submit" value="List"/>
</form>
</body>
</html>


<?php
#---------------------------------------------------------------------------#

function sae_kvdb_usage(){
  global $kv;
  $ret = $kv->get_info();
  echo '<p>SAE KVDB Usage: ', $ret['total_size'],' Bytes, ',$ret['total_count'],' Files</p>';
}
function sae_kvdb_empty($prefix='dokuwiki/', $N=100){
  global $kv;
  $values = $kv->pkrget($prefix, $N, '');
  echo '<pre>';
  foreach($values as $key=>$data){
    $status = $kv->delete($key);
    if(!$status) echo "KVDB delete $key FAIL\n";
  }
  echo '</pre>';
  return count($values);
}

function sae_kvdb_ls($prefix='dokuwiki/', $N=100){
  global $kv;
  $values = $kv->pkrget($prefix, $N, '');
  echo "<h6>SAE KVDB Prefix: $prefix*</h6>";
  echo '<pre>';
  foreach($values as $key=>$data){
    echo "$key ",strlen($data),"\n";
  }
  echo '</pre>';
  return count($values);
}

function saestor_curl($url){
        $ch=curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPGET, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt( $ch, CURLOPT_USERAGENT, 'SAE Online Platform' );
        curl_setopt( $ch, CURLOPT_FORBID_REUSE, false);

        curl_setopt( $ch, CURLINFO_HEADER_OUT, true);
        curl_setopt( $ch, CURLOPT_HEADER, true);
        //curl_setopt( $ch, CURLOPT_NOBODY, true);

        $headers = array();
        $headers[] = "Connection: keep-alive";
        $headers[] = "Keep-Alive: 300";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response =curl_exec( $ch );
        $info     = curl_getinfo($ch);
        $header = substr($response, 0, $info['header_size']);
        //$body   = substr($response, -$info['download_content_length']);
        $body   = substr($response, $info['header_size']);

        if(!empty($_POST['debug'])){
            echo "<pre>Request:\n\n",$info['request_header'],'</pre>';
            echo "<pre>Response:\n\n",$header,'</pre>';
            echo "<pre>Body:\n\n",$body,'</pre>';
        }
        echo "<pre>Data:\n\n",var_export(json_decode($body, true),true),'</pre>';
        //var_dump($content, $info);
        curl_close($ch);

}

function saestor_build_url(){
    $optUrlList = array(
        "uploadfile"=>'?act=uploadfile&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
        "getdomfilelist"=>'?act=getdomfilelist&ak=_AK_&sk=_SK_&dom=_DOMAIN_&prefix=_PREFIX_&limit=_LIMIT_&skip=_SKIP_',
        "getfileattr"=>'?act=getfileattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attrkey=_ATTRKEY_',
        "getfilecontent"=>'?act=getfilecontent&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
        "delfile"=>'?act=delfile&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
        "delfolder"=>'?act=delfolder&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
        "getdomcapacity"=>'?act=getdomcapacity&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
        "setdomattr"=>'?act=setdomattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
        "setfileattr"=>'?act=setfileattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
        "getfilesnum"=>'?act=getfilesnum&ak=_AK_&sk=_SK_&dom=_DOMAIN_&path=_PATH_',
        "getfileslist"=>'?act=getfileslist&ak=_AK_&sk=_SK_&dom=_DOMAIN_&path=_PATH_&limit=_LIMIT_&skip=_SKIP_&fold=_FOLD_',
    );

	$key   = $_POST['cmd'];   #'getfileslist';
	$path  = $_POST['path'];  #'dokuwiki';
	$limit = $_POST['limit']; #10;]
	$skip  = $_POST['skip'];  #0;
	$fold  = empty($_POST['fold'])?0:$_POST['fold'];  #0;

	$access_key = '0xoynlynj3';
	$secret_key = '53lwj0ijwjlzkl5jw45j4l35z01ll2y1xzximjik';
	$host  = 'http://stor.sae.sina.com.cn/storageApi.php';
	$agent = 'SAE Online Platform';
	$domain = urlencode('dokuwiki-dokuwiki');


	$url=$host.str_replace(array('_AK_','_SK_','_DOMAIN_','_PATH_','_LIMIT_','_SKIP_','_FOLD_','_PREFIX_'),
						   array($access_key,$secret_key, $domain, $path, $limit, $skip, $fold,$path),
						   $optUrlList[$key]);

    //echo "\ncurl -A '$agent' '$url'\n";
    return $url;
}

function html_saestor(){
    if($_POST['_saestor']){
        $url = saestor_build_url();
        echo "<p>URL = $url</p>";
        echo '<div>';
        saestor_curl($url);
        echo '</div>';
    }
}

function html_saekv(){
    if(!empty($_GET['file'])){
      echo "<h6>File: ",$_GET['file'],"</h6>";
      echo '<div><textarea style="width:100%;height:9em">', htmlspecialchars(file_get_contents($_GET['file'])),'</textarea></div>';
    }

    if($_POST['_submit']=="Empty"){
      sae_kvdb_usage();
      for($i=0, $n=1;$i<7 && $n>0;$i++){
        $n = sae_kvdb_empty($_POST['prefix']);
      }
    }elseif($_POST['_submit']=="List"){
        sae_kvdb_ls($_POST['prefix']);
    }
}
