<?php
use RedCat\Ding\Expander;
use Zend\Diactoros\ServerRequestFactory;
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
		'versioning'=>'new:RedCat\Framework\Versioning\Number',
		'router'=>RedCat\Framework\Route::class,
	],
	'rules'=>[
		'#router'=>[
			'$instanceOf'=>'router',
			'construct'=>[
				'$l10n'=>'l10n',
			],
		],
		RedCat\Framework\Artist\RouteList::class	=> [
			'substitutions'=>[
				'$'.RedCat\Framework\FrontController\RouterInterface::class => 'router',
			],
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
			'instanceOf'=> RedCat\Framework\App::class,
		],
		RedCat\DataMap\Bases::class	=> [
			'shared'=>true,
			'construct' => [
				'$map' => 'databaseMap',
				'$debug'=>'dev.db',
				'entityClassPrefix'=>'EntityModel\\',
				'entityClassDefault'=>'stdClass',
			],
		],
		RedCat\Identify\Auth::class => [
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
		RedCat\Identify\Session::class => [
			'shared'=>true,
			'newInstances'=>RedCat\Identify\SessionHandlerInterface::class,
			'substitutions'=>[
				RedCat\Identify\SessionHandlerInterface::class => RedCat\Identify\SessionHandler::class,
			],
			'construct'=>[
				'name'=>'redcatphp',
				'saveRoot'=>REDCAT_CWD.'.tmp/sessions/',
			],
		],
		RedCat\Framework\RouteMatch\ByTmlL10n::class =>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		RedCat\Framework\Templix\TemplixL10n::class =>[
			'construct'=>[
				'$langDefault'=>'l10nDefault',
			],
		],
		RedCat\Localize\Translator::class =>[
			'shared'=>true,
			'construct'=>[
				'timezone'=>'Europe/Paris',
				'$dev'=>'dev.l10n',
			],
		],
		RedCat\Debug\ErrorHandler::class =>[
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
				'addPluginPrefix'=>'RedCat\Framework\Templix\Markup\\',
				'addDirCwd'=>[[
					'template/',
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
	],
];
