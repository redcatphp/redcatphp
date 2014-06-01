<?php namespace surikat\view;
class TML extends CORE{
	protected function loaded(){
		$this->preventLoad = true;
		if($this->namespaceClass&&method_exists($this,$m=__FUNCTION__.ucfirst($this->namespaceClass)))
			$this->$m();
	}
	protected function load(){
		if($this->namespaceClass&&method_exists($this,$m=__FUNCTION__.ucfirst($this->namespaceClass)))
			$this->$m();
	}
	protected function loadApply($v){
		$this->__unset('apply');
		$this->parse("<apply file=\"$v\">$this</apply>");
	}
	protected function loadCacheSync($v,$k){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheSync']);
		$this->cacheForge($v);
	}
	protected function loadCacheStatic($v){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheStatic']);
		$this->cacheForge(null,false,true);
	}
}
