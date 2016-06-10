<?php
use RedCat\Ding\Di;
use RedCat\Ding\Expander;
use RedCat\Ding\Factory;
use Zend\Diactoros\ServerRequestFactory;
$MyApp = 'MyApp';
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
			//'db'      =>'on',
			'mail'      =>0,
			'security'  =>0,
		],
		'superRoot'=>[
			//'email'=>'',
			'login'=>'demo',
			'password'=>'demo',
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
				'modelClassPrefix'=> [$MyApp.'\Model\Entity\\'],
				//'frozen'=>true,
			],
		],
		'l10n'=>false,
		'l10nDefault'=>'en',
		'versioning'=>'new:RedCat\Framework\Versioning\Number',
		'autoload'=>[
			[__DIR__.'/php',$MyApp],
			[__DIR__.'/model',$MyApp.'\Model'],
			[__DIR__.'/controller',$MyApp.'\Controller'],
			[__DIR__.'/plugins/artist',$MyApp.'\Artist'],
			[__DIR__.'/plugins/templix',$MyApp.'\Templix'],
			[__DIR__.'/route',$MyApp.'\Route'],
		],
		'mergeConfig'=>['.config.env.php'],
		'router'=>$MyApp.'\Route\Route',
		'artist'=>[
			'pluginDirsMap'=>[
				__DIR__.'/plugins/artist'=>$MyApp.'\Artist',
			]
		],
	],
	
	'rules'=>[
		'#router'=>[
			'$instanceOf'=>'router',
			'shared'=>true
		],
		RedCat\Framework\FrontController\RouterInterface::class => [
			'instanceOf'=>'#router',
		],
		RedCat\Route\Request::class => [
			'shared'=>true,
		],
		RedCat\Identify\PHPMailer::class => [
			'shared'=>true,
			'construct'=>[
				'$debug'=>'dev.mail',
				/*
					'fromEmail'=>'demo@redcatphp.com',
					'fromName'=>'MyApp - MySociety',
					'replyEmail'=>'answer@redcatphp.com',
					'replyName'=>'MyApp - MySociety',
					
					'host'=>'smtp.gmail.com',
					'port'=>25,
					'username'=>'me@gmail.com',
					'password'=>'d3v3loper',
					'secure'=>null,
					'sendmail'=>false,
					'exceptions'=>false,
					
					'SMTPOptions'=>[
						'ssl' => [ //not secure - enable for dev
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true
						]
					],
				*/
			]
			
		],
		
	
		Psr\Http\Message\ServerRequestInterface::class	=> [
			'shared'=>true,
			'instanceOf'=>new Expander(function(){
				return ServerRequestFactory::fromGlobals(
					$_SERVER,
					$_GET,
					$_POST,
					$_COOKIE,
					$_FILES
				);
			}),
		],
		RedCat\Ding\Di::class	=> [
			'instanceOf'=>RedCat\Framework\App::class,
		],
		RedCat\DataMap\Bases::class	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'$debug'=>'dev.db',
				'modelClassPrefix'=>$MyApp.'\Model\Entity\\',
				'entityClassDefault'=>$MyApp.'\Model\Entity',
			],
			'call'=>[
				'setEntityFactory'=>[new Factory(function($type,$db,Di $di){
					return $di($db->findEntityClass($type),['data'=>[],'type'=>$type,'db'=>$db,'table'=>$db[$type]]);
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
			],
		],
		RedCat\Framework\FrontController\FrontOffice::class=>[
			'construct'=>[
				'$l10n'=>'l10n',
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
				'addPluginPrefix'=>[['RedCat\Framework\Templix\Markup\\',$MyApp.'\Templix\Markup\\']],
				'addDirCwd'=>[[
					'view/',
					'shared/template/',
				]],
				'setDirCompile'=>'.tmp/templix/compile/',
				'setDirCache'=>'.tmp/templix/cache/',
				'setDirSync'=>'.tmp/sync/',
			],
		],
		RedCat\Framework\FrontController\Synaptic::class=>[
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
		RedCat\Identify\Session::class=>[
			'construct'=>[
				'$bruteforceProtection'=>'dev.security'
			]
		]
	],
];
