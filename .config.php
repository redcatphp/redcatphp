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
		'databaseMap'=>[
			0 => [
				'type'=>'sqlite',
				'file'=>SURIKAT_CWD.'.data/db.sqlite',
				'entityClassPrefix'=>['EntityModel\\'],
				'entityClassDefault'=>'stdClass',
				'primaryKey'=>'id',
				'uniqTextKey'=>'uniq',
				'createDb'=>true,
			],
			'translation' => [
				'type'			=>'sqlite',
				'file'			=>SURIKAT_CWD.'.data/db.translation.sqlite',
			],
		],
		'l10n'=>false,
		'l10nDefault'=>'en',
	],
	'rules'=>[
		'Wild\DataMap\Bases'	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'$debug'=>'dev.db',
				'entityClassPrefix'=>'EntityModel\\',
				'entityClassDefault'=>'stdClass',
			],
		],
		'Wild\Identify\Auth'=>[
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
		'Wild\Identify\Session'=>[
			'shared'=>true,
			'newInstances'=>'Wild\Identify\SessionHandlerInterface',
			'substitutions'=>[
				'Wild\Identify\SessionHandlerInterface'=>'Wild\Identify\SessionHandler',
			],
			'construct'=>[
				'name'=>'surikat',
				'saveRoot'=>SURIKAT_CWD.'.tmp/sessions/',
			],
		],
		'Wild\Plugin\FrontController\FrontOffice'=>[
			'construct'=>[
				'$l10n'=>'l10n',
			],
		],
		'Wild\Plugin\RouteMatch\ByTmlL10n'=>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		'Wild\Localize\Translator'=>[
			'shared'=>true,
			'construct'=>[
				'timezone'=>'Europe/Paris',
				'$dev'=>'dev.l10n',
			],
		],
		'Wild\Debug\ErrorHandler'=>[
			'shared'=>true,
		],
		'Wild\Templix\Templix'=>[
			'construct'=>[
				'$devTemplate'=>'dev.tml',
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
				'$devImg'=>'dev.img',
			],
		],
		'Wild\Plugin\Templix\Templix'=>[
			'call'=>[
				'addPluginPrefix'=>'Wild\Plugin\Templix\Markup\\',
			],
		],
		'Wild\Plugin\FrontController\Synaptic'=>[
			'construct'=>[
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
			],
		],
		'Wild\Stylize\Server' => [
			'construct'=>[
				'$cache'=>'dev.css',
			],
		],
		'Wild\Route\Url' => [
			'shared'=>true,
		],
		'Banago\PHPloy\PHPloy' => [
			'construct'=>[
				'$map' => 'gitDeploy',
			],
		],
	],
];