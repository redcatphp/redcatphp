<?php namespace Templix; 
class PHP extends Tml{
	protected $hiddenWrap = true;
	var $nodeName = 'PHP';
	function parse($text){
		$this->innerHead(self::phpImplode($text,$this->constructor));
	}
}