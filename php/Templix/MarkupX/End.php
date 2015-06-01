<?php namespace Templix\MarkupX; 
class _End extends \Templix\Markup {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}