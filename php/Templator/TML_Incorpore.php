<?php namespace Surikat\Templator; 
class TML_Incorpore extends TML_Include{
	function load(){
		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		$this->parseFile($file,$this->attributes,'incorpore');
	}
}