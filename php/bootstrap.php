<?php
error_reporting(-1);
ini_set('display_startup_errors',true);
ini_set('display_errors','stdout');
require __DIR__.'/DependencyInjection/MutatorTrait.php';
require __DIR__.'/DependencyInjection/MutatorMagicPropertyTrait.php';
require __DIR__.'/DependencyInjection/MutatorPropertyTrait.php';
require __DIR__.'/DependencyInjection/MutatorMagicCallTrait.php';
require __DIR__.'/DependencyInjection/MutatorCallTrait.php';
require __DIR__.'/DependencyInjection/MutatorMagicTrait.php';
require __DIR__.'/DependencyInjection/RegistryTrait.php';
require __DIR__.'/DependencyInjection/Container.php';
require __DIR__.'/Autoload/Psr4.php';
define('SURIKAT_CWD',getcwd().'/');
define('SURIKAT',realpath(__DIR__.'/..').'/');
global $SURIKAT;
$SURIKAT = DependencyInjection\Container::get();
$SURIKAT->Autoload_Psr4->addNamespaces([
	''			=> [
		getcwd().'/php',
		__DIR__
	]
])->splRegister();
$SURIKAT->setDependencyFactory([$SURIKAT->DependencyInjection_ConfigInjector,'objectFactory']);