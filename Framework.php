<?php
use Surikat\Autoloader;
use Surikat\Dev;
if(!defined('SURIKAT_PATH'))
	define('SURIKAT_PATH',getcwd().'/');
if(!defined('SURIKAT_SPATH'))
	define('SURIKAT_SPATH',__DIR__.'/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
set_include_path('.');
require __DIR__.'/Factory.php';
require __DIR__.'/Autoloader.php';
Autoloader::addNamespace('',SURIKAT_PATH);
Autoloader::addSuperNamespace('Surikat',SURIKAT_SPATH);