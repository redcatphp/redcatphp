<?php namespace Surikat\View;
use Surikat\I18n\Lang;
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
		$this->attr($k,Lang::gettext($v));
	}
	
	private static $loadVarsIndex = 100;
	function loadVars($v){
		if(!$this->TeMpLate)
			return;
		self::$loadVarsIndex++;
		$index = self::$loadVarsIndex;
		$this->attr('compileVars',$index);
		$this->TeMpLate->onCompile(function($TML)use($v,$index){
			$el = $TML->find("[compileVars=$index]",0);
			$el->removeAttr('compileVars');
			$rw = $el->getInnerTml();
			if(substr($rw,0,11)=='<?php echo '&&substr($rw,-3)==';?>'){
				$rw = substr($rw,11,-3);
			}
			else{
				$rw = '"'.str_replace('"','\"',$rw).'"';
			}
			$rw = '<?php echo sprintf('.$rw.','.$v.');?>';
			$el->write($rw);
		},self::$loadVarsIndex);
		$this->removeAttr('vars');
	}
}