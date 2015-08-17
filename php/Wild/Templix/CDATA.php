<?php namespace Wild\Templix; 
class CDATA extends Markup{
	var $nodeName = 'CDATA';
	protected $hiddenWrap = true;
	private $contentText = '';
	protected $noParseContent = true;
	function parse($text){
		$text = self::phpImplode($text,$this->constructor);
		$this->contentText = '<![CDATA['.$text.']]>';
	}
	function getInner(){
		return $this->contentText;
	}
}