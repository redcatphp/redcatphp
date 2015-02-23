<?php namespace Surikat\Templator;
use ReflectionClass;
class TML_Dev extends TML{
	protected $hiddenWrap = true;
	function load(){
		if(!empty($this->attributes)){
			foreach($this->attributes as $dev)
				if($this->Dev_Level()->has($dev))
					return;
		}
		elseif($this->Dev_Level()->level())
			return;
		$this->childNodes = [];
	}
	
}
