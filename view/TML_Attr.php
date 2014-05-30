<?php namespace surikat\view;
class TML_Attr extends CALL_APL {
	protected $selfClosed = true;
	function extendLoad(){
		if($extend = $this->closest('extend'))
			$this->applyLoad($extend);
	}
	function applyLoad($apply = null){
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			foreach($this->attributes as $k=>$v)
				if($k=='selector')
					continue;
				elseif(strpos($k,'add')===0&&ctype_upper(substr($k,3,1))){
					$key = lcfirst(substr($k,3));
					$apply->find($this->selector,true)->each(function($o)use($key,$v){
						if(!isset($o->attributes[$key])||strpos($o->attributes[$key],$v)===false)
							$o->attr($key,trim($o->$key.' '.$v));
					});
				}
				elseif(strpos($k,'remove')===0&&ctype_upper(substr($k,6,1))){
					$key = lcfirst(substr($k,6));
					$apply->find($this->selector,true)->each(function($o)use($key,$v){
						if(isset($o->attributes[$key])&&strpos($o->attributes[$key],$v)!==false)
							$o->attr($key,str_replace($v,'',$o->$key));
					});
				}
				else{
					$apply->find($this->selector,true)->each(function($o)use($k,$v){
						$v = $this->selectorCodeTHAT($o,$v);
						$o->attr($k,$v);
					});
				}
	}
}
