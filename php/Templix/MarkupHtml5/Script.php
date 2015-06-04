<?php namespace Templix\MarkupHtml5;
class Script extends \Templix\Markup{
	protected $noParseContent = true;
	function loaded(){
		if($this->templix&&$this->templix->devJs&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos($this->src,'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
}