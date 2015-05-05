<?php
use Git\GitDeploy\GitDeploy;

set_time_limit(0);
$this->FluxServer_Http_Request()->nocacheHeaders();
ob_implicit_flush(true);
@ob_end_flush();

echo '<pre>';
GitDeploy::factory()
	->maintenanceOn()
	->deploy(SURIKAT_CWD)
	->maintenanceOff()
;
echo '</pre>';