<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlEnd extends \Surikat\Component\Templix\Tml {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}