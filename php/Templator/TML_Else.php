<?php namespace Surikat\Templator; 
class TML_Else extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}