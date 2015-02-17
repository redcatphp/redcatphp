<?php namespace Surikat;
use Surikat\Autoload\SuperNamespace;
use Surikat\Dependency\Container;
require __DIR__.'/Autoload/SuperNamespace.php';
require __DIR__.'/Dependency/Injector.php';
require __DIR__.'/Dependency/Facade.php';
require __DIR__.'/Dependency/Container.php';
$autoload = new SuperNamespace();
Container::set('Autoload',$autoload);
if(!defined('SURIKAT_PATH'))
	define('SURIKAT_PATH',getcwd().'/');
if(!defined('SURIKAT_SPATH'))
	define('SURIKAT_SPATH',realpath(__DIR__.'/..').'/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');
$autoload->addNamespace('',SURIKAT_PATH.'php');
$autoload->addSuperNamespace('Surikat',SURIKAT_SPATH.'php');
set_include_path('.');
spl_autoload_register($autoload);