<?php return [
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
	],
	'rules'=>[
		'Authentic\Session'=>[
			'shared'=>true,
			'newInstances'=>'Authentic\SessionHandlerInterface',
			'substitutions'=>[
				'Authentic\SessionHandlerInterface'=>'Authentic\SessionHandler',
			],
			'construct'=>[
				'surikat',
				SURIKAT_CWD.'.tmp/sessions/',
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
		'InterNative\Translator'=>[
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
		'KungFu\Cms\FrontController\Synaptic'=>[
			'construct'=>[
				'$devCss'=>'dev.css',
				'$devJs'=>'dev.js',
			],
		],
		'InterNative\Translator'=>[
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