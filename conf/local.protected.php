<?php

$conf['debug'] = 0;

// $conf['title'] = 'DokuWiki';
// $conf['lang'] = 'zh';
// $conf['license'] = 'cc-by-sa';
// $conf['useacl'] = 1;
// $conf['superuser'] = '@admin';


$conf['updatecheck'] = 0;
$conf['userewrite']  = 1;                //this makes nice URLs: 0: off 1: .htaccess 2: internal
$conf['useslash']    = 1;                //use slash instead of colon? only when rewrite is on
$conf['htmlok']      = 0;                //may raw HTML be embedded? This may break layout and XHTML validity 0|1
$conf['phpok']       = 0;                //may PHP code be embedded? Never do this on the internet! 0|1
$conf['mediarevisions'] = 0;             //enable/disable media revisions
$conf['fetchsize']   = 0;                //maximum size (bytes) fetch.php may download from extern, disabled by default
$conf['dnslookups']  = 0;                //disable to disallow IP to hostname lookups
$conf['timezone']    = 'Asia/Shanghai';

$conf['media']['fetch']  = null;
$conf['media']['detail'] = null;

$conf['logo'] = array('images/logo.png', ':wiki:logo.png');

$conf['favicon']['favicon'] =  array('images/favicon.ico', ':favicon.ico');
$conf['favicon']['mobile']  =  array('images/apple-touch-icon.png', ':apple-touch-icon.png');
$conf['favicon']['generic'] =  array('images/favicon.svg', ':favicon.svg');


$conf['vfs.type']   = 'sqlite';
require(dirname(__FILE__).'/vfs.'.$conf['vfs.type'].'.php');
