<?php namespace Surikat\Templator; 
class TML_For extends TML {
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('for');
		$c = $this->for;
		if(!$c&&$this->i)
			$c = '$i=0;$i<'.$this->attributes['i'].';$i++';
		if(!$c&&$this->from&&$this->to)
			$c = '$i='.$this->from.';$i<='.$this->to.';$i++';
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