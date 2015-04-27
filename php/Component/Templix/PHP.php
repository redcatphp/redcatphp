<?php namespace Surikat\Component\Templix; 
class PHP extends CORE{
	protected $hiddenWrap = true;
	var $nodeName = 'PHP';
	function parse($text){
		$this->innerHead(self::phpImplode($text,$this->constructor));
	}
}