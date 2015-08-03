<?php
set_time_limit(0);

//nocache headers
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" );
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: -1");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Cache-Control: no-store, no-cache, must-revalidate");

ob_implicit_flush(true);
@ob_end_flush();

echo '<pre>';
Unit\Di::make('Git\GitDeploy\GitDeploy',[SURIKAT_CWD])
	->maintenanceOn()
	->autocommit()
	->deploy()
	->maintenanceOff()
;
echo '</pre>';