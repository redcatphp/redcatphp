<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
	function addTypes(){
		if(property_exists($this,'addTypes'))
			foreach($this->addTypes as $k=>$v)
				$this->addType($v,is_integer($k)?null:$k);
	}
	function addType($v,$k=null){
		if($k==null){
			$this->typeno_sqltype[] = $v;
			end($this->typeno_sqltype);
			$k = key($this->typeno_sqltype);
		}
		else
			$this->typeno_sqltype[$k] = $v;
		$this->typeno_sqltype[trim(strtolower($v))] = $k;
	}
}