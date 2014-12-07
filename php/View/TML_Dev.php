<?php namespace Surikat\View;
use Surikat\Core\Dev;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		$refl = new ReflectionClass('Surikat\\Core\\Dev');
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
