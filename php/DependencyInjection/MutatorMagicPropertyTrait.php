<?php namespace DependencyInjection;
trait MutatorMagicPropertyTrait{
	private $__legacyRegistry = [];
	function &__get($k){
		if(ctype_upper($k{0})||($k{0}=='_'&&(ctype_upper($k{1})||($k{1}=='_'&&ctype_upper($k{2}))))){
			if(strpos($k,'__'))
				$r = $this->treeDependency($k);
			else
				$r = $this->getDependency($k);
		}
		elseif(method_exists($this,'___get')){
			$r = $this->___get($k);
		}
		elseif(is_callable('parent::__get')){
			$r = parent::__get($k);
		}
		else{
			if(!isset($this->__legacyRegistry[$k]))
				$this->__legacyRegistry[$k] = null;
			return $this->__legacyRegistry[$k];
		}
		return $r;
	}
	function __set($k,$v){
		if(ctype_upper($k{0})||($k{0}=='_'&&(ctype_upper($k{1})||($k{1}=='_'&&ctype_upper($k{2}))))){
			if(strpos($k,'__'))
				$this->treeDependency($k,null,$v);
			else
				$this->setDependency($k,$v);
		}
		elseif(method_exists($this,'___set')){
			$this->___set($k,$v);
		}
		elseif(is_callable('parent::__set')){
			parent::__set($k,$v);
		}
		else{
			$this->__legacyRegistry[$k] = $v;
		}
	}
}