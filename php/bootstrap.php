<?php namespace Surikat;

//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

use Surikat\DependencyInjection\Container;
use Surikat\Autoload\SuperNamespace;

require __DIR__.'/Component/DependencyInjection/Convention.php';
require __DIR__.'/Component/DependencyInjection/Mutator.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicProperty.php';
require __DIR__.'/Component/DependencyInjection/MutatorProperty.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagicCall.php';
require __DIR__.'/Component/DependencyInjection/MutatorCall.php';
require __DIR__.'/Component/DependencyInjection/MutatorMagic.php';
require __DIR__.'/Component/DependencyInjection/Facade.php';
require __DIR__.'/Component/DependencyInjection/Container.php';

require __DIR__.'/Component/Autoload/SuperNamespace.php';

require __DIR__.'/constants.php';

global $SURIKAT;
$SURIKAT = Container::get();
$SURIKAT->Autoload = (new SuperNamespace())
	->addNamespace('',SURIKAT_PATH.'php')
	->addSuperNamespace('Surikat',SURIKAT_SPATH.'php/Component')
	->splRegister()
;