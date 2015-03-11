<?php #Copy this file to ../DOCUMENT_ROOT to start a new project
if(!@include(__DIR__.'/Surikat/php/bootstrap.php'))
	symlink('../Surikat','Surikat')&&include('Surikat/php/bootstrap.php');

use DependencyInjection\Container;
global $SURIKAT;
$SURIKAT = Container::get();

//$SURIKAT->Dev_Level->PHP();
//$SURIKAT->Dev_Level->CONTROL();
//$SURIKAT->Dev_Level->VIEW();
//$SURIKAT->Dev_Level->PRESENT();
//$SURIKAT->Dev_Level->MODEL();
//$SURIKAT->Dev_Level->DB();
//$SURIKAT->Dev_Level->DBSPEED();
//$SURIKAT->Dev_Level->SQL();
//$SURIKAT->Dev_Level->ROUTE();
//$SURIKAT->Dev_Level->I18N();
//$SURIKAT->Dev_Level->IMG();
//$SURIKAT->Dev_Level->SERVER();
//$SURIKAT->Dev_Level->NAV();
$SURIKAT->Dev_Level->STD();
$SURIKAT->Dev_Level->CSS();
$SURIKAT->Dev_Level->JS();

$SURIKAT->Dispatcher_Index->runFromGlobals();