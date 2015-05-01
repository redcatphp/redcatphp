<?php namespace Templix\MarkupX; 
class TmlElse extends \Templix\Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}