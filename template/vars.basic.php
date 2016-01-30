<?php
global $redcat;
return [
	'BASE_HREF'		=> $redcat(RedCat\Route\Url::class)->getBaseHref(),
	'redcat'		=> $redcat,
];