<?php namespace Surikat\Component\Templator; 
class TmlPre extends Tml{
	protected $noParseContent = true;
	function load(){
		$text = $this->getInnerTml();
		$this->clearInner();
		$this->innerHead[] = htmlentities($text);
	}
}