<?php namespace Templix\MarkupHtml5;
class TmlScript extends \Templix\Tml{
	protected $noParseContent = true;
	function loaded(){
		if($this->Dev_Level()->JS&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos($this->src,'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
}