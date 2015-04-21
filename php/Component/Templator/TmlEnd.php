<?php namespace Surikat\Component\Templator; 
class TmlEnd extends Tml {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}