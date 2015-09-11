<?php
namespace Wild\DataMap;
class B{
	protected static $bases;
	protected static $currentDataSource;
	static $useKineticDi = true;
	static function _init(){
		if(!isset(self::$bases)){
			if(class_exists('Wild\Kinetic\Di')&&self::$useKineticDi){
				self::$bases = \Wild\Kinetic\Di::getInstance()->create('Wild\DataMap\Bases');
				if(isset(self::$bases[0]))
					self::selectDatabase(0);
			}
			else{
				self::$bases = new Bases();
			}
		}
	}
	static function getBases(){
		return self::$bases;
	}
	static function setup($dsn = null, $user = null, $password = null, $config = []){
		if(is_null($dsn))
			$dsn = 'sqlite:/'.sys_get_temp_dir().'/bases.db';
		self::addDatabase(0, $dsn, $username, $password, $config);
		self::selectDatabase(0);
		return self::$bases;
	}
	static function addDatabase($key,$dsn,$user=null,$password=null,$config=[]){
		self::$bases[$key] = [
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
		return self::$currentDataSource = self::$bases[$key];
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
	static function put($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function readId($type,$id){
		return call_user_func_array([self::$currentDataSource,'readId'],func_get_args());
	}
	static function exists($type,$id){
		return call_user_func_array([self::$currentDataSource,'readId'],func_get_args());
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
	
	static function on($type,$event,$call=null,$index=0,$prepend=false){
		return self::$currentDataSource[$type]->on($event,$call,$index,$prepend);
	}
	static function off($type,$event,$call=null,$index=0){
		return self::$currentDataSource[$type]->off($event,$call,$index);
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
	
	static function setEntityClassPrefix($entityClassPrefix='EntityModel\\'){
		return self::$bases->setEntityClassPrefix($entityClassPrefix);
	}
	static function appendEntityClassPrefix($entityClassPrefix){
		return self::$bases->appendEntityClassPrefix($entityClassPrefix);
	}
	static function prependEntityClassPrefix($entityClassPrefix){
		return self::$bases->prependEntityClassPrefix($entityClassPrefix);
	}
	static function setEntityClassDefault($entityClassDefault='stdClass'){
		return self::$bases->setEntityClassDefault($entityClassDefault);
	}
	static function setPrimaryKeyDefault($primaryKeyDefault='id'){
		return self::$bases->setPrimaryKeyDefault($primaryKeyDefault);
	}
	static function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		return self::$bases->setUniqTextKeyDefault($uniqTextKeyDefault);
	}
	
	static function debug(){
		return call_user_func_array([self::$currentDataSource,'debug'],func_get_args());
	}
}
B::_init();