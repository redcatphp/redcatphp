<?php namespace Surikat\View;
use Surikat\Config\Dev;
class TML_Script extends TML{
	protected $noParseContent = true;
	function loaded(){
		if(Dev::has(Dev::JS)&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos($this->src,'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
}
