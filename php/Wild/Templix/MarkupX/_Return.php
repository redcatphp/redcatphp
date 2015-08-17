<?php namespace Wild\Templix\MarkupX; 
class _Return extends \Wild\Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}