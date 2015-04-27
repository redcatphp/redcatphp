<?php namespace Surikat\Component\Templix\MarkupX; 
use Surikat\Component\Templix\CALL_APL;
class TmlCss extends \Surikat\Component\Templix\CALL_APL{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	protected $callback = 'addCssLink';
	var $selector = false;
	function load(){
		$this->remapAttr('href');
		if($this->closest('extend')){
			$o = $this;
			$this->closest()->onLoaded(function()use($o){
				$o->addCssLink();
			});
		}
	}
	function loaded(){
		$this->addCssLink();
	}
}
