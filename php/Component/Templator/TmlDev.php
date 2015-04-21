<?php namespace Surikat\Component\Templator;
use ReflectionClass;
class TmlDev extends Tml{
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
