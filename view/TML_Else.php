<?php namespace surikat\view; 
class TML_Else extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head('<?php }else{?>');
	}
}
