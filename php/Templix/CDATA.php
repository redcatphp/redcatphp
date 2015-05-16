<?php namespace Templix; 
class CDATA extends Tml{
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