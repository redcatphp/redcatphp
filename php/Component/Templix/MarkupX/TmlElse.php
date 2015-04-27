<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlElse extends \Surikat\Component\Templix\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}