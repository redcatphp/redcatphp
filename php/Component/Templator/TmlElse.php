<?php namespace Surikat\Component\Templator; 
class TmlElse extends Tml {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}