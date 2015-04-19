<?php
//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');
require __DIR__.'/Component/DependencyInjection/MutatorTrait.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicPropertyTrait.php';
require __DIR__.'/Component/DependencyInjection/MutatorPropertyTrait.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicCallTrait.php';
require __DIR__.'/Component/DependencyInjection/MutatorCallTrait.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicTrait.php';
require __DIR__.'/Component/DependencyInjection/RegistryTrait.php';
require __DIR__.'/Component/DependencyInjection/Container.php';
require __DIR__.'/Component/Autoload/Psr4.php';
require __DIR__.'/constants.php';
global $SURIKAT;
$SURIKAT = Surikat\Component\DependencyInjection\Container::get();
$SURIKAT->Autoload_Psr4([
	''			=> getcwd().'/php',
	'Surikat'	=> __DIR__,
])->splRegister();
$SURIKAT->setDependencyFactory([$SURIKAT->Config_DependencyInjection,'objectFactory']);