<?php namespace Surikat\Component\Templator\MarkupX; 
class TmlEnd extends \Surikat\Component\Templator\Tml {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}