<?php
namespace RedCat\Templix\MarkupX; 
class _End extends \RedCat\Templix\Markup {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}