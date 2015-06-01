<?php namespace Templix\MarkupX; 
class _Switch extends \Templix\Markup {
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('e');
		array_unshift($this->head,'<?php switch('.$this->e.'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
