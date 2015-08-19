<?php
namespace Wild\Templix\MarkupX; 
class _Else extends \Wild\Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}