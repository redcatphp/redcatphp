<?php namespace Templix\MarkupX;
class TmlHttp extends \Templix\Tml{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($this->Template->Http_Get())||(
				count($this->Template->treeDependency('Mvc_View:Mvc_Controller:Route'))>1
			)){
				$this->notFound();
			}
		}
	}
	function notFound(){
		$this->_Package_Cms_DispatcherUri_Index()->Mvc_Controller->error(404);
	}
}