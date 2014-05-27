<?php namespace surikat\view; 
class TML_Switch extends TML {
	function __toString(){
		$c = $this->switch;
		if(!$c)
			$c = $this->key;
		if(!$c)
			$c = $this->e;
		if(!$c)
			$c = current($this->attributes);
		if(!$c)
			$c = key($this->attributes);
		return '<?php switch('.$c.'){?>'.(!$this->selfClosed?$this->getInnerTml().'<?php }?>':'');
	}
}
?>
