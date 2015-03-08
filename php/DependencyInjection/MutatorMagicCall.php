<?php namespace Surikat\DependencyInjection;
use BadMethodCallException;
trait MutatorMagicCall{
	function __call($f,$args){
		if(ctype_upper($f{0}))
			return $this->getDependency($f,$args);
		elseif(is_callable('parent::__call'))
			return parent::__call($f,$args);
		elseif(method_exists($this,'___call'))
			return parent::___call($f,$args);
		else
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
}