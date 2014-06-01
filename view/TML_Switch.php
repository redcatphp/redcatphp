<?php namespace surikat\view; 
class TML_Switch extends TML {
	protected $hiddenWrap = true;
	function load(){
		$c = $this->switch;
		if(!$c)
			$c = $this->key;
		if(!$c)
			$c = $this->e;
		if(!$c)
			$c = current($this->attributes);
		if(!$c)
			$c = key($this->attributes);
		array_unshift($this->head,'<?php switch('.$c.'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
