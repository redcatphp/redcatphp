<?php
namespace Templix\MarkupX;
class Link extends \Templix\MarkupHtml5\Link{
	function load(){
		if($this->templix&&$this->templix->devCss&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&pathinfo($this->href,PATHINFO_EXTENSION)=='css'){
			if(strpos($this->href,'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}