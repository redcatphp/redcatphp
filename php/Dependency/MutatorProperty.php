<?php namespace Surikat\Dependency;
use Surikat\Dependency\MutatorLegacy;
trait MutatorProperty{
	function __get($k){
		if(ctype_upper($k{0}))
			return $this->getDependency($k);
		elseif(is_callable('parent::__get'))
			return parent::__get($k);
		else
			return isset($this->__metaRegistry[$k])?$this->__metaRegistry[$k]:null;
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