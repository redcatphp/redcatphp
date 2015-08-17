<?php
namespace Wild\Templix\MarkupX; 
class Pre extends \Wild\Templix\Markup{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerMarkups();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}