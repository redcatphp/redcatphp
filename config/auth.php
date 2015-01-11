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
	'site_key'=> 'fghuior.)/%dgdhjUyhdbv7867HVHG7777ghg',
	'cookie_remember'=> '+1 month',
	'cookie_forget'=> '+30 minutes',
	'bcrypt_cost'=> '10',
	'table_attempts'=> 'attempts',
	'table_requests'=> 'requests',
	'table_sessions'=> 'sessions',
	'table_users'=> 'users',
	'site_timezone'=> 'Europe/Paris'
];