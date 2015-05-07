<?php
error_reporting(-1);
ini_set('display_startup_errors',true);
ini_set('display_errors','stdout');
require __DIR__.'/ObjexLoader/MutatorTrait.php';
require __DIR__.'/ObjexLoader/MutatorMagicPropertyTrait.php';
require __DIR__.'/ObjexLoader/MutatorPropertyTrait.php';
require __DIR__.'/ObjexLoader/MutatorMagicCallTrait.php';
require __DIR__.'/ObjexLoader/MutatorCallTrait.php';
require __DIR__.'/ObjexLoader/MutatorMagicTrait.php';
require __DIR__.'/ObjexLoader/RegistryTrait.php';
require __DIR__.'/ObjexLoader/Container.php';
require __DIR__.'/Unit/AutoloadPsr4.php';
define('SURIKAT',realpath(__DIR__.'/..').'/');
define('SURIKAT_CWD',getcwd().'/');
use ObjexLoader\Container;
Container::get()->Unit_AutoloadPsr4->addNamespace('',[
	getcwd().'/php',
	__DIR__
])->splRegister();
Container::get()->setDependencyFactory([
	Container::get()->_ConfigInjector,
	'objectFactory'
]);