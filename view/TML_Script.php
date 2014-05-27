<?php namespace surikat\view;
use surikat\control;
class TML_Script extends TML{
	protected $noParseContent = true;
	function loaded(){
		if(control::devHas(control::dev_js)&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos($this->src,'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
	function loadInclude(){
		$this->innerHead(file_get_contents($this->pathFile($this->__get('include'))));
		$this->__unset('include');
	}
}
