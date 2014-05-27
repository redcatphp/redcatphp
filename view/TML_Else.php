<?php namespace surikat\view; 
class TML_Else extends TML {
	protected $selfClosed = true;
	function __toString(){
		return '<?php }else{?>';
	}
}
?>
