<?php namespace Surikat\Templator; 
class TML_Case extends TML {
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('e');
		if(!$this->e||$this->e=="default"){
			$this->head("<?php default:?>\n");
		}
		else{
			if(is_string($this->e))
				$this->e = "'{$this->e}'";
			$this->head("<?php case {$this->e}:?>\n");
		}
		if(!$this->selfClosed){
			$this->foot("<?php break;?>");
		}
	}
}