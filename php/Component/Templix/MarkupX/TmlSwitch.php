<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlSwitch extends \Surikat\Component\Templix\Tml {
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('e');
		array_unshift($this->head,'<?php switch('.$this->e.'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
