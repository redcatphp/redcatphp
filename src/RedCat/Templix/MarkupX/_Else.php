<?php
namespace RedCat\Templix\MarkupX; 
class _Else extends \RedCat\Templix\Markup {
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		$this->head("<?php }else{?>\n");
	}
}