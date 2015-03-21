<?php namespace Surikat\Templator; 
class TML_Var extends TML{
	protected $hiddenWrap = true;
	function load(){
		$this->nodeName = null;
		$var = $this->var;
		if(!$var&&isset($this->attributes[0]))
			$var = $this->attributes[0];
		if(!$var)
			return;
		if(isset($this->cache)){
			$val = $this->evalue($this->getInnerTml());
			$this->innerHead('<?php $'.$var.'='.var_export($val,true).';?>');
		}
		else{
			$this->innerHead('<?php $'.$var.'=eval(\'?>'.str_replace("'","\'",$this->getInnerTml()).'\');?>');
		}
		$this->childNodes = [];
	}
}
