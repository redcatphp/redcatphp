<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlIncorpore extends TmlInclude{
	function load(){
		$this->remapAttr('file');
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.tml';
		$this->parseFile($file,$this->attributes,'incorpore');
	}
}