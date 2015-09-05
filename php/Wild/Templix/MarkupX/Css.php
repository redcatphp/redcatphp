<?php
namespace Wild\Templix\MarkupX; 
use Wild\Templix\CallerMarkup;
class Css extends \Wild\Templix\CallerMarkup{
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
	function callback(){
		return [$this,'addCssLink'];
	}
	function addCssLink($css=null,$path='css/'){
		if(!$css)
			$css = $this;
		$dom = $this->closest()->children('head',0);
		if(!$dom)
			return;
		$href = trim($css->href?$css->href:($css->src?$css->src:key($css->attributes)));
		if($href&&strpos($href,'://')===false&&strpos($href,'/')!==0){
			if(strpos($href,$path)!==0)
				$href = $path.$href;
			if(substr($href,-4)!='.css')
				$href .= '.css';
		}
		$media_s = $css->media?'[media="'.$css->media.'"]':'';
		$media = $css->media?' media="'.$css->media.'"':'';
		if($href&&!($dom->children('link[href="'.$href.'"],link[href^="'.$href.'?_t="]'.$media_s,0)))
			$dom[] = '<link href="'.$href.'" rel="stylesheet" type="text/css"'.$media.'>';
	}
}
