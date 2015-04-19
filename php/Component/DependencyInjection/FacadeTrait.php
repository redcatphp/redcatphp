<?php namespace Surikat\Component\DependencyInjection;
trait FacadeTrait{
	use RegistryTrait;
	function __call($f,$args){
		$method = '_'.$f;
		if(method_exists($this,$method)&&(new \ReflectionMethod($this, $method))->isPublic())
			return call_user_func_array([$this,$method],$args);
		elseif(is_callable('parent::__call'))
			return parent::__call($f,$args);
		elseif(method_exists($this,'___call'))
			return $this->___call($f,$args);
		else
			throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$f));
	}
	static function __callStatic($f,$args){
		$method = '_'.$f;
		$c = get_called_class();
		if(method_exists($c,$method)&&(new \ReflectionMethod($c, $method))->isPublic()){
			return call_user_func_array([$c::getStatic(),$method],$args);
		}
		elseif(is_callable('parent::__callStatic'))
			return parent::__callStatic($f,$args);
		elseif(method_exists($c,'___callStatic'))
			return parent::___callStatic($f,$args);
		else
			throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()',$c,$f));
	}
}