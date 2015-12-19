<?php
define('REDCAT',__DIR__.'/');
define('REDCAT_CWD',getcwd().'/');
require_once __DIR__.'/vendor/autoload.php';
return RedCat\Framework\App::bootstrap();