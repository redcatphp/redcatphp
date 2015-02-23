<?php #Copy this file to ../DOCUMENT_ROOT to start a new project
if(!@include(__DIR__.'/Surikat/php/bootstrap.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/php/bootstrap.php');
use DependencyInjection\Container;
//Container::get('Dev\Level')->PHP();
//Container::get('Dev\Level')->CONTROL();
//Container::get('Dev\Level')->VIEW();
//Container::get('Dev\Level')->PRESENT();
//Container::get('Dev\Level')->MODEL();
//Container::get('Dev\Level')->DB();
//Container::get('Dev\Level')->DBSPEED();
//Container::get('Dev\Level')->SQL();
//Container::get('Dev\Level')->ROUTE();
//Container::get('Dev\Level')->I18N();
//Container::get('Dev\Level')->IMG();
//Container::get('Dev\Level')->SERVER();
//Container::get('Dev\Level')->NAV();
Container::get('Dev\Level')->STD();
Container::get('Dev\Level')->CSS();
Container::get('Dev\Level')->JS();
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);