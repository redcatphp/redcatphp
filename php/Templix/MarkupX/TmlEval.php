<?php namespace Templix\MarkupX;
class TmlEval extends \Templix\CallerMarkup{
	function load(){
		$vars = isset($this->templix)?$this->templix->getVars():null;
		$this->replaceWith($this->evalue($this,$vars));
	}
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended)){
			$this->replaceWith($this->evalue(str_replace('$this','$apply',$this)));
		}
	}
}