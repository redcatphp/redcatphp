<?php
use RedCat\Strategy\Di;
use RedCat\Strategy\Expander;
use RedCat\Strategy\Factory;
return [
	'$'=>[
		'dev'=>[
			'php'       =>1,
			'tml'       =>1,
			'js'        =>1,
			'css'       =>1,
			'img'       =>0,
			'chrono'    =>1,
			'l10n'      =>0,
			'db'        =>1,
			'mail'      =>0,
			'security'  =>0,
		],
		'databaseMap'=>[
			0 => [
				'$type'=>'db.type',
				'$host'=>'db.host',
				'$name'=>'db.name',
				'$user'=>'db.user',
				'$password'=>'db.password',
				'primaryKey'=>'id',
				'uniqTextKey'=>'uniq',
				'uniqTextKeys'=>[
					'user'=>'email',
				],
				'modelClassPrefix'=> ['MyApp\Model\Entity\\'],
			],
		],
		'l10n'=>false,
		'l10nDefault'=>'en',
		'versioning'=>'new:RedCat\Framework\Versioning\Number',
		'autoload'=>[
			
		],
		'artist'=>[
			'pluginDirsMap'=>[
				REDCAT_CWD.'plugins/artist'=>'MyApp\Artist',
			]
		],
	],
	
	'rules'=>[
		'#router'=>[
			'instanceOf'=>'MyApp\Route\Route',
			'shared'=>true,
			'construct'=>[
				'$l10n'=>'l10n',
			]
		],
		'MyApp\Route\Route'=>[
			'alias'=>'#router',
		],
		RedCat\Framework\FrontController\RouterInterface::class => [
			'alias'=>'#router',
		],
		RedCat\Route\Request::class => [
			'shared'=>true,
		],
		RedCat\Route\SilentProcess::class => [
			'shared'=>true,
		],
		RedCat\Identify\PHPMailer::class => [
			'shared'=>true,
			'construct'=>[
				'$debug'=>'dev.mail'
			]
			
		],
		RedCat\Strategy\Di::class	=> [
			'instanceOf'=>RedCat\Framework\App::class,
		],
		FoxORM\Bases::class	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'$debug'=>'dev.db',
				'modelClassPrefix'=>'MyApp\Model\Entity\\',
				'entityClassDefault'=>'MyApp\Model\Entity',
			],
			'call'=>[
				'setEntityFactory'=>[new Factory(function($type,$db,Di $di){
					return $di($db->findEntityClass($type),['data'=>[],'type'=>$type,'db'=>$db,'table'=>$db[$type]]);
				})],
				'setTableWapperFactory'=>[new Factory(function($name,$db,$dataTable,$tableWrapper,Di $di){
					$c = $db->findTableWrapperClass($name,$tableWrapper);
					if($c)
						return $di($c,[$name,$db,$dataTable]);
				})],
			]
		],
		RedCat\Identify\Session::class=>[
			'shared'=>true,
			'newInstances'=>'RedCat\Identify\SessionHandlerInterface',
			'substitutions'=>[
				'RedCat\Identify\SessionHandlerInterface'=>'RedCat\Identify\SessionHandler',
			],
			'construct'=>[
				'name'=>'redcatphp',
				'saveRoot'=>REDCAT_CWD.'.tmp/sessions/',
				'$disableBruteforceProtection'=>'dev.security',
			],
		],
		RedCat\Framework\RouteMatch\ByTmlL10n::class=>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		RedCat\Framework\Templix\TemplixL10n::class=>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		RedCat\Localize\Translator::class=>[
			'shared'=>true,
			'construct'=>[
				'timezone'=>'Europe/Paris',
				'$dev'=>'dev.l10n',
			],
		],
		RedCat\Debug\ErrorHandler::class=>[
			'shared'=>true,
		],
		RedCat\Templix\Templix::class=>[
			'construct'=>[
				'$devTemplate'=>'dev.tml',
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
				'$devImg'=>'dev.img',
			],
		],
		RedCat\Framework\Templix\Templix::class=>[
			'call'=>[
				'addPluginPrefix'=>[['RedCat\Framework\Templix\Markup\\','MyApp\Templix\Markup\\']],
				'addDirCwd'=>[[
					'view/',
					'shared/template/',
				]],
				'setDirCompile'=>'.tmp/templix/compile/',
				'setDirCache'=>'.tmp/templix/cache/',
				'setDirSync'=>'.tmp/sync/',
			],
		],
		RedCat\Framework\FrontController\AssetLoader::class=>[
			'construct'=>[
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
			],
		],
		RedCat\Stylize\Server::class => [
			'construct'=>[
				'$cache'=>'dev.css',
			],
		],
		RedCat\Route\Url::class => [
			'shared'=>true,
		],
	],
];
