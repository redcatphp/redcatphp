<?php namespace Surikat\Route;
use ArrayAccess;
class Router_SuperURI extends Router implements ArrayAccess{
	protected $separators = [
		'Eq'=>':',
		'And'=>'+',
		'Or'=>'&',
	];
	function match($path){
		$path = ltrim($path,'/');
		$this->path = $path;
		$uriParams = [];
		$min = [];
		if(($pos=strpos($path,$this->separators['Eq']))!==false)
			$min[] = $pos;
		if(($pos=strpos($path,$this->separators['And']))!==false)
			$min[] = $pos;
		if(!empty($min)){
			$sepDir = min($min);
			$uriParams[0] = substr($path,0,$sepDir);
			$path = substr($path,$sepDir);
			$x = explode($this->separators['And'],$path);
			foreach($x as $v){
				$x2 = explode($this->separators['Or'],$v);
				if($k=$i=strpos($v,$this->separators['Eq'])){
					$k = substr($v,0,$i);
					$v = substr($v,$i+1);
				}
				$v = strpos($v,$this->separators['Or'])?explode($this->separators['Or'],$v):$v;
				if($k)
					$uriParams[$k] = $v;
				elseif(!empty($v))
					$uriParams[] = $v;
			}
		}
		else
			$uriParams[0] = $path;
		$this->uriParams = $uriParams;
		return $uriParams;
	}
	
	protected $path;
	protected $uriParams = [];
	function __toString(){
		return (string)$this->getPath();
	}
	function getPath(){
		return $this->path;
	}
	protected $separatorWord = '-';
	protected $forbiddenChrParam = [
		'?','%',',','!','^','¨','#','~',"'",'"',"\r","\n","\t"," ",
		'{','(','_','$','@',')',']','}','=','+','$','£','*','µ','§','/',
		';','.'
	];
	function filterParam($s){
		$s = trim($s);
		$s = strip_tags($s);
		$forbid = array_merge(array_values($this->separators),$this->forbiddenChrParam);
		$s = str_replace($forbid,$this->separatorWord,$s);
		$s = trim($s,$this->separatorWord);
		if(strpos($s,$this->separatorWord.$this->separatorWord)!==false){
			$ns = '';
			$l = strlen($s)-1;
			for($i=0;$i<=$l;$i++)
				if(!($i&&$s[$i]==$this->separatorWord&&$s[$i-1]==$this->separatorWord))
					$ns .= $s[$i];
			$s = $ns;
		}
		return $s;
	}
	
	function param(){ //deprecated
		return func_num_args()?$this->uriParams[func_get_arg(0)]:$this->uriParams;
	}
	
	function getParams(){
		return $this->uriParams;
	}
	function __set($k,$v){
		$this->uriParams[$k] = $v;
	}
	function __get($k){
		return isset($this->uriParams[$k])?$this->uriParams[$k]:null;
	}
	function __isset($k){
		return isset($this->uriParams[$k]);
	}
	function __unset($k){
		if(isset($this->uriParams[$k]))
			unset($this->uriParams[$k]);
	}
	function offsetSet($k,$v){
		return $this->__set($k,$v);
	}
	function offsetGet($k){
		return $this->__get($k);
	}
	function offsetExists($k){
		return $this->__isset($k);
	}
	function offsetUnset($k){
		return $this->__unset($k);
	}
	static function __set_state($a){
		$uri = new static();
		foreach($a as $k=>$v)
			$uri->$k = $v;
		return $uri;
	}
}
