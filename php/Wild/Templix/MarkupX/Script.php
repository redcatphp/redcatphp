<?php
namespace Wild\Templix\MarkupX;
class Script extends \Wild\Templix\MarkupHtml5\Script{
	function loaded(){
		if($this->templix&&$this->templix->devJs&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos(str_replace(['<?','?>'],'',$this->src),'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
}