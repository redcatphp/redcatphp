<?php
use Surikat\Git\GitDeploy\GitDeploy;

set_time_limit(0);
$this->HTTP_Request()->nocacheHeaders();
ob_implicit_flush(true);
@ob_end_flush();

echo '<pre>';
GitDeploy::factory(SURIKAT_SPATH)
	->maintenanceOn()
	->getParent()
		->deploy()
	->getOrigin()
		->maintenanceOff()
;
echo '</pre>';