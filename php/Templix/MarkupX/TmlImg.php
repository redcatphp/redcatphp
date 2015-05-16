<?php namespace Templix\MarkupX;
use Templix\MarkupHtml5\TmlImg as MarkupHtml5_TmlImg;
use Templix\Templix;
class TmlImg extends MarkupHtml5_TmlImg{
	function loaded(){
		if($this->src&&strpos($this->src,'://')===false){
			if(!($this->height&&$this->width)){
				$size = @getimagesize($this->src);
				if(isset($size[0])&&isset($size[1])){
					$this->width =  $size[0];
					$this->height = $size[1];
				}
			}
			if($this->Template&&$this->Template->devLevel()&Templix::DEV_IMG&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
				if(strpos($this->src,'?')===false)
					$this->src .= '?';
				else
					$this->src .= '&';
				$this->src .= '_t='.time();
			}
		}
	}
}