<?php namespace surikat\view;
abstract class CALL_APL extends CORE{
	protected $hiddenWrap = true;
	protected $callback;
	function callback(){
		if(!isset($this->callback))
			$this->callback = lcfirst(substr($c=get_class($this),(strrpos($c,'_')+1)));
		if($this->selector===null){
			if(count($this->attributes)===1&&isset($this->metaAttribution[0])&&($k=$this->metaAttribution[0])&&$this->attributes[$k]==$k){
				$this->selector = $k;
			}
			else if(!empty($this->attributes)){
				$this->selector = '*';
				foreach(array_keys($this->attributes) as $k)
					if($k!='selector')
						$this->selector .= '['.$k.'="'.str_replace('"','\"',$this->attributes[$k]).'"]';
			}
		}
		$this->selector = str_replace("'",'"',$this->selector);
		return $this->callback;
	}
	function load(){
		$this->callback();
	}
	function selectorCodeTHIS($__this){
		$str = $this->selectorCodeTHAT($__this,"$this");
		return $str===null?$this:$str;
	}
	function selectorCodeTHAT($__this,$str){
		$pos = 0;
		if(preg_match_all('/\\{\\{this:(.*?)\\}\\}/',$str, $matches))
			foreach($matches[1] as $i=>$eve)
				$str = substr($str,0,$pos=strpos($str,$matches[0][$i],$pos)).eval('return $__this->'.$eve.';').substr($str,$pos+strlen($matches[0][$i]));
		$pos = 0;
		if(preg_match_all('/\\{\\{eval:([^\\}\\}]+)/',$str, $matches))
			foreach($matches[1] as $i=>$eve)
				$str = substr($str,0,$pos=strpos($str,$matches[0][$i],$pos)).eval('return '.$eve.';').substr($str,$pos+strlen($matches[0][$i]));
		return $str;
	}
	function extendLoad(){
		$c = $this->callback();
		if($extend = $this->closest('extend'))
			if($this->selector)
				$extend->find($this->selector,true)->$c($this);
			else
				$extend->closest()->$c($this);
	}
	function applyLoad($apply = null){
		$c = $this->callback();
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			if($this->selector)
				foreach($apply->find($this->selector) as $select)
					$select->$c($this->selectorCodeTHIS($select));
			else
				$apply->closest()->$c($this);
	}
}
