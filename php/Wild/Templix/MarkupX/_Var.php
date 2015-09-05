<?php
namespace Wild\Templix\MarkupX; 
class _Var extends \Wild\Templix\Markup{
	protected $hiddenWrap = true;
	function load(){
		$this->nodeName = null;
		$this->remapAttr('var');
		if(!$this->var)
			return;
		if(isset($this->static)){
			$val = $this->evalue($this->getInnerMarkups());
			$this->innerHead('<?php $'.$this->var.'='.var_export($val,true).';?>');
		}
		else{
			$this->innerHead('<?php $'.$this->var.'=eval(\'?>'.str_replace("'","\'",$this->getInnerMarkups()).'\');?>');
		}
		$this->childNodes = [];
	}
}
