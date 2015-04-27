<?php namespace Surikat\Component\Templix\MarkupHtml5; 
class TmlPre extends \Surikat\Component\Templix\Tml{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerTml();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}