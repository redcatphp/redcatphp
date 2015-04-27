<?php namespace Surikat\Component\Templix\MarkupX;
class TmlAttrprepend extends \Surikat\Component\Templix\CALL_APL {
	protected $selfClosed = true;
	function extendLoad(){
		if($extend = $this->closest('extend'))
			$this->applyLoad($extend);
	}
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			foreach($this->attributes as $k=>$v)
				if($k!='selector')
					$apply->children($this->selector,true)->each(function($o)use($v){
						$v = $this->selectorCodeTHAT($o,$v);
						array_unshift($o->metaAttribution,$v);
					});
	}
}