<?php namespace Surikat\View; 
class TML_Else extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head('<?php }else{?>');
	}
}
