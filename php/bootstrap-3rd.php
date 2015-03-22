<?php
require __DIR__.'/bootstrap.php';
$SURIKAT->Autoload_Psr4
	->addNamespace('Zend',SURIKAT_SPATH.'php-3rd/Zend')
	->addNamespace('Symfony',SURIKAT_SPATH.'php-3rd/Symfony')
;