<?php

#---------------------------------------------------------#
$conf['vfs.sqlite.db']   = DOKU_INC.'data/dokuwiki.sqlite';
#---------------------------------------------------------#
$conf['conf']        = 'sqlite://conf';         # DOKU_INC.'conf';
$conf['savedir']     = 'sqlite://data';            //where to store all the files
#---------------------------------------------------------#
$conf['datadir']     = 'sqlite://pages';
$conf['olddir']      = 'sqlite://attic/pages';  // binary
$conf['mediaolddir'] = 'sqlite://attic/media';
$conf['metadir']     = 'sqlite://meta/pages';
$conf['mediametadir']= 'sqlite://meta/media';
$conf['cachedir']    = 'sqlite://cache';       // binary
$conf['indexdir']    = 'sqlite://index';       // binary
$conf['lockdir']     = 'sqlite://memory/locks';// memory/locks 
$conf['mediadir']    = 'data/media';
$conf['tmpdir']      = DOKU_INC.'data/tmp';
