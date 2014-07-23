<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
	function addTypes(){
		if(property_exists($this,'addTypes'))
			foreach($this->addTypes as $k=>$v)
				$this->addType($k,$v);
	}
	protected $__typeIndex = 200;
	function addType($k,$v=null){
		if($v==null)
			$v = $this->__typeIndex+=1;
		$this->typeno_sqltype[$v] = $k;
		$this->typeno_sqltype[trim(strtolower($k))] = $v;
	}
}