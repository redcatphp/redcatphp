<?php namespace surikat\view;
use surikat\control;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		$refl = new ReflectionClass('control');
		$c = $refl->getConstants();
		if(!empty($this->attributes)){
			foreach($this->attributes as $dev)
				if(control::devHas($c["dev_$dev"]))
					return;
		}
		elseif(control::$DEV)
			return;
		$this->childNodes = [];
	}
	
}
