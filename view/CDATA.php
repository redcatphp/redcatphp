<?php namespace surikat\view; 
class CDATA extends CORE{
	var $nodeName = 'CDATA';
	protected $hiddenWrap = true;
	private $contentText = '';
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$text = $this->phpImplode($text,$constructor);
		$this->contentText = '<![CDATA['.$text.']]>';
	}
	function getInner(){
		return $this->contentText;
	}
}
