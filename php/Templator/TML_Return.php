<?php namespace Surikat\Templator; 
class TML_Return extends TML {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}