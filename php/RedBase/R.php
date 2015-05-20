<?php namespace RedBase;
use RedBase\RedBeanPHP\Database;
class R {
	private static $instances = [];
	private static $instance;
	static function getDb($key=0){
		if(empty($key))
			$key = 0;
		if(!isset(self::$instances[$key])){
			self::$instances[$key] = new Database($key);
			$config = defined('SURIKAT_CWD')?SURIKAT_CWD:'';
			$config .= 'config/db'.($key?'.'.$key:'').'.php';
			self::$instances[$key]->setConfig(include($config));
		}
		return self::$instances[$key];
	}
	static function setDb($key=0){
		return self::$instance = self::getDb($key);
	}
	static function __callStatic($method,$args){
		if(method_exists('RedBase\RedBeanPHP\Database',$method))
			return call_user_func_array([self::getDb(),$method],$args);
		else
			throw new \BadMethodCallException('Call to undefined method Database::'.$method.'()');
	}
}