<?php
namespace RedCat\Templix\MarkupX; 
class _Elseif extends \RedCat\Templix\_If {
	protected $selfClosed = true;
	function load(){
		array_unshift($this->head,'<?php }elseif('.$this->evalSource()."){?>\n");
		if(!$this->selfClosed)
			array_push($this->foot,"<?php }?>\n");
	}
}