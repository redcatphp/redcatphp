<?php namespace Surikat\Templator;
abstract class CALL_APL extends CORE{
	protected $hiddenWrap = true;
	protected $callback;
	function callback(){
		$this->remapAttr('selector');
		if($this->__isset('compile')){
			$this->__set('selector',$this->evalue($this->__get('compile')));
			$this->__unset('compile');
		}
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
		//if(!$this->View)
			//return;
		$this->callback();
	}
	function selectorCodeTHIS($__this){
		$str = $this->selectorCodeTHAT($__this,"$this");
		return $str===null?$this:$str;
	}
	private function __evePlus($eve,$prefix='',$sufix='',$__this=null){		
		$plus = 0;
		while(strpos($eve,'+')===0){
			$eve = substr($eve,1);
			$plus++;
		}
		$eve = $this->evalue($prefix.$eve.$sufix,['__this'=>$__this]);
		if($plus){
			for($i=0;$i<$plus;$i++){
				if(strpos($eve,'<?')===false)
					break;
				$eve = $this->evalue($eve,['__this'=>$__this]);
			}
		}
		return $eve;
	}
	function selectorCodeTHAT($__this,$str){
		$pos = 0;
		if(preg_match_all('/\\{\\{this:([^\\}\\}]+)/',$str, $matches)){
			foreach($matches[1] as $i=>$eve){
				$eve = $this->__evePlus($eve,'<?php echo $__this->',';',$__this);
				$str = substr($str,0,$pos=strpos($str,$matches[0][$i],$pos)).$eve.substr($str,$pos+strlen($matches[0][$i])+2);
			}
		}
		$pos = 0;
		if(preg_match_all('/\\{\\{compile:([^\\}\\}]+)/',$str, $matches)){
			foreach($matches[1] as $i=>$eve){
				$eve = $this->__evePlus($eve,'<?php echo ',';');
				$str = substr($str,0,$pos=strpos($str,$matches[0][$i],$pos)).$eve.substr($str,$pos+strlen($matches[0][$i])+2);
			}
		}
		return $str;
	}
	function extendLoad(){
		$c = $this->callback();
		if($extend = $this->closest('extend')){
			if($this->selector){
				foreach($extend->children($this->selector) as $i=>$tg){
					if($i)
						$append = clone $this;
					else
						$append = $this;
					$append->parent = $tg;
					$tg->$c($append);
				}
			}
			else{
				$extend->closest()->$c($this);
			}
		}
	}
	function applyLoad($apply = null){
		$c = $this->callback();
		if($apply || (($apply = $this->closest('apply'))) && ($apply = $apply->selfClosed?$this->closest():$apply->_extended))
			if($this->selector)
				foreach($apply->children($this->selector) as $select)
					$select->$c($this->selectorCodeTHIS($select));
			else
				$apply->closest()->$c($this);
	}
}
