<?php namespace Surikat\Templator;
abstract class CALL_SUB extends CORE{
	function extendLoad($extend = null){ #untested
		if($extend || ($extend = $this->closest('extend')))
			foreach($this->childNodes as $node)
				if(method_exists($node,'extendLoad'))
					$node->extendLoad($extend);
	}
	function applyLoad($apply = null,$vars = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			foreach($this->childNodes as $node)
				if(method_exists($node,'applyLoad'))
					$node->applyLoad($apply);
	}
}
