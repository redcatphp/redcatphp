<?php namespace Surikat\Component\Templator; 
class TmlReturn extends Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}