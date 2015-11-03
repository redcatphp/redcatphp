<?php
namespace RedCat\Templix\MarkupX; 
class Pre extends \RedCat\Templix\Markup{
	protected $noParseContent = true;
	function load(){
		if($this->attr('tmp-once'))
			return;
		$this->attr('tmp-once',1);
		$text = $this->getInnerMarkups();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}