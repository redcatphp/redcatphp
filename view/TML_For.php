<?php namespace surikat\view; 
class TML_For extends TML {
	function load(){
		$c = $this->for;
		if(!$c&&isset($this->attributes['i'])){
			$c = '$i=0;$i<'.$this->attributes['i'].';$i++';
		}
		if(!$c)
			$c = $this->e;
		if(!$c)
			$c = current($this->attributes);
		if(!$c)
			$c = key($this->attributes);
		array_unshift($this->head,'<?php for('.$c.'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
