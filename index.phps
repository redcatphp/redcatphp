<?php
#Copy this file to ../DOCUMENT_ROOT to start a new project

if(!@include(__DIR__.'/Surikat/Loader.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/Loader.php');
use Core\Dev;
Dev::level(
	//Dev::CONTROL
	Dev::STD
	//|Dev::ROUTE
	//|Dev::MODEL
	|Dev::CSS
	|Dev::JS
	//|Dev::IMG
);
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);