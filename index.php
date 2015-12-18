<?php
//define('REDCAT_FREEZE_DI',true);
$redcat = require_once 'redcat.php';
$redcat->create('RedCat\Plugin\FrontController\FrontOffice')->runFromGlobals();