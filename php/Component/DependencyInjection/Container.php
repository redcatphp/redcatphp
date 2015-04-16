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
		return $this->__factoryDependency(self::__interfaceSubstitutionDefaultClass($this->__prefixClassName($key)),$args);
	}
	static function hashArguments($args){
		static $storage = null;
		if(!isset($storage))
			$storage = new \SplObjectStorage();
		$hash = [];
		foreach($args as $arg){
			if(is_array($arg)){
				$hash[] = Container::hashArguments($arg);
			}
			elseif(is_object($arg)){
				$storage->attach($arg);
				$hash[] = $storage->getHash($arg);
				//$hash[] = spl_object_hash($arg);
			}
			else{
				$hash[] = sha1($arg);
			}
		}
		return sha1(implode('.',$hash));
	}
}