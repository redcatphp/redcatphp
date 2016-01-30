<?php
define('REDCAT',__DIR__.'/');
define('REDCAT_CWD',getcwd().'/');
require_once __DIR__.'/packages/autoload.php';
return $redcat = RedCat\Framework\App::bootstrap();