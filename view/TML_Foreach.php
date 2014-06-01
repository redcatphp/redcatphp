<?php namespace surikat\view; 
class TML_Foreach extends TML {
	protected $hiddenWrap = true;
	function getForeach(){
		$c = $this->foreach;
		if(!$c)
			$c = $this->e;
		$k = isset($this->key)?$this->key:'key';
		$v = isset($this->val)?$this->val:(isset($this->assign)?$this->assign:'val');
		$as = strpos($c,' as ')!==false?'':' as $'.$k.'=>$'.$v;
		return $c.$as;
	}
	function load(){
		array_unshift($this->head,'<?php foreach('.$this->getForeach().'){?>');
		if(!$this->selfClosed)
			array_push($this->foot,'<?php }?>');
	}
}
