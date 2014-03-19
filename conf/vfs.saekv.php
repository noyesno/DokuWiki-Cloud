<?php

#---------------------------------------------------------#
$conf['conf']        = 'saekv://dokuwiki/conf';
$conf['savedir']     = 'saekv://dokuwiki/data';            //where to store all the files
#---------------------------------------------------------#
$conf['datadir']     = 'saekv://dokuwiki/pages';
$conf['olddir']      = 'saekv://dokuwiki/attic/pages';  // binary
$conf['mediaolddir'] = 'saekv://dokuwiki/attic/media';
$conf['metadir']     = 'saekv://dokuwiki/meta/pages';
$conf['mediametadir']= 'saekv://dokuwiki/meta/media';
$conf['cachedir']    = 'saekv://dokuwiki/cache';       // binary
$conf['indexdir']    = 'saekv://dokuwiki/index';       // binary
$conf['lockdir']     = 'saekv://dokuwiki/memory/locks';
$conf['mediadir']    = 'saestor://dokuwiki/data/media';
$conf['tmpdir']      = SAE_TMP_PATH; // 'data/tmp'; // SAE_TMP_PATH


## User Defined (performance enhance for SAE) ##
$conf['media']['fetch']  = sprintf('http://%s-dokuwiki.stor.sinaapp.com/data/media', $_SERVER['HTTP_APPNAME']);
$conf['media']['detail'] = null;
