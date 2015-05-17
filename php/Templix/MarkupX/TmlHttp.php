<?php namespace Templix\MarkupX;
class TmlHttp extends \Templix\Tml{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($_GET)
				//||(count($this->Template->getModel()->getRoute())>1)
			){
				$this->notFound();
			}
		}
	}
	function notFound(){
		if(method_exists($this->Template,'error'))
			$this->Template->error(404);
	}
}