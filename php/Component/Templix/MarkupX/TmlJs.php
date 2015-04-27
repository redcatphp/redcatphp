<?php namespace Surikat\Component\Templix\MarkupX; 
class TmlJs extends \Surikat\Component\Templix\CALL_APL{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	protected $callback = 'addJsScript';
	var $selector = false;
	function load(){
		$this->remapAttr('src');
		$this->remapAttr('async',1);
		if($this->closest('extend')){
			$o = $this;
			$this->closest()->onLoaded(function()use($o){
				$o->addJsScript();
			});
		}
	}
	function loaded(){
		$this->addJsScript();
	}
}