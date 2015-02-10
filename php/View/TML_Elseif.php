<?php namespace Surikat\View; 
class TML_Elseif extends TML_If {
	protected $selfClosed = true;
	function load(){
		array_unshift($this->head,'<?php }elseif('.$this->evalSource()."){?>\n");
		if(!$this->selfClosed)
			array_push($this->foot,"<?php }?>\n");
	}
}