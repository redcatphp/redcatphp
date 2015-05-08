<?php namespace Templix\MarkupX;
class TmlHttp extends \Templix\Tml{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		if($this->__get('static')){
			if(count($_GET)||(
				count($this->Template->treeDependency('Templix:Route'))>1
			)){
				$this->notFound();
			}
		}
	}
	function notFound(){
		$this->KungFu_Cms_DispatcherUri_Index()->Templix->error(404);
	}
}