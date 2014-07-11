<?php namespace surikat\model\QueryWriter;
trait AQueryWriter{
	function __get($k){
		if(property_exists($this,$k))
			return $this->$k;
	}
}