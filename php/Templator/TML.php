<?php namespace Surikat\Templator;
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
	
	private static $loadVarsIndex = 100;
	function loadVars($v){
		if(!$this->Template)
			return;
		$index = uniqid();
		$this->attr('compileVars',$index);
		$this->removeAttr('vars');
		$this->Template->onCompile(function($TML)use($v,$index){
			$el = $TML->find("[compileVars=$index]",0);
			if(!$el)
				return;
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
		},self::$loadVarsIndex++);
	}
}