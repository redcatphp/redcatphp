<?php namespace Surikat;

//error_reporting(-1);
//ini_set('display_startup_errors',true);
//ini_set('display_errors','stdout');

use Surikat\DependencyInjection\Container;
use Surikat\Autoload\SuperNamespace;

require __DIR__.'/DependencyInjection/Convention.php';
require __DIR__.'/DependencyInjection/Mutator.php';
require __DIR__.'/DependencyInjection/MutatorMagicProperty.php';
require __DIR__.'/DependencyInjection/MutatorProperty.php';
require __DIR__.'/DependencyInjection/MutatorMagicCall.php';
require __DIR__.'/DependencyInjection/MutatorCall.php';
require __DIR__.'/DependencyInjection/MutatorMagic.php';
require __DIR__.'/DependencyInjection/Facade.php';
require __DIR__.'/DependencyInjection/Container.php';

require __DIR__.'/Autoload/SuperNamespace.php';

require __DIR__.'/constants.php';

global $SURIKAT;
$SURIKAT = Container::get();
$SURIKAT->Autoload = new SuperNamespace();
$SURIKAT->Autoload->addNamespace('',SURIKAT_PATH.'php');
$SURIKAT->Autoload->addSuperNamespace('Surikat',SURIKAT_SPATH.'php');
$SURIKAT->Autoload->splRegister();