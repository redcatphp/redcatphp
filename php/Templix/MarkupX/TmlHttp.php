<?php namespace Templix\MarkupX;
class TmlHttp extends \Templix\Tml{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($_GET)||(
				count($this->Template->treeDependency('Unit_Mvc_View:Unit_Mvc_Controller:Route'))>1
			)){
				$this->notFound();
			}
		}
	}
	function notFound(){
		$this->KungFu_Cms_DispatcherUri_Index()->Unit_Mvc_Controller->error(404);
	}
}