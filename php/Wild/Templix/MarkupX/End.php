<?php
namespace Wild\Templix\MarkupX; 
class _End extends \Wild\Templix\Markup {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}