<?php namespace Surikat\View;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		if(!empty($this->attributes)){
			foreach($this->attributes as $dev)
				if($this->getDependency('Dev\Level')->has($dev))
					return;
		}
		elseif($this->getDependency('Dev\Level')->level())
			return;
		$this->childNodes = [];
	}
	
}
