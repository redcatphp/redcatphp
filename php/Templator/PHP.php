<?php namespace Surikat\Templator; 
class PHP extends CORE{
	protected $hiddenWrap = true;
	var $nodeName = 'PHP';
	function parse($text){
		$this->innerHead(self::phpImplode($text,$this->constructor));
	}
}