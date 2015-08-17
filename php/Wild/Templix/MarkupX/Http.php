<?php namespace Wild\Templix\MarkupX;
class Http extends \Wild\Templix\Markup{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($_GET)||strpos($_SERVER['REQUEST_URI'],'?')!==false){
				$this->notFound();
			}
		}
	}
	function notFound(){
		//if(method_exists($this->templix,'error'))
			//$this->templix->error(404);
	}
}