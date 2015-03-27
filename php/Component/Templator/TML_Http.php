<?php namespace Surikat\Component\Templator;
class TML_Http extends TML{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	function load(){
		//if(isset($this->presentAttributes->uri)&&$this->presentAttributes->uri=='static'){
			//if(count($this->Http_Get())){
				//$this->notFound();
			//}
			//elseif(($r = $this->getView())&&($r = $r->getController())&&($r = $r->getRouter())){
				//if((method_exists($r,'getParams'))&&count($r->getParams())>1){
					//$this->notFound();
				//}
				//elseif(0){
					//$this->haveParameters();
				//}
			//}
		//}
	}
	function notFound(){
		$this->_Surikat_Package_Cms_DispatcherUri_Index()->getController()->error(404);
	}
}
