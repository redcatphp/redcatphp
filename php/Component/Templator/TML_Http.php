<?php namespace Surikat\Component\Templator;
class TML_Http extends TML{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($this->Template->Http_Get())||(
				count($this->treeDependency('Mvc_View__Mvc_Controller__Route'))
			)){
				$this->notFound();
			}
		}
	}
	function notFound(){
		$this->_Surikat_Package_Cms_DispatcherUri_Index()->Mvc_Controller->error(404);
	}
}