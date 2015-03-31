<?php
require __DIR__.'/Component/DependencyInjection/Mutator.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicProperty.php';
require __DIR__.'/Component/DependencyInjection/MutatorProperty.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicCall.php';
require __DIR__.'/Component/DependencyInjection/MutatorCall.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagic.php';
require __DIR__.'/Component/DependencyInjection/Registry.php';
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