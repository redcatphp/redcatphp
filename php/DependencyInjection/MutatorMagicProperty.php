<?php namespace Surikat\DependencyInjection;
trait MutatorMagicProperty{
	private $__metaRegistry = [];
	function &__get($k){
		if(ctype_upper($k{0})){
			$r = $this->getDependency($k);
			return $r;
		}
		elseif(is_callable('parent::__get')){
			$r = parent::__get($k);
			return $r;
		}
		else{
			if(!isset($this->__metaRegistry[$k]))
				$this->__metaRegistry[$k] = null;
			return $this->__metaRegistry[$k];
		}
	}
	function __set($k,$v){
		if(ctype_upper($k{0}))
			$this->setDependency($k,$v);
		elseif(is_callable('parent::__set'))
			parent::__set($k,$v);
		else
			$this->__metaRegistry[$k] = $v;
	}
}