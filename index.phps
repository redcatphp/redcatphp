<?php
if(!@include(__DIR__.'/Surikat/Loader.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/Loader.php');
use Core\Dev;
Dev::level(
	//Dev::CONTROL
	Dev::STD
	//|Dev::URI
	//|Dev::MODEL
	|Dev::CSS
	|Dev::JS
	//|Dev::IMG
);
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);