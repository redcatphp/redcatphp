<?php namespace Surikat\Component\DependencyInjection;
use Surikat\Component\DependencyInjection\Container;
trait Mutator {
	private $__dependenciesRegistry = [];
	private $__dependenciesPrefix = 'Surikat\\Component';
	function setDependency($key,$value=null,$rkey=null){
		if(is_object($key)){
			if(!isset($value))
				$value = $key;
			$key = get_class($key);
		}
		$key = $this->__prefixClassName($key);
		if(!isset($rkey))
			$rkey = $key;
		if(!is_object($value)&&$value){
			if(is_array($value)){
				$args = $value;
				$value = array_shift($args);
			}
			else{
				$args = null;
			}
			$value = $this->__prefixClassName($value);
			$value = self::__interfaceSubstitutionDefaultClass($value);
			$value = self::__factoryDependency($value,$args);
		}
		$c = str_replace('_','\\',$key);
		if(interface_exists($c)&&!($value instanceof $c)){
			throw new \Exception(sprintf('Instance of %s interface was expected, you have to implements it in %s',$c,get_class($value)));
		}
		return $this->__dependenciesRegistry[$rkey] = $value;
	}
	function getDependency($key,$args=null){
		$method = str_replace('\\','_',$key);
		$key = $this->__prefixClassName($key);
		if(empty($args)){
			$rkey = $key;
		}
		else{
			if(!is_array($args))
				$args = [$args];
			$rkey = $key.'.'.sha1(json_encode($args));
		}
		if(array_key_exists($rkey,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$rkey];
		if(method_exists($this,$method))
			$value = $this->$method($args);
		elseif(method_exists($this,$method='_'.$method))
			$value = $this->$method($args);
		else
			$value = $this->defaultDependency($key,$args);
		$this->setDependency($key,$value,$rkey);
		return $this->getDependency($key,$args);
	}
	function newDependency($key,$args=null){
		$method = str_replace('\\','_',$key);
		if(method_exists($this,$method))
			return $this->$method($args);
		elseif(method_exists($this,$method='_'.$method))
			$value = $this->$method($args);
		else
			return $this->factoryDependency($key,$args);
	}
	function factoryDependency($key,$args=null){
		$key = $this->__prefixClassName($key);
		$key = self::__interfaceSubstitutionDefaultClass($key);
		return self::__factoryDependency($key,$args);
	}
	function setDependencyPrefix($prefix){
		$this->__dependenciesPrefix = $prefix;
	}
	function defaultDependency($key,$args=null){
		return Container::get()->getDependency($key,$args);
	}
	function treeDependency($key,$args=null){
		if(is_string($key))
			$key = explode(':',str_replace('__',':',$key));
		$r = $this;
		$c = count($key)-1;
		foreach($key as $i=>$k){
			if($i<$c){
				$r = $r->getDependency($k);
			}
			elseif(func_num_args()>2){
				$r->setDependency($k,func_get_arg(2));
				$r = func_get_arg(2);
			}
			else{
				$r = $r->getDependency($k,$args);
			}
		}
		return $r;
	}
	
	private function __prefixClassName($value){
		if($value{0}=='_'){
			if($value{1}=='_'){
				$value = substr($value,2);
				$c = get_class($this);
				$prefix = substr($c, 0, strrpos($c, '\\'));
			}
			else{
				$value = substr($value,1);
				$prefix = '';
			}
		}
		else{
			$prefix = $this->__dependenciesPrefix;
		}
		if($prefix){
			$prefix = str_replace('\\','_',$prefix).'_';
		}
		return '_'.$prefix.$value;
	}
	private static function __interfaceSubstitutionDefaultClass($value){
		$value = str_replace('_','\\',$value);
		if(interface_exists($value)){
			$pos = strrpos($value,'\\');
			if($pos===false)
				$value .= '\\'.$value;
			else
				$value .= substr($value,strrpos($value,'\\'));
		}
		if(strpos($value,'\\')===false&&!class_exists($value))
			$value = $value.'\\'.$value;
		return $value;
	}
	private static function __factoryDependency($c,$args=null){
		static $reflectors = [];
		if(class_exists($c)){
			if(is_array($args)&&!empty($args)&&method_exists($c,'__construct')){
				if(!isset($reflectors[$c]))
					$reflectors[$c] = new \ReflectionClass($c);
				return $reflectors[$c]->newInstanceArgs($args);
			}
			else{
				return new $c();
			}
		}
	}
}