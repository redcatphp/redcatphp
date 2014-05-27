<?php namespace surikat\view; 
class COMMENT extends CORE{
	protected $hiddenWrap = true;
	private $contentText = '';
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$text = $this->phpImplode($text,$constructor);
		$this->contentText = '<!--'.$text.'-->';
	}
	function getInner(){
		return $this->contentText;
	}
}
