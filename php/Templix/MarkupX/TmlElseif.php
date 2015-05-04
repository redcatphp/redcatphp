<?php namespace Templix\MarkupX; 
class TmlElseif extends \Templix\TmlIf {
	protected $selfClosed = true;
	function load(){
		array_unshift($this->head,'<?php }elseif('.$this->evalSource()."){?>\n");
		if(!$this->selfClosed)
			array_push($this->foot,"<?php }?>\n");
	}
}