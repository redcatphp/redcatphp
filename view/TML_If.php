<?php namespace surikat\view; 
class TML_If extends TML {
	function __toString(){
		return '<?php if('.$this->evalSource().'){?>'.(!$this->selfClosed?$this->getInnerTml().'<?php }?>':'');
	}
	function evalSource($justCode=null){
		return $this->e&&$this->e!=='e'?$this->e:'';
	}
	function extendLoad($extend = null){ #untested
		if(($extend || ($extend = $this->closest('extend')))
		&&$this->selector?count($apply->find($this->selector))
			:eval('return '.$this->evalSource().';'))
			foreach($this->childNodes as $node)
				if(method_exists($node,'extendLoad'))
					$node->extendLoad($extend);
	}
	function applyLoad($apply = null){
		if(($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
		&& ($this->selector?count($apply->find($this->selector))
			:eval('return '.$this->evalSource().';')))
			foreach($this->childNodes as $node)
				if(method_exists($node,'applyLoad'))
					$node->applyLoad($apply);
	}
}
?>
