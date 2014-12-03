<?php
if(!@include(__DIR__.'/Surikat/Loader.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/Loader.php');
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);