<?php namespace Surikat\DependencyInjection;
use BadMethodCallException;
trait MutatorCall{
	function __call($f,$args){
		if(ctype_upper($f{0}))
			return !empty($args)?$this->getDependency($f):$this->setDependency($f,$args[0]);
		elseif(is_callable('parent::__call'))
			return parent::__call($f,$args);
		elseif(method_exists($this,'___call'))
			return parent::___call($f,$args);
		else
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
}