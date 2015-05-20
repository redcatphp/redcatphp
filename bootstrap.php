<?php
//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

define('SURIKAT',__DIR__.'/');
define('SURIKAT_CWD',getcwd().'/');

require __DIR__.'/php/Unit/Autoloader.php';
Unit\Autoloader::getInstance()->addNamespace('',[
		SURIKAT_CWD.'php',
		SURIKAT.'php'
])->splRegister();

global $SURIKAT;
$SURIKAT = new Unit\DiContainer;

$SURIKAT->loadXml(SURIKAT.'.config.xml');
$SURIKAT->loadXml(SURIKAT_CWD.'.config.xml');
