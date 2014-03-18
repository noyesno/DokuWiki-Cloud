<?php

#---------------------------------------------------------#
$conf['mysql.user'] = SAE_MYSQL_USER;    # = SAE_ACCESSKEY
$conf['mysql.pass'] = SAE_MYSQL_PASS;    # = SAE_SECRETKEY
$conf['mysql.host'] = SAE_MYSQL_HOST_M;
$conf['mysql.port'] = SAE_MYSQL_PORT;
$conf['mysql.db']   = SAE_MYSQL_DB;
$conf['mysql.salve']= SAE_MYSQL_HOST_S;
#---------------------------------------------------------#
$conf['conf']        = 'mysql://conf';         # DOKU_INC.'conf';
$conf['savedir']     = 'mysql://data';            //where to store all the files
#---------------------------------------------------------#
$conf['datadir']     = 'mysql://pages';
$conf['olddir']      = 'mysql://attic/pages';  // binary
$conf['mediaolddir'] = 'mysql://attic/media';
$conf['metadir']     = 'mysql://meta/pages';
$conf['mediametadir']= 'mysql://meta/media';
$conf['cachedir']    = 'mysql://cache';       // binary
$conf['indexdir']    = 'mysql://index';       // binary
$conf['lockdir']     = 'mysql://memory/locks';
$conf['mediadir']    = 'saestor://dokuwiki/data/media';
$conf['tmpdir']      = SAE_TMP_PATH ;
