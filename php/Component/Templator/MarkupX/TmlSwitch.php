<?php namespace Surikat\Component\Templator\MarkupX; 
class TmlSwitch extends \Surikat\Component\Templator\Tml {
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('e');
		array_unshift($this->head,'<?php switch('.$this->e.'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
