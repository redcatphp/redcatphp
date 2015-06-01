<?php namespace Templix\MarkupHtml5; 
class Pre extends \Templix\Markup{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerMarkups();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}