<?php namespace Templix\MarkupX; 
class _Return extends \Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}