<?php namespace surikat\view;
class TML_Eval extends CALL_APL {
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended)){
			eval('?>'.str_replace('$this','$apply',$this));
			$this->delete();
		}
	}
}
