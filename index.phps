<?php #Copy this file to ../DOCUMENT_ROOT to start a new project
if(!@include(__DIR__.'/Surikat/php/bootstrap.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/php/bootstrap.php');
use ObjexLoader\Container;
Container::get()->KungFu_Cms_Dispatcher_Index->runFromGlobals();