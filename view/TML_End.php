<?php namespace surikat\view; 
class TML_End extends TML {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }?>';
	}
}
?>
