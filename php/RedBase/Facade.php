<?php
namespace RedBase;
class Facade{
	protected static $redbase;
	protected static $currentDataSource;
	static $useUnitDi = true;
	static function _initialiaze(){
		if(!isset(self::$redbase)){
			if(class_exists('Unit\Di')&&self::$useUnitDi){
				self::$redbase = \Unit\Di::getInstance()->create('RedBase\RedBase');
				if(isset(self::$redbase[0]))
					self::selectDatabase(0);
			}
			else{
				self::$redbase = new RedBase();
			}
		}
	}
	static function setup($dsn = null, $user = null, $password = null, $config = []){
		if(is_null($dsn))
			$dsn = 'sqlite:/'.sys_get_temp_dir().'/redbase.db';
		self::addDatabase(0, $dsn, $username, $password, $config);
		self::selectDatabase(0);
		return self::$redbase;
	}
	static function addDatabase($key,$dsn,$user=null,$password=null,$config=[]){
		self::$redbase[$key] = [
			'dsn'=>$dsn,
			'user'=>$user,
			'password'=>$password,
		]+$config;
		if(!isset(self::$currentDataSource))
			self::selectDatabase($key);
	}
	static function selectDatabase($key){
		if(func_num_args()>1)
			call_user_func_array(['self','addDatabase'],func_get_args());
		return self::$currentDataSource = self::$redbase[$key];
	}
	static function __callStatic($f,$args){
		self::_initialiaze();
		if(!isset(self::$currentDataSource))
			throw new Exception('Use '.__CLASS__.'::setup() first');
		return call_user_func_array([self::$currentDataSource,$f],$args);
	}
	
	static function create($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function read($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function update($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function delete($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	
	static function dispense($type){
		return self::$currentDataSource->entityFactory($type);
	}
	
	static function execute($sql,$binds=[]){
		return self::$currentDataSource->execute($sql,$binds);
	}
	
	static function getDatabase(){
		return self::$currentDataSource;
	}
	static function getTable($type){
		return self::$currentDataSource[$type];
	}
	
	static function on($type,$event,$call=null){
		return self::$currentDataSource[$type]->on($event,$call);
	}
	static function off($type,$event,$call=null){
		return self::$currentDataSource[$type]->off($event,$call);
	}
	
	static function many2one($obj,$type){
		return self::$currentDataSource->many2one($obj,$type);
	}
	static function one2many($obj,$type){
		return self::$currentDataSource->one2many($obj,$type);
	}
	static function many2many($obj,$type,$via=null){
		return self::$currentDataSource->many2many($obj,$type,$via);
	}
	static function loadMany2one($obj,$type){
		return self::$currentDataSource[$type]->loadOne($obj);
	}
	static function loadOne2many($obj,$type){
		return self::$currentDataSource[$type]->loadMany($obj);
	}
	static function loadMany2many($obj,$type,$via=null){
		return self::$currentDataSource[$type]->loadMany2many($obj,$via);
	}
	
	static function setEntityClassPrefix($entityClassPrefix='Model\\'){
		return self::$redbase->setEntityClassPrefix($entityClassPrefix);
	}
	static function appendEntityClassPrefix($entityClassPrefix){
		return self::$redbase->appendEntityClassPrefix($entityClassPrefix);
	}
	static function prependEntityClassPrefix($entityClassPrefix){
		return self::$redbase->prependEntityClassPrefix($entityClassPrefix);
	}
	static function setEntityClassDefault($entityClassDefault='stdClass'){
		return self::$redbase->setEntityClassDefault($entityClassDefault);
	}
	static function setPrimaryKeyDefault($primaryKeyDefault='id'){
		return self::$redbase->setPrimaryKeyDefault($primaryKeyDefault);
	}
	static function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		return self::$redbase->setUniqTextKeyDefault($uniqTextKeyDefault);
	}
	
	static function debug(){
		return call_user_func_array([self::$currentDataSource,'debug'],func_get_args());
	}
}
Facade::_initialiaze();