<?php namespace Surikat\Model;
use Surikat\Core\Dev;
use Surikat\Core\Config;
class R extends RedBeanPHP\Facade{
	static function loadDB($key){
		$getter = 'db';
		if(!$key)
			$key = 'default';
		if($key!='default')
			$getter = $getter.'_'.implode('_',explode('/',$key));

		$type = Config::$getter('type');
		if(!$type)
			return;
		$port = Config::$getter('port');
		$host = Config::$getter('host');
		$file = Config::$getter('file');
		$name = Config::$getter('name');
		$prefix = Config::$getter('prefix');
		$case = Config::$getter('case');
		$frozen = Config::$getter('frozen');
		$user = Config::$getter('user');
		$password = Config::$getter('password');
		
		if($port)
			$port = ';port='.$port;
		if($host)
			$host = 'host='.$host;
		elseif($file)
			$host = $file;
		if($name)
			$name = ';dbname='.$name;
		if(!isset($frozen))
			$frozen = !Dev::has(Dev::DB);		
		if(!isset($case))
			$case = true;
		$dsn = $type.':'.$host.$port.$name;
		
		
		self::addDatabase($key,$dsn,$user,$password,$frozen,$prefix,$case);
		
		return true;
	}
	static function getDatabase($key=null){
		if(!$key)
			$key = 'default';
		if(!isset(self::$databases[$key]))
			self::loadDB($key);
		return parent::getInstance($key);
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