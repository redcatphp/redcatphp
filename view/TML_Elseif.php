<?php namespace surikat\view; 
class TML_Elseif extends TML {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }elseif{?>';
	}
}
?>
