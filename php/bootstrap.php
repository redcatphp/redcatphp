<?php namespace Surikat;
use Surikat\Autoload\SuperNamespace;
require __DIR__.'/Autoload/SuperNamespace.php';
$autoloader = new SuperNamespace();
if(!defined('SURIKAT_PATH'))
	define('SURIKAT_PATH',getcwd().'/');
if(!defined('SURIKAT_SPATH'))
	define('SURIKAT_SPATH',realpath(__DIR__.'/..').'/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
$autoloader->addNamespace('',SURIKAT_PATH.'php');
$autoloader->addSuperNamespace('Surikat',SURIKAT_SPATH.'php');
set_include_path('.');
spl_autoload_register($autoloader);