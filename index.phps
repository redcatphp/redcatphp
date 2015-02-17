<?php
#Copy this file to ../DOCUMENT_ROOT to start a new project

if(!@include(__DIR__.'/Surikat/php/Loader.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/php/Loader.php');

//Registry::instance('Dev\Level')->PHP();
//Registry::instance('Dev\Level')->CONTROL();
//Registry::instance('Dev\Level')->VIEW();
//Registry::instance('Dev\Level')->PRESENT();
//Registry::instance('Dev\Level')->MODEL();
//Registry::instance('Dev\Level')->DB();
//Registry::instance('Dev\Level')->DBSPEED();
//Registry::instance('Dev\Level')->SQL();
//Registry::instance('Dev\Level')->ROUTE();
//Registry::instance('Dev\Level')->I18N();
//Registry::instance('Dev\Level')->IMG();
//Registry::instance('Dev\Level')->SERVER();
//Registry::instance('Dev\Level')->NAV();
Registry::instance('Dev\Level')->STD();
Registry::instance('Dev\Level')->CSS();
Registry::instance('Dev\Level')->JS();

(new Controller\Application())->run(@$_SERVER['PATH_INFO']);