<?php namespace surikat\control;
use surikat\control;
class ArrayObject extends \ArrayObject implements \ArrayAccess{
	function __construct($a=array()){
		foreach($a as $k=>$v)
            if (is_array($v))
                $a[$k] = self::array2object($v);
		parent::setFlags(parent::ARRAY_AS_PROPS);
		parent::__construct((array)$a);
	}
	private function offsetDefine($k,$v){
		return parent::offsetSet($k,$v);
	}
	function offsetExists($k){
		$e = parent::offsetExists($k);
		if($e&&($a=parent::offsetGet($k)) instanceof \ArrayObject)
			return count($a);
		return $e;
	}
	function offsetSet($k,$v){
		if(is_array($v))
			$v = self::array2object($v);
		return parent::offsetSet($k,$v);
	}
	function offsetGet($k){
		return parent::offsetExists($k)?parent::offsetGet($k):null;
	}
	function getArray(){
		return self::object2array($this);
	}
	function merge(){
		foreach(func_get_args() as $a)
			foreach($a as $k=>$v)
				$this->offsetSet($k,$v);
	}
	function extend(){
		$c = clone $this;
		foreach(func_get_args() as $a)
			$c->merge($a);
		return $c;
	}
	static function __recurseKey($v,$key,$depth=null){
		$c = new ArrayObject();
		foreach($v as $k=>$v){
			if($key==$k){
				$c[] = $v;
			}
			elseif($depth&&$v instanceof ArrayObject){
				foreach(self::__recurseKey($v,$key,$depth===true?true:$depth-1) as $_v)
					$c[] = $_v;
			}
		}
		return $c;
	}
	static function __groupKey($o,$group,$key,$depth=null){
		$c = new ArrayObject();
		foreach($group as $g){
			$v = $o->offsetGet($g);
			if(!$v instanceof ArrayObject)
				continue;
			foreach(self::__recurseKey($v,$key,$depth) as $v)
				$c[] = $v;
		}
		return $c;
	}
	function groupKey($group,$key,$depth=null){
		return self::__groupKey($this,$group,$key,$depth);
	}
	function group(){
		$c = new ArrayObject();
		foreach(func_get_args() as $k)
			if($this->$k instanceof ArrayObject||is_array($this->$k))
				$c->push($this->$k);
		return $c;
	}
	function submerge(){
		$c = new ArrayObject();
		foreach(func_get_args() as $k)
			if($this->$k instanceof ArrayObject||is_array($this->$k))
				$c->merge($this->$k);
		return $c;
	}
	function push(){
		foreach(func_get_args() as $v)
			$this[] = $v;
	}
	function keys(){
		return array_keys((array)$this);
	}
	function values(){
		return array_values((array)$this);
	}
	function unshift(){
		$a = (array)$this;
		foreach(func_get_args() as $v)
			array_unshift($a, $v);
        $this->exchangeArray($a);
	}
	function shift(){
		$a = (array)$this;
		$r = array_shift($a);
        $this->exchangeArray($a);
        return $r;
	}
	function pop(){
		$a = (array)$this;
		$r = array_pop($a);
        $this->exchangeArray($a);
        return $r;
	}
	function __toString(){
		if(control::devHas(control::dev_control))
			return $this->__debug();
	}
	function __debug(){
		return '<pre>'.htmlentities(print_r($this->getArray(),true)).'<pre>';
	}
	function debug(){
		echo $this->__debug();
	}
	static function object2array($a){
		foreach($a as $k=>$v)
            if($v instanceof \ArrayObject||is_array($v))
				if($a instanceof \ArrayObject)
					$a->offsetDefine($k,self::object2array($v));
				else
					$a[$k] = self::object2array($v);
        return $a instanceof \ArrayObject?$a->getArrayCopy():(array)$a;
	}
	static function array2object(array $a){
        foreach($a as $k=>$v)
            if(is_array($v))
                $a[$k] = self::array2object($v);
        return new ArrayObject($a);
    }
}
