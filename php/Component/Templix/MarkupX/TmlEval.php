<?php namespace Surikat\Component\Templix\MarkupX;
class TmlEval extends \Surikat\Component\Templix\CALL_APL{
	function load(){
		$vars = isset($this->Template)?$this->Template->getVars():null;
		$this->replaceWith($this->evalue($this,$vars));
	}
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended)){
			$this->replaceWith($this->evalue(str_replace('$this','$apply',$this)));
		}
	}
}