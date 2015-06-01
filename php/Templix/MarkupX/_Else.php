<?php namespace Templix\MarkupX; 
class _Else extends \Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}