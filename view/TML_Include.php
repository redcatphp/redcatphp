<?php namespace surikat\view; 
class TML_Include extends CALL_SUB{
	protected $selfClosed = true;
	function load(){
		$this->remapAttr('file');
		$file = $this->__get('file');
		$this->__unset('file');
		$this->parseFile($file,$this->attributes,__CLASS__);
	}
}