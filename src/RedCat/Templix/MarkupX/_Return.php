<?php
namespace RedCat\Templix\MarkupX; 
class _Return extends \RedCat\Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php return;?>\n");
	}
}