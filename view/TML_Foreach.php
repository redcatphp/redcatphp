<?php namespace surikat\view; 
class TML_Foreach extends TML {
	function getForeach(){
		$c = $this->foreach;
		if(!$c)
			$c = $this->e;
		$k = isset($this->key)?$this->key:'key';
		$v = isset($this->val)?$this->val:(isset($this->assign)?$this->assign:'val');
		$as = strpos($c,' as ')!==false?'':' as $'.$k.'=>$'.$v;
		return $c.$as;
	}
	function __toString(){
		return '<?php foreach('.$this->getForeach().'){?>'.(!$this->selfClosed?$this->getInnerTml().'<?php }?>':'');
	}
}
?>
