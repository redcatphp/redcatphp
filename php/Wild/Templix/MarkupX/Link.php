<?php
namespace Wild\Templix\MarkupX;
class Link extends \Wild\Templix\MarkupHtml5\Link{
	function load(){
		if(
			$this->templix&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&
			($this->templix->devCss&&pathinfo($this->href,PATHINFO_EXTENSION)=='css')
			||($this->templix->devImg&&pathinfo($this->href,PATHINFO_EXTENSION)=='ico')
		){
			if(strpos($this->href,'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}