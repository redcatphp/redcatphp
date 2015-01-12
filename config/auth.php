<?php
use Core\Domain;
return [
	'site_name'=> 'The Lab',
	'site_url'=> rtrim(Domain::getBaseHref(),'/'),
	'site_email'=> 'no-reply@lab.cuonic.com',
	'bcrypt_cost'=> '10',
	'tableRequests'=> 'requests',
	'tableUsers'=> 'users',
];