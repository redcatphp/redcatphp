<?php namespace surikat\view; 
class TML_Foreach extends TML {
	protected $hiddenWrap = true;
	function getForeach(){
		$this->remapAttr('e');
		$this->remapAttr('e','foreach');
		$k = isset($this->key)?$this->key:'key';
		$v = isset($this->val)?$this->val:(isset($this->assign)?$this->assign:'val');
		$as = strpos($this->e,' as ')!==false?'':' as $'.$k.'=>$'.$v;
		return $this->e.$as;
	}
	function load(){
		array_unshift($this->head,'<?php foreach('.$this->getForeach().'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}