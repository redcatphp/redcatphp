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
			'model'	=>true,
			'sql'	=>true,
			'sql+'	=>true,
			'db'	=>true,
		],
		'superRoot'=>[
			'login'=>'root',
			'password'=>'root',
		],
		'databaseMap'=>[
			0 => [
				'type'=>'sqlite',
				'file'=>SURIKAT_CWD.'.data/db.sqlite',
				'entityClassPrefix'=>['Model\\'],
				'entityClassDefault'=>'stdClass',
				'primaryKey'=>'id',
				'uniqTextKey'=>'uniq',
				'createTable'=>true,
			],
			'translation' => [
				'type'			=>'sqlite',
				'file'			=>SURIKAT_CWD.'.data/db.translation.sqlite',
			],
		],
		'gitDeploy'=>[
			//'ftp://user:password@host:21/www',
			[
				'user' => 'user',
				'pass' => 'password',
				'host' => 'host',
				'port' => 21,
				'path' => '/www',
				'passive' => true,
				//'scheme' => 'ftps',
				//'scheme' => 'sftp',
				'clean_directories' => ['.tmp'],
				//'ignore_files' => ['file/toignore.txt'],
				//'upload_untracked' => ['folder/to/upload','another/file/upload.php'],
			],
		],
	],
	'rules'=>[
		'RedBase\RedBase'	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'entityClassPrefix'=>'Model\\',
				'entityClassDefault'=>'stdClass',
			],
		],
		'Authentic\Auth'=>[
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
		'Authentic\Session'=>[
			'shared'=>true,
			'newInstances'=>'Authentic\SessionHandlerInterface',
			'substitutions'=>[
				'Authentic\SessionHandlerInterface'=>'Authentic\SessionHandler',
			],
			'construct'=>[
				'name'=>'surikat',
				'saveRoot'=>SURIKAT_CWD.'.tmp/sessions/',
			],
		],
		'Session'=>[
			'instanceOf'=>'Authentic\Session',
		],
		'KungFu\Cms\RouteMatch\ByTmlL10n'=>[
			'construct'=>[
				'langDefault'=>'en',
			],
		],
		'InterEthnic\Translator'=>[
			'shared'=>true,
			'construct'=>[
				'timezone'=>'Europe/Paris',
			],
		],
		'Unit\Debug'=>[
			'shared'=>true,
		],
		'Templix\Templix'=>[
			'construct'=>[
				'$devTemplate'=>'dev.tml',
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
				'$devImg'=>'dev.img',
			],
		],
		'KungFu\TemplixPlugin\Templix'=>[
			'call'=>[
				'addPluginPrefix'=>'KungFu\TemplixPlugin\Markup\\',
			],
		],
		'KungFu\Cms\FrontController\Synaptic'=>[
			'construct'=>[
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
			],
		],
		'Stylish\Server' => [
			'construct'=>[
				'$cache'=>'dev.css',
			],
		],
		'InterEthnic\Translator'=>[
			'construct'=>[
				'$dev'=>'dev.l10n',
			],
		],
		'RedBase\RedBeanPHP\Database'=>[
			'construct'=>[
				'$devStructure'=>'dev.model',
				'$devQuery'=>'dev.sql',
				'$devSpeed'=>'dev.sql+',
				'$devError'=>'dev.db',
			],
		],
	],
];