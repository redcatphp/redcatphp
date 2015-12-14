<?php
namespace RedCat\Templix\MarkupX;
class Link extends \RedCat\Templix\MarkupHtml5\Link{
	function load(){
		if(!$this->templix)
			return;
		if(
			$this->templix&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&
			($this->templix->devCss&&pathinfo($this->href,PATHINFO_EXTENSION)=='css')
			||($this->templix->devImg&&
				(
					($e=pathinfo($this->href,PATHINFO_EXTENSION))=='ico'
					||$e=='png'
				))
		){
			if(strpos(str_replace(['<?','?>'],'',$this->href),'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}