<?php namespace Templix\MarkupHtml5;
use Templix\Templix;
class TmlScript extends \Templix\Tml{
	protected $noParseContent = true;
	function loaded(){
		if($this->Template&&$this->Template->devLevel()&Templix::DEV_JS&&$this->src&&strpos($this->src,'://')===false&&strpos($this->src,'_t=')===false){
			if(strpos($this->src,'?')===false)
				$this->src .= '?';
			else
				$this->src .= '&';
			$this->src .= '_t='.time();
		}
	}
}