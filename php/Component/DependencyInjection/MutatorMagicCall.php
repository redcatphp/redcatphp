<?php namespace Surikat\Component\DependencyInjection;
use BadMethodCallException;
trait MutatorMagicCall{
	function __call($f,$args){
		if(ctype_upper($f{0})||($f{0}=='_'&&$f{1}=='_'&&ctype_upper($f{2}))||($f{0}=='_'&&ctype_upper($f{1}))){
			if(strpos($f,'__'))
				return $this->treeDependency($f,$args);
			else
				return $this->getDependency($f,$args);
		}
		elseif(method_exists($this,'___call')){
			return $this->___call($f,$args);
		}
		elseif(is_callable('parent::__call')){
			return parent::__call($f,$args);
		}
		else{
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
		}
	}
}