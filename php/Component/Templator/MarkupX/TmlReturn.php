<?php namespace Surikat\Component\Templator\MarkupX; 
class TmlReturn extends \Surikat\Component\Templator\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}