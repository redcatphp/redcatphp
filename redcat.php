<?php
define('REDCAT',__DIR__.'/');
define('REDCAT_CWD',getcwd().'/');
$loader = require __DIR__.'/packages/autoload.php';
$redcat = RedCat\Framework\App::bootstrap($loader);
return $redcat;