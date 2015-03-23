<?php
require __DIR__.'/bootstrap.php';
$SURIKAT->Autoload_Psr4
	->addNamespace('Zend',__DIR__.'/ThirdParty/Zend')
	->addNamespace('Symfony',__DIR__.'/ThirdParty/Symfony')
;