<?php namespace surikat\model;
use surikat\dev;
use surikat\model;
use surikat\control\Config;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use surikat\model\RedBeanPHP\RedException;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
use surikat\model\Query4D;
class R extends RedBeanPHP\Facade{
	static function loadDB($key){
		$getter = 'model';
		if(!$key)
			$key = 'default';
		if($key!='default')
			$getter = $getter.'_'.implode('_',explode('/',$key));
		$conf = Config::$getter();
		if(!$conf)
			return;
		extract($conf);
		
		$port = isset($port)&&$port?';port='.$port:'';
		$host = isset($host)?'host='.$host:(isset($file)?$file:'');
		$dbname = isset($name)?';dbname='.$name:'';
		
		$dsn = $type.':'.$host.$port.$dbname;
		
		$frozen = isset($frozen)?$frozen:!dev::has(dev::MODEL);		
		$prefix = isset($prefix)?$prefix:'';
		
		self::addDatabase($key,$dsn,@$user,@$password,$frozen,$prefix);
		
		return true;
	}
	static function selectDatabase($key){
		if(!$key)
			$key = 'default';
		if(!isset(self::$databases[$key]))
			self::loadDB($key);
		return parent::selectDatabase($key);
	}
	static function initialize(){		
		if(self::loadDB('default')){
			self::selectDatabase('default');
			if(dev::has(dev::MODEL))
				self::debug(true,2);
		}
	}
}
R::initialize();