<?php namespace Surikat\Route;
use ArrayAccess;
class Faceted extends Route implements ArrayAccess{
	protected $separatorAnd = '+';
	protected $separatorEq = ':';
	protected $separatorOr = '&';
	protected $path;
	protected $uriParams = [];
	function buildPath(){
		$uriParams = $this->uriParams;
		$path = array_shift($uriParams);
		foreach($uriParams as $k=>$param){
			$path .= $this->separatorAnd;
			if(!is_integer($k)){
				$path .= $k;
				$path .= $this->separatorEq;
			}
			if(is_array($param))
				$param = implode($this->separatorOr,$param);
			$path .= $param;
		}
		return $path;
	}
	function __invoke(&$uri){
		$path = ltrim($uri,'/');
		$this->path = $path;
		$uriParams = [];
		if(($pos=strpos($path,$this->separatorAnd))!==false){
			$uriParams[0] = substr($path,0,$pos);
			$path = substr($path,$pos);
			$x = explode($this->separatorAnd,$path);
			foreach($x as $v){
				$x2 = explode($this->separatorOr,$v);
				if($k=$i=strpos($v,$this->separatorEq)){
					$k = substr($v,0,$i);
					$v = substr($v,$i+1);
				}
				$v = strpos($v,$this->separatorOr)?explode($this->separatorOr,$v):$v;
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
	function __toString(){
		return (string)$this->buildPath();
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
		$forbid = array_merge([$this->separatorAnd,$this->separatorOr,$this->separatorEq],$this->forbiddenChrParam);
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
