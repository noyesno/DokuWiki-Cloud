<?php

//if(!defined('DOKU_FARMDIR')) define('DOKU_FARMDIR', '/var/www/farm');
//include(fullpath(dirname(__FILE__)).'/farm.php');
//$config_cascade = array(
//);

global $conf; $conf = array();
include(DOKU_INC.'conf/local.protected.php');
include(DOKU_INC.'inc/ext/boot.php');
if(!defined('DOKU_LOCAL')) define('DOKU_LOCAL', $conf['conf'].'/');

if(!file_exists(DOKU_LOCAL.'local.php')){
    header('Location: /install.php');
    exit(0);
}

$config_cascade = array(
    'main' => array(
        'default'   => array(DOKU_INC.'conf/dokuwiki.php', DOKU_INC.'conf/local.protected.php'),
        'local'     => array(DOKU_LOCAL.'local.php'),
        'protected' => array(DOKU_INC.'conf/local.protected.php'),
    ),
    'acronyms'  => array(
        'default'   => array(DOKU_INC.'conf/acronyms.conf'),
        'local'     => array(),
    ),
    'entities'  => array(
        'default'   => array(DOKU_INC.'conf/entities.conf'),
        'local'     => array(),
    ),
    'interwiki' => array(
        'default'   => array(DOKU_INC.'conf/interwiki.conf'),
        'local'     => array(),
    ),
    'license' => array(
        'default'   => array(DOKU_INC.'conf/license.php'),
        'local'     => array(),
    ),
    'mediameta' => array(
        'default'   => array(DOKU_INC.'conf/mediameta.php'),
        'local'     => array(),
    ),
    'mime'      => array(
        'default'   => array(DOKU_INC.'conf/mime.conf'),
        'local'     => array(),
    ),
    'scheme'    => array(
        'default'   => array(DOKU_INC.'conf/scheme.conf'),
        'local'     => array(),
    ),
    'smileys'   => array(
        'default'   => array(DOKU_INC.'conf/smileys.conf'),
        'local'     => array(),
    ),
    'wordblock' => array(
        'default'   => array(DOKU_INC.'conf/wordblock.conf'),
        'local'     => array(),
    ),
    'acl'       => array(
        'default'   => DOKU_LOCAL.'acl.auth.php',
    ),
    'plainauth.users' => array(
        'default'   => DOKU_LOCAL.'users.auth.php',
    ),
    'plugins' => array( // needed since Angua
        'default'   => array(DOKU_INC.'conf/plugins.php'),
        'local'     => array(DOKU_LOCAL.'plugins.local.php'),
        'protected' => array(DOKU_INC.'conf/plugins.required.php'),
    ),
    'userstyle' => array(
        'default' => DOKU_INC.'conf/userstyle.css', // 'default' was renamed to 'screen' on 2011-02-26, so will be deprecated in the next version
        'screen'  => DOKU_INC.'conf/userstyle.css',
        'rtl'     => DOKU_INC.'conf/userrtl.css', // deprecated since version after 2012-04-09
        'print'   => DOKU_INC.'conf/userprint.css',
        'feed'    => DOKU_INC.'conf/userfeed.css',
        'all'     => DOKU_INC.'conf/userall.css',
    ),
    'userscript' => array(
        'default' => DOKU_INC.'conf/userscript.js'
    ),
);
