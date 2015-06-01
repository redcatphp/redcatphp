<?php namespace Templix\MarkupX; 
class Incorpore extends MarkupInclude{
	function load(){
		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		$this->parseFile($file,$this->attributes,'incorpore');
	}
}