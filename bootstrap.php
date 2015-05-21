<?php
//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

define('SURIKAT',__DIR__.'/');
define('SURIKAT_CWD',getcwd().'/');

require_once __DIR__.'/php/Unit/Autoloader.php';
Unit\Autoloader::getInstance()->addNamespace('',[
		SURIKAT_CWD.'php',
		SURIKAT.'php'
])->splRegister();

global $SURIKAT;
$SURIKAT = Unit\DiContainer::getInstance();
$SURIKAT->loadXml(SURIKAT.'.config.xml');
$SURIKAT->loadXml(SURIKAT_CWD.'.config.xml');