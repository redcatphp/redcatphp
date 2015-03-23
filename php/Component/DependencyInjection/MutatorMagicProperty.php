<?php namespace Surikat\Component\DependencyInjection;
trait MutatorMagicProperty{
	private $__legacyRegistry = [];
	function &__get($k){
		if(ctype_upper($k{0})||($k{0}=='_'&&ctype_upper($k{1}))){
			if(strpos($k,'__')!==false)
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
			$r = $this->__legacyRegistry[$k];
		}
		return $r;
	}
	function __set($k,$v){
		if(ctype_upper($k{0})||($k{0}=='_'&&ctype_upper($k{1}))){
			if(strpos($k,'__')!==false)
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