<?php
namespace Wild\Templix\MarkupX; 
class Js extends \Wild\Templix\CallerMarkup{
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
	function callback(){
		return [$this,'addJsScript'];
	}
	function addJsScript($js=null){
		if(!$js)
			$js = $this;
		$dom = $this->closest()->find('body',0);
		if(!$dom)
			return;
		$src = trim($js->src);
		if($src){
			$script = $dom->find('script:not([src]):last',0);
			if(!$script){
				$dom[] = '<script type="text/javascript"></script>';
				$script = $dom->find('script:not([src]):last',0);
			}
			$sync = isset($js->sync)&&$js->sync!='false'||$js->async=='false'?',true':'';
			$app = "\$js('$src'$sync);";
			if(strpos("$script",$app)===false)
				$script->append($app);
		}
	}
}