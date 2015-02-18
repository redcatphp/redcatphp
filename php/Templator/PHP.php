<?php namespace Surikat\Templator; 
class PHP extends CORE{
	protected $hiddenWrap = true;
	var $nodeName = 'PHP';
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$this->nodeName = $nodeName;
		$this->constructor = $constructor;
		$text = self::phpImplode($text,$this->constructor);
		$this->innerHead($text);
	}
}