<?php namespace DependencyInjection;
trait MutatorTrait {
	private $__dependenciesRegistry = [];
	private $__dependenciesFactory = 'static::makeDependency';
	function setDependency($key,$value=null,$rkey=null){
		if(is_object($key)){
			if(!isset($value))
				$value = $key;
			$key = get_class($key);
		}
		$key = $this->__prefixClassName($key);
		if(!isset($rkey))
			$rkey = $key;
		elseif(is_array($rkey))
			$rkey = $key.'.'.Container::hashArguments($rkey);
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
			$value = [$value,$args];
		}
		$c = str_replace('_','\\',$key);
		if((interface_exists($c)&&!($value instanceof $c))
			||(interface_exists($c.'Interface')&&!($value instanceof $c.'Interface'))
		){
			throw new \Exception(sprintf('Instance of %s interface was expected, you have to implements it in %s',$c,get_class($value)));
		}
		return $this->__dependenciesRegistry[$rkey] = $value;
	}
	function getDependency($key,$args=null){
		$method = str_replace('\\','_',$key);
		$key = $this->__prefixClassName($key);		
		if(empty($args)){
			$c = str_replace('_','\\',$key);
			if(method_exists($c,'getStatic')){
				return $c::getStatic();
			}
			$rkey = $key;
		}
		else{
			if(!is_array($args))
				$args = [$args];
			$rkey = $key.'.'.Container::hashArguments($args);
		}
		if(array_key_exists($rkey,$this->__dependenciesRegistry)){
			if(is_array($this->__dependenciesRegistry[$rkey])){
				$this->__dependenciesRegistry[$rkey] = call_user_func_array([$this,'factoryDependency'],$this->__dependenciesRegistry[$rkey]);
			}
			if($this->__dependenciesRegistry[$rkey] instanceof \Closure){
				$this->__dependenciesRegistry[$rkey] = $this->__dependenciesRegistry[$rkey]();
			}
			return $this->__dependenciesRegistry[$rkey];
		}
		if(method_exists($this,$method)){
			$value = $this->$method($args);
		}
		elseif(method_exists($this,$method='_'.$method)){
			$value = $this->$method($args);
		}
		else{
			$value = $this->defaultDependency($key,$args);
		}
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
			return $this->factoryDependency(self::__interfaceSubstitutionDefaultClass($this->__prefixClassName($key)),$args,true);
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
			$value = substr($value,1);
			$c = get_class($this);
			$prefix = substr($c, 0, strrpos($c, '\\'));
			$prefix = str_replace('\\','_',$prefix).'_';
			$value = $prefix.$value;
		}
		return $value;
	}
	function setDependencyFactory($callback){
		if($callback instanceof \Closure){
			$callback->bindTo($this);
		}
		$this->__dependenciesFactory = $callback;
	}
	function factoryDependency($c,$args=null,$new=null){
		return call_user_func($this->__dependenciesFactory,$c,$args,$new,$this);
	}
	static function makeDependency($c,$args=null,$new=null){
		static $reflectors = [];
		if(class_exists($c)){
			if(is_array($args)&&!empty($args)){
				if(!$new&&method_exists($c,'getStaticArray')){
					return $c::getStaticArray($args);
				}
				elseif(method_exists($c,'__construct')){
					if(!isset($reflectors[$c]))
						$reflectors[$c] = new \ReflectionClass($c);
					return $reflectors[$c]->newInstanceArgs($args);
				}
			}
			elseif(!$new&&method_exists($c,'getStatic')){
				return $c::getStatic();
			}
			return new $c();
		}
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
		if(!class_exists($value)){
			$x = explode('\\',$value);
			$s1 = array_pop($x);
			$s2 = array_pop($x);
			if($s1!=$s2){
				array_push($x,$s2);
				array_push($x,$s1);
				$c = implode('\\',$x).'\\'.$s1;
				if(class_exists($c))
					$value = $c;
			}
		}
		return $value;
	}
}