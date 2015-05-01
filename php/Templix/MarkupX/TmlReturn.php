<?php namespace Templix\MarkupX; 
class TmlReturn extends \Templix\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}