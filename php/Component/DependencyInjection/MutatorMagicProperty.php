<?php namespace Surikat\DependencyInjection;
trait MutatorMagicProperty{
	private $__legacyRegistry = [];
	function &__get($k){
		if(ctype_upper($k{0})||($k{0}=='_'&&ctype_upper($k{1}))){
			if(strpos($k,'__')!==false)
				$r = $this->treeDependency($k);
			else
				$r = $this->getDependency($k);
			return $r;
		}
		elseif(is_callable('parent::__get')){
			$r = parent::__get($k);
			return $r;
		}
		else{
			if(!isset($this->__legacyRegistry[$k]))
				$this->__legacyRegistry[$k] = null;
			return $this->__legacyRegistry[$k];
		}
	}
	function __set($k,$v){
		if(ctype_upper($k{0})||($k{0}=='_'&&ctype_upper($k{1}))){
			if(strpos($k,'__')!==false)
				$this->treeDependency($k,null,$v);
			else
				$this->setDependency($k,$v);
		}
		elseif(is_callable('parent::__set')){
			parent::__set($k,$v);
		}
		else{
			$this->__legacyRegistry[$k] = $v;
		}
	}
}