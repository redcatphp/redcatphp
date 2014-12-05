<?php namespace Surikat\Model;
use Surikat\Config\Dev;
use Surikat\Model;
use Surikat\Config\Config;
use Surikat\Model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use Surikat\Model\RedBeanPHP\RedException;
use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter;
use Surikat\Model\Query4D;
class R extends RedBeanPHP\Facade{
	static function loadDB($key){
		$getter = 'db';
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
		
		$frozen = isset($frozen)?$frozen:!Dev::has(Dev::MODEL_SCHEMA);		
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
}
if(R::loadDB('default'))
	R::selectDatabase('default');