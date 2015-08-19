<?php
namespace Wild\Templix; 
class PHP extends Markup{
	protected $hiddenWrap = true;
	var $nodeName = 'PHP';
	function parse($text){
		$this->innerHead(self::phpImplode($text,$this->constructor));
	}
}