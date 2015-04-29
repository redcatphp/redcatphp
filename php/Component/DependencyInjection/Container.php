<?php namespace Surikat\Component\DependencyInjection;
class Container{
	use MutatorMagicTrait;
	use RegistryTrait;
	static function get(){
		$args = func_get_args();
		if(empty($args))
			return static::getStatic();
		$key = array_shift($args);
		return static::getStatic()->getDependency($key,$args);
	}
	static function set($key,$value){
		return static::getStatic()->setDependency($key,$value);
	}
	function defaultDependency($key,$args=null){
		return $this->factoryDependency(self::__interfaceSubstitutionDefaultClass($this->__prefixClassName($key)),$args);
	}
	static function hashArguments($args){
		static $storage = null;
		if(!isset($storage))
			$storage = new \SplObjectStorage();
		$hash = [];
		foreach($args as $arg){
			if(is_array($arg)){
				$hash[] = self::hashArguments($arg);
			}
			elseif(is_object($arg)){
				$storage->attach($arg);
				$hash[] = $storage->getHash($arg);
			}
			else{
				$hash[] = sha1($arg);
			}
		}
		return sha1(implode('.',$hash));
	}
	static function __getStaticNew($args=null,$class=null){
		if(empty($args))
			return new $class();
		else
			return static::getStatic()->factoryDependency($class,$args,true);
	}
}