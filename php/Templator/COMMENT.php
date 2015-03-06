<?php namespace Surikat\Templator; 
class COMMENT extends CORE{
	protected $hiddenWrap = true;
	private $contentText = '';
	protected $noParseContent = true;
	function parse($text){
		$text = self::phpImplode($text,$this->constructor);
		$this->contentText = '<!--'.$text.'-->';
	}
	function getInner(){
		return $this->contentText;
	}
}