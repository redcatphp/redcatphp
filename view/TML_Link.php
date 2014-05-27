<?php namespace surikat\view; 
use surikat\control;
class TML_Link extends TML{
	protected $selfClosed = true;#http://www.w3.org/TR/html5/syntax.html#void-elements
	function load(){
		if(control::devHas(control::dev_css)&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&pathinfo($this->href,PATHINFO_EXTENSION)=='css'){
			if(strpos($this->href,'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}
