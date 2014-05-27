<?php namespace surikat\view;
class TML extends CORE{
	protected function loadApply($v){
		$this->__unset('apply');
		$this->parse("<apply file=\"$v\">$this</apply>");
	}
	protected function loadedCacheSync($v,$k){
		unset($this->metaAttribution['cacheSync']);
		$this->cacheForge($v);
	}
	protected function loadedCacheStatic($v){
		unset($this->metaAttribution['cacheStatic']);
		$this->cacheForge(null,false,true);
	}
}
