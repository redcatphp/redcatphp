<?php namespace Surikat\View;
use Surikat\Dev;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		$refl = new ReflectionClass('control');
		$c = $refl->getConstants();
		if(!empty($this->attributes)){
			foreach($this->attributes as $dev)
				if(Dev::has($c[$dev]))
					return;
		}
		elseif(Dev::level())
			return;
		$this->childNodes = [];
	}
	
}
