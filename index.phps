<?php #Copy this file to ../DOCUMENT_ROOT to start a new project
if(!@include(__DIR__.'/Surikat/php/bootstrap.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/php/bootstrap.php');
use ObjexLoader\Container;
Container::get()->Dev_Level
	->PHP()
	//->CONTROL()
	//->VIEW()
	//->PRESENT()
	//->MODEL()
	//->DB()
	//->DBSPEED()
	//->SQL()
	//->ROUTE()
	//->I18N()
	//->IMG()
	//->SERVER()
	//->NAV()
	->STD()
	->CSS()
	->JS()
;

Container::get()->KungFu_Cms_Dispatcher_Index->runFromGlobals();