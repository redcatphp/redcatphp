<?php namespace Templix\MarkupX; 
class TmlEnd extends \Templix\Tml {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}