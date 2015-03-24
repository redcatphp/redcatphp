<?php namespace Surikat\Component\DependencyInjection;
use BadMethodCallException;
trait MutatorMagicCall{
	function __call($k,$args){
		if(ctype_upper($k{0})||($k{0}=='_'&&(ctype_upper($k{1})||($k{1}=='_'&&ctype_upper($k{2}))))){
			if(strpos($k,'__'))
				return $this->treeDependency($k,$args);
			else
				return $this->getDependency($k,$args);
		}
		elseif(method_exists($this,'___call')){
			return $this->___call($k,$args);
		}
		elseif(is_callable('parent::__call')){
			return parent::__call($k,$args);
		}
		else{
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$k));
		}
	}
}