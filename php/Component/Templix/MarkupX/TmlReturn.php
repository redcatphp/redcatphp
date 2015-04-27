<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlReturn extends \Surikat\Component\Templix\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}