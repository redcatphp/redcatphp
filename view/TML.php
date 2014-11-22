<?php namespace surikat\view;
use surikat\i18n;
class TML extends CORE{
	function loaded(){
		
	}
	function load(){
	}
	function loadCacheSync($v,$k){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheSync']);
		$this->cacheForge($v);
	}
	function loadCacheStatic($v){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheStatic']);
		$this->cacheForge(null,false,true);
	}
	
	function loadI18n_($v,$k){
		$this->removeAttr('i18n-'.$k);
		$this->attr($k,i18n::gettext($v));
	}
}
