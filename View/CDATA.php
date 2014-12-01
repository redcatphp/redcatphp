<?php namespace Surikat\View; 
class CDATA extends CORE{
	var $nodeName = 'CDATA';
	protected $hiddenWrap = true;
	private $contentText = '';
	protected $noParseContent = true;
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$text = self::phpImplode($text,$constructor);
		$this->contentText = '<![CDATA['.$text.']]>';
	}
	function getInner(){
		return $this->contentText;
	}
}