<?php namespace Surikat\Component\Templator\MarkupX; 
class TmlElse extends \Surikat\Component\Templator\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}