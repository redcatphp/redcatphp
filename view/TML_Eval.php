<?php namespace surikat\view;
class TML_Eval extends CALL_APL {
	function load(){
		ob_start();
		eval('?>'.$this);
		$this->replaceWith(ob_get_clean());
	}
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended)){
			ob_start();
			eval('?>'.str_replace('$this','$apply',$this));
			$this->replaceWith(ob_get_clean());
		}
	}
}