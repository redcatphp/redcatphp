<?php namespace Wild\Templix; 
class COMMENT extends Markup{
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