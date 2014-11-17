<?php namespace surikat\view;
use surikat\dev;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		$refl = new ReflectionClass('control');
		$c = $refl->getConstants();
		if(!empty($this->attributes)){
			foreach($this->attributes as $dev)
				if(dev::has($c[$dev]))
					return;
		}
		elseif(dev::level())
			return;
		$this->childNodes = [];
	}
	
}
