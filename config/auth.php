<?php
use Core\Domain;
return [
	'site_name'=> 'The Lab',
	'site_url'=> rtrim(Domain::getBaseHref(),'/'),
	'site_email'=> 'no-reply@lab.cuonic.com',
	'cookie_name'=> 'authID',
	'cookie_path'=> '/',
	'cookie_domain'=> NULL,
	'cookie_secure'=> '0',
	'cookie_http'=> '0',
	'cookie_remember'=> '+1 month',
	'bcrypt_cost'=> '10',
	'tableRequests'=> 'requests',
	'tableUsers'=> 'users',
];