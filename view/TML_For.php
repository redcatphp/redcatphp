<?php namespace surikat\view; 
class TML_For extends TML {
	function __toString(){
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
		return '<?php for('.$c.'){?>'.(!$this->selfClosed?$this->getInnerTml().'<?php }?>':'');
	}
}
?>
