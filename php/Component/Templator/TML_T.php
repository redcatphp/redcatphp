<?php namespace Surikat\Templator; 
class TML_T extends TML{
	function load(){
		$this->attr('tmp-wrap',1);
		if(isset($this->metaAttribution[0]))
			$this->loadVars($this->metaAttribution[0]);
		unset($this->metaAttribution[0]);
	}
}
