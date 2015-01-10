<?php namespace Surikat\View; 
class TML_Elseif extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }elseif{?>\n");
	}
}
