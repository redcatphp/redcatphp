<?php
#Copy this file to ../DOCUMENT_ROOT to start a new project

if(!@include(__DIR__.'/Surikat/Loader.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/Loader.php');
use Core\Dev;
Dev::level(	
	//Dev::NO
	//|Dev::PHP
	//|Dev::CONTROL
	//|Dev::VIEW
	//|Dev::PRESENT
	//|Dev::MODEL
	//|Dev::MODEL_SCHEMA
	//|Dev::ROUTE
	//|Dev::I18N
	//|Dev::IMG
	//|Dev::SERVER //PHP+CONTROL+VIEW+PRESENT+MODEL+MODEL_SCHEMA+I18N
	//|Dev::NAV //URI+JS+CSS+IMG
	Dev::STD //PHP+CONTROL+VIEW+PRESENT+MODEL_SCHEMA+I18N
	|Dev::JS
	|Dev::CSS
);
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);