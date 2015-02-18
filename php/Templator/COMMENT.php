<?php namespace Surikat\Templator; 
class COMMENT extends CORE{
	protected $hiddenWrap = true;
	private $contentText = '';
	protected $noParseContent = true;
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$text = self::phpImplode($text,$constructor);
		$this->contentText = '<!--'.$text.'-->';
	}
	function getInner(){
		return $this->contentText;
	}
}
