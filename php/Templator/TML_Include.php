<?php namespace Surikat\Templator; 
class TML_Include extends CALL_SUB{
	protected $selfClosed = true;
	function load(){
		//if(!$this->View)
			//return;
		$this->remapAttr('file');
		$file = $this->__get('file');
		$this->__unset('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		$this->parseFile($file,$this->attributes,'include');
	}
}