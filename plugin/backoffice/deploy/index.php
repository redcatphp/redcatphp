<?php
use Git\GitDeploy\GitDeploy;

set_time_limit(0);
$this->Http_Request()->nocacheHeaders();
ob_implicit_flush(true);
@ob_end_flush();

echo '<pre>';
GitDeploy::factory(SURIKAT_PATH)
	->maintenanceOn()
	->getChild(SURIKAT_SPATH)
		->deploy()
	->getOrigin()
		->autocommit()
		->deploy()
		->maintenanceOff()
;
echo '</pre>';