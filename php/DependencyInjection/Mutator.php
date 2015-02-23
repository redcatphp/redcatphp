<?php namespace Surikat\DependencyInjection;
use ReflectionClass;
use Exception;
use Surikat\DependencyInjection\Container;
trait Mutator {
	private $__dependenciesRegistry = [];
	public $Dependency_Container;
	private static function __toClass($value){
		return str_replace('_','\\',$value);
	}
	private static function __toMethod($value){
		return str_replace('\\','_',$value);
	}
	static function __interfaceSubstitutionDefaultClass(&$value){
		$value = self::__toClass($value);
		if(interface_exists($value)){
			$pos = strrpos($a,'\\');
			$value = $a;
			if($pos===false)
				$value .= '\\'.$a;
			else
				$value .= substr($a,strrpos($a,'\\'));
		}
		return $value;
	}
	static function __toClassMixed($value){
		if(is_array($value)){
			if(isset($value[0])){
				self::__interfaceSubstitutionDefaultClass($value[0]);
			}
		}
		elseif(is_string($value)){
			self::__interfaceSubstitutionDefaultClass($value);
		}
		return $value;
	}
	function setDependency($key,$value=null){
		if(is_object($key)){
			if(!isset($value))
				$value = $key;
			$key = get_class($key);
		}
		$key = self::__toMethod($key);
		$obj = $this->Dependency_Container()->factory(self::__toClassMixed($value));
		$c = self::__toClass($key);
		if(interface_exists($c)){
			if(!($obj instanceof $c)){
				throw new Exception(sprintf('Instance of %s interface was expected, you have to implements it in %s',$c,get_class($obj)));
			}
		}
		$this->__dependenciesRegistry[$key] = $obj;
		return $this;
	}
	function getDependency($key){
		$key = self::__toMethod($key);
		if(array_key_exists($key,$this->__dependenciesRegistry))
			return $this->__dependenciesRegistry[$key];
		if(method_exists($this,$key))
			$value = $this->$key();
		else
			$value = $this->defaultDependency($key);
		$this->setDependency($key,$value);
		return $this->getDependency($key);
	}
	function getNew($key){
		$key = self::__toMethod($key);
		if(method_exists($this,$key))
			$new = $this->$key();
		else
			$new = $this->defaultNew($key);
		return $this->Dependency_Container()->factory(self::__toClassMixed($new));
	}
	function defaultNew($key){
		$key = self::__toClass($key);
		if(strpos($key,'\\')===false)
			$key = $key.'\\'.$key;
		return $this->Dependency_Container()->factory(self::__toClassMixed($key));
	}
	function defaultDependency($key){
		return $this->Dependency_Container()->getDependency($key);
	}
	function Dependency_Container(){
		if(func_num_args()){
			$this->Dependency_Container = Container::factory(self::__toClassMixed(func_get_arg(0)));
		}
		else{
			if(!isset($this->Dependency_Container))
				$this->Dependency_Container = Container::getStatic();
		}
		return $this->Dependency_Container;
	}
}