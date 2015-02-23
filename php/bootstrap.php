<?php namespace Surikat;
use Surikat\Autoload\SuperNamespace;
use Surikat\DependencyInjection\Container;

error_reporting(-1);
ini_set('display_startup_errors',true);
ini_set('display_errors','stdout');

require __DIR__.'/Autoload/SuperNamespace.php';

require __DIR__.'/DependencyInjection/Mutator.php';
require __DIR__.'/DependencyInjection/MutatorProperty.php';
require __DIR__.'/DependencyInjection/MutatorCall.php';
require __DIR__.'/DependencyInjection/MutatorMagic.php';

require __DIR__.'/DependencyInjection/Facade.php';
require __DIR__.'/DependencyInjection/Container.php';

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