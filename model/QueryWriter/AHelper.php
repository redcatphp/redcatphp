<?php namespace surikat\model\QueryWriter;
trait AHelper{
	function __call($f,$args){
		if(strpos($f,'get')===0&&ctype_upper(substr($f,3,1))){
			$k =  lcfirst(substr($f,3));
			return $this->$k;
		}
		throw new \Exception('Call to undefined method '.$func.' of class '.get_class($this));
	}
	function getQuote(){
		return $this->quoteCharacter;
	}
}