<?php namespace Surikat\Templator; 
class TML_If extends TML {
	protected $hiddenWrap = true;
	function load(){
		array_unshift($this->head,'<?php if('.$this->evalSource()."){?>\n");
		if(!$this->selfClosed)
			array_push($this->foot,"<?php }?>\n");
	}
	
	function evalSource($justCode=null){
		$this->remapAttr('e');
		return $this->e&&$this->e!=='e'?$this->e:'';
	}
	function extendLoad($extend = null){ #untested
		if(($extend || ($extend = $this->closest('extend')))
		&&$this->selector?count($apply->children($this->selector))
			:$this->evalue($this->evalSource()))
			foreach($this->childNodes as $node)
				if(method_exists($node,'extendLoad'))
					$node->extendLoad($extend);
	}
	function applyLoad($apply = null){
		if(($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
		&& ($this->selector?count($apply->children($this->selector))
			:$this->evalue($this->evalSource())))
			foreach($this->childNodes as $node)
				if(method_exists($node,'applyLoad'))
					$node->applyLoad($apply);
	}
}