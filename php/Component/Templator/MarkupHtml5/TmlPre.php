<?php namespace Surikat\Component\Templator\MarkupHtml5; 
class TmlPre extends \Surikat\Component\Templator\Tml{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerTml();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}