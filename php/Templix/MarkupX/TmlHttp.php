<?php namespace Templix\MarkupX;
class TmlHttp extends \Templix\Tml{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($_GET)||(
				count($this->Template->treeDependency('FluxServer_Mvc_View:FluxServer_Mvc_Controller:Route'))>1
			)){
				$this->notFound();
			}
		}
	}
	function notFound(){
		$this->Package_Cms_DispatcherUri_Index()->FluxServer_Mvc_Controller->error(404);
	}
}