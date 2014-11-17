<?php namespace surikat\view;
class TML extends CORE{
	function loaded(){
		
	}
	function load(){
	}
	function loadApply($v){
		$this->__unset('apply');
		$this->parse("<apply file=\"$v\">$this</apply>");
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
	
	function loadNi18n(){
		
	}
	function loadI18n(){
		
	}
	function loadI18n_($v,$k){
		
	}
}
