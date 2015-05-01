<?php namespace Templix\MarkupHtml5; 
class TmlPre extends \Templix\Tml{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerTml();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}