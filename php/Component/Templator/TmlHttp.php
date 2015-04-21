<?php namespace Surikat\Component\Templator;
class TmlHttp extends Tml{
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
		$this->_Surikat_Package_Cms_DispatcherUri_Index()->Mvc_Controller->error(404);
	}
}