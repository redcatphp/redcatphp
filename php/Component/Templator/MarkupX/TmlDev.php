<?php namespace Surikat\Component\Templator\MarkupX;
use ReflectionClass;
class TmlDev extends \Surikat\Component\Templator\Tml{
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
