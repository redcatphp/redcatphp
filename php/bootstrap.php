<?php
error_reporting(-1);
ini_set('display_startup_errors',true);
ini_set('display_errors','stdout');
require __DIR__.'/Unit/AutoloadPsr4.php';
define('SURIKAT',realpath(__DIR__.'/..').'/');
define('SURIKAT_CWD',getcwd().'/');
global $SURIKAT;
$SURIKAT['Autoloader'] = new Unit\AutoloadPsr4();
$SURIKAT['Autoloader']->addNamespace('',[
	getcwd().'/php',
	__DIR__
])->splRegister();