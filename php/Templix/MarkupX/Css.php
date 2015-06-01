<?php namespace Templix\MarkupX; 
use Templix\CallerMarkup;
class Css extends \Templix\CallerMarkup{
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
