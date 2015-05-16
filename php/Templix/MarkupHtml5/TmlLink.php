<?php namespace Templix\MarkupHtml5;
use Templix\Templix;
class TmlLink extends \Templix\Tml{
	protected $selfClosed = true;#http://www.w3.org/TR/html5/syntax.html#void-elements
	function load(){
		if($this->Template&&$this->Template->devLevel()&Templix::DEV_CSS&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&pathinfo($this->href,PATHINFO_EXTENSION)=='css'){
			if(strpos($this->href,'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}