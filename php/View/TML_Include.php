<?php namespace Surikat\View; 
class TML_Include extends CALL_SUB{
	protected $selfClosed = true;
	function load(){
		//if(!$this->TeMpLate)
			//return;
		$this->remapAttr('file');
		$file = $this->__get('file');
		$this->__unset('file');
		$this->parseFile($file,$this->attributes,'include');
	}
}