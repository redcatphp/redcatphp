<?php
return [
	'$'=>[
		'dev'=>[
			'php'	=>true,
			'tml'	=>true,
			'js'	=>true,
			'css'	=>true,
			'img'	=>false,
			'chrono'=>true,
			'l10n'	=>true,
			'db'	=>false,
		],
		'superRoot'=>[
			'login'=>'root',
			'password'=>'root',
		],
		'databaseMap'=>[],
		'l10n'=>false,
		'l10nDefault'=>'en',
		'versioning'=>'new:RedCat\Plugin\Versioning\Number',
	],
	'rules'=>[
		'RedCat\DataMap\Bases'	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'$debug'=>'dev.db',
				'entityClassPrefix'=>'EntityModel\\',
				'entityClassDefault'=>'stdClass',
			],
		],
		'RedCat\Identify\Auth'=>[
			'construct'=>[
				'$rootLogin' => 'superRoot.login',
				'$rootPassword' => 'superRoot.password',
				'rootName'	=> 'Developer',
				'siteLoginUri' => 'Login',
				'siteActivateUri' => 'Signin',
				'siteResetUri' => 'Signin',
				'tableUsers' => 'user',
				'tableRequests' => 'request',
				'algo' => PASSWORD_DEFAULT,
				'mailSendmail' => true,
				'mailHost' => null,
				'mailUsername' => null,
				'mailPassword' => null,
				'mailPort' => 25,
				'mailSecure' => 'tls',
			],
		],
		'RedCat\Identify\Session'=>[
			'shared'=>true,
			'newInstances'=>'RedCat\Identify\SessionHandlerInterface',
			'substitutions'=>[
				'RedCat\Identify\SessionHandlerInterface'=>'RedCat\Identify\SessionHandler',
			],
			'construct'=>[
				'name'=>'surikat',
				'saveRoot'=>REDCAT_CWD.'.tmp/sessions/',
			],
		],
		'RedCat\Plugin\FrontController\FrontOffice'=>[
			'construct'=>[
				'$l10n'=>'l10n',
			],
		],
		'RedCat\Plugin\RouteMatch\ByTmlL10n'=>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		'RedCat\Plugin\Templix\TemplixL10n'=>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		'RedCat\Localize\Translator'=>[
			'shared'=>true,
			'construct'=>[
				'timezone'=>'Europe/Paris',
				'$dev'=>'dev.l10n',
			],
		],
		'RedCat\Debug\ErrorHandler'=>[
			'shared'=>true,
		],
		'RedCat\Templix\Templix'=>[
			'construct'=>[
				'$devTemplate'=>'dev.tml',
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
				'$devImg'=>'dev.img',
			],
		],
		'RedCat\Plugin\Templix\Templix'=>[
			'call'=>[
				'addPluginPrefix'=>'RedCat\Plugin\Templix\Markup\\',
			],
		],
		'RedCat\Plugin\FrontController\Synaptic'=>[
			'construct'=>[
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
			],
		],
		'RedCat\Stylize\Server' => [
			'construct'=>[
				'$cache'=>'dev.css',
			],
		],
		'RedCat\Route\Url' => [
			'shared'=>true,
		],
		'Banago\PHPloy\PHPloy' => [
			'construct'=>[
				'$map' => 'gitDeploy',
			],
		],
	],
];